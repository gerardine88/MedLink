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

// ── GET ───────────────────────────────────────────────────
if ($method === 'GET') {
    $filter  = trim($_GET['filter']  ?? 'today');
    $search  = trim($_GET['search']  ?? '');
    $date    = trim($_GET['date']    ?? '');

    $where = 'WHERE 1=1';
    $params = []; $types = '';

    if ($filter === 'today') {
        $where .= ' AND a.appointment_date = CURDATE()';
    } elseif (in_array($filter, ['scheduled','confirmed','completed','cancelled','in_progress','no_show'])) {
        $where .= ' AND a.status = ?';
        $types .= 's'; $params[] = $filter;
    }
    if ($date) {
        $where .= ' AND a.appointment_date = ?';
        $types .= 's'; $params[] = $date;
    }
    if ($search) {
        $like = '%' . $search . '%';
        $where .= ' AND (pu.first_name LIKE ? OR pu.last_name LIKE ?
                         OR CONCAT(pu.first_name," ",pu.last_name) LIKE ?
                         OR p.patient_code LIKE ?)';
        $types .= 'ssss';
        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    }

    // Stats for today
    $sm = $conn->query(
        "SELECT COUNT(*) total,
                SUM(status='scheduled') scheduled,
                SUM(status='confirmed') confirmed,
                SUM(status IN ('in_progress','completed')) completed
         FROM appointments WHERE appointment_date=CURDATE()"
    )->fetch_assoc();

    $sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.reason, a.booking_type,
                   CONCAT(pu.first_name,' ',pu.last_name) AS patient_name, p.patient_code, pu.phone,
                   CONCAT(du.first_name,' ',du.last_name) AS doctor_name,
                   COALESCE(dep.department_name,'') AS department,
                   COALESCE(s.service_name,'General Consultation') AS service_name
            FROM appointments a
            JOIN patients p   ON a.patient_id  = p.id
            JOIN users pu     ON p.user_id     = pu.id
            JOIN doctors doc  ON a.doctor_id   = doc.id
            JOIN users du     ON doc.user_id   = du.id
            LEFT JOIN departments dep ON doc.department_id = dep.id
            LEFT JOIN services s      ON a.service_id       = s.id
            $where
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
            LIMIT 100";

    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $appts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Active doctors for the schedule form
    $docs = $conn->query(
        "SELECT doc.id AS doctor_id, CONCAT(u.first_name,' ',u.last_name) AS doctor_name,
                doc.specialization, dep.department_name, dep.id AS department_id
         FROM doctors doc JOIN users u ON doc.user_id=u.id
         LEFT JOIN departments dep ON doc.department_id=dep.id
         WHERE doc.status='active' AND u.status='active' ORDER BY u.first_name"
    )->fetch_all(MYSQLI_ASSOC);

    send_json_response(true, 'OK', [
        'stats'        => $sm,
        'appointments' => $appts,
        'doctors'      => $docs,
    ]);
}

// ── POST: schedule new appointment ───────────────────────
if ($method === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $patient_id = (int)($data['patient_id']  ?? 0);
    $doctor_id  = (int)($data['doctor_id']   ?? 0);
    $service_id = (int)($data['service_id']  ?? 0) ?: null;
    $date       = trim($data['date']         ?? '');
    $time       = trim($data['time']         ?? '');
    $reason     = trim($data['reason']       ?? '');
    $btype      = in_array($data['booking_type'] ?? '', ['walk-in','online','phone'])
                    ? $data['booking_type'] : 'walk-in';

    $errors = [];
    if (!$patient_id) $errors['patient_id'] = 'Patient required';
    if (!$doctor_id)  $errors['doctor_id']  = 'Doctor required';
    if (!$date)       $errors['date']       = 'Date required';
    if (!$time)       $errors['time']       = 'Time required';
    if (!empty($errors)) send_json_response(false, 'Validation failed', ['errors' => $errors]);

    $ins = $conn->prepare(
        "INSERT INTO appointments (patient_id, doctor_id, service_id, appointment_date, appointment_time, booking_type, reason, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled')"
    );
    $ins->bind_param('iiissss', $patient_id, $doctor_id, $service_id, $date, $time, $btype, $reason);
    if ($ins->execute()) {
        send_json_response(true, 'Appointment scheduled', ['appointment_id' => $conn->insert_id]);
    } else {
        send_json_response(false, 'Failed: ' . $ins->error);
    }
}

// ── PUT: update appointment status or reschedule ─────────
if ($method === 'PUT') {
    $data    = json_decode(file_get_contents('php://input'), true) ?: [];
    $appt_id = (int)($data['appointment_id'] ?? 0);
    $action  = trim($data['action'] ?? '');
    if (!$appt_id) send_json_response(false, 'Appointment ID required');

    if ($action === 'confirm') {
        $u = $conn->prepare("UPDATE appointments SET status='confirmed' WHERE id=? AND status='scheduled'");
        $u->bind_param('i', $appt_id); $u->execute();
        send_json_response(true, 'Appointment confirmed');
    }
    if ($action === 'cancel') {
        $u = $conn->prepare("UPDATE appointments SET status='cancelled' WHERE id=? AND status IN ('scheduled','confirmed')");
        $u->bind_param('i', $appt_id); $u->execute();
        if ($u->affected_rows > 0) send_json_response(true, 'Appointment cancelled');
        send_json_response(false, 'Cannot cancel this appointment');
    }
    if ($action === 'reschedule') {
        $new_date = trim($data['new_date'] ?? '');
        $new_time = trim($data['new_time'] ?? '');
        if (!$new_date || !$new_time) send_json_response(false, 'New date and time required');
        $u = $conn->prepare("UPDATE appointments SET appointment_date=?, appointment_time=?, status='scheduled' WHERE id=?");
        $u->bind_param('ssi', $new_date, $new_time, $appt_id); $u->execute();
        send_json_response(true, 'Appointment rescheduled');
    }
    send_json_response(false, 'Unknown action');
}

send_json_response(false, 'Method not allowed');
