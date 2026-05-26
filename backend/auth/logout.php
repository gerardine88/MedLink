<?php
/**
 * User Logout
 *
 * Handles user session termination.
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/helpers.php';

session_start();

if (isset($_SESSION['user_id'])) {
    log_event('INFO', 'User logged out', ['user_id' => $_SESSION['user_id']]);
}

session_destroy();

header("Location: " . BASE_URL . "/frontend/pages/public/login.html");
exit;
?>
