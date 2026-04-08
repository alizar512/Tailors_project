<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/notifications.php';
require_once '../includes/mailer.php';
require_once '../includes/order_messages.php';

$tailor_id = (int)$_SESSION['tailor_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$pdo) {
    header("Location: my_orders.php");
    exit;
}

try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN chat_token VARCHAR(64)");
} catch (Exception $e) {
}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN order_number VARCHAR(20)");
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
    $pdo->exec("ALTER TABLE orders ADD COLUMN balance_payment_status VARCHAR(30) DEFAULT 'Pending'");
} catch (Exception $e) {
}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN balance_payment_proof_image VARCHAR(255)");
} catch (Exception $e) {
}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN balance_payment_submitted_at TIMESTAMP NULL");
} catch (Exception $e) {
}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN balance_payment_confirmed_at TIMESTAMP NULL");
} catch (Exception $e) {
}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN cargo_company VARCHAR(120)");
} catch (Exception $e) {
}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN cargo_tracking_number VARCHAR(120)");
} catch (Exception $e) {
}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN cargo_receipt_image VARCHAR(255)");
} catch (Exception $e) {
}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN shipped_at TIMESTAMP NULL");
} catch (Exception $e) {
}

silah_ensure_order_messages_table($pdo);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_action']) && isset($_POST['order_id'])) {
    $post_order_id = (int)$_POST['order_id'];
    $action = (string)$_POST['payment_action'];
    if ($post_order_id > 0 && ($action === 'confirm' || $action === 'reject' || $action === 'confirm_balance' || $action === 'reject_balance')) {
        try {
            $stmt = $pdo->prepare("SELECT id, tailor_id, total_price, budget FROM orders WHERE id = ? LIMIT 1");
            $stmt->execute([$post_order_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && (int)$row['tailor_id'] === $tailor_id) {
                $total = isset($row['total_price']) && $row['total_price'] !== null && $row['total_price'] !== '' ? (float)$row['total_price'] : (isset($row['budget']) ? (float)$row['budget'] : 0.0);
                $advance = $total * 0.3;
                $newStatus = ($action === 'confirm' || $action === 'confirm_balance') ? 'Confirmed' : 'Rejected';

                if ($action === 'confirm' || $action === 'reject') {
                    $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, advance_payment_amount = ?, payment_confirmed_at = NOW() WHERE id = ? AND tailor_id = ?");
                    $stmt->execute([$newStatus, $advance, $post_order_id, $tailor_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE orders SET balance_payment_status = ?, balance_payment_confirmed_at = NOW() WHERE id = ? AND tailor_id = ?");
                    $stmt->execute([$newStatus, $post_order_id, $tailor_id]);
                }

                try {
                    $stmt = $pdo->prepare("SELECT name, email FROM tailors WHERE id = ? LIMIT 1");
                    $stmt->execute([$tailor_id]);
                    $t = $stmt->fetch(PDO::FETCH_ASSOC);
                    $senderName = $t && isset($t['name']) ? (string)$t['name'] : 'Tailor';
                    $senderEmail = $t && isset($t['email']) ? (string)$t['email'] : '';
                    if ($action === 'confirm' || $action === 'reject') {
                        $msg = $action === 'confirm' ? 'Advance payment confirmed.' : 'Advance payment rejected. Please re-upload proof.';
                    } else {
                        $msg = $action === 'confirm_balance' ? 'Remaining payment confirmed.' : 'Remaining payment rejected. Please re-upload proof.';
                    }
                    $ins = $pdo->prepare("INSERT INTO order_messages (order_id, sender_type, sender_name, sender_email, message) VALUES (?, 'tailor', ?, ?, ?)");
                    $ins->execute([$post_order_id, $senderName, $senderEmail, $msg]);
                } catch (Exception $e) {
                }

                $key = ($action === 'confirm_balance' || $action === 'reject_balance') ? 'pay70' : 'pay';
                header("Location: order_details.php?id=" . $post_order_id . "&" . $key . "=1");
                exit;
            }
        } catch (Exception $e) {
        }
    }
    $key = ($action === 'confirm_balance' || $action === 'reject_balance') ? 'pay70' : 'pay';
    header("Location: order_details.php?id=" . $post_order_id . "&" . $key . "=0");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_cargo']) && isset($_POST['order_id'])) {
    $post_order_id = (int)$_POST['order_id'];
    $cargoCompany = isset($_POST['cargo_company']) ? trim((string)$_POST['cargo_company']) : '';
    $trackingNumber = isset($_POST['cargo_tracking_number']) ? trim((string)$_POST['cargo_tracking_number']) : '';

    if ($post_order_id > 0 && $cargoCompany !== '' && $trackingNumber !== '') {
        try {
            $stmt = $pdo->prepare("SELECT id, tailor_id, status, customer_name, customer_email, chat_token, order_number, payment_status, balance_payment_status FROM orders WHERE id = ? LIMIT 1");
            $stmt->execute([$post_order_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && (int)$row['tailor_id'] === $tailor_id) {
                $advanceOk = isset($row['payment_status']) && (string)$row['payment_status'] === 'Confirmed';
                $balanceOk = isset($row['balance_payment_status']) && (string)$row['balance_payment_status'] === 'Confirmed';
                if (!$advanceOk || !$balanceOk) {
                    header("Location: order_details.php?id=" . $post_order_id . "&ship=0");
                    exit;
                }

                $destRel = null;
                if (isset($_FILES['cargo_receipt']) && is_array($_FILES['cargo_receipt']) && isset($_FILES['cargo_receipt']['tmp_name']) && is_uploaded_file($_FILES['cargo_receipt']['tmp_name'])) {
                    $tmp = (string)$_FILES['cargo_receipt']['tmp_name'];
                    $size = isset($_FILES['cargo_receipt']['size']) ? (int)$_FILES['cargo_receipt']['size'] : 0;
                    if ($size > 0 && $size <= 2 * 1024 * 1024) {
                        $info = @getimagesize($tmp);
                        $mime = is_array($info) && isset($info['mime']) ? (string)$info['mime'] : '';
                        $ext = '';
                        if ($mime === 'image/jpeg') $ext = 'jpg';
                        if ($mime === 'image/png') $ext = 'png';
                        if ($mime === 'image/webp') $ext = 'webp';
                        if ($ext !== '') {
                            $dirAbs = __DIR__ . '/../uploads/shipments';
                            if (!is_dir($dirAbs)) {
                                @mkdir($dirAbs, 0777, true);
                            }
                            $fileName = uniqid('ship_', true) . '.' . $ext;
                            $destAbs = $dirAbs . '/' . $fileName;
                            if (@move_uploaded_file($tmp, $destAbs)) {
                                $destRel = 'uploads/shipments/' . $fileName;
                            }
                        }
                    }
                }

                $stmt = $pdo->prepare("UPDATE orders SET cargo_company = ?, cargo_tracking_number = ?, cargo_receipt_image = COALESCE(?, cargo_receipt_image), shipped_at = COALESCE(shipped_at, NOW()), status = 'Shipped' WHERE id = ? AND tailor_id = ?");
                $stmt->execute([$cargoCompany, $trackingNumber, $destRel, $post_order_id, $tailor_id]);

                try {
                    $stmt = $pdo->prepare("SELECT name, email FROM tailors WHERE id = ? LIMIT 1");
                    $stmt->execute([$tailor_id]);
                    $t = $stmt->fetch(PDO::FETCH_ASSOC);
                    $senderName = $t && isset($t['name']) ? (string)$t['name'] : 'Tailor';
                    $senderEmail = $t && isset($t['email']) ? (string)$t['email'] : '';
                    $msg = "Order dispatched.\nCargo: " . $cargoCompany . "\nTracking: " . $trackingNumber;
                    $ins = $pdo->prepare("INSERT INTO order_messages (order_id, sender_type, sender_name, sender_email, message) VALUES (?, 'tailor', ?, ?, ?)");
                    $ins->execute([$post_order_id, $senderName, $senderEmail, $msg]);
                } catch (Exception $e) {
                }

                try {
                    silah_add_notification(
                        $pdo,
                        'admin',
                        null,
                        'Order Shipped',
                        "Order #" . (isset($row['order_number']) && trim((string)$row['order_number']) !== '' ? (string)$row['order_number'] : ('SIL-' . str_pad((string)$post_order_id, 4, '0', STR_PAD_LEFT))) . " shipped via " . $cargoCompany . " (" . $trackingNumber . ")",
                        'order',
                        'order_details.php?id=' . (int)$post_order_id
                    );
                } catch (Exception $e) {
                }

                try {
                    $customerEmail = isset($row['customer_email']) ? trim((string)$row['customer_email']) : '';
                    if ($customerEmail !== '') {
                        $orderNumber = isset($row['order_number']) && trim((string)$row['order_number']) !== '' ? (string)$row['order_number'] : ('SIL-' . str_pad((string)$post_order_id, 4, '0', STR_PAD_LEFT));
                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
                        $chatLink = $baseUrl . '/order_chat.php?token=' . urlencode((string)($row['chat_token'] ?? ''));
                        $subject = 'Silah: Order shipped (' . $orderNumber . ')';
                        $body =
                            "Hi " . (string)($row['customer_name'] ?? 'Customer') . ",\n\n" .
                            "Your order " . $orderNumber . " has been dispatched.\n\n" .
                            "Cargo: " . $cargoCompany . "\n" .
                            "Tracking Number: " . $trackingNumber . "\n\n" .
                            "Chat link: " . $chatLink . "\n\n" .
                            "Silah Team\n";
                        silah_send_email($customerEmail, $subject, $body);
                    }
                } catch (Exception $e) {
                }

                header("Location: order_details.php?id=" . $post_order_id . "&ship=1");
                exit;
            }
        } catch (Exception $e) {
        }
    }
    header("Location: order_details.php?id=" . $post_order_id . "&ship=0");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message']) && isset($_POST['order_id'])) {
    $post_order_id = (int)$_POST['order_id'];
    $message = isset($_POST['message']) ? trim((string)$_POST['message']) : '';
    if ($post_order_id > 0 && $message !== '') {
        try {
            $stmt = $pdo->prepare("SELECT o.id, o.tailor_id, t.name, t.email FROM orders o LEFT JOIN tailors t ON t.id = o.tailor_id WHERE o.id = ? LIMIT 1");
            $stmt->execute([$post_order_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && (int)$row['tailor_id'] === $tailor_id) {
                $ins = $pdo->prepare("INSERT INTO order_messages (order_id, sender_type, sender_name, sender_email, message) VALUES (?, 'tailor', ?, ?, ?)");
                $ins->execute([$post_order_id, (string)($row['name'] ?? 'Tailor'), (string)($row['email'] ?? ''), $message]);
            }
        } catch (Exception $e) {
        }
    }
    header("Location: order_details.php?id=" . $post_order_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_offer']) && isset($_POST['order_id'])) {
    $post_order_id = (int)$_POST['order_id'];
    $offer_price = isset($_POST['tailor_offer_price']) && is_numeric($_POST['tailor_offer_price']) ? (float)$_POST['tailor_offer_price'] : null;
    $offer_notes = isset($_POST['tailor_offer_notes']) ? trim((string)$_POST['tailor_offer_notes']) : '';
    if ($post_order_id > 0 && $offer_price !== null && $offer_price > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET tailor_offer_price = ?, tailor_offer_notes = ? WHERE id = ? AND tailor_id = ?");
            $stmt->execute([$offer_price, $offer_notes !== '' ? $offer_notes : null, $post_order_id, $tailor_id]);

            $stmt = $pdo->prepare("SELECT t.name, t.email FROM tailors t WHERE t.id = ? LIMIT 1");
            $stmt->execute([$tailor_id]);
            $t = $stmt->fetch(PDO::FETCH_ASSOC);
            $label = $t && isset($t['name']) ? (string)$t['name'] : 'Tailor';
            $tEmail = $t && isset($t['email']) ? (string)$t['email'] : '';

            $msg = "Offer: PKR " . number_format($offer_price);
            if ($offer_notes !== '') {
                $msg .= "\n\n" . $offer_notes;
            }
            $ins = $pdo->prepare("INSERT INTO order_messages (order_id, sender_type, sender_name, sender_email, message) VALUES (?, 'tailor', ?, ?, ?)");
            $ins->execute([$post_order_id, $label, $tEmail, $msg]);
        } catch (Exception $e) {
        }
    }
    header("Location: order_details.php?id=" . $post_order_id . "&offer=1");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $post_order_id = (int)$_POST['order_id'];
    $new_status = (string)$_POST['status'];
    $allowed_statuses = ['Tailor Selected', 'In Progress', 'Completed', 'Shipped'];

    if ($post_order_id > 0 && in_array($new_status, $allowed_statuses, true)) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND tailor_id = ?");
            $stmt->execute([$new_status, $post_order_id, $tailor_id]);

            if ($new_status === 'Completed') {
                try {
                    $stmt = $pdo->prepare("SELECT id, order_number, customer_email, customer_name, chat_token FROM orders WHERE id = ? AND tailor_id = ? LIMIT 1");
                    $stmt->execute([$post_order_id, $tailor_id]);
                    $o = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($o) {
                        $orderNumber = isset($o['order_number']) && trim((string)$o['order_number']) !== '' ? (string)$o['order_number'] : ('SIL-' . str_pad((string)$post_order_id, 4, '0', STR_PAD_LEFT));
                        $eventKey = 'order_completed_' . (int)$post_order_id;
                        if (silah_should_notify($pdo, 'admin', null, $eventKey, 0)) {
                            silah_add_notification(
                                $pdo,
                                'admin',
                                null,
                                'Order Completed',
                                $orderNumber . ' has been marked as Completed by the tailor.',
                                'order',
                                'order_details.php?id=' . (int)$post_order_id
                            );
                            silah_record_notified($pdo, 'admin', null, $eventKey);
                        }

                        try {
                            $stmt = $pdo->query("SELECT id, email FROM admins");
                            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($admins as $a) {
                                $adminId = isset($a['id']) ? (int)$a['id'] : 0;
                                $to = isset($a['email']) ? trim((string)$a['email']) : '';
                                if ($to === '') continue;
                                $subject = 'Silah: Order completed (' . $orderNumber . ')';
                                $body =
                                    "Hi Admin,\n\n" .
                                    "Tailor marked " . $orderNumber . " as Completed.\n\n" .
                                    "Silah Team\n";
                                $emailKey = $eventKey;
                                if (silah_should_email($pdo, 'admin_email', $adminId, $emailKey, 600)) {
                                    silah_send_email($to, $subject, $body);
                                    silah_record_emailed($pdo, 'admin_email', $adminId, $emailKey);
                                }
                            }
                        } catch (Exception $e) {
                        }

                        $customerEmail = isset($o['customer_email']) ? trim((string)$o['customer_email']) : '';
                        if ($customerEmail !== '') {
                            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
                            $chatLink = $baseUrl . '/order_chat.php?token=' . urlencode((string)($o['chat_token'] ?? ''));
                            $subject = 'Silah: Your order is completed (' . $orderNumber . ')';
                            $body =
                                "Hi,\n\n" .
                                "Your order " . $orderNumber . " has been marked as Completed by the tailor.\n\n" .
                                "If you have any concerns, please contact admin.\n\n" .
                                "Open chat: " . $chatLink . "\n\n" .
                                "Silah Team\n";
                            if (silah_should_email($pdo, 'customer_email', (int)$post_order_id, $eventKey, 600)) {
                                silah_send_email($customerEmail, $subject, $body);
                                silah_record_emailed($pdo, 'customer_email', (int)$post_order_id, $eventKey);
                            }
                        }
                    }
                } catch (Exception $e) {
                }
            }
            header("Location: order_details.php?id=" . $post_order_id . "&updated=1");
            exit;
        } catch (Exception $e) {
            header("Location: order_details.php?id=" . $post_order_id . "&updated=0");
            exit;
        }
    }
}

$order = null;
if ($order_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND tailor_id = ?");
        $stmt->execute([$order_id, $tailor_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
    }
}

if (!$order) {
    header("Location: my_orders.php");
    exit;
}

if (!isset($order['chat_token']) || trim((string)$order['chat_token']) === '') {
    $newToken = bin2hex(random_bytes(16));
    try {
        $stmt = $pdo->prepare("UPDATE orders SET chat_token = ? WHERE id = ? AND tailor_id = ?");
        $stmt->execute([$newToken, (int)$order['id'], $tailor_id]);
        $order['chat_token'] = $newToken;
    } catch (Exception $e) {
    }
}

$chatMessages = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM order_messages WHERE order_id = ? ORDER BY created_at ASC, id ASC");
    $stmt->execute([(int)$order['id']]);
    $chatMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $chatMessages = [];
}

include 'header.php';
include 'sidebar.php';
?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="glass-card p-8 mb-8">
            <div class="flex items-center justify-between mb-8 pb-6 border-b border-gray-100">
                <div>
                    <span class="text-[10px] font-black text-primary uppercase tracking-[0.2em]">Order</span>
                    <h2 class="text-3xl font-black text-primary mt-1">#SIL-<?= str_pad((string)$order['id'], 4, '0', STR_PAD_LEFT) ?></h2>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Placed On</p>
                    <p class="text-sm font-bold text-gray-700 mb-0"><?= isset($order['created_at']) ? date('F d, Y • H:i', strtotime($order['created_at'])) : '-' ?></p>
                </div>
            </div>

            <?php if (isset($_GET['updated'])): ?>
                <div class="mb-8 p-4 rounded-2xl border <?= $_GET['updated'] == '1' ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' ?>">
                    <p class="text-xs font-extrabold uppercase tracking-widest mb-1 <?= $_GET['updated'] == '1' ? 'text-green-700' : 'text-red-600' ?>">
                        <?= $_GET['updated'] == '1' ? 'Updated' : 'Update Failed' ?>
                    </p>
                    <p class="text-sm font-semibold mb-0 <?= $_GET['updated'] == '1' ? 'text-green-800' : 'text-red-800' ?>">
                        <?= $_GET['updated'] == '1' ? 'Order status updated successfully.' : 'Could not update the order status.' ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="row g-6 mb-10">
                <div class="col-md-6">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Customer Details</p>
                    <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                        <p class="text-sm font-black text-gray-800 mb-1"><?= htmlspecialchars(isset($order['customer_name']) ? $order['customer_name'] : '') ?></p>
                        <p class="text-xs text-gray-500 mb-1"><?= htmlspecialchars(isset($order['customer_email']) ? $order['customer_email'] : '') ?></p>
                        <p class="text-xs text-gray-500 mb-0"><?= htmlspecialchars(isset($order['customer_phone']) ? $order['customer_phone'] : '') ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Service</p>
                    <div class="p-4 bg-primary/5 rounded-2xl border border-primary/10">
                        <p class="text-sm font-black text-primary mb-1"><?= htmlspecialchars(isset($order['service_type']) ? $order['service_type'] : '') ?></p>
                        <p class="text-xs text-gray-500 mb-0">Budget: <span class="font-bold text-gray-700">PKR <?= number_format((float)(isset($order['budget']) ? $order['budget'] : 0)) ?></span></p>
                    </div>
                </div>
            </div>

            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Notes</p>
            <div class="p-5 bg-white border border-gray-100 rounded-2xl shadow-sm mb-10">
                <p class="text-sm text-gray-600 leading-relaxed mb-0 italic"><?= nl2br(htmlspecialchars(isset($order['notes']) ? $order['notes'] : '')) ?></p>
            </div>

            <?php if (isset($order['measurements']) && $order['measurements']): ?>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Measurements</p>
                <div class="p-5 bg-white border border-gray-100 rounded-2xl shadow-sm mb-10">
                    <?php $mText = str_replace("\\n", "\n", (string)$order['measurements']); ?>
                    <p class="text-sm text-gray-700 leading-relaxed mb-0"><?= nl2br(htmlspecialchars($mText)) ?></p>
                </div>
            <?php endif; ?>

            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Delivery Address</p>
            <div class="p-5 bg-amber-50/50 border border-amber-100 rounded-2xl mb-10">
                <div class="flex gap-3">
                    <i class="fas fa-map-pin text-amber-500 mt-1"></i>
                    <p class="text-sm text-gray-700 leading-relaxed mb-0 font-medium"><?= htmlspecialchars(isset($order['location_details']) ? $order['location_details'] : '') ?></p>
                </div>
            </div>

            <?php if (isset($order['reference_image']) && $order['reference_image']): ?>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Reference Image</p>
                <div class="rounded-2xl overflow-hidden border border-gray-100 shadow-sm max-w-sm relative group">
                    <img src="../<?= htmlspecialchars($order['reference_image']) ?>" class="w-full h-auto hover:scale-105 transition-transform duration-500">
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                        <a href="../<?= htmlspecialchars($order['reference_image']) ?>" target="_blank" class="w-10 h-10 rounded-full bg-white text-primary flex items-center justify-center shadow-xl hover:scale-110 transition-transform">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="glass-card p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-black text-primary mb-1">Bargaining Chat</h3>
                    <p class="text-xs text-gray-500 font-medium mb-0">Negotiate price and details with the customer.</p>
                </div>
                <?php if (isset($order['chat_token']) && trim((string)$order['chat_token']) !== ''): ?>
                    <?php
                        $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
                        $chatUrl = $baseUrl . '/order_chat.php?token=' . urlencode((string)$order['chat_token']);
                    ?>
                    <a href="<?= htmlspecialchars($chatUrl) ?>" target="_blank" class="btn btn-outline !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold no-underline">Customer Link</a>
                <?php endif; ?>
            </div>

            <div class="p-5 bg-primary/5 border border-primary/10 rounded-3xl shadow-sm mb-6" style="max-height: 360px; overflow:auto;">
                <?php if (empty($chatMessages)): ?>
                    <p class="text-sm text-gray-500 mb-0">No messages yet.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($chatMessages as $m): ?>
                            <?php
                                $senderType = isset($m['sender_type']) ? (string)$m['sender_type'] : '';
                                $isTailorMsg = $senderType === 'tailor';
                                $bubbleClass = $isTailorMsg ? 'bg-primary text-white' : 'bg-gray-50 text-gray-800 border border-gray-100';
                                $alignClass = $isTailorMsg ? 'justify-end' : 'justify-start';
                                $name = isset($m['sender_name']) && trim((string)$m['sender_name']) !== '' ? (string)$m['sender_name'] : ucfirst($senderType);
                            ?>
                            <div class="flex <?= $alignClass ?>">
                                <div class="rounded-2xl px-4 py-3 <?= $bubbleClass ?>" style="max-width: 85%;">
                                    <div class="flex justify-between gap-3 mb-1">
                                        <span class="text-[11px] font-extrabold <?= $isTailorMsg ? 'text-white/90' : 'text-gray-500' ?>"><?= htmlspecialchars($name) ?></span>
                                        <span class="text-[10px] <?= $isTailorMsg ? 'text-white/70' : 'text-gray-400' ?>"><?= htmlspecialchars(date('M d, H:i', strtotime((string)$m['created_at']))) ?></span>
                                    </div>
                                    <div class="text-sm leading-relaxed"><?= nl2br(htmlspecialchars((string)$m['message'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <form action="order_details.php?id=<?= (int)$order['id'] ?>" method="POST" class="space-y-3">
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <input type="hidden" name="send_message" value="1">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Message</label>
                    <textarea name="message" class="form-control" rows="3" required></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary rounded-xl py-3 px-5 font-black uppercase tracking-widest text-xs shadow-lg hover:shadow-primary/20 transition-all">Send</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="tailor-right-rail">
        <div class="glass-card p-8 mb-6">
            <h3 class="text-xl font-black text-primary mb-6">Manage Status</h3>
            
            <form action="order_details.php?id=<?= (int)$order['id'] ?>" method="POST" class="space-y-6">
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Current Status</label>
                    <select name="status" class="form-select rounded-xl border-gray-100 bg-gray-50 text-sm font-bold text-gray-700 py-3">
                        <?php
                        $statuses = ['Tailor Selected', 'In Progress', 'Completed', 'Shipped'];
                        foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= isset($order['status']) && $order['status'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs shadow-lg hover:shadow-primary/20 transition-all">Update Status</button>
            </form>

            <div class="mt-8 pt-8 border-t border-gray-100">
                <a href="my_orders.php" class="btn btn-outline w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs no-underline">Back to Orders</a>
            </div>
        </div>

        <div class="glass-card p-8 mb-6">
            <h3 class="text-xl font-black text-primary mb-6">Advance Payment</h3>

            <?php if (isset($_GET['pay'])): ?>
                <div class="mb-6 p-4 rounded-2xl border <?= $_GET['pay'] == '1' ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' ?>">
                    <p class="text-xs font-extrabold uppercase tracking-widest mb-1 <?= $_GET['pay'] == '1' ? 'text-green-700' : 'text-red-600' ?>">
                        <?= $_GET['pay'] == '1' ? 'Updated' : 'Update Failed' ?>
                    </p>
                    <p class="text-sm font-semibold mb-0 <?= $_GET['pay'] == '1' ? 'text-green-800' : 'text-red-800' ?>">
                        <?= $_GET['pay'] == '1' ? 'Payment status updated.' : 'Could not update payment status.' ?>
                    </p>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['pay70'])): ?>
                <div class="mb-6 p-4 rounded-2xl border <?= $_GET['pay70'] == '1' ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' ?>">
                    <p class="text-xs font-extrabold uppercase tracking-widest mb-1 <?= $_GET['pay70'] == '1' ? 'text-green-700' : 'text-red-600' ?>">
                        <?= $_GET['pay70'] == '1' ? 'Updated' : 'Update Failed' ?>
                    </p>
                    <p class="text-sm font-semibold mb-0 <?= $_GET['pay70'] == '1' ? 'text-green-800' : 'text-red-800' ?>">
                        <?= $_GET['pay70'] == '1' ? 'Remaining payment status updated.' : 'Could not update remaining payment status.' ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php
                $tTotal = isset($order['total_price']) && $order['total_price'] !== null && $order['total_price'] !== '' ? (float)$order['total_price'] : (float)(isset($order['budget']) ? $order['budget'] : 0);
                $tAdvance = $tTotal * 0.3;
                $tBalance = max(0, $tTotal - $tAdvance);
                $pStatus = isset($order['payment_status']) && $order['payment_status'] ? (string)$order['payment_status'] : 'Pending';
                $pProof = isset($order['payment_proof_image']) ? trim((string)$order['payment_proof_image']) : '';
                $bStatus = isset($order['balance_payment_status']) && $order['balance_payment_status'] ? (string)$order['balance_payment_status'] : 'Pending';
                $bProof = isset($order['balance_payment_proof_image']) ? trim((string)$order['balance_payment_proof_image']) : '';
                $badge = $pStatus === 'Confirmed'
                    ? 'bg-green-100 text-green-700'
                    : ($pStatus === 'Submitted'
                        ? 'bg-amber-100 text-amber-700'
                        : ($pStatus === 'Rejected'
                            ? 'bg-red-100 text-red-700'
                            : 'bg-gray-100 text-gray-600'));
            ?>

            <div class="p-4 rounded-2xl bg-white border border-gray-100">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">30% Required</p>
                        <p class="text-sm font-black text-primary mb-0">PKR <?= number_format($tAdvance) ?></p>
                        <p class="text-[11px] text-gray-500 mb-0">Total: PKR <?= number_format($tTotal) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Status</p>
                        <span class="text-[10px] font-black uppercase px-3 py-1 rounded-full <?= $badge ?>"><?= htmlspecialchars($pStatus) ?></span>
                    </div>
                </div>

                <?php if ($pProof !== ''): ?>
                    <div class="mt-4">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Payment Proof</p>
                        <div class="rounded-2xl overflow-hidden border border-gray-100">
                            <img src="../<?= htmlspecialchars($pProof) ?>" alt="Payment proof" class="w-full h-auto">
                        </div>
                    </div>

                    <?php if ($pStatus === 'Submitted'): ?>
                        <div class="mt-4 flex flex-wrap gap-2 justify-end">
                            <form method="POST" action="order_details.php?id=<?= (int)$order['id'] ?>">
                                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                <input type="hidden" name="payment_action" value="confirm">
                                <button type="submit" class="btn btn-primary rounded-xl py-2 px-4 text-[10px] font-black uppercase tracking-widest">Confirm</button>
                            </form>
                            <form method="POST" action="order_details.php?id=<?= (int)$order['id'] ?>">
                                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                <input type="hidden" name="payment_action" value="reject">
                                <button type="submit" class="btn btn-outline rounded-xl py-2 px-4 text-[10px] font-black uppercase tracking-widest">Reject</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-sm text-gray-500 mt-4 mb-0">Waiting for customer to upload payment screenshot in chat.</p>
                <?php endif; ?>

                <?php if ($pStatus === 'Confirmed'): ?>
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <?php
                            $bBadge = $bStatus === 'Confirmed'
                                ? 'bg-green-100 text-green-700'
                                : ($bStatus === 'Submitted'
                                    ? 'bg-amber-100 text-amber-700'
                                    : ($bStatus === 'Rejected'
                                        ? 'bg-red-100 text-red-700'
                                        : 'bg-gray-100 text-gray-600'));
                        ?>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">70% Remaining</p>
                                <p class="text-sm font-black text-primary mb-0">PKR <?= number_format($tBalance) ?></p>
                                <p class="text-[11px] text-gray-500 mb-0">Total: PKR <?= number_format($tTotal) ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Status</p>
                                <span class="text-[10px] font-black uppercase px-3 py-1 rounded-full <?= $bBadge ?>"><?= htmlspecialchars($bStatus) ?></span>
                            </div>
                        </div>

                        <?php if ($bProof !== ''): ?>
                            <div class="mt-4">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Payment Proof</p>
                                <div class="rounded-2xl overflow-hidden border border-gray-100">
                                    <img src="../<?= htmlspecialchars($bProof) ?>" alt="Remaining payment proof" class="w-full h-auto">
                                </div>
                            </div>

                            <?php if ($bStatus === 'Submitted'): ?>
                                <div class="mt-4 flex flex-wrap gap-2 justify-end">
                                    <form method="POST" action="order_details.php?id=<?= (int)$order['id'] ?>">
                                        <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                        <input type="hidden" name="payment_action" value="confirm_balance">
                                        <button type="submit" class="btn btn-primary rounded-xl py-2 px-4 text-[10px] font-black uppercase tracking-widest">Confirm</button>
                                    </form>
                                    <form method="POST" action="order_details.php?id=<?= (int)$order['id'] ?>">
                                        <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                        <input type="hidden" name="payment_action" value="reject_balance">
                                        <button type="submit" class="btn btn-outline rounded-xl py-2 px-4 text-[10px] font-black uppercase tracking-widest">Reject</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-sm text-gray-500 mt-4 mb-0">Waiting for customer to upload remaining payment screenshot in chat.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="glass-card p-8 mt-6">
            <h3 class="text-xl font-black text-primary mb-6">Dispatch Details</h3>

            <?php if (isset($_GET['ship'])): ?>
                <div class="mb-6 p-4 rounded-2xl border <?= $_GET['ship'] == '1' ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' ?>">
                    <p class="text-xs font-extrabold uppercase tracking-widest mb-1 <?= $_GET['ship'] == '1' ? 'text-green-700' : 'text-red-600' ?>">
                        <?= $_GET['ship'] == '1' ? 'Saved' : 'Error' ?>
                    </p>
                    <p class="text-sm font-semibold mb-0 <?= $_GET['ship'] == '1' ? 'text-green-800' : 'text-red-800' ?>">
                        <?= $_GET['ship'] == '1' ? 'Dispatch details saved and order marked as Shipped.' : 'Could not save dispatch details. Make sure payments are confirmed.' ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php
                $cargoCompany = isset($order['cargo_company']) ? trim((string)$order['cargo_company']) : '';
                $cargoTrack = isset($order['cargo_tracking_number']) ? trim((string)$order['cargo_tracking_number']) : '';
                $cargoReceipt = isset($order['cargo_receipt_image']) ? trim((string)$order['cargo_receipt_image']) : '';
                $shipAt = isset($order['shipped_at']) ? trim((string)$order['shipped_at']) : '';
                $advanceOk = isset($order['payment_status']) && (string)$order['payment_status'] === 'Confirmed';
                $balanceOk = isset($order['balance_payment_status']) && (string)$order['balance_payment_status'] === 'Confirmed';
            ?>

            <?php if (!$advanceOk || !$balanceOk): ?>
                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-100">
                    <p class="text-sm font-semibold text-amber-900 mb-0">Confirm both 30% and 70% payments before dispatch.</p>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="set_cargo" value="1">
                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">

                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Cargo Name</label>
                        <input type="text" name="cargo_company" class="form-control" value="<?= htmlspecialchars($cargoCompany) ?>" placeholder="e.g. TCS, Leopards, DHL" required>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Cargo Number</label>
                        <input type="text" name="cargo_tracking_number" class="form-control" value="<?= htmlspecialchars($cargoTrack) ?>" placeholder="Tracking / Consignment No." required>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Upload Transcript</label>
                        <input type="file" name="cargo_receipt" class="form-control" accept="image/png,image/jpeg,image/webp">
                        <div class="form-text text-xs">Upload cargo receipt screenshot/photo (JPG/PNG/WEBP, max 2MB).</div>
                    </div>

                    <?php if ($cargoReceipt !== ''): ?>
                        <div class="rounded-2xl overflow-hidden border border-gray-100 bg-white">
                            <img src="../<?= htmlspecialchars($cargoReceipt) ?>" alt="Cargo receipt" class="w-full h-auto">
                        </div>
                    <?php endif; ?>

                    <?php if ($shipAt !== ''): ?>
                        <p class="text-[11px] text-gray-500 mb-0">Shipped: <?= htmlspecialchars(date('M d, Y H:i', strtotime($shipAt))) ?></p>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs shadow-lg hover:shadow-primary/20 transition-all">Save & Mark Shipped</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="glass-card p-8 mt-6">
            <h3 class="text-xl font-black text-primary mb-6">Your Offer</h3>
            <?php if (isset($_GET['offer']) && $_GET['offer'] == '1'): ?>
                <div class="mb-6 p-4 rounded-2xl border bg-green-50 border-green-100">
                    <p class="text-xs font-extrabold text-green-700 uppercase tracking-widest mb-1">Saved</p>
                    <p class="text-sm font-semibold text-green-800 mb-0">Offer sent to customer.</p>
                </div>
            <?php endif; ?>
            <form action="order_details.php?id=<?= (int)$order['id'] ?>" method="POST" class="space-y-4">
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <input type="hidden" name="set_offer" value="1">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Offer Price (PKR)</label>
                    <input type="number" name="tailor_offer_price" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars(isset($order['tailor_offer_price']) ? (string)$order['tailor_offer_price'] : '') ?>" required>
                </div>
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Offer Notes (optional)</label>
                    <textarea name="tailor_offer_notes" class="form-control" rows="4"><?= htmlspecialchars(isset($order['tailor_offer_notes']) ? (string)$order['tailor_offer_notes'] : '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs shadow-lg hover:shadow-primary/20 transition-all">Send Offer</button>
            </form>
        </div>
        </div>
    </div>
</div>

<?php include '../admin/footer.php'; ?>
