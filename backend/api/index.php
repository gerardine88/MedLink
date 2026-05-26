<?php
/**
 * API Response Handler
 * 
 * Centralized API response management for AJAX requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../config/settings.php';
require_once '../includes/helpers.php';

// Define API version
define('API_VERSION', '1.0');

/**
 * Route API requests to appropriate handlers
 */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$endpoint = str_replace('/MedLink/backend/api/', '', $request_uri);
$endpoint_parts = explode('/', trim($endpoint, '/'));

// Route API calls
switch ($endpoint_parts[0]) {
    case 'users':
        require 'endpoints/users.php';
        break;
    
    case 'appointments':
        require 'endpoints/appointments.php';
        break;
    
    case 'queue':
        require 'endpoints/queue.php';
        break;
    
    case 'services':
        require 'endpoints/services.php';
        break;
    
    default:
        send_json_response(false, 'Endpoint not found', [], 404);
}

?>
