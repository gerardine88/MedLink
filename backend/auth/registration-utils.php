<?php
/**
 * Registration Utility Functions
 * 
 * Helper functions for user registration
 */

/**
 * Generate verification token for email confirmation
 * 
 * @return string Random verification token
 */
function generate_verification_token() {
    return bin2hex(random_bytes(32));
}

/**
 * Send verification email to user
 * 
 * @param string $email User email address
 * @param string $first_name User first name
 * @param string $verification_link Verification link
 * @return bool True if email sent successfully
 */
function send_verification_email($email, $first_name, $verification_link) {
    $subject = "Verify Your MedLink Account";
    $message = "
    <html>
    <head>
        <title>Email Verification</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #0066cc; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .footer { text-align: center; font-size: 12px; color: #666; padding: 20px; }
            .btn { background-color: #0066cc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>MedLink Account Verification</h1>
            </div>
            <div class='content'>
                <p>Hello $first_name,</p>
                <p>Welcome to MedLink! Please verify your email address to complete your registration.</p>
                <p>
                    <a href='$verification_link' class='btn'>Verify Email</a>
                </p>
                <p>Or copy and paste this link in your browser:</p>
                <p>$verification_link</p>
                <p>This link will expire in 24 hours.</p>
            </div>
            <div class='footer'>
                <p>© 2026 MedLink Inc. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">" . "\r\n";

    return mail($email, $subject, $message, $headers);
}

/**
 * Get registration statistics
 * 
 * @param mysqli $conn Database connection
 * @return array Statistics array
 */
function get_registration_stats($conn) {
    $stats = [];

    // Total users
    $result = $conn->query("SELECT COUNT(*) as total FROM users");
    $row = $result->fetch_assoc();
    $stats['total_users'] = $row['total'];

    // Users by role
    $result = $conn->query("SELECT user_role, COUNT(*) as count FROM users GROUP BY user_role");
    while ($row = $result->fetch_assoc()) {
        $stats['by_role'][$row['user_role']] = $row['count'];
    }

    // Users by status
    $result = $conn->query("SELECT status, COUNT(*) as count FROM users GROUP BY status");
    while ($row = $result->fetch_assoc()) {
        $stats['by_status'][$row['status']] = $row['count'];
    }

    // Registrations today
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()");
    $row = $result->fetch_assoc();
    $stats['registrations_today'] = $row['count'];

    return $stats;
}

/**
 * Get user registration details
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array|null User details or null if not found
 */
function get_user_registration_details($conn, $user_id) {
    $stmt = $conn->prepare(
        "SELECT id, first_name, last_name, email, phone, gender, user_role, status, created_at, last_login 
         FROM users WHERE id = ?"
    );
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}

/**
 * Validate phone number format
 * 
 * @param string $phone Phone number
 * @return bool True if valid phone format
 */
function is_valid_phone($phone) {
    $phone = preg_replace('/[^0-9\+\-\s\(\)]/i', '', $phone);
    return strlen($phone) >= 7 && strlen($phone) <= 15;
}

/**
 * Check password against breach databases (optional)
 * 
 * @param string $password Password to check
 * @return bool True if password appears to be safe
 */
function is_password_safe($password) {
    // Common weak passwords
    $weak_passwords = [
        'password', '123456', 'password123', 'admin', 'letmein',
        'welcome', 'monkey', '1q2w3e4r', 'qwerty', 'abc123'
    ];
    
    return !in_array(strtolower($password), $weak_passwords);
}

?>
