<?php
/**
 * Validate Password Strength
 * 
 * AJAX endpoint to validate password strength in real-time
 * Method: POST
 * Parameters: password
 */

// Set error handling for JSON responses
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
});

header('Content-Type: application/json; charset=utf-8');

try {
    $password = $_POST['password'] ?? '';

    $response = [
        'success' => true,
        'strength' => 'weak',
        'score' => 0,
        'requirements' => [
            'length' => [
                'required' => 8,
                'met' => strlen($password) >= 8
            ],
            'uppercase' => [
                'pattern' => 'At least one uppercase letter',
                'met' => (bool)preg_match('/[A-Z]/', $password)
            ],
            'lowercase' => [
                'pattern' => 'At least one lowercase letter',
                'met' => (bool)preg_match('/[a-z]/', $password)
            ],
            'number' => [
                'pattern' => 'At least one number',
                'met' => (bool)preg_match('/[0-9]/', $password)
            ],
            'special' => [
                'pattern' => 'At least one special character (!@#$%^&*)',
                'met' => (bool)preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)
            ]
        ]
    ];

    // Calculate strength score
    $score = 0;
    foreach ($response['requirements'] as $requirement) {
        if (isset($requirement['met']) && $requirement['met']) {
            $score++;
        }
    }

    $response['score'] = $score;

    // Determine strength level
    if ($score <= 2) {
        $response['strength'] = 'weak';
    } elseif ($score === 3) {
        $response['strength'] = 'fair';
    } elseif ($score === 4) {
        $response['strength'] = 'good';
    } else {
        $response['strength'] = 'strong';
    }

    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Password validation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'strength' => 'weak',
        'score' => 0,
        'requirements' => []
    ]);
}

exit();
