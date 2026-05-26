<?php
/**
 * Application Settings Configuration
 * 
 * Global settings for the MedLink application
 */

// Application name and version
define('APP_NAME', 'MedLink');
define('APP_VERSION', '1.0.0');

// Paths
define('BASE_PATH', dirname(dirname(__DIR__)));
define('FRONTEND_PATH', BASE_PATH . '/frontend');
define('BACKEND_PATH', BASE_PATH . '/backend');
define('UPLOADS_PATH', BASE_PATH . '/uploads');

// URLs
define('BASE_URL', 'http://localhost/MedLink');
define('API_URL', BASE_URL . '/backend/api');

// Session settings
define('SESSION_TIMEOUT', 30 * 60); // 30 minutes in seconds
define('SESSION_COOKIE_SECURE', false); // Set to true in production with HTTPS
define('SESSION_COOKIE_HTTPONLY', true);

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCK_TIMEOUT', 15 * 60); // 15 minutes

// Email settings
define('EMAIL_FROM', 'noreply@medlink.com');
define('EMAIL_FROM_NAME', 'MedLink System');

// File upload settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);

// Pagination
define('ITEMS_PER_PAGE', 20);

// Debug mode
define('DEBUG_MODE', true); // Set to false in production

?>
