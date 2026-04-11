<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

if (!$pdo) {
    header("Location: login.php?error=db_error");
    exit;
}

$orderId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
    header("Location: orders.php");
    exit;
}

$customerId = (int)($_SESSION['customer_id'] ?? 0);
$customerEmail = isset($_SESSION['customer_email']) ? (string)$_SESSION['customer_email'] : '';

try {
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN customer_id INT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN order_number VARCHAR(20)"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN total_price DECIMAL(10,2)"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN payment_status VARCHAR(30) DEFAULT 'Pending'"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN payment_proof_image VARCHAR(255)"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN payment_submitted_at TIMESTAMP NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN payment_confirmed_at TIMESTAMP NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN balance_payment_status VARCHAR(30) DEFAULT 'Pending'"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN balance_payment_proof_image VARCHAR(255)"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN balance_payment_submitted_at TIMESTAMP NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN balance_payment_confirmed_at TIMESTAMP NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN cargo_company VARCHAR(120)"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN cargo_tracking_number VARCHAR(120)"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN cargo_receipt_image VARCHAR(255)"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN shipped_at TIMESTAMP NULL"); } catch (Exception $e) {}
} catch (Exception $e) {
}

$order = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $order = null;
}

if (!$order) {
    header("Location: orders.php");
    exit;
}

$ok = false;
$rowCustomerId = isset($order['customer_id']) ? (int)$order['customer_id'] : 0;
if ($rowCustomerId > 0 && $customerId > 0) {
    $ok = $rowCustomerId === $customerId;
} else {
    $dbEmail = isset($order['customer_email']) ? (string)$order['customer_email'] : '';
    $normDb = str_replace(' ', '', strtolower(trim($dbEmail)));
    $normMe = str_replace(' ', '', strtolower(trim($customerEmail)));
    $ok = $normDb !== '' && $normDb === $normMe;
}
if (!$ok) {
    header("Location: orders.php");
    exit;
}

