<?php
/**
 * Authentication Check
 * 
 * Verify user session and redirect to login if not authenticated
 */

require_once __DIR__ . '/../config/settings.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: " . BASE_URL . "/frontend/pages/public/login.html");
    exit();
}

// Check session timeout
$session_timeout = SESSION_TIMEOUT;
$current_time = time();

if (isset($_SESSION['last_activity'])) {
    $time_elapsed = $current_time - $_SESSION['last_activity'];
    
    if ($time_elapsed > $session_timeout) {
        // Session expired
        session_destroy();
        header("Location: " . BASE_URL . "/frontend/pages/public/login.html?expired=1");
        exit();
    }
}

// Update last activity time
$_SESSION['last_activity'] = $current_time;

/**
 * Role-based access control
 * 
 * @param array $allowed_roles Array of allowed user roles
 * @return bool True if user has access, false otherwise
 */
if (!function_exists('check_role')) {
    function check_role($allowed_roles = []) {
        if (empty($allowed_roles)) {
            return true;
        }
        
        if (!isset($_SESSION['user_role'])) {
            return false;
        }
        
        return in_array($_SESSION['user_role'], $allowed_roles);
    }
}

/**
 * Get current user information
 * 
 * @return array User details
 */
if (!function_exists('get_current_user')) {
    function get_current_user() {
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'role' => $_SESSION['user_role'] ?? null,
            'name' => $_SESSION['user_name'] ?? null
        ];
    }
}

?>
