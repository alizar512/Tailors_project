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
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img src="../images/logo1.png" alt="Silah Logo" class="w-10 h-10 object-contain mix-blend-multiply">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Customer Portal</p>
                        <h1 class="text-xl font-black text-gray-900 mb-0">My Orders</h1>
                        <p class="text-xs font-bold text-gray-500 mb-0"><?= htmlspecialchars((string)$customerEmail) ?></p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="../place_order.php#order" class="px-4 py-2 rounded-full bg-white border border-gray-200 text-xs font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all no-underline">
                        <i class="fas fa-plus me-1"></i> New Order
                    </a>
                    <a href="logout.php" class="px-4 py-2 rounded-full bg-pink-600 text-white text-xs font-black uppercase tracking-widest hover:bg-pink-700 transition-all no-underline">
                        <i class="fas fa-arrow-right-from-bracket me-1"></i> Logout
                    </a>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Total Orders</p>
                    <p class="text-lg font-black text-gray-900 mb-0"><?= (int)count($orders) ?></p>
                </div>
                <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50 sm:col-span-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="relative">
                            <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                            <input id="orderSearch" type="text" class="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 focus:outline-none focus:ring-4 focus:ring-pink-100 focus:border-pink-400" placeholder="Search by order no, status, service...">
                        </div>
                        <select id="statusFilter" class="w-full px-4 py-3 rounded-2xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 focus:outline-none focus:ring-4 focus:ring-pink-100 focus:border-pink-400">
                            <option value="">All Status</option>
                            <option value="Order Placed">Order Placed</option>
                            <option value="Under Review">Under Review</option>
                            <option value="Tailor Selected">Tailor Selected</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <p class="text-[11px] font-bold text-gray-500 mt-3 mb-0">
                        Showing <span id="visibleCount">0</span> of <?= (int)count($orders) ?> orders
                    </p>
                </div>
            </div>
        </div>

            <?php if (empty($orders)): ?>
                <div class="p-10 text-center">
                    <div class="text-gray-300 text-4xl mb-3"><i class="fas fa-receipt"></i></div>
                    <p class="text-sm font-black text-gray-700 mb-1">No orders found</p>
                    <p class="text-xs font-bold text-gray-500 mb-4">Use the same email you used when placing an order.</p>
                    <a href="../place_order.php#order" class="inline-block px-6 py-3 rounded-full bg-pink-600 text-white text-xs font-black uppercase tracking-widest hover:bg-pink-700 transition-all no-underline">Place Order</a>
                </div>
            <?php else: ?>
                <div id="ordersGrid" class="grid grid-cols-1 gap-4">
                    <?php foreach ($orders as $o): ?>
                        <?php
                            $id = isset($o['id']) ? (int)$o['id'] : 0;
                            $orderNumber = isset($o['order_number']) && trim((string)$o['order_number']) !== '' ? (string)$o['order_number'] : ('SIL-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT));
                            $status = isset($o['status']) ? (string)$o['status'] : 'Order Placed';
                            $createdAt = isset($o['created_at']) ? (string)$o['created_at'] : '';
                            $serviceType = isset($o['service_type']) ? (string)$o['service_type'] : '';
                            $paymentStatus = isset($o['payment_status']) && $o['payment_status'] ? (string)$o['payment_status'] : 'Pending';
                            $total = isset($o['total_price']) && $o['total_price'] !== null && $o['total_price'] !== '' ? (float)$o['total_price'] : (isset($o['budget']) ? (float)$o['budget'] : 0.0);

                            $statusColors = [
                                'Order Placed' => 'bg-slate-50 text-slate-700 border-slate-200',
                                'Under Review' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'Tailor Selected' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'In Progress' => 'bg-pink-50 text-pink-700 border-pink-200',
                                'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            ];
                            $statusCls = isset($statusColors[$status]) ? $statusColors[$status] : 'bg-gray-50 text-gray-700 border-gray-200';
                        ?>
                        <div class="orderCard bg-white rounded-3xl shadow-sm border border-gray-100 p-5 hover:border-pink-200 hover:shadow-md transition-all"
                             data-order="<?= htmlspecialchars((string)strtolower($orderNumber . ' ' . $status . ' ' . $serviceType . ' ' . $paymentStatus)) ?>"
                             data-status="<?= htmlspecialchars((string)$status) ?>">
                            <div>
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black text-gray-900 mb-1"><?= htmlspecialchars((string)$orderNumber) ?></p>
                                        <p class="text-xs font-bold text-gray-600 mb-0"><?= htmlspecialchars((string)$serviceType) ?></p>
                                    </div>
                                    <p class="text-sm font-black text-pink-600 mb-0">PKR <?= number_format((float)$total) ?></p>
                                </div>

                                <div class="flex flex-wrap gap-2 items-center mt-3">
                                    <span class="px-3 py-1 rounded-full border text-[10px] font-black uppercase tracking-widest <?= $statusCls ?>"><?= htmlspecialchars((string)$status) ?></span>
                                    <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-100 text-[10px] font-black uppercase tracking-widest text-gray-600">Payment: <?= htmlspecialchars((string)$paymentStatus) ?></span>
                                    <?php if ($createdAt !== ''): ?>
                                        <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-100 text-[10px] font-black uppercase tracking-widest text-gray-600"><?= htmlspecialchars((string)date('M d, Y', strtotime($createdAt))) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 mt-4">
                                <a href="order_details.php?id=<?= (int)$id ?>" class="px-5 py-2 rounded-full bg-white border border-gray-200 text-xs font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all no-underline">
                                    <i class="fas fa-receipt me-1"></i> Details
                                </a>
                                <a href="../order_chat.php?order_id=<?= (int)$id ?>" class="px-5 py-2 rounded-full bg-pink-600 text-white text-xs font-black uppercase tracking-widest hover:bg-pink-700 transition-all no-underline">
                                    <i class="fas fa-comments me-1"></i> Chat
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
    </div>

    <script>
        (function() {
            const search = document.getElementById('orderSearch');
            const filter = document.getElementById('statusFilter');
            const cards = Array.from(document.querySelectorAll('.orderCard'));
            const visibleCount = document.getElementById('visibleCount');

            const apply = () => {
                const q = (search ? search.value : '').trim().toLowerCase();
                const st = (filter ? filter.value : '').trim();
                let shown = 0;

                cards.forEach(card => {
                    const hay = (card.getAttribute('data-order') || '').toLowerCase();
                    const cst = card.getAttribute('data-status') || '';
                    const okQ = q === '' || hay.includes(q);
                    const okS = st === '' || cst === st;
                    const show = okQ && okS;
                    card.classList.toggle('hidden', !show);
                    if (show) shown += 1;
                });

                if (visibleCount) visibleCount.textContent = String(shown);
            };

            if (search) search.addEventListener('input', apply);
            if (filter) filter.addEventListener('change', apply);
            apply();
        })();
    </script>
</body>
</html>
