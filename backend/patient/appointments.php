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

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: list all appointments ────────────────────────────
if ($method === 'GET') {
    $filter = trim($_GET['filter'] ?? 'all');
    $where  = 'WHERE a.patient_id = ?';
    if ($filter === 'upcoming') {
        $where .= " AND (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.status IN ('scheduled','confirmed','in_progress')))";
    } elseif ($filter === 'past') {
        $where .= " AND (a.appointment_date < CURDATE() OR a.status IN ('completed','cancelled','no_show'))";
    }

    $stmt = $conn->prepare(
        "SELECT a.id, a.appointment_date, a.appointment_time, a.status,
                a.reason, a.booking_type,
                CONCAT(u.first_name,' ',u.last_name) AS doctor_name,
                COALESCE(dep.department_name,'') AS department,
                COALESCE(s.service_name,'General Consultation') AS service_name,
                COALESCE(q.queue_number, NULL) AS queue_number,
                COALESCE(q.status, NULL) AS queue_status
         FROM appointments a
         JOIN doctors doc ON a.doctor_id = doc.id
         JOIN users u ON doc.user_id = u.id
         LEFT JOIN departments dep ON doc.department_id = dep.id
         LEFT JOIN services s ON a.service_id = s.id
         LEFT JOIN queues q ON q.appointment_id = a.id
         $where
         ORDER BY a.appointment_date DESC, a.appointment_time DESC"
    );
    $stmt->bind_param('i', $pid);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    send_json_response(true, 'OK', ['appointments' => $appointments]);
}

// ── POST: book new appointment ────────────────────────────
if ($method === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $doctor_id  = (int)($data['doctor_id']  ?? 0);
    $service_id = (int)($data['service_id'] ?? 0) ?: null;
    $date       = trim($data['appointment_date'] ?? '');
    $time       = trim($data['appointment_time'] ?? '');
    $reason     = trim($data['reason']       ?? '');
    $btype      = in_array($data['booking_type'] ?? '', ['walk-in','online','phone'])
                    ? $data['booking_type'] : 'online';

    $errors = [];
    if (!$doctor_id)             $errors['doctor_id']  = 'Please select a doctor';
    if (empty($date))            $errors['date']       = 'Appointment date is required';
    if (empty($time))            $errors['time']       = 'Appointment time is required';
    if ($date && $date < date('Y-m-d')) $errors['date'] = 'Date must be today or in the future';

    if (!empty($errors)) {
        send_json_response(false, 'Please fix the highlighted fields', ['errors' => $errors]);
    }

    // Check doctor exists and is active
    $dCheck = $conn->prepare("SELECT id FROM doctors WHERE id = ? AND status = 'active'");
    $dCheck->bind_param('i', $doctor_id);
    $dCheck->execute();
    if ($dCheck->get_result()->num_rows === 0) {
        send_json_response(false, 'Selected doctor not found or inactive');
    }

    // Check for duplicate booking on same date+time+doctor
    $dupCheck = $conn->prepare(
        "SELECT id FROM appointments
         WHERE patient_id = ? AND doctor_id = ? AND appointment_date = ? AND appointment_time = ?
           AND status NOT IN ('cancelled','no_show')"
    );
    $dupCheck->bind_param('iiss', $pid, $doctor_id, $date, $time);
    $dupCheck->execute();
    if ($dupCheck->get_result()->num_rows > 0) {
        send_json_response(false, 'You already have an appointment with this doctor at that date and time');
    }

    $ins = $conn->prepare(
        "INSERT INTO appointments
            (patient_id, doctor_id, service_id, appointment_date, appointment_time, booking_type, reason, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled')"
    );
    $ins->bind_param('iiissss', $pid, $doctor_id, $service_id, $date, $time, $btype, $reason);

    if ($ins->execute()) {
        $new_id = $conn->insert_id;
        send_json_response(true, 'Appointment booked successfully', ['appointment_id' => $new_id]);
    } else {
        send_json_response(false, 'Failed to book appointment: ' . $ins->error);
    }
}

// ── DELETE (cancel) ────────────────────────────────────────
if ($method === 'DELETE') {
    $data  = json_decode(file_get_contents('php://input'), true) ?: [];
    $appt_id = (int)($data['appointment_id'] ?? 0);
    if (!$appt_id) send_json_response(false, 'Appointment ID required');

    $upd = $conn->prepare(
        "UPDATE appointments SET status = 'cancelled'
         WHERE id = ? AND patient_id = ? AND status IN ('scheduled','confirmed')"
    );
    $upd->bind_param('ii', $appt_id, $pid);
    $upd->execute();
    if ($upd->affected_rows > 0) {
        send_json_response(true, 'Appointment cancelled');
    } else {
        send_json_response(false, 'Cannot cancel this appointment');
    }
}

send_json_response(false, 'Method not allowed');
