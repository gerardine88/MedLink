<?php
/**
 * Users API Endpoint
 * 
 * Handles user-related API requests (GET, POST, PUT, DELETE)
 */

session_start();

// Verify user is authenticated
if (!isset($_SESSION['user_id'])) {
    send_json_response(false, 'Unauthorized', [], 401);
}

// Route based on HTTP method
switch ($method) {
    case 'GET':
        handle_get_users();
        break;
    
    case 'POST':
        handle_post_users();
        break;
    
    case 'PUT':
        handle_put_users();
        break;
    
    case 'DELETE':
        handle_delete_users();
        break;
    
    default:
        send_json_response(false, 'Method not allowed', [], 405);
}

/**
 * GET: Retrieve user information
 */
function handle_get_users() {
    global $conn, $endpoint_parts;
    
    // Check if specific user ID is requested
    if (isset($endpoint_parts[1])) {
        $user_id = intval($endpoint_parts[1]);
        
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone, gender, user_role, status, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            send_json_response(false, 'User not found', [], 404);
        }
        
        $user = $result->fetch_assoc();
        send_json_response(true, 'User retrieved successfully', $user);
    }
    
    // Get all users (admin only)
    send_json_response(false, 'Permission denied', [], 403);
}

/**
 * POST: Create new user or update profile
 */
function handle_post_users() {
    global $conn;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Update current user profile
    $user_id = $_SESSION['user_id'];
    $phone = $data['phone'] ?? '';
    $gender = $data['gender'] ?? '';
    
    $stmt = $conn->prepare("UPDATE users SET phone = ?, gender = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $phone, $gender, $user_id);
    
    if ($stmt->execute()) {
        send_json_response(true, 'Profile updated successfully');
    } else {
        send_json_response(false, 'Update failed', [], 500);
    }
}

/**
 * PUT: Update user information
 */
function handle_put_users() {
    global $conn;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['user_id'])) {
        send_json_response(false, 'User ID required', [], 400);
    }
    
    $user_id = intval($data['user_id']);
    $phone = $data['phone'] ?? '';
    $gender = $data['gender'] ?? '';
    
    $stmt = $conn->prepare("UPDATE users SET phone = ?, gender = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $phone, $gender, $user_id);
    
    if ($stmt->execute()) {
        send_json_response(true, 'User updated successfully');
    } else {
        send_json_response(false, 'Update failed', [], 500);
    }
}

/**
 * DELETE: Deactivate user account
 */
function handle_delete_users() {
    send_json_response(false, 'Delete operation not permitted', [], 403);
}

?>
