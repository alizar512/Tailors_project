<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

if (!$pdo) {
    header("Location: login.php?error=db_error");
    exit;
}

$customerId = (int)($_SESSION['customer_id'] ?? 0);
$customerEmail = isset($_SESSION['customer_email']) ? (string)$_SESSION['customer_email'] : '';

try {
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN customer_id INT NULL");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN order_number VARCHAR(20)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_status VARCHAR(30) DEFAULT 'Pending'");
    } catch (Exception $e) {
    }
} catch (Exception $e) {
}

$orders = [];
try {
    if ($customerId > 0) {
        $stmt = $pdo->prepare("SELECT id, order_number, status, created_at, service_type, budget, total_price, payment_status FROM orders WHERE customer_id = ? OR REPLACE(LOWER(TRIM(customer_email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '') ORDER BY created_at DESC, id DESC");
        $stmt->execute([$customerId, $customerEmail]);
    } else {
        $stmt = $pdo->prepare("SELECT id, order_number, status, created_at, service_type, budget, total_price, payment_status FROM orders WHERE REPLACE(LOWER(TRIM(customer_email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '') ORDER BY created_at DESC, id DESC");
        $stmt->execute([$customerEmail]);
    }
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Silah</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-5xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <img src="../images/logo1.png" alt="Silah Logo" class="w-10 h-10 object-contain mix-blend-multiply">
                <div>
                    <h1 class="text-xl font-black text-gray-900 mb-0">My Orders</h1>
                    <p class="text-xs font-bold text-gray-500 mb-0"><?= htmlspecialchars((string)$customerEmail) ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="../place_order.php#order" class="px-4 py-2 rounded-full bg-white border border-gray-200 text-xs font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all no-underline">New Order</a>
                <a href="logout.php" class="px-4 py-2 rounded-full bg-pink-600 text-white text-xs font-black uppercase tracking-widest hover:bg-pink-700 transition-all no-underline">Logout</a>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-sm font-black uppercase tracking-widest text-gray-400 mb-0">Order History</h2>
            </div>

            <?php if (empty($orders)): ?>
                <div class="p-10 text-center">
                    <div class="text-gray-300 text-4xl mb-3"><i class="fas fa-receipt"></i></div>
                    <p class="text-sm font-black text-gray-700 mb-1">No orders found</p>
                    <p class="text-xs font-bold text-gray-500 mb-4">Use the same email you used when placing an order.</p>
                    <a href="../place_order.php#order" class="inline-block px-6 py-3 rounded-full bg-pink-600 text-white text-xs font-black uppercase tracking-widest hover:bg-pink-700 transition-all no-underline">Place Order</a>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-50">
                    <?php foreach ($orders as $o): ?>
                        <?php
                            $id = isset($o['id']) ? (int)$o['id'] : 0;
                            $orderNumber = isset($o['order_number']) && trim((string)$o['order_number']) !== '' ? (string)$o['order_number'] : ('SIL-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT));
                            $status = isset($o['status']) ? (string)$o['status'] : 'Order Placed';
                            $createdAt = isset($o['created_at']) ? (string)$o['created_at'] : '';
                            $serviceType = isset($o['service_type']) ? (string)$o['service_type'] : '';
                            $paymentStatus = isset($o['payment_status']) && $o['payment_status'] ? (string)$o['payment_status'] : 'Pending';
                            $total = isset($o['total_price']) && $o['total_price'] !== null && $o['total_price'] !== '' ? (float)$o['total_price'] : (isset($o['budget']) ? (float)$o['budget'] : 0.0);
                        ?>
                        <div class="p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <p class="text-sm font-black text-gray-900 mb-1"><?= htmlspecialchars((string)$orderNumber) ?></p>
                                <p class="text-xs font-bold text-gray-600 mb-1"><?= htmlspecialchars((string)$serviceType) ?></p>
                                <div class="flex flex-wrap gap-2 items-center">
                                    <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-100 text-[10px] font-black uppercase tracking-widest text-gray-600"><?= htmlspecialchars((string)$status) ?></span>
                                    <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-100 text-[10px] font-black uppercase tracking-widest text-gray-600">Payment: <?= htmlspecialchars((string)$paymentStatus) ?></span>
                                    <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-100 text-[10px] font-black uppercase tracking-widest text-gray-600">PKR <?= number_format((float)$total) ?></span>
                                    <?php if ($createdAt !== ''): ?>
                                        <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-100 text-[10px] font-black uppercase tracking-widest text-gray-600"><?= htmlspecialchars((string)date('M d, Y', strtotime($createdAt))) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="order_details.php?id=<?= (int)$id ?>" class="px-5 py-2 rounded-full bg-white border border-gray-200 text-xs font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all no-underline">
                                    View Details
                                </a>
                                <a href="../order_chat.php?order_id=<?= (int)$id ?>" class="w-10 h-10 rounded-full bg-pink-600 text-white flex items-center justify-center hover:bg-pink-700 transition-all no-underline" aria-label="Open chat">
                                    <i class="fas fa-comments"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
