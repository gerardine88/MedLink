<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

function send_registration_response($payload, $status_code = 200) {

    http_response_code($status_code);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (!empty($db_error) || !$conn) {

    send_registration_response([
        'success' => false,
        'message' => $db_error ?: 'Database connection failed'
    ], 500);
}

/**
 * Allow only POST request
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    send_registration_response([
        'success' => false,
        'message' => 'Invalid request method'
    ], 405);
}

/**
 * Get form data
 */
$first_name =
    trim($_POST['first_name'] ?? '');

$last_name =
    trim($_POST['last_name'] ?? '');

$email =
    trim($_POST['email'] ?? '');

$password =
    trim($_POST['password'] ?? '');

$phone =
    trim($_POST['phone'] ?? '');

$gender =
    trim($_POST['gender'] ?? '');

$status = 'active';

/**
 * Validation
 */
$errors = [];

if (empty($first_name)) {
    $errors['first_name'] =
        'First name is required';
}

if (empty($last_name)) {
    $errors['last_name'] =
        'Last name is required';
}

if (empty($email)) {
    $errors['email'] =
        'Email is required';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] =
        'Invalid email format';
}

if (empty($password)) {
    $errors['password'] =
        'Password is required';
}

if (strlen($password) < 8) {
    $errors['password'] =
        'Password must be at least 8 characters';
}

if (empty($phone)) {
    $errors['phone'] =
        'Phone number is required';
}

if (empty($gender)) {
    $errors['gender'] =
        'Gender is required';
}

/**
 * Return validation errors
 */
if (!empty($errors)) {

    send_registration_response([
        'success' => false,
        'message' => 'Please correct the highlighted fields',
        'errors' => $errors
    ]);
}

/**
 * Resolve patient role_id from roles table
 */
$roleStmt = $conn->prepare("SELECT id FROM roles WHERE role_name = 'patient' LIMIT 1");

if (!$roleStmt || !$roleStmt->execute()) {
    send_registration_response([
        'success' => false,
        'message' => 'System configuration error: roles table missing'
    ], 500);
}

$roleResult = $roleStmt->get_result();

if ($roleResult->num_rows === 0) {
    send_registration_response([
        'success' => false,
        'message' => 'System configuration error: patient role not found'
    ], 500);
}

$role_id = (int) $roleResult->fetch_assoc()['id'];

$roleStmt->close();

/**
 * Check existing email
 */
$checkQuery =
    "SELECT id FROM users WHERE email = ?";

$checkStmt =
    $conn->prepare($checkQuery);

if (!$checkStmt) {

    send_registration_response([
        'success' => false,
        'message' => 'Database error: ' . $conn->error
    ], 500);
}

$checkStmt->bind_param(
    "s",
    $email
);

if (!$checkStmt->execute()) {

    send_registration_response([
        'success' => false,
        'message' => 'Database query failed: ' . $checkStmt->error
    ], 500);
}

$result =
    $checkStmt->get_result();

if ($result->num_rows > 0) {

    send_registration_response([
        'success' => false,
        'message' => 'Email already exists',
        'errors' => [
            'email' =>
                'Email already exists'
        ]
    ]);
}

/**
 * Hash password
 */
$password_hash =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );

/**
 * Insert user
 */
$query = "
    INSERT INTO users (
        role_id,
        first_name,
        last_name,
        email,
        password_hash,
        phone,
        gender,
        status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt =
    $conn->prepare($query);

if (!$stmt) {

    send_registration_response([
        'success' => false,
        'message' => 'Database error: ' . $conn->error
    ], 500);
}

$stmt->bind_param(
    "isssssss",
    $role_id,
    $first_name,
    $last_name,
    $email,
    $password_hash,
    $phone,
    $gender,
    $status
);

/**
 * Execute query
 */
if ($stmt->execute()) {

    send_registration_response([
        'success' => true,
        'message' =>
            'Registration successful'
    ]);

} else {

    send_registration_response([
        'success' => false,
        'message' =>
            'Registration failed: ' . $stmt->error
    ], 500);
}

$stmt->close();
$conn->close();

?>
