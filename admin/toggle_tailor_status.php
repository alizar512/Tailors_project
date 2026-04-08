<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';

if (!$pdo) {
    $_SESSION['error'] = 'Database connection failed.';
    header("Location: tailor_management.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['tailor_id']) || !isset($_POST['to'])) {
    header("Location: tailor_management.php");
    exit;
}

$tailor_id = (int)$_POST['tailor_id'];
$to = (int)$_POST['to'] === 1 ? 1 : 0;

try {
    $pdo->exec("ALTER TABLE tailors ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
} catch (Exception $e) {
}

try {
    $stmt = $pdo->prepare("UPDATE tailors SET is_active = ? WHERE id = ?");
    $stmt->execute([$to, $tailor_id]);
    $_SESSION['success'] = $to === 1 ? 'Tailor activated successfully.' : 'Tailor deactivated successfully.';
} catch (Exception $e) {
    $_SESSION['error'] = 'Could not update tailor status.';
}

header("Location: tailor_management.php");
exit;