$id = isset($order['id']) ? (int)$order['id'] : 0;
$orderNumber = isset($order['order_number']) && trim((string)$order['order_number']) !== '' ? (string)$order['order_number'] : ('SIL-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT));
$status = isset($order['status']) && trim((string)$order['status']) !== '' ? (string)$order['status'] : 'Order Placed';
$createdAt = isset($order['created_at']) ? (string)$order['created_at'] : '';
$serviceType = isset($order['service_type']) ? (string)$order['service_type'] : '';
$paymentStatus = isset($order['payment_status']) && $order['payment_status'] ? (string)$order['payment_status'] : 'Pending';
$balanceStatus = isset($order['balance_payment_status']) && $order['balance_payment_status'] ? (string)$order['balance_payment_status'] : '';
$total = isset($order['total_price']) && $order['total_price'] !== null && $order['total_price'] !== '' ? (float)$order['total_price'] : (isset($order['budget']) ? (float)$order['budget'] : 0.0);
$advanceRequired = $total * 0.3;
$paymentProof = isset($order['payment_proof_image']) ? (string)$order['payment_proof_image'] : '';
$paymentSubmittedAt = isset($order['payment_submitted_at']) ? (string)$order['payment_submitted_at'] : '';
$paymentConfirmedAt = isset($order['payment_confirmed_at']) ? (string)$order['payment_confirmed_at'] : '';
$balanceProof = isset($order['balance_payment_proof_image']) ? (string)$order['balance_payment_proof_image'] : '';
$balanceSubmittedAt = isset($order['balance_payment_submitted_at']) ? (string)$order['balance_payment_submitted_at'] : '';
$balanceConfirmedAt = isset($order['balance_payment_confirmed_at']) ? (string)$order['balance_payment_confirmed_at'] : '';
$cargoCompany = isset($order['cargo_company']) ? (string)$order['cargo_company'] : '';
$cargoTrack = isset($order['cargo_tracking_number']) ? (string)$order['cargo_tracking_number'] : '';
$cargoReceipt = isset($order['cargo_receipt_image']) ? (string)$order['cargo_receipt_image'] : '';
$shippedAt = isset($order['shipped_at']) ? (string)$order['shipped_at'] : '';
$notes = isset($order['notes']) ? (string)$order['notes'] : '';
$measurements = isset($order['measurements']) ? (string)$order['measurements'] : '';

$messages = [];
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
    $stmt = $pdo->prepare("SELECT sender_type, sender_name, message, created_at FROM order_messages WHERE order_id = ? ORDER BY created_at DESC, id DESC LIMIT 50");
    $stmt->execute([$id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $messages = [];
}

$steps = ['Order Placed', 'Under Review', 'Tailor Selected', 'In Progress', 'Completed'];
$currentIndex = array_search($status, $steps, true);
if ($currentIndex === false) {
    $currentIndex = 0;
}

function silah_fmt_dt($dt) {
    if (!$dt) return '';
    $ts = strtotime((string)$dt);
    if (!$ts) return '';
    return date('M d, Y h:i A', $ts);
}
?>
<?php
$cp_title = 'Order Details';
$cp_active = 'orders';
require_once __DIR__ . '/portal_header.php';
?>
        <?php
            $statusColors = [
                'Order Placed' => 'bg-slate-50 text-slate-700 border-slate-200',
                'Under Review' => 'bg-amber-50 text-amber-700 border-amber-200',
                'Tailor Selected' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'In Progress' => 'bg-pink-50 text-pink-700 border-pink-200',
                'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ];
            $statusCls = isset($statusColors[$status]) ? $statusColors[$status] : 'bg-gray-50 text-gray-700 border-gray-200';
        ?>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img src="../images/logo1.png" alt="Silah Logo" class="w-10 h-10 object-contain mix-blend-multiply">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Order Details</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl font-black text-gray-900 mb-0"><?= htmlspecialchars((string)$orderNumber) ?></h1>
                            <button type="button" id="copyOrderNo" class="px-3 py-1 rounded-full bg-white border border-gray-200 text-[10px] font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all">
                                <i class="fa-regular fa-copy me-1"></i> Copy
                            </button>
                            <span class="px-3 py-1 rounded-full border text-[10px] font-black uppercase tracking-widest <?= $statusCls ?>"><?= htmlspecialchars((string)$status) ?></span>
                        </div>
                        <p class="text-xs font-bold text-gray-500 mb-0"><?= htmlspecialchars((string)$customerEmail) ?></p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="orders.php" class="px-4 py-2 rounded-full bg-white border border-gray-200 text-xs font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all no-underline">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                    <a href="chat.php?order_id=<?= (int)$id ?>" class="px-4 py-2 rounded-full bg-pink-600 text-white text-xs font-black uppercase tracking-widest hover:bg-pink-700 transition-all no-underline">
                        <i class="fas fa-comments me-1"></i> Chat
                    </a>
                    <button type="button" id="printOrder" class="px-4 py-2 rounded-full bg-white border border-gray-200 text-xs font-black uppercase tracking-widest text-gray-700 hover:border-gray-300 transition-all">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mt-5">
                <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Total</p>
                    <p class="text-base font-black text-gray-900 mb-0">PKR <?= number_format((float)$total) ?></p>
                </div>
                <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Advance (30%)</p>
                    <p class="text-base font-black text-gray-900 mb-0">PKR <?= number_format((float)$advanceRequired) ?></p>
                </div>
                <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Payment</p>
                    <p class="text-base font-black text-gray-900 mb-0"><?= htmlspecialchars((string)$paymentStatus) ?></p>
                </div>
                <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Placed</p>
                    <p class="text-base font-black text-gray-900 mb-0"><?= htmlspecialchars((string)($createdAt !== '' ? date('M d, Y', strtotime($createdAt)) : '-')) ?></p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 mt-5">
                <button type="button" class="tabBtn px-4 py-2 rounded-full bg-pink-600 text-white text-[11px] font-black uppercase tracking-widest" data-tab="tabOverview">Overview</button>
                <button type="button" class="tabBtn px-4 py-2 rounded-full bg-white border border-gray-200 text-[11px] font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all" data-tab="tabTracking">Tracking</button>
                <button type="button" class="tabBtn px-4 py-2 rounded-full bg-white border border-gray-200 text-[11px] font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all" data-tab="tabPayments">Payments</button>
                <button type="button" class="tabBtn px-4 py-2 rounded-full bg-white border border-gray-200 text-[11px] font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all" data-tab="tabShipment">Shipment</button>
                <button type="button" class="tabBtn px-4 py-2 rounded-full bg-white border border-gray-200 text-[11px] font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all" data-tab="tabHistory">History</button>
            </div>
        </div>

        <div id="tabOverview" class="tabPanel grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-4">Summary</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Service</p>
                            <p class="text-sm font-black text-gray-900 mb-0"><?= htmlspecialchars((string)$serviceType) ?></p>
                        </div>
                        <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Order Status</p>
                            <p class="text-sm font-black text-gray-900 mb-0"><?= htmlspecialchars((string)$status) ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                            <?php $pct = (int)round((($currentIndex + 1) / max(1, count($steps))) * 100); ?>
                            <div class="h-2 bg-pink-600" style="width: <?= (int)$pct ?>%"></div>
                        </div>
                        <div class="flex justify-between mt-2">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-0">Progress</p>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-0"><?= (int)$pct ?>%</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-4">Quick Actions</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <a href="chat.php?order_id=<?= (int)$id ?>" class="p-4 rounded-3xl border border-pink-100 bg-pink-50 hover:bg-pink-100 transition-all no-underline">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-pink-600 text-white flex items-center justify-center"><i class="fas fa-comments"></i></div>
                                <div>
                                    <p class="text-xs font-black text-gray-900 mb-1">Open Chat</p>
                                    <p class="text-[11px] font-bold text-gray-600 mb-0">Talk with the tailor/admin</p>
                                </div>
                            </div>
                        </a>
                        <a href="../place_order.php#order" class="p-4 rounded-3xl border border-gray-100 bg-gray-50 hover:bg-gray-100 transition-all no-underline">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-gray-900 text-white flex items-center justify-center"><i class="fas fa-plus"></i></div>
                                <div>
                                    <p class="text-xs font-black text-gray-900 mb-1">Place New Order</p>
                                    <p class="text-[11px] font-bold text-gray-600 mb-0">Create another request</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <?php if (trim($notes) !== '' || trim($measurements) !== ''): ?>
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-4">Order Info</p>
                        <?php if (trim($notes) !== ''): ?>
                            <details class="group rounded-3xl border border-gray-100 bg-gray-50 p-4">
                                <summary class="cursor-pointer list-none flex items-center justify-between gap-3">
                                    <span class="text-xs font-black text-gray-900">Notes</span>
                                    <span class="text-gray-400 group-open:rotate-180 transition-transform"><i class="fas fa-chevron-down"></i></span>
                                </summary>
                                <div class="mt-3">
                                    <p class="text-sm font-medium text-gray-700 mb-0 whitespace-pre-wrap"><?= htmlspecialchars((string)$notes) ?></p>
                                </div>
                            </details>
                        <?php endif; ?>
                        <?php if (trim($measurements) !== ''): ?>
                            <details class="group rounded-3xl border border-gray-100 bg-gray-50 p-4 mt-3">
                                <summary class="cursor-pointer list-none flex items-center justify-between gap-3">
                                    <span class="text-xs font-black text-gray-900">Measurements</span>
                                    <span class="text-gray-400 group-open:rotate-180 transition-transform"><i class="fas fa-chevron-down"></i></span>
                                </summary>
                                <div class="mt-3">
                                    <p class="text-sm font-medium text-gray-700 mb-0 whitespace-pre-wrap"><?= htmlspecialchars((string)$measurements) ?></p>
                                </div>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="space-y-4">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-4">Shipment</p>
                    <?php if (trim($cargoCompany) === '' && trim($cargoTrack) === '' && trim($cargoReceipt) === ''): ?>
                        <p class="text-sm font-bold text-gray-500 mb-0">Not shipped yet.</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php if (trim($cargoCompany) !== ''): ?>
                                <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Cargo Name</p>
                                    <p class="text-sm font-black text-gray-900 mb-0"><?= htmlspecialchars((string)$cargoCompany) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (trim($cargoTrack) !== ''): ?>
                                <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Tracking Number</p>
                                    <p class="text-sm font-black text-gray-900 mb-0"><?= htmlspecialchars((string)$cargoTrack) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($shippedAt !== ''): ?>
                                <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Shipped</p>
                                    <p class="text-sm font-black text-gray-900 mb-0"><?= htmlspecialchars((string)silah_fmt_dt($shippedAt)) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (trim($cargoReceipt) !== ''): ?>
                                <a class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-gray-200 text-xs font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all no-underline" href="../<?= htmlspecialchars((string)$cargoReceipt) ?>" target="_blank">
                                    <i class="fas fa-file-image"></i> View Receipt
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="tabTracking" class="tabPanel hidden">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-4">Tracking</p>
                <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                    <?php foreach ($steps as $i => $label): ?>
                        <div class="p-3 rounded-2xl border <?= $i <= $currentIndex ? 'border-pink-200 bg-pink-50' : 'border-gray-100 bg-gray-50' ?>">
                            <p class="text-[10px] font-black uppercase tracking-widest mb-1 <?= $i <= $currentIndex ? 'text-pink-600' : 'text-gray-400' ?>">
                                Step <?= (int)($i + 1) ?>
                            </p>
                            <p class="text-[11px] font-black mb-0 <?= $i <= $currentIndex ? 'text-gray-900' : 'text-gray-600' ?>">
                                <?= htmlspecialchars((string)$label) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-5 p-4 rounded-3xl border border-gray-100 bg-gray-50">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Current</p>
                    <p class="text-sm font-black text-gray-900 mb-0"><?= htmlspecialchars((string)$status) ?></p>
                </div>
            </div>
        </div>

        <div id="tabPayments" class="tabPanel hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-4">Advance (30%)</p>
                    <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-black text-gray-900 mb-0"><?= htmlspecialchars((string)$paymentStatus) ?></p>
                            <p class="text-sm font-black text-pink-600 mb-0">PKR <?= number_format((float)$advanceRequired) ?></p>
                        </div>
                        <div class="mt-3 space-y-1">
                            <?php if ($paymentSubmittedAt !== ''): ?>
                                <p class="text-[11px] font-bold text-gray-500 mb-0">Submitted: <?= htmlspecialchars((string)silah_fmt_dt($paymentSubmittedAt)) ?></p>
                            <?php endif; ?>
                            <?php if ($paymentConfirmedAt !== ''): ?>
                                <p class="text-[11px] font-bold text-gray-500 mb-0">Confirmed: <?= htmlspecialchars((string)silah_fmt_dt($paymentConfirmedAt)) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4">
                            <?php if ($paymentProof !== ''): ?>
                                <a class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-gray-200 text-xs font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all no-underline" href="../<?= htmlspecialchars((string)$paymentProof) ?>" target="_blank">
                                    <i class="fas fa-file-image"></i> View Proof
                                </a>
                            <?php else: ?>
                                <p class="text-xs font-bold text-gray-500 mb-0">No proof uploaded yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-4">Balance</p>
                    <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-black text-gray-900 mb-0"><?= htmlspecialchars((string)($balanceStatus !== '' ? $balanceStatus : 'Pending')) ?></p>
                            <p class="text-sm font-black text-gray-900 mb-0">PKR <?= number_format((float)max(0, $total - $advanceRequired)) ?></p>
                        </div>
                        <div class="mt-3 space-y-1">
                            <?php if ($balanceSubmittedAt !== ''): ?>
                                <p class="text-[11px] font-bold text-gray-500 mb-0">Submitted: <?= htmlspecialchars((string)silah_fmt_dt($balanceSubmittedAt)) ?></p>
                            <?php endif; ?>
                            <?php if ($balanceConfirmedAt !== ''): ?>
                                <p class="text-[11px] font-bold text-gray-500 mb-0">Confirmed: <?= htmlspecialchars((string)silah_fmt_dt($balanceConfirmedAt)) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4">
                            <?php if ($balanceProof !== ''): ?>
                                <a class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-gray-200 text-xs font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all no-underline" href="../<?= htmlspecialchars((string)$balanceProof) ?>" target="_blank">
                                    <i class="fas fa-file-image"></i> View Proof
                                </a>
                            <?php else: ?>
                                <p class="text-xs font-bold text-gray-500 mb-0">No proof uploaded yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="tabShipment" class="tabPanel hidden">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-4">Shipment</p>
                <?php if (trim($cargoCompany) === '' && trim($cargoTrack) === '' && trim($cargoReceipt) === ''): ?>
                    <div class="p-5 rounded-3xl border border-gray-100 bg-gray-50">
                        <p class="text-sm font-black text-gray-900 mb-1">Not shipped yet</p>
                        <p class="text-xs font-bold text-gray-500 mb-0">You’ll see tracking details here once shipped.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Cargo Name</p>
                            <p class="text-sm font-black text-gray-900 mb-0"><?= htmlspecialchars((string)($cargoCompany !== '' ? $cargoCompany : '-')) ?></p>
                        </div>
                        <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Tracking Number</p>
                            <p class="text-sm font-black text-gray-900 mb-0"><?= htmlspecialchars((string)($cargoTrack !== '' ? $cargoTrack : '-')) ?></p>
                        </div>
                        <?php if ($shippedAt !== ''): ?>
                            <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50 sm:col-span-2">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Shipped</p>
                                <p class="text-sm font-black text-gray-900 mb-0"><?= htmlspecialchars((string)silah_fmt_dt($shippedAt)) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (trim($cargoReceipt) !== ''): ?>
                        <div class="mt-4">
                            <a class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-gray-200 text-xs font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all no-underline" href="../<?= htmlspecialchars((string)$cargoReceipt) ?>" target="_blank">
                                <i class="fas fa-file-image"></i> View Receipt
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div id="tabHistory" class="tabPanel hidden">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-0">History</p>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-0"><?= (int)count($messages) ?> messages</p>
                </div>
                <?php if (empty($messages)): ?>
                    <p class="text-sm font-bold text-gray-500 mb-0">No history yet.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($messages as $m): ?>
                            <?php
                                $senderType = isset($m['sender_type']) ? (string)$m['sender_type'] : '';
                                $senderName = isset($m['sender_name']) && trim((string)$m['sender_name']) !== '' ? (string)$m['sender_name'] : ucfirst($senderType ?: 'user');
                                $msg = isset($m['message']) ? (string)$m['message'] : '';
                                $dt = isset($m['created_at']) ? (string)$m['created_at'] : '';
                                $typeCls = 'bg-gray-900';
                                if ($senderType === 'customer') $typeCls = 'bg-pink-600';
                                if ($senderType === 'tailor') $typeCls = 'bg-indigo-600';
                                if ($senderType === 'admin') $typeCls = 'bg-emerald-600';
                            ?>
                            <div class="p-4 rounded-3xl border border-gray-100 bg-gray-50">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full <?= $typeCls ?>"></span>
                                        <p class="text-xs font-black text-gray-800 mb-0"><?= htmlspecialchars((string)$senderName) ?></p>
                                    </div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-0"><?= htmlspecialchars((string)silah_fmt_dt($dt)) ?></p>
                                </div>
                                <p class="text-sm font-medium text-gray-700 mb-0 whitespace-pre-wrap"><?= htmlspecialchars((string)$msg) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <script>
        (function() {
            const orderNo = <?= json_encode((string)$orderNumber) ?>;
            const copyBtn = document.getElementById('copyOrderNo');
            const printBtn = document.getElementById('printOrder');
            const tabs = Array.from(document.querySelectorAll('.tabBtn'));
            const panels = Array.from(document.querySelectorAll('.tabPanel'));

            const setTab = (id) => {
                panels.forEach(p => p.classList.add('hidden'));
                const active = document.getElementById(id);
                if (active) active.classList.remove('hidden');
                tabs.forEach(b => {
                    const isActive = b.getAttribute('data-tab') === id;
                    b.classList.toggle('bg-pink-600', isActive);
                    b.classList.toggle('text-white', isActive);
                    b.classList.toggle('bg-white', !isActive);
                    b.classList.toggle('border', !isActive);
                    b.classList.toggle('border-gray-200', !isActive);
                    b.classList.toggle('text-gray-700', !isActive);
                });
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            tabs.forEach(btn => {
                btn.addEventListener('click', () => setTab(btn.getAttribute('data-tab')));
            });

            if (copyBtn) {
                copyBtn.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(orderNo);
                        copyBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Copied';
                        setTimeout(() => { copyBtn.innerHTML = '<i class="fa-regular fa-copy me-1"></i> Copy'; }, 1200);
                    } catch (e) {
                        copyBtn.innerHTML = '<i class="fa-solid fa-xmark me-1"></i> Failed';
                        setTimeout(() => { copyBtn.innerHTML = '<i class="fa-regular fa-copy me-1"></i> Copy'; }, 1200);
                    }
                });
            }

            if (printBtn) {
                printBtn.addEventListener('click', () => window.print());
            }

            setTab('tabOverview');
        })();
    </script>

<?php require_once __DIR__ . '/portal_footer.php'; ?>
