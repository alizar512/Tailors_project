<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['app_id']) && isset($_POST['image_path'])) {
    $app_id = (int)$_POST['app_id'];
    $image_to_delete = $_POST['image_path'];

    if ($pdo) {
        try {
            // 1. Fetch current portfolio
            $stmt = $pdo->prepare("SELECT portfolio_link FROM tailor_applications WHERE id = ?");
            $stmt->execute([$app_id]);
            $app = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($app) {
                $images = json_decode($app['portfolio_link'], true) ?: [];
                
                // 2. Remove the specific image from array
                $new_images = array_filter($images, function($img) use ($image_to_delete) {
                    return $img !== $image_to_delete;
                });
                
                // 3. Update DB
                $new_portfolio = json_encode(array_values($new_images));
                $updateStmt = $pdo->prepare("UPDATE tailor_applications SET portfolio_link = ? WHERE id = ?");
                $updateStmt->execute([$new_portfolio, $app_id]);

                // 4. Delete physical file
                $full_path = "../" . $image_to_delete;
                if (file_exists($full_path)) {
                    unlink($full_path);
                }
                
                $_SESSION['success'] = "Portfolio image deleted.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error deleting portfolio image: " . $e->getMessage();
        }
    }
}

header("Location: application_details.php?id=" . $app_id);
exit();
