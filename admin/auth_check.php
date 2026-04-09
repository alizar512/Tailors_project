<?php
require_once __DIR__ . '/../includes/session_init.php';

if (!isset($_SESSION['admin_id'])) {
    // Session lost or never created
    session_write_close();
    header("Location: login.php?error=session_expired");
    exit;
}
?>