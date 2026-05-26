<?php
require_once __DIR__ . '/../includes/auth-check.php';

if (!check_role(['receptionist'])) {
    header("Location: " . BASE_URL . "/frontend/pages/public/login.html");
    exit;
}
?>
