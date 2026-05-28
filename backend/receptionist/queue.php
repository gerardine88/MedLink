<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'receptionist') {
    http_response_code(403); send_json_response(false, 'Access denied');
}
if (!$conn) send_json_response(false, $db_error ?: 'Database unavailable');

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: today's full queue ───────────────────────────────
if ($method === 'GET') {
    $filter = trim($_GET['filter'] ?? 'active');

    $statusCond = match($filter) {
        'all'       => '',
        'completed' => "AND q.status = 'completed'",
        default     => "AND q.status IN ('waiting','serving')",
    };

    $stmt = $conn->prepare(
        "SELECT q.id AS queue_id, q.queue_number, q.status, q.called_at, q.served_at,
                a.id AS appointment_id, a.appointment_time,
                CONCAT(pu.first_name,' ',pu.last_name) AS patient_name,
                p.patient_code, pu.phone,
                CONCAT(du.first_name,' ',du.last_name) AS doctor_name,
                COALESCE(dep.department_name,'General') AS department,
                COALESCE(s.service_name,'General Consultation') AS service_name
         FROM queues q
         JOIN appointments a  ON q.appointment_id = a.id
         JOIN patients p      ON a.patient_id      = p.id
         JOIN users pu        ON p.user_id          = pu.id
         JOIN doctors doc     ON a.doctor_id        = doc.id
         JOIN users du        ON doc.user_id         = du.id
         LEFT JOIN departments dep ON doc.department_id = dep.id
         LEFT JOIN services s      ON a.service_id       = s.id
         WHERE a.appointment_date = CURDATE() $statusCond
         ORDER BY
           CASE q.status WHEN 'serving' THEN 1 WHEN 'waiting' THEN 2 ELSE 3 END,
           q.queue_number ASC"
    );
    $stmt->execute();
    $queue = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Summary
    $sm = $conn->query(
        "SELECT SUM(q.status='waiting') AS waiting, SUM(q.status='serving') AS serving,
                SUM(q.status='completed') AS completed, COUNT(*) AS total
         FROM queues q JOIN appointments a ON q.appointment_id=a.id
         WHERE a.appointment_date=CURDATE()"
    )->fetch_assoc();

    send_json_response(true, 'OK', ['queue' => $queue, 'summary' => $sm]);
}

// ── PUT: update queue status ──────────────────────────────
if ($method === 'PUT') {
    $data     = json_decode(file_get_contents('php://input'), true) ?: [];
    $queue_id = (int)($data['queue_id'] ?? 0);
    $action   = trim($data['action']   ?? '');

    if (!$queue_id) send_json_response(false, 'Queue ID required');

    $qStmt = $conn->prepare("SELECT id, status, appointment_id FROM queues WHERE id=?");
    $qStmt->bind_param('i', $queue_id);
    $qStmt->execute();
    $entry = $qStmt->get_result()->fetch_assoc();
    if (!$entry) send_json_response(false, 'Queue entry not found');

    switch ($action) {
        case 'call':     // waiting → serving
            if ($entry['status'] !== 'waiting') send_json_response(false, 'Patient is not waiting');
            $conn->prepare("UPDATE queues SET status='serving', called_at=NOW() WHERE id=?")->bind_param('i',$queue_id) && false;
            $u = $conn->prepare("UPDATE queues SET status='serving', called_at=NOW() WHERE id=?");
            $u->bind_param('i', $queue_id); $u->execute();
            send_json_response(true, 'Patient called');

        case 'complete': // serving → completed
            if ($entry['status'] !== 'serving') send_json_response(false, 'Patient is not being served');
            $u = $conn->prepare("UPDATE queues SET status='completed', served_at=NOW() WHERE id=?");
            $u->bind_param('i', $queue_id); $u->execute();
            // Mark appointment in_progress
            $ua = $conn->prepare("UPDATE appointments SET status='in_progress' WHERE id=?");
            $ua->bind_param('i', $entry['appointment_id']); $ua->execute();
            send_json_response(true, 'Marked as completed');

        case 'noshow':   // waiting/serving → skipped (no_show on appointment)
            $u = $conn->prepare("UPDATE queues SET status='skipped' WHERE id=?");
            $u->bind_param('i', $queue_id); $u->execute();
            $ua = $conn->prepare("UPDATE appointments SET status='no_show' WHERE id=?");
            $ua->bind_param('i', $entry['appointment_id']); $ua->execute();
            send_json_response(true, 'Marked as no-show');

        case 'call_next': // find lowest waiting and call it
            $next = $conn->query(
                "SELECT q.id FROM queues q
                 JOIN appointments a ON q.appointment_id=a.id
                 WHERE a.appointment_date=CURDATE() AND q.status='waiting'
                 ORDER BY q.queue_number ASC LIMIT 1"
            )->fetch_assoc();
            if (!$next) send_json_response(false, 'No patients waiting');
            $u = $conn->prepare("UPDATE queues SET status='serving', called_at=NOW() WHERE id=?");
            $u->bind_param('i', $next['id']); $u->execute();
            send_json_response(true, 'Next patient called', ['queue_id' => $next['id']]);

        default:
            send_json_response(false, 'Unknown action');
    }
}

send_json_response(false, 'Method not allowed');
