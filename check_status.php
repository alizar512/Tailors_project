<?php
require_once 'includes/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$emailRaw = isset($_GET['email']) ? trim((string)$_GET['email']) : '';
$email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL) ? $emailRaw : '';
$orderNumberRaw = isset($_GET['order_number']) ? trim((string)$_GET['order_number']) : '';
$orderIdRaw = isset($_GET['order_id']) ? trim((string)$_GET['order_id']) : '';

if ($email === '') {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

if (!$pdo) {
    echo json_encode([
        'success' => true,
        'status' => 'Under Review',
        'date' => date('M d, Y'),
        'demo' => true
    ]);
    exit;
}

try {
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN order_number VARCHAR(20)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN total_price DECIMAL(10,2)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_status VARCHAR(30) DEFAULT 'Pending'");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN chat_token VARCHAR(64)");
    } catch (Exception $e) {
    }
} catch (Exception $e) {
}

$orderId = 0;
if ($orderNumberRaw !== '') {
    $orderNumberRaw = strtoupper($orderNumberRaw);
    if (preg_match('/^SIL-\d+$/', $orderNumberRaw)) {
        $orderId = (int)substr($orderNumberRaw, 4);
    } elseif (is_numeric($orderNumberRaw)) {
        $orderId = (int)$orderNumberRaw;
    }
}
if ($orderId <= 0 && $orderIdRaw !== '' && is_numeric($orderIdRaw)) {
    $orderId = (int)$orderIdRaw;
}

try {
    if ($orderId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND REPLACE(LOWER(TRIM(customer_email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '') LIMIT 1");
        $stmt->execute([$orderId, $email]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE REPLACE(LOWER(TRIM(customer_email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '') ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email]);
    }
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'No order found with this email and order number.']);
        exit;
    }

    $id = isset($order['id']) ? (int)$order['id'] : 0;
    $orderNumber = isset($order['order_number']) && trim((string)$order['order_number']) !== ''
        ? trim((string)$order['order_number'])
        : ('SIL-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT));
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET order_number = ? WHERE id = ? AND (order_number IS NULL OR TRIM(order_number) = '')");
            $stmt->execute([$orderNumber, $id]);
        } catch (Exception $e) {
        }
    }

    $status = isset($order['status']) ? (string)$order['status'] : 'Order Placed';
    $createdAt = isset($order['created_at']) ? (string)$order['created_at'] : date('Y-m-d');
    $paymentStatus = isset($order['payment_status']) && $order['payment_status'] ? (string)$order['payment_status'] : 'Pending';
    $total = isset($order['total_price']) && $order['total_price'] !== null && $order['total_price'] !== '' ? (float)$order['total_price'] : (isset($order['budget']) ? (float)$order['budget'] : 0.0);

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Tailors%20project';
    $chatReopen = $baseUrl . '/order_chat.php?order_id=' . urlencode((string)$id) . '&email=' . urlencode($email);

    $cargoCompany = isset($order['cargo_company']) ? (string)$order['cargo_company'] : '';
    $cargoTrack = isset($order['cargo_tracking_number']) ? (string)$order['cargo_tracking_number'] : '';
    $cargoReceipt = isset($order['cargo_receipt_image']) ? (string)$order['cargo_receipt_image'] : '';

    echo json_encode([
        'success' => true,
        'order_id' => $id,
        'order_number' => $orderNumber,
        'status' => $status,
        'date' => date('M d, Y', strtotime($createdAt)),
        'payment_status' => $paymentStatus,
        'total_price' => $total,
        'advance_required' => $total * 0.3,
        'chat_url' => $chatReopen,
        'cargo_company' => $cargoCompany,
        'cargo_tracking_number' => $cargoTrack,
        'cargo_receipt_image' => $cargoReceipt
    ]);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error checking status.']);
    exit;
}
?>
