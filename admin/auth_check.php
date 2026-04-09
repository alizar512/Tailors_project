<?php
require_once __DIR__ . '/../includes/session_init.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
?>