<?php
/**
 * Current User
 *
 * Returns the logged-in user's session details.
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/helpers.php';

session_start();

if (empty($_SESSION['user_id'])) {
    send_json_response(false, 'Not logged in');
}

$current_time = time();
$last_activity = $_SESSION['last_activity'] ?? $current_time;

if (($current_time - $last_activity) > SESSION_TIMEOUT) {
    session_destroy();
    send_json_response(false, 'Session expired');
}

$_SESSION['last_activity'] = $current_time;

send_json_response(true, 'Current user loaded', [
    'id' => $_SESSION['user_id'],
    'name' => $_SESSION['user_name'] ?? '',
    'email' => $_SESSION['user_email'] ?? '',
    'role' => $_SESSION['user_role'] ?? ''
]);
?>
