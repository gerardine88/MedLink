<?php
/**
 * Check Email Availability
 * 
 * AJAX endpoint to check if email is available for registration
 * Method: POST
 * Parameters: email
 */

// Set error handling for JSON responses
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'An error occurred', 'data' => ['available' => false]]);
        exit();
    }
});

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Initialize response
$response = [
    'success' => false,
    'message' => 'Email check failed',
    'data' => ['available' => false]
];

try {
    // Check database connection
    if (!empty($db_error) || !$conn || !isset($conn)) {
        throw new Exception($db_error ?: 'Database connection failed');
    }
    
    // Get email from request
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        throw new Exception('Email is required');
    }

    if (!is_valid_email($email)) {
        throw new Exception('Invalid email format');
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        throw new Exception('Query failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $available = $result->num_rows === 0;

    $stmt->close();

    $response['success'] = true;
    $response['message'] = $available ? 'Email is available' : 'Email is already registered';
    $response['data'] = ['available' => $available];

} catch (Exception $e) {
    error_log("Email check error: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['data'] = ['available' => false];
}

// Close database connection
if (isset($conn) && $conn) {
    $conn->close();
}

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit();
