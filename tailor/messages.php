<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';

$tailor_id = (int)$_SESSION['tailor_id'];

if (!$pdo) {
    header("Location: index.php");
    exit;
}

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tailor_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tailor_id INT NOT NULL,
            order_id INT,
            customer_name VARCHAR(100) NOT NULL,
            customer_email VARCHAR(100) NOT NULL,
            customer_phone VARCHAR(30),
            customer_address TEXT,
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tailor_messages_tailor_id (tailor_id),
            INDEX idx_tailor_messages_order_id (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (Exception $e) {
}

try {
    $pdo->exec("ALTER TABLE tailor_messages ADD COLUMN order_id INT");
} catch (Exception $e) {
}
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
} catch (Exception $e) {
}

$tailorInfo = null;
try {
    $stmt = $pdo->prepare("SELECT id, name, email FROM tailors WHERE id = ? LIMIT 1");
    $stmt->execute([$tailor_id]);
    $tailorInfo = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $msg_id = (int)$_POST['mark_read_id'];
    try {
        $stmt = $pdo->prepare("UPDATE tailor_messages SET is_read = 1 WHERE id = ? AND tailor_id = ?");
        $stmt->execute([$msg_id, $tailor_id]);
    } catch (Exception $e) {
    }
    header("Location: messages.php");
    exit;
}

$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_order_id']) && isset($_POST['reply_message'])) {
    $order_id = (int)$_POST['reply_order_id'];
    $reply = trim((string)$_POST['reply_message']);
    if ($order_id > 0 && $reply !== '') {
        try {
            $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND tailor_id = ? LIMIT 1");
            $stmt->execute([$order_id, $tailor_id]);
            $ok = (int)$stmt->fetchColumn() > 0;
            if ($ok) {
                $senderName = $tailorInfo && isset($tailorInfo['name']) ? (string)$tailorInfo['name'] : 'Tailor';
                $senderEmail = $tailorInfo && isset($tailorInfo['email']) ? (string)$tailorInfo['email'] : '';
                $ins = $pdo->prepare("INSERT INTO order_messages (order_id, sender_type, sender_name, sender_email, message) VALUES (?, 'tailor', ?, ?, ?)");
                $ins->execute([$order_id, $senderName, $senderEmail, $reply]);
                $flash = 'sent';
            } else {
                $flash = 'invalid';
            }
        } catch (Exception $e) {
            $flash = 'failed';
        }
    } else {
        $flash = 'failed';
    }
    header("Location: messages.php?flash=" . urlencode($flash));
    exit;
}

