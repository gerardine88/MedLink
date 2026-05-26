<?php
/**
 * User Login
 *
 * Handles user authentication and session management.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/helpers.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(false, 'Invalid request method');
}

if (!empty($db_error) || !$conn) {
    send_json_response(false, $db_error ?: 'Database connection failed');
}

$email = sanitize_input($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    send_json_response(false, 'Email and password are required');
}

if (!is_valid_email($email)) {
    send_json_response(false, 'Please enter a valid email address');
}

$attempt_key = 'login_attempts_' . md5($email);
$attempts = $_SESSION[$attempt_key] ?? 0;

if ($attempts >= MAX_LOGIN_ATTEMPTS) {
    $last_attempt = $_SESSION['last_attempt_' . md5($email)] ?? 0;
    $time_elapsed = time() - $last_attempt;

    if ($time_elapsed < LOCK_TIMEOUT) {
        send_json_response(false, 'Too many login attempts. Please try again later.');
    }

    $_SESSION[$attempt_key] = 0;
}

$stmt = $conn->prepare(
    "SELECT id, first_name, last_name, email, password_hash, user_role, status
     FROM users
     WHERE email = ?
     LIMIT 1"
);

if (!$stmt) {
    send_json_response(false, 'Database error: ' . $conn->error);
}

$stmt->bind_param("s", $email);

if (!$stmt->execute()) {
    send_json_response(false, 'Database query failed: ' . $stmt->error);
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION[$attempt_key] = $attempts + 1;
    $_SESSION['last_attempt_' . md5($email)] = time();
    log_event('WARNING', 'Login attempt with non-existent email', ['email' => $email]);
    send_json_response(false, 'Invalid email or password');
}

$user = $result->fetch_assoc();

if (!verify_password($password, $user['password_hash'])) {
    $_SESSION[$attempt_key] = $attempts + 1;
    $_SESSION['last_attempt_' . md5($email)] = time();
    log_event('WARNING', 'Failed login attempt', ['email' => $email]);
    send_json_response(false, 'Invalid email or password');
}

if ($user['status'] !== 'active') {
    log_event('WARNING', 'Login attempt on inactive account', [
        'email' => $email,
        'status' => $user['status']
    ]);

    send_json_response(false, 'Account is ' . $user['status']);
}

$update_stmt =
    $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");

if ($update_stmt) {
    $update_stmt->bind_param("i", $user['id']);
    $update_stmt->execute();
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['user_role'] = $user['user_role'];
$_SESSION['last_activity'] = time();

unset($_SESSION[$attempt_key]);
unset($_SESSION['last_attempt_' . md5($email)]);

log_event('INFO', 'User logged in successfully', [
    'email' => $email,
    'role' => $user['user_role']
]);

$redirect_url = BASE_URL . '/frontend/pages/';

switch ($user['user_role']) {
    case 'patient':
        $redirect_url .= 'patient/patient-dashboard.html';
        break;

    case 'doctor':
        $redirect_url .= 'doctor/doctor-dashboard.html';
        break;

    case 'receptionist':
        $redirect_url .= 'receptionist/reception-dashboard.html';
        break;

    case 'admin':
        $redirect_url .= 'admin/admin-dashboard.html';
        break;

    default:
        $redirect_url .= 'public/index.html';
}

send_json_response(true, 'Login successful', [
    'redirect_url' => $redirect_url,
    'user_role' => $user['user_role']
]);
?>
