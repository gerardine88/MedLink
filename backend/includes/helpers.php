<?php
/**
 * Helper Functions
 * 
 * Common utility functions used across the application
 */

/**
 * Sanitize user input to prevent XSS attacks
 * 
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Validate email format
 * 
 * @param string $email Email address
 * @return bool True if valid email
 */
if (!function_exists('is_valid_email')) {
    function is_valid_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Hash password using bcrypt
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
if (!function_exists('hash_password')) {
    function hash_password($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}

/**
 * Verify password against hash
 * 
 * @param string $password Plain text password
 * @param string $hash Password hash
 * @return bool True if password matches
 */
if (!function_exists('verify_password')) {
    function verify_password($password, $hash) {
        return password_verify($password, $hash);
    }
}

/**
 * Log application events
 * 
 * @param string $level Log level (INFO, WARNING, ERROR)
 * @param string $message Log message
 * @param array $context Additional context data
 */
if (!function_exists('log_event')) {
    function log_event($level = 'INFO', $message = '', $context = []) {
        $log_file = BASE_PATH . '/logs/app-' . date('Y-m-d') . '.log';
        
        // Create logs directory if it doesn't exist
        if (!is_dir(dirname($log_file))) {
            mkdir(dirname($log_file), 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $user_id = $_SESSION['user_id'] ?? 'GUEST';
        
        $log_message = "[$timestamp] [$level] [User: $user_id] $message";
        
        if (!empty($context)) {
            $log_message .= " " . json_encode($context);
        }
        
        file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
    }
}

/**
 * Send JSON response
 * 
 * @param bool $success Success status
 * @param string $message Response message
 * @param array $data Response data
 */
if (!function_exists('send_json_response')) {
    function send_json_response($success = true, $message = '', $data = []) {
        header('Content-Type: application/json');
        
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
        
        echo json_encode($response);
        exit();
    }
}

/**
 * Redirect user to specific URL
 * 
 * @param string $url Redirect URL
 * @param int $delay Delay in seconds
 */
if (!function_exists('redirect')) {
    function redirect($url, $delay = 0) {
        if ($delay > 0) {
            header("Refresh: $delay; url=$url");
        } else {
            header("Location: $url");
        }
        exit();
    }
}

?>
