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

// ── GET: search today's appointments ─────────────────────
if ($method === 'GET') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 1) send_json_response(true, 'OK', ['appointments' => []]);

    $like = '%' . $conn->real_escape_string($q) . '%';
    $stmt = $conn->prepare(
        "SELECT a.id AS appointment_id, a.appointment_time, a.status, a.reason,
                CONCAT(pu.first_name,' ',pu.last_name) AS patient_name,
                p.patient_code, pu.phone,
                CONCAT(du.first_name,' ',du.last_name) AS doctor_name,
                COALESCE(s.service_name,'General Consultation') AS service_name,
                dep.department_name,
                q.id AS queue_id, q.queue_number, q.status AS queue_status
         FROM appointments a
         JOIN patients p   ON a.patient_id  = p.id
         JOIN users pu     ON p.user_id     = pu.id
         JOIN doctors doc  ON a.doctor_id   = doc.id
         JOIN users du     ON doc.user_id   = du.id
         LEFT JOIN services s   ON a.service_id   = s.id
         LEFT JOIN departments dep ON doc.department_id = dep.id
         LEFT JOIN queues q ON q.appointment_id = a.id
         WHERE a.appointment_date = CURDATE()
           AND a.status NOT IN ('cancelled','no_show')
           AND (pu.first_name LIKE ? OR pu.last_name LIKE ?
                OR CONCAT(pu.first_name,' ',pu.last_name) LIKE ?
                OR p.patient_code LIKE ? OR pu.phone LIKE ?)
         ORDER BY a.appointment_time ASC
         LIMIT 20"
    );
    $stmt->bind_param('sssss', $like, $like, $like, $like, $like);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    send_json_response(true, 'OK', ['appointments' => $results]);
}

// ── POST: confirm arrival + add to queue ─────────────────
if ($method === 'POST') {
    $data        = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $appt_id     = (int)($data['appointment_id'] ?? 0);
    if (!$appt_id) send_json_response(false, 'Appointment ID required');

    // Fetch appointment
    $aStmt = $conn->prepare(
        "SELECT a.id, a.patient_id, a.doctor_id, a.status,
                a.appointment_date, a.appointment_time
         FROM appointments a WHERE a.id = ? AND a.appointment_date = CURDATE()"
    );
    $aStmt->bind_param('i', $appt_id);
    $aStmt->execute();
    $appt = $aStmt->get_result()->fetch_assoc();
    if (!$appt) send_json_response(false, 'Appointment not found for today');
    if (in_array($appt['status'], ['cancelled','no_show','completed'])) {
        send_json_response(false, 'Cannot check in a ' . $appt['status'] . ' appointment');
    }

    // Check already in queue
    $qChk = $conn->prepare("SELECT id, queue_number FROM queues WHERE appointment_id = ?");
    $qChk->bind_param('i', $appt_id);
    $qChk->execute();
    $existing = $qChk->get_result()->fetch_assoc();
    if ($existing) {
        send_json_response(false, 'Patient is already in the queue (No. ' . $existing['queue_number'] . ')');
    }

    // Next queue number for today
    $nStmt = $conn->query(
        "SELECT COALESCE(MAX(q.queue_number),0)+1 AS next_num
         FROM queues q JOIN appointments a ON q.appointment_id = a.id
         WHERE a.appointment_date = CURDATE()"
    );
    $next_num = (int)$nStmt->fetch_assoc()['next_num'];

    $conn->begin_transaction();
    try {
        // Insert queue entry
        $qIns = $conn->prepare(
            "INSERT INTO queues (appointment_id, queue_number, status) VALUES (?, ?, 'waiting')"
        );
        $qIns->bind_param('ii', $appt_id, $next_num);
        $qIns->execute();

        // Update appointment to confirmed
        $conn->prepare("UPDATE appointments SET status='confirmed' WHERE id=?")->execute() || true;
        $upd = $conn->prepare("UPDATE appointments SET status='confirmed' WHERE id=?");
        $upd->bind_param('i', $appt_id);
        $upd->execute();

        $conn->commit();
        send_json_response(true, 'Patient checked in successfully', ['queue_number' => $next_num]);
    } catch (Exception $e) {
        $conn->rollback();
        send_json_response(false, 'Check-in failed: ' . $e->getMessage());
    }
}

send_json_response(false, 'Method not allowed');
