<?php
/**
 * Temporary password hash generator.
 * USE ONCE, then delete this file.
 * Access: http://localhost/MedLink/backend/auth/make-hash.php?password=YourPassword123@
 */

$password = $_GET['password'] ?? '';

if (empty($password)) {
    echo '<p>Provide a password: <code>?password=YourPassword123@</code></p>';
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

echo '<p><strong>Password:</strong> ' . htmlspecialchars($password) . '</p>';
echo '<p><strong>Hash (copy this):</strong></p>';
echo '<textarea rows="3" style="width:100%;font-family:monospace">' . $hash . '</textarea>';
echo '<hr><p style="color:red"><strong>Delete this file after use!</strong></p>';
?>