$messages = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM tailor_messages WHERE tailor_id = ? ORDER BY created_at DESC");
    $stmt->execute([$tailor_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $messages = [];
}

include 'header.php';
include 'sidebar.php';
?>

<div class="glass-card overflow-hidden">
    <div class="px-8 py-6 border-b border-gray-100 flex flex-wrap items-center justify-between bg-white/50 gap-4">
        <div>
            <h3 class="text-xl font-black text-primary mb-1">Messages</h3>
            <p class="text-xs text-gray-500 font-medium mb-0">Customer messages from your profile</p>
        </div>
    </div>

    <?php if (isset($_GET['flash']) && $_GET['flash'] !== ''): ?>
        <?php
            $f = (string)$_GET['flash'];
            $isOk = $f === 'sent';
            $txt = $isOk ? 'Message sent to customer in chat.' : ($f === 'invalid' ? 'This order is not assigned to you.' : 'Could not send message.');
            $box = $isOk ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100';
            $t1 = $isOk ? 'text-green-700' : 'text-red-600';
            $t2 = $isOk ? 'text-green-800' : 'text-red-800';
        ?>
        <div class="px-8 pt-6">
            <div class="p-4 rounded-2xl border <?= $box ?>">
                <p class="text-xs font-extrabold uppercase tracking-widest mb-1 <?= $t1 ?>"><?= $isOk ? 'Sent' : 'Error' ?></p>
                <p class="text-sm font-semibold mb-0 <?= $t2 ?>"><?= htmlspecialchars((string)$txt) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 portal-messages-table">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">From</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Contact</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Message</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-center">Status</th>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-end">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($messages)): ?>
                <tr>
                    <td colspan="5" class="py-20 text-center">
                        <div class="text-gray-300 mb-4 text-4xl"><i class="fas fa-envelope"></i></div>
                        <p class="text-sm text-gray-400 font-bold uppercase tracking-widest">No messages yet</p>
                    </td>
                </tr>
                <?php endif; ?>

                <?php foreach ($messages as $m): ?>
                <?php
                    $unread = isset($m['is_read']) && (int)$m['is_read'] === 0;
                    $statusClass = $unread ? 'bg-amber-100 text-amber-600' : 'bg-green-100 text-green-600';
                    $statusText = $unread ? 'Unread' : 'Read';

                    $orderId = isset($m['order_id']) && is_numeric($m['order_id']) ? (int)$m['order_id'] : 0;
                    if ($orderId <= 0 && isset($m['customer_email'])) {
                        try {
                            $stmt = $pdo->prepare("SELECT id FROM orders WHERE tailor_id = ? AND REPLACE(LOWER(TRIM(customer_email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '') ORDER BY id DESC LIMIT 1");
                            $stmt->execute([$tailor_id, (string)$m['customer_email']]);
                            $orderId = (int)$stmt->fetchColumn();
                            if ($orderId > 0) {
                                $u = $pdo->prepare("UPDATE tailor_messages SET order_id = ? WHERE id = ? AND tailor_id = ?");
                                $u->execute([$orderId, (int)$m['id'], $tailor_id]);
                            }
                        } catch (Exception $e) {
                        }
                    }

                    $msgText = str_replace("\\n", "\n", (string)$m['message']);
                ?>
                <tr class="group hover:bg-primary/5 transition-colors">
                    <td class="px-8 py-5 border-0">
                        <p class="text-sm font-black text-gray-800 mb-0"><?= htmlspecialchars((string)$m['customer_name']) ?></p>
                        <p class="text-[11px] text-gray-500 font-medium mb-0"><?= isset($m['created_at']) ? date('M d, Y H:i', strtotime($m['created_at'])) : '' ?></p>
                    </td>
                    <td class="py-5 border-0">
                        <p class="text-[12px] font-bold text-gray-700 mb-0"><?= htmlspecialchars((string)$m['customer_phone']) ?></p>
                        <p class="text-[11px] text-gray-500 font-medium mb-0"><?= htmlspecialchars((string)$m['customer_email']) ?></p>
                        <p class="text-[11px] text-gray-500 font-medium mb-0"><?= htmlspecialchars((string)($m['customer_address'] ?? '')) ?></p>
                    </td>
                    <td class="py-5 border-0">
                        <div class="portal-message-preview">
                            <?= nl2br(htmlspecialchars((string)$msgText)) ?>
                        </div>
                    </td>
                    <td class="py-5 border-0 text-center">
                        <span class="text-[10px] font-black uppercase px-3 py-1 rounded-full <?= $statusClass ?>"><?= $statusText ?></span>
                    </td>
                    <td class="px-8 py-5 border-0 text-end">
                        <div class="d-inline-flex gap-1 flex-nowrap align-items-center">
                            <?php if ($orderId > 0): ?>
                                <a href="order_details.php?id=<?= (int)$orderId ?>" class="portal-action-icon" title="Open Chat" aria-label="Open Chat">
                                    <i class="fas fa-comments"></i>
                                </a>
                                <button type="button" class="portal-action-icon" title="Reply" aria-label="Reply" data-bs-toggle="collapse" data-bs-target="#reply<?= (int)$m['id'] ?>">
                                    <i class="fas fa-reply"></i>
                                </button>
                            <?php endif; ?>

                            <?php if ($unread): ?>
                                <form action="messages.php" method="POST" class="m-0">
                                    <input type="hidden" name="mark_read_id" value="<?= (int)$m['id'] ?>">
                                    <button type="submit" class="portal-action-icon portal-action-icon--play" title="Mark Read" aria-label="Mark Read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php if ($orderId > 0): ?>
                <tr class="collapse bg-white" id="reply<?= (int)$m['id'] ?>">
                    <td colspan="5" class="px-8 py-5 border-0">
                        <form action="messages.php" method="POST" class="row g-3">
                            <input type="hidden" name="reply_order_id" value="<?= (int)$orderId ?>">
                            <div class="col-12">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Reply Message</label>
                                <textarea name="reply_message" class="form-control" rows="3" required></textarea>
                                <div class="form-text text-xs">This sends a message in the bargaining chat (customer will see it).</div>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <a href="order_details.php?id=<?= (int)$orderId ?>" class="btn btn-outline rounded-full px-5 py-2.5 font-bold no-underline">View Full Chat</a>
                                <button type="submit" class="btn btn-primary rounded-full px-5 py-2.5 font-bold">Send</button>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../admin/footer.php'; ?>
