<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM tailors WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        // Handle error
    }
}

header("Location: index.php");
exit;
?>