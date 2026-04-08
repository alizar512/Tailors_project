<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/notifications.php';
require_once '../includes/mailer.php';
require_once '../includes/order_messages.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = null;
$chatMessages = [];
$tailor = null;

if ($pdo) {
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN chat_token VARCHAR(64)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN order_number VARCHAR(20)");
    } catch (Exception $e) {
    }
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
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_proof_image VARCHAR(255)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_submitted_at TIMESTAMP NULL");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_confirmed_at TIMESTAMP NULL");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN tailor_offer_price DECIMAL(10,2)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN tailor_offer_notes TEXT");
    } catch (Exception $e) {
    }
    silah_ensure_order_messages_table($pdo);
}

if ($order_id && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Log error
    }
}

if (!$order) {
    header("Location: orders.php");
    exit;
}

$totalPrice = isset($order['total_price']) && $order['total_price'] !== null && $order['total_price'] !== '' ? (float)$order['total_price'] : (isset($order['budget']) ? (float)$order['budget'] : 0.0);
$paymentStatus = isset($order['payment_status']) && $order['payment_status'] ? (string)$order['payment_status'] : 'Pending';
$paymentBadge = $paymentStatus === 'Confirmed'
    ? 'bg-green-100 text-green-600'
    : ($paymentStatus === 'Submitted'
        ? 'bg-amber-100 text-amber-700'
        : ($paymentStatus === 'Rejected'
            ? 'bg-red-100 text-red-500'
            : 'bg-gray-100 text-gray-600'));

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message']) && isset($_POST['message'])) {
    $message = trim((string)$_POST['message']);
    if ($message !== '') {
        try {
            $ins = $pdo->prepare("INSERT INTO order_messages (order_id, sender_type, sender_name, sender_email, message) VALUES (?, 'admin', ?, ?, ?)");
            $ins->execute([
                (int)$order['id'],
                'Admin',
                isset($_SESSION['admin_email']) ? (string)$_SESSION['admin_email'] : '',
                $message
            ]);
        } catch (Exception $e) {
        }

        try {
            $tailorId = isset($order['tailor_id']) ? (int)$order['tailor_id'] : 0;
            $orderNumber = isset($order['order_number']) && trim((string)$order['order_number']) !== '' ? (string)$order['order_number'] : ('SIL-' . str_pad((string)$order['id'], 4, '0', STR_PAD_LEFT));

            if ($tailorId > 0) {
                silah_add_notification(
                    $pdo,
                    'tailor',
                    $tailorId,
                    'New Admin Message',
                    $orderNumber . ': Admin sent a message in chat.',
                    'order',
                    'order_details.php?id=' . (int)$order['id']
                );
            }

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
            $tailorLink = $baseUrl . '/tailor/order_details.php?id=' . (int)$order['id'];
            $customerLink = $baseUrl . '/order_chat.php?token=' . urlencode((string)($order['chat_token'] ?? ''));

            $from = 'silah@' . $_SERVER['HTTP_HOST'];
            $headers = "From: " . $from . "\r\n" . "Reply-To: " . $from . "\r\n";

            $customerEmail = isset($order['customer_email']) ? trim((string)$order['customer_email']) : '';
            if ($customerEmail !== '') {
                $subject = 'Silah: Admin message (' . $orderNumber . ')';
                $body =
                    "Hi,\n\n" .
                    "Admin sent a message for " . $orderNumber . ":\n\n" .
                    $message . "\n\n" .
                    "Open chat: " . $customerLink . "\n\n" .
                    "Silah Team\n";
                $eventKey = 'admin_msg_customer_order_' . (int)$order['id'];
                if (silah_should_email($pdo, 'admin', null, $eventKey, 0)) {
                    silah_send_email($customerEmail, $subject, $body);
                    silah_record_emailed($pdo, 'admin', null, $eventKey);
                }
            }

            if ($tailorId > 0) {
                $stmt = $pdo->prepare("SELECT email, name FROM tailors WHERE id = ? LIMIT 1");
                $stmt->execute([$tailorId]);
                $t = $stmt->fetch(PDO::FETCH_ASSOC);
                $to = $t && isset($t['email']) ? trim((string)$t['email']) : '';
                if ($to !== '') {
                    $name = $t && isset($t['name']) ? (string)$t['name'] : 'Tailor';
                    $subject = 'Silah: Admin message (' . $orderNumber . ')';
                    $body =
                        "Hi " . $name . ",\n\n" .
                        "Admin sent a message for " . $orderNumber . ":\n\n" .
                        $message . "\n\n" .
                        "Open order: " . $tailorLink . "\n\n" .
                        "Silah Team\n";
                    $eventKey = 'admin_msg_tailor_order_' . (int)$order['id'];
                    if (silah_should_email($pdo, 'tailor', $tailorId, $eventKey, 0)) {
                        silah_send_email($to, $subject, $body);
                        silah_record_emailed($pdo, 'tailor', $tailorId, $eventKey);
                    }
                }
            }
        } catch (Exception $e) {
        }
    }
    header("Location: order_details.php?id=" . (int)$order['id'] . "&chat=1");
    exit;
}

