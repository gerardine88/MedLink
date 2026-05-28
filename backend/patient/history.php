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
    send_json_response(true, 'No records found', [
        'consultations' => [],
        'prescriptions' => [],
        'records'       => [],
    ]);
}
$pid = (int)$patient['id'];

// Past consultations with vitals
$cStmt = $conn->prepare(
    "SELECT c.id, c.created_at, a.appointment_date,
            c.chief_complaint, c.symptoms, c.diagnosis, c.treatment_plan,
            c.vitals_bp, c.vitals_temp, c.vitals_pulse, c.vitals_o2,
            c.vitals_weight, c.vitals_height,
            c.patient_instructions, c.follow_up, c.status AS consult_status,
            CONCAT(u.first_name,' ',u.last_name) AS doctor_name,
            COALESCE(dep.department_name,'General') AS department
     FROM consultations c
     JOIN appointments a ON c.appointment_id = a.id
     JOIN doctors doc ON c.doctor_id = doc.id
     JOIN users u ON doc.user_id = u.id
     LEFT JOIN departments dep ON doc.department_id = dep.id
     WHERE a.patient_id = ?
     ORDER BY a.appointment_date DESC"
);
$cStmt->bind_param('i', $pid);
$cStmt->execute();
$consultations = $cStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Prescriptions
$prStmt = $conn->prepare(
    "SELECT p.id, p.medication_name, p.dosage, p.frequency, p.duration,
            p.route, p.instructions, p.status, p.created_at,
            CONCAT(u.first_name,' ',u.last_name) AS doctor_name
     FROM prescriptions p
     JOIN doctors doc ON p.doctor_id = doc.id
     JOIN users u ON doc.user_id = u.id
     WHERE p.patient_id = ?
     ORDER BY p.created_at DESC"
);
$prStmt->bind_param('i', $pid);
$prStmt->execute();
$prescriptions = $prStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Medical records/attachments
$mrStmt = $conn->prepare(
    "SELECT mr.id, mr.record_type, mr.description, mr.created_at,
            mr.is_confidential, mr.attachment_path,
            CONCAT(u.first_name,' ',u.last_name) AS created_by_name
     FROM medical_records mr
     LEFT JOIN users u ON mr.created_by = u.id
     WHERE mr.patient_id = ? AND mr.is_confidential = 0
     ORDER BY mr.created_at DESC"
);
$mrStmt->bind_param('i', $pid);
$mrStmt->execute();
$records = $mrStmt->get_result()->fetch_all(MYSQLI_ASSOC);

send_json_response(true, 'OK', [
    'consultations' => $consultations,
    'prescriptions' => $prescriptions,
    'records'       => $records,
]);
