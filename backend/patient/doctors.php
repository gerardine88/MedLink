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

// Active doctors with their department and services
$stmt = $conn->prepare(
    "SELECT doc.id AS doctor_id,
            CONCAT(u.first_name,' ',u.last_name) AS doctor_name,
            doc.specialization,
            doc.consultation_fee,
            COALESCE(dep.department_name,'General') AS department_name,
            dep.id AS department_id
     FROM doctors doc
     JOIN users u ON doc.user_id = u.id
     LEFT JOIN departments dep ON doc.department_id = dep.id
     WHERE doc.status = 'active' AND u.status = 'active'
     ORDER BY u.first_name ASC"
);
$stmt->execute();
$doctors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Services grouped by department
$svcStmt = $conn->prepare(
    "SELECT id AS service_id, service_name, price, duration_minutes, department_id
     FROM services
     WHERE status = 'active'
     ORDER BY service_name ASC"
);
$svcStmt->execute();
$services = $svcStmt->get_result()->fetch_all(MYSQLI_ASSOC);

send_json_response(true, 'OK', [
    'doctors'  => $doctors,
    'services' => $services,
]);
