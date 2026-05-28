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
$method  = $_SERVER['REQUEST_METHOD'];

// ── GET profile ───────────────────────────────────────────
if ($method === 'GET') {
    $stmt = $conn->prepare(
        "SELECT u.first_name, u.last_name, u.email, u.phone, u.gender, u.status,
                p.patient_code, p.date_of_birth, p.blood_group, p.allergies,
                p.chronic_conditions, p.emergency_contact_name,
                p.emergency_contact_phone, p.emergency_contact_relation, p.address
         FROM users u
         LEFT JOIN patients p ON p.user_id = u.id
         WHERE u.id = ?"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();

    if (!$profile) send_json_response(false, 'User not found');

    send_json_response(true, 'OK', ['profile' => $profile]);
}

// ── PUT: update profile ───────────────────────────────────
if ($method === 'PUT' || $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $first_name = trim($data['first_name'] ?? '');
    $last_name  = trim($data['last_name']  ?? '');
    $phone      = trim($data['phone']      ?? '');
    $gender     = trim($data['gender']     ?? '');
    $password   = $data['password']        ?? '';

    $dob      = trim($data['date_of_birth']               ?? '');
    $blood    = trim($data['blood_group']                  ?? '');
    $allergies= trim($data['allergies']                   ?? '');
    $chronic  = trim($data['chronic_conditions']          ?? '');
    $ec_name  = trim($data['emergency_contact_name']      ?? '');
    $ec_phone = trim($data['emergency_contact_phone']     ?? '');
    $ec_rel   = trim($data['emergency_contact_relation']  ?? '');
    $address  = trim($data['address']                     ?? '');

    $errors = [];
    if (empty($first_name)) $errors['first_name'] = 'First name is required';
    if (empty($last_name))  $errors['last_name']  = 'Last name is required';
    if (!empty($errors)) send_json_response(false, 'Validation failed', ['errors' => $errors]);

    // Update users table
    if (!empty($password)) {
        if (strlen($password) < 8) {
            send_json_response(false, 'Password must be at least 8 characters');
        }
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $uStmt = $conn->prepare(
            "UPDATE users SET first_name=?, last_name=?, phone=?, gender=?, password_hash=?, updated_at=NOW()
             WHERE id=?"
        );
        $uStmt->bind_param('sssssi', $first_name, $last_name, $phone, $gender, $hash, $user_id);
    } else {
        $uStmt = $conn->prepare(
            "UPDATE users SET first_name=?, last_name=?, phone=?, gender=?, updated_at=NOW()
             WHERE id=?"
        );
        $uStmt->bind_param('ssssi', $first_name, $last_name, $phone, $gender, $user_id);
    }
    $uStmt->execute();

    // Upsert patients table
    $pCheck = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
    $pCheck->bind_param('i', $user_id);
    $pCheck->execute();
    $existing = $pCheck->get_result()->fetch_assoc();

    $bloodValid = in_array($blood, ['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown']) ? $blood : 'Unknown';
    $dobVal     = !empty($dob) ? $dob : null;

    if ($existing) {
        $pUpd = $conn->prepare(
            "UPDATE patients
             SET date_of_birth=?, blood_group=?, allergies=?, chronic_conditions=?,
                 emergency_contact_name=?, emergency_contact_phone=?,
                 emergency_contact_relation=?, address=?, updated_at=NOW()
             WHERE user_id=?"
        );
        $pUpd->bind_param('ssssssssi',
            $dobVal, $bloodValid, $allergies, $chronic,
            $ec_name, $ec_phone, $ec_rel, $address, $user_id
        );
        $pUpd->execute();
    } else {
        $code = 'PAT-' . str_pad($user_id, 5, '0', STR_PAD_LEFT);
        $pIns = $conn->prepare(
            "INSERT INTO patients
                (user_id, patient_code, date_of_birth, blood_group, allergies, chronic_conditions,
                 emergency_contact_name, emergency_contact_phone, emergency_contact_relation, address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $pIns->bind_param('isssssssss',
            $user_id, $code, $dobVal, $bloodValid, $allergies, $chronic,
            $ec_name, $ec_phone, $ec_rel, $address
        );
        $pIns->execute();
    }

    // Update session name
    $_SESSION['user_name'] = $first_name . ' ' . $last_name;

    send_json_response(true, 'Profile updated successfully');
}

send_json_response(false, 'Method not allowed');
