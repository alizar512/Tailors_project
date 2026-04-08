<?php
// Determine session save path (portable fix for missing system tmp folders)
$session_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tmp';

if (!is_dir($session_path)) {
    mkdir($session_path, 0777, true);
}

session_save_path($session_path);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
