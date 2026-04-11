<?php
$cp_title = 'Messages';
$cp_active = 'messages';
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

$messages = [];
try {
    $stmt = $pdo->prepare(
        "SELECT om.id, om.order_id, om.sender_type, om.sender_name, om.message, om.created_at,
                o.order_number, o.service_type, o.status
         FROM order_messages om
         JOIN orders o ON o.id = om.order_id
         WHERE o.customer_id = ? OR REPLACE(LOWER(TRIM(o.customer_email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '')
         ORDER BY om.created_at DESC, om.id DESC
         LIMIT 200"
    );
    $stmt->execute([$customerId, $customerEmail]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $messages = [];
}
?>

<div class="bg-white rounded-3xl border border-gray-100 p-5 mb-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">All Messages</p>
            <p class="text-sm font-bold text-gray-600 mb-0">Search and open any conversation</p>
        </div>
        <div class="w-full sm:w-[380px] relative">
            <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
            <input id="msgSearch" type="text" class="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 focus:outline-none focus:ring-4 focus:ring-pink-100 focus:border-pink-400" placeholder="Search by order, name, message...">
        </div>
    </div>
</div>

<div class="bg-white rounded-3xl border border-gray-100 overflow-hidden">
    <div class="p-5 border-b border-gray-100 flex items-center justify-between gap-3">
        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-0">Messages</p>
        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-0">Showing <span id="msgCount">0</span></p>
    </div>

    <?php if (empty($messages)): ?>
        <div class="p-8 text-center">
            <div class="text-gray-300 text-4xl mb-3"><i class="fas fa-comments"></i></div>
            <p class="text-sm font-black text-gray-700 mb-1">No messages yet</p>
            <p class="text-xs font-bold text-gray-500 mb-0">When you chat on an order, it will appear here.</p>
        </div>
    <?php else: ?>
        <div id="msgList" class="divide-y divide-gray-50">
            <?php foreach ($messages as $m): ?>
                <?php
                    $oid = isset($m['order_id']) ? (int)$m['order_id'] : 0;
                    $orderNumber = isset($m['order_number']) && trim((string)$m['order_number']) !== '' ? (string)$m['order_number'] : ('SIL-' . str_pad((string)$oid, 4, '0', STR_PAD_LEFT));
                    $service = isset($m['service_type']) ? (string)$m['service_type'] : '';
                    $senderType = isset($m['sender_type']) ? (string)$m['sender_type'] : '';
                    $sender = isset($m['sender_name']) && trim((string)$m['sender_name']) !== '' ? (string)$m['sender_name'] : ucfirst($senderType ?: 'user');
                    $msg = isset($m['message']) ? (string)$m['message'] : '';
                    $dt = isset($m['created_at']) ? (string)$m['created_at'] : '';

                    $badge = 'bg-gray-900';
                    if ($senderType === 'customer') $badge = 'bg-pink-600';
                    if ($senderType === 'tailor') $badge = 'bg-indigo-600';
                    if ($senderType === 'admin') $badge = 'bg-emerald-600';
                    $hay = strtolower(trim($orderNumber . ' ' . $service . ' ' . $sender . ' ' . $msg));
                ?>
                <a class="msgRow block p-5 hover:bg-gray-50 transition-all no-underline"
                   href="chat.php?order_id=<?= (int)$oid ?>"
                   data-hay="<?= htmlspecialchars((string)$hay) ?>">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400"><?= htmlspecialchars((string)$orderNumber) ?></span>
                                <?php if (trim($service) !== ''): ?>
                                    <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-100 text-[10px] font-black uppercase tracking-widest text-gray-600"><?= htmlspecialchars((string)$service) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="w-2.5 h-2.5 rounded-full <?= $badge ?>"></span>
                                <p class="text-xs font-black text-gray-900 mb-0"><?= htmlspecialchars((string)$sender) ?></p>
                            </div>
                            <p class="text-sm font-semibold text-gray-700 mb-0 line-clamp-2"><?= htmlspecialchars((string)$msg) ?></p>
                        </div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-0 whitespace-nowrap"><?= $dt !== '' ? htmlspecialchars((string)date('M d, Y', strtotime($dt))) : '' ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    (function() {
        const input = document.getElementById('msgSearch');
        const rows = Array.from(document.querySelectorAll('.msgRow'));
        const count = document.getElementById('msgCount');

        const apply = () => {
            const q = (input ? input.value : '').trim().toLowerCase();
            let shown = 0;
            rows.forEach(r => {
                const hay = (r.getAttribute('data-hay') || '').toLowerCase();
                const ok = q === '' || hay.includes(q);
                r.classList.toggle('hidden', !ok);
                if (ok) shown += 1;
            });
            if (count) count.textContent = String(shown);
        };

        if (input) input.addEventListener('input', apply);
        apply();
    })();
</script>

<?php require_once __DIR__ . '/portal_footer.php'; ?>
