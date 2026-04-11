<?php
$cp_title = 'Chat';
$cp_active = 'chat';
require_once __DIR__ . '/portal_header.php';
require_once __DIR__ . '/../includes/order_messages.php';

if (!$pdo) {
    echo '<div class="bg-white rounded-3xl border border-gray-100 p-6"><p class="text-sm font-black text-red-600 mb-0">Database connection failed.</p></div>';
    require_once __DIR__ . '/portal_footer.php';
    exit;
}

$orderId = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0) {
    header("Location: messages.php");
    exit;
}

$customerId = (int)($_SESSION['customer_id'] ?? 0);
$customerEmail = isset($_SESSION['customer_email']) ? (string)$_SESSION['customer_email'] : '';

try { $pdo->exec("ALTER TABLE orders ADD COLUMN customer_id INT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN order_number VARCHAR(20)"); } catch (Exception $e) {}
silah_ensure_order_messages_table($pdo);

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

$orderNumber = isset($order['order_number']) && trim((string)$order['order_number']) !== '' ? (string)$order['order_number'] : ('SIL-' . str_pad((string)$orderId, 4, '0', STR_PAD_LEFT));
$serviceType = isset($order['service_type']) ? (string)$order['service_type'] : '';
$status = isset($order['status']) && trim((string)$order['status']) !== '' ? (string)$order['status'] : 'Order Placed';

$customerName = 'Customer';
try {
    $stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ? LIMIT 1");
    $stmt->execute([$customerId]);
    $n = $stmt->fetchColumn();
    if ($n) $customerName = (string)$n;
} catch (Exception $e) {
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = isset($_POST['message']) ? trim((string)$_POST['message']) : '';
    if ($message !== '') {
        try {
            $stmt = $pdo->prepare("INSERT INTO order_messages (order_id, sender_type, sender_name, sender_email, message) VALUES (?, 'customer', ?, ?, ?)");
            $stmt->execute([$orderId, $customerName, $customerEmail, $message]);
        } catch (Exception $e) {
        }
    }
    header("Location: chat.php?order_id=" . urlencode((string)$orderId));
    exit;
}

$messages = [];
try {
    $stmt = $pdo->prepare("SELECT sender_type, sender_name, message, created_at FROM order_messages WHERE order_id = ? ORDER BY created_at ASC, id ASC LIMIT 500");
    $stmt->execute([$orderId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $messages = [];
}
?>

<div class="bg-white rounded-3xl border border-gray-100 overflow-hidden">
    <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Conversation</p>
            <p class="text-sm font-black text-gray-900 mb-0"><?= htmlspecialchars((string)$orderNumber) ?> <span class="text-gray-400">•</span> <?= htmlspecialchars((string)$serviceType) ?></p>
            <p class="text-[11px] font-bold text-gray-500 mb-0"><?= htmlspecialchars((string)$status) ?></p>
        </div>
        <div class="flex items-center gap-2">
            <a href="order_details.php?id=<?= (int)$orderId ?>" class="px-4 py-2 rounded-full bg-white border border-gray-200 text-xs font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all no-underline">
                <i class="fa-solid fa-receipt me-1"></i> Details
            </a>
            <a href="orders.php" class="px-4 py-2 rounded-full bg-white border border-gray-200 text-xs font-black uppercase tracking-widest text-gray-700 hover:border-gray-300 transition-all no-underline">
                <i class="fa-solid fa-arrow-left me-1"></i> Orders
            </a>
        </div>
    </div>

    <div id="chatScroll" class="p-5 bg-gray-50 max-h-[65vh] overflow-y-auto space-y-3">
        <?php if (empty($messages)): ?>
            <div class="p-6 text-center bg-white rounded-3xl border border-gray-100">
                <p class="text-sm font-black text-gray-900 mb-1">No messages yet</p>
                <p class="text-xs font-bold text-gray-500 mb-0">Send a message to start the conversation.</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $m): ?>
                <?php
                    $senderType = isset($m['sender_type']) ? (string)$m['sender_type'] : 'system';
                    $senderName = isset($m['sender_name']) && trim((string)$m['sender_name']) !== '' ? (string)$m['sender_name'] : ucfirst($senderType);
                    $msg = isset($m['message']) ? (string)$m['message'] : '';
                    $dt = isset($m['created_at']) ? (string)$m['created_at'] : '';
                    $mine = $senderType === 'customer';
                    $bubble = $mine ? 'bg-pink-600 text-white border-pink-600' : 'bg-white text-gray-700 border-gray-200';
                ?>
                <div class="flex <?= $mine ? 'justify-end' : 'justify-start' ?>">
                    <div class="max-w-[85%] sm:max-w-[70%]">
                        <div class="px-4 py-3 rounded-3xl border <?= $bubble ?>">
                            <p class="text-[11px] font-black mb-1 <?= $mine ? 'text-white/90' : 'text-gray-900' ?>"><?= htmlspecialchars((string)$senderName) ?></p>
                            <p class="text-sm font-medium mb-0 whitespace-pre-wrap"><?= htmlspecialchars((string)$msg) ?></p>
                        </div>
                        <?php if ($dt !== ''): ?>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mt-1 mb-0 <?= $mine ? 'text-right' : '' ?>"><?= htmlspecialchars((string)date('M d, Y h:i A', strtotime($dt))) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="p-5 border-t border-gray-100 bg-white">
        <form action="chat.php?order_id=<?= (int)$orderId ?>" method="POST" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <textarea name="message" rows="2" class="w-full px-4 py-3 rounded-2xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 focus:outline-none focus:ring-4 focus:ring-pink-100 focus:border-pink-400" placeholder="Type your message..." required></textarea>
            </div>
            <button type="submit" class="px-6 py-3 rounded-2xl bg-pink-600 text-white text-xs font-black uppercase tracking-widest hover:bg-pink-700 transition-all">
                <i class="fa-solid fa-paper-plane me-1"></i> Send
            </button>
        </form>
    </div>
</div>

<script>
    (function() {
        const el = document.getElementById('chatScroll');
        if (!el) return;
        el.scrollTop = el.scrollHeight;
    })();
</script>

<?php require_once __DIR__ . '/portal_footer.php'; ?>
