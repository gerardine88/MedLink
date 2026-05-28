<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'patient') {
    http_response_code(403);
    send_json_response(false, 'Access denied');
}
if (!$conn) send_json_response(false, $db_error ?: 'Database unavailable');

$user_id = (int)$_SESSION['user_id'];

$pStmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$pStmt->bind_param('i', $user_id);
$pStmt->execute();
$patient = $pStmt->get_result()->fetch_assoc();

if (!$patient) {
    send_json_response(true, 'No queue found', ['queue' => null]);
}
$pid = (int)$patient['id'];

// Patient's active queue entry today
$stmt = $conn->prepare(
    "SELECT q.id, q.queue_number, q.status, q.called_at, q.served_at,
            a.appointment_date, a.appointment_time,
            CONCAT(u.first_name,' ',u.last_name) AS doctor_name,
            COALESCE(dep.department_name,'General') AS department,
            COALESCE(s.service_name,'General Consultation') AS service_name
     FROM queues q
     JOIN appointments a ON q.appointment_id = a.id
     JOIN doctors doc ON a.doctor_id = doc.id
     JOIN users u ON doc.user_id = u.id
     LEFT JOIN departments dep ON doc.department_id = dep.id
     LEFT JOIN services s ON a.service_id = s.id
     WHERE a.patient_id = ?
       AND a.appointment_date = CURDATE()
     ORDER BY
       CASE q.status
         WHEN 'serving'   THEN 1
         WHEN 'waiting'   THEN 2
         WHEN 'completed' THEN 3
         ELSE 4
       END,
       q.queue_number ASC
     LIMIT 1"
);
$stmt->bind_param('i', $pid);
$stmt->execute();
$queue = $stmt->get_result()->fetch_assoc();

// How many people are ahead (status=waiting with lower queue number)
$aheadCount = 0;
if ($queue && $queue['status'] === 'waiting') {
    $aheadStmt = $conn->prepare(
        "SELECT COUNT(*) AS cnt
         FROM queues q2
         JOIN appointments a2 ON q2.appointment_id = a2.id
         WHERE a2.appointment_date = CURDATE()
           AND q2.status = 'waiting'
           AND q2.queue_number < ?"
    );
    $qn = (int)$queue['queue_number'];
    $aheadStmt->bind_param('i', $qn);
    $aheadStmt->execute();
    $aheadCount = (int)$aheadStmt->get_result()->fetch_assoc()['cnt'];
}

send_json_response(true, 'OK', [
    'queue'       => $queue,
    'ahead_count' => $aheadCount,
    'est_wait_min'=> $aheadCount * 15,
]);
