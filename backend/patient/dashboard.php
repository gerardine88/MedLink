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

// Get or auto-create patient record
$pStmt = $conn->prepare("SELECT id, patient_code FROM patients WHERE user_id = ?");
$pStmt->bind_param('i', $user_id);
$pStmt->execute();
$patient = $pStmt->get_result()->fetch_assoc();

if (!$patient) {
    $code = 'PAT-' . str_pad($user_id, 5, '0', STR_PAD_LEFT);
    $ins  = $conn->prepare("INSERT INTO patients (user_id, patient_code) VALUES (?, ?)");
    $ins->bind_param('is', $user_id, $code);
    $ins->execute();
    $patient = ['id' => $conn->insert_id, 'patient_code' => $code];
}
$pid = (int)$patient['id'];

// Next upcoming appointment
$apptStmt = $conn->prepare(
    "SELECT a.id, a.appointment_date, a.appointment_time, a.status,
            CONCAT(u.first_name,' ',u.last_name) AS doctor_name,
            COALESCE(s.service_name,'General Consultation') AS service_name
     FROM appointments a
     JOIN doctors doc ON a.doctor_id = doc.id
     JOIN users u ON doc.user_id = u.id
     LEFT JOIN services s ON a.service_id = s.id
     WHERE a.patient_id = ?
       AND (a.appointment_date > CURDATE()
         OR (a.appointment_date = CURDATE() AND a.status IN ('scheduled','confirmed','in_progress')))
     ORDER BY a.appointment_date ASC, a.appointment_time ASC
     LIMIT 1"
);
$apptStmt->bind_param('i', $pid);
$apptStmt->execute();
$nextAppt = $apptStmt->get_result()->fetch_assoc();

// Today's queue
$qStmt = $conn->prepare(
    "SELECT q.queue_number, q.status,
            CONCAT(u.first_name,' ',u.last_name) AS doctor_name
     FROM queues q
     JOIN appointments a ON q.appointment_id = a.id
     JOIN doctors doc ON a.doctor_id = doc.id
     JOIN users u ON doc.user_id = u.id
     WHERE a.patient_id = ?
       AND a.appointment_date = CURDATE()
       AND q.status IN ('waiting','serving')
     ORDER BY q.created_at DESC
     LIMIT 1"
);
$qStmt->bind_param('i', $pid);
$qStmt->execute();
$queue = $qStmt->get_result()->fetch_assoc();

// Unpaid bills
$bStmt = $conn->prepare(
    "SELECT COUNT(*) AS cnt,
            COALESCE(SUM(total_amount - amount_paid), 0) AS total_due
     FROM bills
     WHERE patient_id = ? AND payment_status IN ('unpaid','partial')"
);
$bStmt->bind_param('i', $pid);
$bStmt->execute();
$bills = $bStmt->get_result()->fetch_assoc();

// Recent activity (last 5 consultations)
$aStmt = $conn->prepare(
    "SELECT c.created_at, c.diagnosis,
            CONCAT(u.first_name,' ',u.last_name) AS doctor_name,
            c.status, a.appointment_date
     FROM consultations c
     JOIN doctors doc ON c.doctor_id = doc.id
     JOIN users u ON doc.user_id = u.id
     JOIN appointments a ON c.appointment_id = a.id
     WHERE a.patient_id = ?
     ORDER BY c.created_at DESC
     LIMIT 5"
);
$aStmt->bind_param('i', $pid);
$aStmt->execute();
$activity = $aStmt->get_result()->fetch_all(MYSQLI_ASSOC);

send_json_response(true, 'OK', [
    'patient_code'     => $patient['patient_code'],
    'next_appointment' => $nextAppt,
    'queue'            => $queue,
    'bills'            => $bills,
    'recent_activity'  => $activity,
]);
