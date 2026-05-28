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

// ── GET: search patients ──────────────────────────────────
if ($method === 'GET') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) send_json_response(true, 'OK', ['patients' => []]);

    $like = '%' . $conn->real_escape_string($q) . '%';
    $stmt = $conn->prepare(
        "SELECT p.id AS patient_id, p.patient_code,
                CONCAT(u.first_name,' ',u.last_name) AS full_name,
                u.phone, u.email, u.gender,
                p.date_of_birth, p.blood_group
         FROM patients p
         JOIN users u ON p.user_id = u.id
         WHERE (u.first_name LIKE ? OR u.last_name LIKE ?
                OR CONCAT(u.first_name,' ',u.last_name) LIKE ?
                OR u.phone LIKE ? OR p.patient_code LIKE ?)
           AND u.status = 'active'
         ORDER BY u.first_name ASC
         LIMIT 15"
    );
    $stmt->bind_param('sssss', $like, $like, $like, $like, $like);
    $stmt->execute();
    $patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    send_json_response(true, 'OK', ['patients' => $patients]);
}

// ── POST: register walk-in patient ────────────────────────
if ($method === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $first_name = trim($data['first_name'] ?? '');
    $last_name  = trim($data['last_name']  ?? '');
    $phone      = trim($data['phone']      ?? '');
    $gender     = trim($data['gender']     ?? '');
    $dob        = trim($data['date_of_birth'] ?? '');
    $email      = trim($data['email']      ?? '');

    $errors = [];
    if (!$first_name) $errors['first_name'] = 'First name required';
    if (!$last_name)  $errors['last_name']  = 'Last name required';
    if (!$phone)      $errors['phone']      = 'Phone required';
    if (!empty($errors)) send_json_response(false, 'Validation failed', ['errors' => $errors]);

    // Generate email if not provided
    if (!$email) {
        $email = 'walkin_' . preg_replace('/\D/', '', $phone) . '_' . time() . '@medlink.local';
    } else {
        // Check duplicate
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $chk->bind_param('s', $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            send_json_response(false, 'Email already registered. Search for the existing patient.');
        }
    }

    // Lookup role_id for patient
    $rStmt = $conn->prepare("SELECT id FROM roles WHERE role_name = 'patient' LIMIT 1");
    $rStmt->execute();
    $role = $rStmt->get_result()->fetch_assoc();
    if (!$role) send_json_response(false, 'Role config error');
    $role_id = $role['id'];

    // Generate temp password
    $tmp_pass = password_hash('WalkIn@' . date('Y'), PASSWORD_BCRYPT, ['cost' => 10]);

    $conn->begin_transaction();
    try {
        $uIns = $conn->prepare(
            "INSERT INTO users (first_name, last_name, email, password_hash, phone, gender, role_id, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'active')"
        );
        $uIns->bind_param('ssssssi', $first_name, $last_name, $email, $tmp_pass, $phone, $gender, $role_id);
        $uIns->execute();
        $user_id = $conn->insert_id;

        $code = 'PAT-' . str_pad($user_id, 5, '0', STR_PAD_LEFT);
        $dobVal = $dob ?: null;
        $pIns = $conn->prepare(
            "INSERT INTO patients (user_id, patient_code, date_of_birth) VALUES (?, ?, ?)"
        );
        $pIns->bind_param('iss', $user_id, $code, $dobVal);
        $pIns->execute();
        $patient_id = $conn->insert_id;

        $conn->commit();
        send_json_response(true, 'Patient registered successfully', [
            'patient_id'   => $patient_id,
            'patient_code' => $code,
            'full_name'    => $first_name . ' ' . $last_name,
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        send_json_response(false, 'Registration failed: ' . $e->getMessage());
    }
}

send_json_response(false, 'Method not allowed');
