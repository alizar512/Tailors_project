<?php
require_once __DIR__ . '/includes/db_connect.php';

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
$return = isset($_GET['return']) ? trim((string)$_GET['return']) : '';

if ($token === '' || !$pdo) {
    header("Location: index.php");
    exit;
}

try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN order_number VARCHAR(20)");
} catch (Exception $e) {
}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN chat_token VARCHAR(64)");
} catch (Exception $e) {
}

$order = null;
try {
    $stmt = $pdo->prepare("SELECT id, order_number, status, created_at, customer_email, chat_token FROM orders WHERE chat_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

if (!$order) {
    header("Location: index.php");
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

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
$chatUrl = $baseUrl . '/order_chat.php?token=' . urlencode((string)$order['chat_token']) . '&new=1';
if ($return !== '') {
    $chatUrl .= '&return=' . urlencode($return);
}

$trackUrl = $baseUrl . '/index.php#track';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Submitted | Silah</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-bg text-text">
    <div class="container py-5">
        <div class="max-w-3xl mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-primary mb-1">Order Submitted</h1>
                    <p class="text-sm text-gray-500 mb-0">Save your order number to track and reopen chat anytime.</p>
                </div>
                <?php if ($return !== ''): ?>
                    <a href="<?= htmlspecialchars((string)$return) ?>" class="btn btn-outline rounded-full px-4 py-2">Back</a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-outline rounded-full px-4 py-2">Home</a>
                <?php endif; ?>
            </div>

            <div class="glass-card p-4 p-md-5 mb-4">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Order Number</p>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="px-4 py-3 rounded-3xl bg-primary/5 border border-primary/10">
                        <span class="text-lg font-black text-primary"><?= htmlspecialchars((string)$orderNumber) ?></span>
                    </div>
                    <button type="button" class="btn btn-outline rounded-full px-5 py-2.5 font-bold" id="copyOrderBtn">Copy</button>
                </div>
                <p class="text-xs text-gray-500 mt-3 mb-0">Email: <?= htmlspecialchars((string)($order['customer_email'] ?? '')) ?></p>
            </div>

            <div class="glass-card p-4 p-md-5">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Next Step</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= htmlspecialchars((string)($chatUrl ?? '')) ?>" class="btn btn-primary rounded-full px-6 py-2.5 font-bold">Open Bargaining Chat</a>
                    <a href="<?= htmlspecialchars((string)($trackUrl ?? '')) ?>" class="btn btn-outline rounded-full px-6 py-2.5 font-bold">Track Order</a>
                </div>
                <p class="text-xs text-gray-400 mt-3 mb-0">You can reopen chat later using tracking or the chat link.</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            const btn = document.getElementById('copyOrderBtn');
            if (!btn) return;
            btn.addEventListener('click', async () => {
                const value = <?= json_encode($orderNumber) ?>;
                try {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        await navigator.clipboard.writeText(value);
                    }
                } catch (e) {
                }
            });
        })();
    </script>
</body>
</html>

