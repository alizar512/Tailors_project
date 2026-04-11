<?php
require_once __DIR__ . '/../includes/session_init.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php?error=session_expired");
    exit;
}
?>
