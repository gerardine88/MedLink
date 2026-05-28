<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'receptionist') {
    http_response_code(403); send_json_response(false, 'Access denied');
}
if (!$conn) send_json_response(false, $db_error ?: 'Database unavailable');

// Today's appointment counts
$s = $conn->query(
    "SELECT
        COUNT(*) AS total_today,
        SUM(status IN ('confirmed','in_progress')) AS arrivals,
        SUM(status IN ('scheduled','confirmed')) AS pending_count
     FROM appointments WHERE appointment_date = CURDATE()"
);
$appts = $s->fetch_assoc();

// Queue stats today
$q = $conn->query(
    "SELECT
        COUNT(*) AS total_queue,
        SUM(q.status='waiting')   AS waiting,
        SUM(q.status='serving')   AS serving,
        SUM(q.status='completed') AS completed
     FROM queues q
     JOIN appointments a ON q.appointment_id = a.id
     WHERE a.appointment_date = CURDATE()"
);
$queue = $q->fetch_assoc();

// Today's revenue
$r = $conn->query(
    "SELECT COALESCE(SUM(amount_paid),0) AS revenue FROM payments WHERE DATE(paid_at) = CURDATE()"
);
$revenue = $r->fetch_assoc()['revenue'];

// Unpaid bills count (all time, outstanding)
$u = $conn->query(
    "SELECT COUNT(*) AS cnt FROM bills WHERE payment_status IN ('unpaid','partial')"
);
$unpaid = $u->fetch_assoc()['cnt'];

// Recent activity: last 8 queue entries today
$act = $conn->query(
    "SELECT q.queue_number, q.status, q.created_at,
            CONCAT(pu.first_name,' ',pu.last_name) AS patient_name,
            CONCAT(du.first_name,' ',du.last_name) AS doctor_name
     FROM queues q
     JOIN appointments a  ON q.appointment_id = a.id
     JOIN patients pat    ON a.patient_id = pat.id
     JOIN users pu        ON pat.user_id   = pu.id
     JOIN doctors doc     ON a.doctor_id   = doc.id
     JOIN users du        ON doc.user_id   = du.id
     WHERE a.appointment_date = CURDATE()
     ORDER BY q.created_at DESC LIMIT 8"
);
$activity = $act->fetch_all(MYSQLI_ASSOC);

send_json_response(true, 'OK', [
    'appointments' => $appts,
    'queue'        => $queue,
    'revenue'      => $revenue,
    'unpaid_bills' => $unpaid,
    'activity'     => $activity,
]);
