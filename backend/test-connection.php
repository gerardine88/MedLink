<?php
/**
 * Backend Connection Test
 * Tests database connectivity and basic file structure
 */

header('Content-Type: application/json; charset=utf-8');

$result = [
    'database' => ['status' => 'unknown', 'error' => null],
    'files' => ['status' => 'unknown', 'missing' => []],
    'config' => ['status' => 'unknown', 'error' => null],
    'functions' => ['status' => 'unknown', 'error' => null]
];

// Test database connection
try {
    require_once './config/database.php';
    
    if ($conn && !isset($db_error)) {
        $result['database']['status'] = 'connected';
        $result['database']['host'] = DB_HOST;
        $result['database']['database'] = DB_NAME;
        
        // Test a simple query
        $test_result = $conn->query("SELECT 1");
        if ($test_result) {
            $result['database']['query_test'] = 'passed';
        }
        
        $conn->close();
    } else {
        $result['database']['status'] = 'failed';
        $result['database']['error'] = $db_error ?? 'Unknown connection error';
    }
} catch (Exception $e) {
    $result['database']['status'] = 'error';
    $result['database']['error'] = $e->getMessage();
}

// Test required files
$files_to_check = [
    './config/database.php',
    './config/settings.php',
    './includes/helpers.php',
    './includes/auth-check.php',
    './auth/register.php',
    './auth/check-email.php',
    './auth/validate-password.php'
];

$missing_files = [];
foreach ($files_to_check as $file) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        $missing_files[] = $file;
    }
}

$result['files']['status'] = empty($missing_files) ? 'all_present' : 'missing_files';
$result['files']['missing'] = $missing_files;
$result['files']['base_path'] = __DIR__;

// Test config values
try {
    require_once './config/settings.php';
    
    $result['config']['status'] = 'loaded';
    $result['config']['app_name'] = APP_NAME ?? 'unknown';
    $result['config']['base_url'] = BASE_URL ?? 'unknown';
    $result['config']['base_path'] = BASE_PATH ?? 'unknown';
} catch (Exception $e) {
    $result['config']['status'] = 'error';
    $result['config']['error'] = $e->getMessage();
}

// Test helper functions
try {
    require_once './includes/helpers.php';
    
    $functions_to_check = [
        'sanitize_input',
        'is_valid_email',
        'hash_password',
        'log_event'
    ];
    
    $available_functions = [];
    foreach ($functions_to_check as $func) {
        $available_functions[$func] = function_exists($func) ? 'available' : 'missing';
    }
    
    $result['functions']['status'] = 'checked';
    $result['functions']['available'] = $available_functions;
} catch (Exception $e) {
    $result['functions']['status'] = 'error';
    $result['functions']['error'] = $e->getMessage();
}

// Summary
$result['summary'] = [
    'database_ok' => $result['database']['status'] === 'connected',
    'files_ok' => $result['files']['status'] === 'all_present',
    'config_ok' => $result['config']['status'] === 'loaded',
    'functions_ok' => $result['functions']['status'] === 'checked'
];

http_response_code(200);
echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit();
?>