if ($pdo && (!isset($order['chat_token']) || trim((string)$order['chat_token']) === '')) {
    $newToken = bin2hex(random_bytes(16));
    try {
        $stmt = $pdo->prepare("UPDATE orders SET chat_token = ? WHERE id = ?");
        $stmt->execute([$newToken, (int)$order['id']]);
        $order['chat_token'] = $newToken;
    } catch (Exception $e) {
    }
}

if ($pdo) {
    try {
        if (isset($order['tailor_id']) && (int)$order['tailor_id'] > 0) {
            $stmt = $pdo->prepare("SELECT id, name, email FROM tailors WHERE id = ?");
            $stmt->execute([(int)$order['tailor_id']]);
            $tailor = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM order_messages WHERE order_id = ? ORDER BY created_at ASC, id ASC");
        $stmt->execute([(int)$order['id']]);
        $chatMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $chatMessages = [];
    }
}

include 'header.php';
include 'sidebar.php';
?>

<?php if (isset($_GET['success'])): ?>
    <?php $ok = (string)$_GET['success'] === '1'; ?>
    <div class="mb-6 p-4 rounded-3xl <?= $ok ? 'bg-green-50 border border-green-100' : 'bg-red-50 border border-red-100' ?>">
        <p class="text-sm font-semibold mb-0 <?= $ok ? 'text-green-800' : 'text-red-800' ?>">
            <?= $ok ? 'Order updated successfully.' : 'Could not update order. Please try again.' ?>
        </p>
        <?php if (!$ok && isset($_SESSION['order_update_error']) && (string)$_SESSION['order_update_error'] !== ''): ?>
            <p class="text-xs text-red-700 mt-2 mb-0"><?= htmlspecialchars((string)$_SESSION['order_update_error']) ?></p>
            <?php unset($_SESSION['order_update_error']); ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Order Core Info -->
        <div class="glass-card p-8 mb-8">
            <div class="flex items-center justify-between mb-8 pb-6 border-b border-gray-100">
                <div>
                    <span class="text-[10px] font-black text-primary uppercase tracking-[0.2em]">Order Request</span>
                    <h2 class="text-3xl font-black text-primary mt-1">#SIL-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></h2>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Placed On</p>
                    <p class="text-sm font-bold text-gray-700"><?= date('F d, Y • H:i', strtotime($order['created_at'])) ?></p>
                </div>
            </div>
            
            <div class="row g-6 mb-10">
                <div class="col-md-6">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Customer Details</p>
                    <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                        <p class="text-sm font-black text-gray-800 mb-1"><?= htmlspecialchars($order['customer_name']) ?></p>
                        <p class="text-xs text-gray-500 mb-1"><?= htmlspecialchars($order['customer_email']) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($order['customer_phone']) ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Service Selection</p>
                    <div class="p-4 bg-primary/5 rounded-2xl border border-primary/10">
                        <p class="text-sm font-black text-primary mb-1"><?= htmlspecialchars($order['service_type']) ?></p>
                        <p class="text-xs text-gray-500 mb-1">Budget: <span class="font-bold text-gray-700">PKR <?= number_format($order['budget']) ?></span></p>
                        <p class="text-xs text-gray-500">Timeline: <span class="font-bold text-gray-700"><?= date('M d, Y', strtotime($order['expected_delivery'])) ?></span></p>
                    </div>
                </div>
            </div>

            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Style Description</p>
            <div class="p-5 bg-white border border-gray-100 rounded-2xl shadow-sm mb-10">
                <p class="text-sm text-gray-600 leading-relaxed mb-0 italic">"<?= nl2br(htmlspecialchars($order['notes'])) ?>..."</p>
            </div>

            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Delivery Address</p>
            <div class="p-5 bg-amber-50/50 border border-amber-100 rounded-2xl mb-10">
                <div class="flex gap-3">
                    <i class="fas fa-map-pin text-amber-500 mt-1"></i>
                    <p class="text-sm text-gray-700 leading-relaxed mb-0 font-medium"><?= htmlspecialchars($order['location_details']) ?></p>
                </div>
            </div>

            <?php if (isset($order['cargo_company']) && trim((string)$order['cargo_company']) !== ''): ?>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Shipment Details</p>
            <div class="p-5 bg-green-50 border border-green-100 rounded-2xl mb-10">
                <div class="row g-4">
                    <div class="col-md-6">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Cargo Name</p>
                        <p class="text-sm font-black text-gray-800 mb-0"><?= htmlspecialchars((string)$order['cargo_company']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Cargo Number</p>
                        <p class="text-sm font-black text-gray-800 mb-0"><?= htmlspecialchars((string)$order['cargo_tracking_number']) ?></p>
                    </div>
                    <?php if (isset($order['shipped_at']) && $order['shipped_at']): ?>
                    <div class="col-12 mt-2">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Shipped On</p>
                        <p class="text-sm font-bold text-gray-700 mb-0"><?= date('F d, Y • H:i', strtotime($order['shipped_at'])) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($order['cargo_receipt_image']) && trim((string)$order['cargo_receipt_image']) !== ''): ?>
                    <div class="col-12 mt-4">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Cargo Transcript</p>
                        <div class="rounded-2xl overflow-hidden border border-gray-100 bg-white max-w-sm">
                            <img src="../<?= htmlspecialchars((string)$order['cargo_receipt_image']) ?>" alt="Cargo receipt" class="w-full h-auto">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($order['reference_image']): ?>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Design Reference</p>
            <div class="rounded-2xl overflow-hidden border border-gray-100 shadow-sm max-w-sm relative group">
                <img src="../<?= htmlspecialchars($order['reference_image']) ?>" class="w-full h-auto hover:scale-105 transition-transform duration-500">
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                    <a href="../<?= htmlspecialchars($order['reference_image']) ?>" target="_blank" class="w-10 h-10 rounded-full bg-white text-primary flex items-center justify-center shadow-xl hover:scale-110 transition-transform">
                        <i class="fas fa-eye"></i>
                    </a>
                    <form action="delete_order_media.php" method="POST" onsubmit="return confirm('Delete this image? This action cannot be undone.');">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input type="hidden" name="image_path" value="<?= htmlspecialchars($order['reference_image']) ?>">
                        <button type="submit" class="w-10 h-10 rounded-full bg-white text-red-500 flex items-center justify-center shadow-xl hover:scale-110 transition-transform">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="glass-card p-8" id="chat">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-black text-primary mb-1">Bargaining Chat</h3>
                    <p class="text-xs text-gray-500 font-medium mb-0">Admin can view all negotiation messages.</p>
                </div>
                <?php if (isset($order['chat_token']) && trim((string)$order['chat_token']) !== ''): ?>
                    <?php
                        $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
                        $chatUrl = $baseUrl . '/order_chat.php?token=' . urlencode((string)$order['chat_token']);
                    ?>
                    <a href="<?= htmlspecialchars($chatUrl) ?>" target="_blank" class="btn btn-outline !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold no-underline">Customer Chat</a>
                <?php endif; ?>
            </div>

            <div id="adminChatScroll" class="p-5 bg-primary/5 border border-primary/10 rounded-3xl shadow-sm mb-6" style="max-height: 360px; overflow:auto;">
                <?php if (empty($chatMessages)): ?>
                    <p class="text-sm text-gray-500 mb-0">No messages yet.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($chatMessages as $m): ?>
                            <?php
                                $senderType = isset($m['sender_type']) ? (string)$m['sender_type'] : '';
                                $isAdminMsg = $senderType === 'admin';
                                $bubbleClass = $isAdminMsg ? 'bg-primary text-white' : 'bg-gray-50 text-gray-800 border border-gray-100';
                                $alignClass = $isAdminMsg ? 'justify-end' : 'justify-start';
                                $name = isset($m['sender_name']) && trim((string)$m['sender_name']) !== '' ? (string)$m['sender_name'] : ucfirst($senderType);
                            ?>
                            <div class="flex <?= $alignClass ?>">
                                <div class="rounded-2xl px-4 py-3 <?= $bubbleClass ?>" style="max-width: 85%;">
                                    <div class="flex justify-between gap-3 mb-1">
                                        <span class="text-[11px] font-extrabold <?= $isAdminMsg ? 'text-white/90' : 'text-gray-500' ?>"><?= htmlspecialchars($name) ?></span>
                                        <span class="text-[10px] <?= $isAdminMsg ? 'text-white/70' : 'text-gray-400' ?>"><?= htmlspecialchars(date('M d, H:i', strtotime((string)$m['created_at']))) ?></span>
                                    </div>
                                    <?php $msgText = str_replace("\\n", "\n", (string)$m['message']); ?>
                                    <div class="text-sm leading-relaxed"><?= nl2br(htmlspecialchars($msgText)) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST" class="space-y-3">
                <input type="hidden" name="send_message" value="1">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Message as Admin</label>
                    <textarea name="message" class="form-control" rows="3" required></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary rounded-xl py-3 px-5 font-black uppercase tracking-widest text-xs shadow-lg hover:shadow-primary/20 transition-all">Send</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Status & Pricing Actions -->
        <div class="glass-card p-8 sticky top-32">
            <h3 class="text-xl font-black text-primary mb-6">Manage Status</h3>

            <?php if ($tailor): ?>
                <div class="mb-6 p-4 rounded-2xl border border-gray-100 bg-white">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Assigned Tailor</p>
                    <p class="text-sm font-black text-gray-800 mb-1"><?= htmlspecialchars((string)$tailor['name']) ?></p>
                    <p class="text-xs text-gray-500 mb-0"><?= htmlspecialchars((string)$tailor['email']) ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($order['tailor_offer_price']) && $order['tailor_offer_price'] !== null && (float)$order['tailor_offer_price'] > 0): ?>
                <div class="mb-6 p-4 rounded-2xl bg-primary/5 border border-primary/10">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Tailor Offer</p>
                    <p class="text-lg font-black text-primary mb-0">PKR <?= number_format((float)$order['tailor_offer_price']) ?></p>
                    <?php if (isset($order['tailor_offer_notes']) && trim((string)$order['tailor_offer_notes']) !== ''): ?>
                        <p class="text-sm text-gray-600 mb-0 mt-2"><?= nl2br(htmlspecialchars((string)$order['tailor_offer_notes'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="space-y-6">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Current Status</label>
                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 text-sm font-bold text-gray-700">
                        <?= htmlspecialchars($order['status']) ?>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Final Price (PKR)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-xs">PKR</span>
                        <div class="form-control rounded-xl border-gray-100 bg-gray-50 text-sm font-black pl-12 py-3">
                            <?= number_format($totalPrice) ?>
                        </div>
                    </div>
                    <p class="text-[9px] text-gray-400 mt-2 italic">*Only tailors can update the status and price.</p>
                </div>

                <div class="pt-4">
                    <div class="p-4 rounded-2xl bg-primary/5 border border-primary/10 mb-0">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-[10px] font-bold text-gray-500 uppercase">30% Advance</span>
                            <span class="text-sm font-black text-primary">PKR <?= number_format($totalPrice * 0.3) ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-gray-500 uppercase">Payment Status</span>
                            <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full <?= $paymentBadge ?>">
                                <?= htmlspecialchars($paymentStatus) ?>
                            </span>
                        </div>
                        <?php if (isset($order['payment_proof_image']) && trim((string)$order['payment_proof_image']) !== ''): ?>
                            <div class="mt-4">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Payment Proof</p>
                                <div class="rounded-2xl overflow-hidden border border-gray-100 bg-white">
                                    <img src="../<?= htmlspecialchars((string)$order['payment_proof_image']) ?>" alt="Payment proof" class="w-full h-auto">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-8 border-t border-gray-100">
                <h4 class="text-sm font-black text-gray-800 mb-4 uppercase tracking-widest">Preferred Tailors</h4>
                <div class="space-y-3">
                    <?php 
                    $pref_ids = json_decode($order['preferred_tailors'], true) ?: [];
                    if (empty($pref_ids)): ?>
                        <p class="text-xs text-gray-400 italic">No preferred tailors selected.</p>
                    <?php elseif (!$pdo): ?>
                        <p class="text-xs text-gray-400 italic">Database unavailable.</p>
                    <?php else: 
                        // Fetch names for IDs
                        $placeholders = implode(',', array_fill(0, count($pref_ids), '?'));
                        $stmt = $pdo->prepare("SELECT name FROM tailors WHERE id IN ($placeholders)");
                        $stmt->execute($pref_ids);
                        $tailor_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        foreach($tailor_names as $tn): ?>
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full bg-primary"></div>
                            <span class="text-xs font-bold text-gray-600"><?= htmlspecialchars($tn) ?></span>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        const el = document.getElementById('adminChatScroll');
        if (!el) return;
        el.scrollTop = el.scrollHeight;
    })();
</script>

<?php include 'footer.php'; ?>
