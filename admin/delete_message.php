<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['message_id'])) {
    $message_id = (int)$_POST['message_id'];

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
            $stmt->execute([$message_id]);
            $_SESSION['success'] = "Message deleted successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting message: " . $e->getMessage();
        }
    }
}

header("Location: messages.php");
exit();
