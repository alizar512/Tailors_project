<?php
$cp_title = 'Dashboard';
$cp_active = 'dashboard';
require_once __DIR__ . '/portal_header.php';

if (!$pdo) {
    echo '<div class="bg-white rounded-3xl border border-gray-100 p-6"><p class="text-sm font-black text-red-600 mb-0">Database connection failed.</p></div>';
    require_once __DIR__ . '/portal_footer.php';
    exit;
}

$customerId = (int)($_SESSION['customer_id'] ?? 0);
$customerEmail = isset($_SESSION['customer_email']) ? (string)$_SESSION['customer_email'] : '';

try { $pdo->exec("ALTER TABLE orders ADD COLUMN customer_id INT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN order_number VARCHAR(20)"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN payment_status VARCHAR(30) DEFAULT 'Pending'"); } catch (Exception $e) {}
try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS order_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            sender_type VARCHAR(20) NOT NULL,
            sender_name VARCHAR(100),
            sender_email VARCHAR(120),
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_order_messages_order_id (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (Exception $e) {}

$totalOrders = 0;
$activeOrders = 0;
$latestOrders = [];
$latestMessages = [];

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ? OR REPLACE(LOWER(TRIM(customer_email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '')");
    $stmt->execute([$customerId, $customerEmail]);
    $totalOrders = (int)$stmt->fetchColumn();
} catch (Exception $e) {
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE (customer_id = ? OR REPLACE(LOWER(TRIM(customer_email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '')) AND (status IS NULL OR status <> 'Completed')");
    $stmt->execute([$customerId, $customerEmail]);
    $activeOrders = (int)$stmt->fetchColumn();
} catch (Exception $e) {
}

try {
    $stmt = $pdo->prepare("SELECT id, order_number, status, created_at, service_type, payment_status, total_price, budget FROM orders WHERE customer_id = ? OR REPLACE(LOWER(TRIM(customer_email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '') ORDER BY created_at DESC, id DESC LIMIT 5");
    $stmt->execute([$customerId, $customerEmail]);
    $latestOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $latestOrders = [];
}

try {
    $stmt = $pdo->prepare(
        "SELECT om.order_id, om.sender_type, om.sender_name, om.message, om.created_at, o.order_number
         FROM order_messages om
         JOIN orders o ON o.id = om.order_id
         WHERE o.customer_id = ? OR REPLACE(LOWER(TRIM(o.customer_email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '')
         ORDER BY om.created_at DESC, om.id DESC
         LIMIT 8"
    );
    $stmt->execute([$customerId, $customerEmail]);
    $latestMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $latestMessages = [];
}
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-3xl border border-gray-100 p-6">
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Total Orders</p>
                <div class="flex items-center justify-between gap-3">
                    <p class="text-3xl font-black text-gray-900 mb-0"><?= (int)$totalOrders ?></p>
                    <div class="w-12 h-12 rounded-2xl bg-pink-50 text-pink-600 flex items-center justify-center">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-3xl border border-gray-100 p-6">
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Active Orders</p>
                <div class="flex items-center justify-between gap-3">
                    <p class="text-3xl font-black text-gray-900 mb-0"><?= (int)$activeOrders ?></p>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i class="fa-solid fa-spinner"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between gap-3">
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-0">Recent Orders</p>
                <a class="text-xs font-black uppercase tracking-widest text-pink-600 no-underline" href="orders.php">View all</a>
            </div>
            <?php if (empty($latestOrders)): ?>
                <div class="p-6">
                    <p class="text-sm font-bold text-gray-500 mb-0">No orders found.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-50">
                    <?php foreach ($latestOrders as $o): ?>
                        <?php
                            $id = isset($o['id']) ? (int)$o['id'] : 0;
                            $orderNumber = isset($o['order_number']) && trim((string)$o['order_number']) !== '' ? (string)$o['order_number'] : ('SIL-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT));
                            $status = isset($o['status']) && trim((string)$o['status']) !== '' ? (string)$o['status'] : 'Order Placed';
                            $serviceType = isset($o['service_type']) ? (string)$o['service_type'] : '';
                            $paymentStatus = isset($o['payment_status']) && $o['payment_status'] ? (string)$o['payment_status'] : 'Pending';
                            $createdAt = isset($o['created_at']) ? (string)$o['created_at'] : '';
                            $total = isset($o['total_price']) && $o['total_price'] !== null && $o['total_price'] !== '' ? (float)$o['total_price'] : (isset($o['budget']) ? (float)$o['budget'] : 0.0);
                        ?>
                        <a href="order_details.php?id=<?= (int)$id ?>" class="block p-5 hover:bg-gray-50 transition-all no-underline">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-gray-900 mb-1"><?= htmlspecialchars((string)$orderNumber) ?></p>
                                    <p class="text-xs font-bold text-gray-600 mb-0"><?= htmlspecialchars((string)$serviceType) ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-black text-pink-600 mb-1">PKR <?= number_format((float)$total) ?></p>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-0"><?= htmlspecialchars((string)$paymentStatus) ?> • <?= htmlspecialchars((string)$status) ?></p>
                                </div>
                            </div>
                            <?php if ($createdAt !== ''): ?>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mt-3 mb-0"><?= htmlspecialchars((string)date('M d, Y', strtotime($createdAt))) ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between gap-3">
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-0">Latest Messages</p>
                <a class="text-xs font-black uppercase tracking-widest text-pink-600 no-underline" href="messages.php">View all</a>
            </div>
            <?php if (empty($latestMessages)): ?>
                <div class="p-6">
                    <p class="text-sm font-bold text-gray-500 mb-0">No messages yet.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-50">
                    <?php foreach ($latestMessages as $m): ?>
                        <?php
                            $oid = isset($m['order_id']) ? (int)$m['order_id'] : 0;
                            $orderNumber = isset($m['order_number']) && trim((string)$m['order_number']) !== '' ? (string)$m['order_number'] : ('SIL-' . str_pad((string)$oid, 4, '0', STR_PAD_LEFT));
                            $sender = isset($m['sender_name']) && trim((string)$m['sender_name']) !== '' ? (string)$m['sender_name'] : (isset($m['sender_type']) ? (string)$m['sender_type'] : 'user');
                            $msg = isset($m['message']) ? (string)$m['message'] : '';
                            $dt = isset($m['created_at']) ? (string)$m['created_at'] : '';
                        ?>
                        <a href="order_details.php?id=<?= (int)$oid ?>" class="block p-5 hover:bg-gray-50 transition-all no-underline">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1"><?= htmlspecialchars((string)$orderNumber) ?></p>
                                    <p class="text-xs font-black text-gray-900 mb-1"><?= htmlspecialchars((string)$sender) ?></p>
                                    <p class="text-xs font-bold text-gray-600 mb-0 truncate"><?= htmlspecialchars((string)$msg) ?></p>
                                </div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-0 whitespace-nowrap"><?= $dt !== '' ? htmlspecialchars((string)date('M d', strtotime($dt))) : '' ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/portal_footer.php'; ?>

