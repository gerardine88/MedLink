<?php
/**
 * Database Configuration
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'medlink');
define('DB_PORT', 3306);

$conn = null;
$db_error = null;

try {

    mysqli_report(MYSQLI_REPORT_OFF);

    $conn = new mysqli(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        DB_PORT
    );

    if ($conn->connect_error) {

        $db_error =
            "Database connection failed: " .
            $conn->connect_error;

        $conn = null;

        return;
    }

    if (!$conn->set_charset("utf8mb4")) {

        $db_error =
            "Database charset setup failed: " .
            $conn->error;

        $conn->close();
        $conn = null;

        return;
    }

} catch (Exception $e) {

    $db_error =
        "Database connection failed: " .
        $e->getMessage();

    $conn = null;
}
?>
