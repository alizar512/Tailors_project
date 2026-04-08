<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id']) && isset($_POST['image_path'])) {
    $order_id = (int)$_POST['order_id'];
    $image_path = $_POST['image_path'];

    if ($pdo) {
        try {
            // 1. Delete the physical file if it exists
            $full_path = "../" . $image_path;
            if (file_exists($full_path)) {
                unlink($full_path);
            }

            // 2. Update the order record to clear the reference_image column
            $stmt = $pdo->prepare("UPDATE orders SET reference_image = NULL WHERE id = ?");
            $stmt->execute([$order_id]);
            
            $_SESSION['success'] = "Order media deleted successfully.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error deleting order media: " . $e->getMessage();
        }
    }
}

header("Location: order_details.php?id=" . $order_id);
exit();
