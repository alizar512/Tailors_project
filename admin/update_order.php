<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/notifications.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && $pdo) {
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN total_price DECIMAL(10,2)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN advance_payment_amount DECIMAL(10,2)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_status VARCHAR(30) DEFAULT 'Pending'");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN status VARCHAR(50) DEFAULT 'Order Placed'");
    } catch (Exception $e) {
    }

    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $statusRaw = isset($_POST['status']) ? trim((string)$_POST['status']) : '';
    $allowed = ['Order Placed', 'Under Review', 'Price Updated', 'Tailor Selected', 'In Progress', 'Completed'];
    $status = in_array($statusRaw, $allowed, true) ? $statusRaw : '';
    $total_price = filter_input(INPUT_POST, 'total_price', FILTER_VALIDATE_FLOAT);
    if ($total_price === false || $total_price === null) {
        $total_price = 0.0;
    }

    if (!$order_id || $order_id <= 0) {
        header("Location: orders.php");
        exit;
    }

    if ($status === '') {
        header("Location: order_details.php?id=" . (int)$order_id . "&success=0");
        exit;
    }
    
    // Calculate advance payment (30%)
    $advance_payment = $total_price * 0.3;

    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, total_price = ?, advance_payment_amount = ? WHERE id = ?");
        $stmt->execute([$status, $total_price, $advance_payment, $order_id]);
        
        // Add Notification
        silah_add_notification(
            $pdo,
            'admin',
            null,
            "Order Status Updated",
            "Order #SIL-" . str_pad($order_id, 4, '0', STR_PAD_LEFT) . " is now " . $status,
            "order",
            "order_details.php?id=" . $order_id
        );
        
        header("Location: order_details.php?id=$order_id&success=1");
        exit;
    } catch (PDOException $e) {
        $_SESSION['order_update_error'] = $e->getMessage();
        header("Location: order_details.php?id=" . (int)$order_id . "&success=0");
        exit;
    }
} else {
    header("Location: orders.php");
    exit;
}
?>
