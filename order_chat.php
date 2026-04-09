<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/notifications.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/order_messages.php';

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
$return = isset($_GET['return']) ? trim((string)$_GET['return']) : '';
$new = isset($_GET['new']) && (string)$_GET['new'] === '1';
$order_lookup_id = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$email_lookup_raw = isset($_GET['email']) ? trim((string)$_GET['email']) : '';
$email_lookup = filter_var($email_lookup_raw, FILTER_VALIDATE_EMAIL) ? $email_lookup_raw : '';
$phone_lookup_raw = isset($_GET['phone']) ? trim((string)$_GET['phone']) : '';
$phone_lookup = $phone_lookup_raw;

if (!$pdo) {
    header("Location: index.php");
    exit;
}

silah_ensure_order_messages_table($pdo);

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

$lookup_error = '';
if ($token === '' && $order_lookup_id > 0 && $email_lookup !== '') {
    try {
        $stmt = $pdo->prepare("SELECT id, chat_token, customer_email FROM orders WHERE id = ? LIMIT 1");
        $stmt->execute([$order_lookup_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $dbEmail = isset($row['customer_email']) ? (string)$row['customer_email'] : '';
            $normDb = str_replace(' ', '', strtolower(trim($dbEmail)));
            $normIn = str_replace(' ', '', strtolower(trim($email_lookup)));
            if ($normDb === $normIn) {
                $ct = isset($row['chat_token']) ? trim((string)$row['chat_token']) : '';
                if ($ct === '') {
                    $ct = bin2hex(random_bytes(16));
                    try {
                        $u = $pdo->prepare("UPDATE orders SET chat_token = ? WHERE id = ?");
                        $u->execute([$ct, (int)$row['id']]);
                    } catch (Exception $e) {
                    }
                }
                $redir = "order_chat.php?token=" . urlencode($ct);
                if ($return !== '') {
                    $redir .= "&return=" . urlencode($return);
                }
                header("Location: " . $redir);
                exit;
            }
        }
        $lookup_error = 'Order not found for this email.';
    } catch (Exception $e) {
        $lookup_error = 'Could not open chat.';
    }
}

$is_lookup_mode = $token === '';

$order = null;
if (!$is_lookup_mode) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE chat_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
    }
    if (!$order) {
        header("Location: index.php");
        exit;
    }
}

if (!$is_lookup_mode && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_payment'])) {
    $ok = 0;
    if (isset($_FILES['payment_proof']) && isset($_FILES['payment_proof']['tmp_name']) && is_uploaded_file($_FILES['payment_proof']['tmp_name'])) {
        $tmp = $_FILES['payment_proof']['tmp_name'];
        $orig = isset($_FILES['payment_proof']['name']) ? (string)$_FILES['payment_proof']['name'] : 'proof';
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed, true)) {
            $dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'payments' . DIRECTORY_SEPARATOR;
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            $fileName = uniqid('pay_', true) . '.' . $ext;
            $destAbs = $dir . $fileName;
            $destRel = 'uploads/payments/' . $fileName;
            if (@move_uploaded_file($tmp, $destAbs)) {
                try {
                    $stmt = $pdo->prepare("UPDATE orders SET payment_proof_image = ?, payment_status = 'Submitted', payment_submitted_at = NOW(), payment_confirmed_at = NULL WHERE id = ?");
                    $stmt->execute([$destRel, (int)$order['id']]);
                    $order['payment_proof_image'] = $destRel;
                    $order['payment_status'] = 'Submitted';
                    $ok = 1;
                } catch (Exception $e) {
                }
                try {
                    $msg = "Payment proof submitted (30% advance).";
                    $ins = $pdo->prepare("INSERT INTO order_messages (order_id, sender_type, sender_name, sender_email, message) VALUES (?, 'customer', ?, ?, ?)");
                    $ins->execute([
                        (int)$order['id'],
                        (string)($order['customer_name'] ?? 'Customer'),
                        (string)($order['customer_email'] ?? ''),
                        $msg
                    ]);
                } catch (Exception $e) {
                }

                try {
                    $tailorId = isset($order['tailor_id']) ? (int)$order['tailor_id'] : 0;
                    if ($tailorId > 0) {
                        $orderNumber = isset($order['order_number']) && trim((string)$order['order_number']) !== '' ? (string)$order['order_number'] : ('SIL-' . str_pad((string)$order['id'], 4, '0', STR_PAD_LEFT));
                        $eventKey = 'payment_submitted_order_' . (int)$order['id'];
                        if (silah_should_notify($pdo, 'tailor', $tailorId, $eventKey, 0)) {
                            silah_add_notification(
                                $pdo,
                                'tailor',
                                $tailorId,
                                'Payment Proof Submitted',
                                $orderNumber . ': Customer uploaded 30% payment screenshot.',
                                'order',
                                'order_details.php?id=' . (int)$order['id']
                            );
                            silah_record_notified($pdo, 'tailor', $tailorId, $eventKey);
                        }

                        $stmt = $pdo->prepare("SELECT email, name FROM tailors WHERE id = ? LIMIT 1");
                        $stmt->execute([$tailorId]);
                        $t = $stmt->fetch(PDO::FETCH_ASSOC);
                        $to = $t && isset($t['email']) ? trim((string)$t['email']) : '';
                        if ($to !== '') {
                            $name = $t && isset($t['name']) ? (string)$t['name'] : 'Tailor';
                            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
                            $link = $baseUrl . '/tailor/order_details.php?id=' . (int)$order['id'];
                            $subject = 'Silah: Payment proof submitted (' . $orderNumber . ')';
                            $body =
                                "Hi " . $name . ",\n\n" .
                                "Customer uploaded a 30% advance payment screenshot for " . $orderNumber . ".\n\n" .
                                "Open order: " . $link . "\n\n" .
                                "Silah Team\n";
                            if (silah_should_email($pdo, 'tailor', $tailorId, $eventKey, 0)) {
                                silah_send_email($to, $subject, $body);
                                silah_record_emailed($pdo, 'tailor', $tailorId, $eventKey);
                            }
                        }
                    }
                } catch (Exception $e) {
                }
            }
        }
    }

    $redir = "order_chat.php?token=" . urlencode($token) . "&paid=" . ($ok ? "1" : "0");
    if ($return !== '') {
        $redir .= "&return=" . urlencode($return);
    }
    header("Location: " . $redir);
    exit;
}

if (!$is_lookup_mode && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_balance_payment'])) {
    $ok = 0;

    $advanceConfirmed = isset($order['payment_status']) && (string)$order['payment_status'] === 'Confirmed';
    if (!$advanceConfirmed) {
        header("Location: order_chat.php?token=" . urlencode($token) . "&paid70=0");
        exit;
    }

    if (isset($_FILES['balance_payment_proof']) && isset($_FILES['balance_payment_proof']['tmp_name']) && is_uploaded_file($_FILES['balance_payment_proof']['tmp_name'])) {
        $tmp = $_FILES['balance_payment_proof']['tmp_name'];
        $orig = isset($_FILES['balance_payment_proof']['name']) ? (string)$_FILES['balance_payment_proof']['name'] : 'proof';
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed, true)) {
            $dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'payments' . DIRECTORY_SEPARATOR;
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            $fileName = uniqid('pay70_', true) . '.' . $ext;
            $destAbs = $dir . $fileName;
            $destRel = 'uploads/payments/' . $fileName;
            if (@move_uploaded_file($tmp, $destAbs)) {
                try {
                    $stmt = $pdo->prepare("UPDATE orders SET balance_payment_proof_image = ?, balance_payment_status = 'Submitted', balance_payment_submitted_at = NOW(), balance_payment_confirmed_at = NULL WHERE id = ?");
                    $stmt->execute([$destRel, (int)$order['id']]);
                    $order['balance_payment_proof_image'] = $destRel;
                    $order['balance_payment_status'] = 'Submitted';
                    $ok = 1;
                } catch (Exception $e) {
                }

                try {
                    $msg = "Payment proof submitted (70% remaining).";
                    $ins = $pdo->prepare("INSERT INTO order_messages (order_id, sender_type, sender_name, sender_email, message) VALUES (?, 'customer', ?, ?, ?)");
                    $ins->execute([
                        (int)$order['id'],
                        (string)($order['customer_name'] ?? 'Customer'),
                        (string)($order['customer_email'] ?? ''),
                        $msg
                    ]);
                } catch (Exception $e) {
                }

                try {
                    $tailorId = isset($order['tailor_id']) ? (int)$order['tailor_id'] : 0;
                    if ($tailorId > 0) {
                        $orderNumber = isset($order['order_number']) && trim((string)$order['order_number']) !== '' ? (string)$order['order_number'] : ('SIL-' . str_pad((string)$order['id'], 4, '0', STR_PAD_LEFT));
                        $eventKey = 'balance_payment_submitted_order_' . (int)$order['id'];

                        if (silah_should_notify($pdo, 'tailor', $tailorId, $eventKey, 0)) {
                            silah_add_notification(
                                $pdo,
                                'tailor',
                                $tailorId,
                                'Remaining Payment Proof Submitted',
                                $orderNumber . ': Customer uploaded 70% remaining payment screenshot.',
                                'order',
                                'order_details.php?id=' . (int)$order['id']
                            );
                            silah_record_notified($pdo, 'tailor', $tailorId, $eventKey);
                        }

                        $stmt = $pdo->prepare("SELECT email, name FROM tailors WHERE id = ? LIMIT 1");
                        $stmt->execute([$tailorId]);
                        $t = $stmt->fetch(PDO::FETCH_ASSOC);
                        $to = $t && isset($t['email']) ? trim((string)$t['email']) : '';
                        if ($to !== '') {
                            $name = $t && isset($t['name']) ? (string)$t['name'] : 'Tailor';
                            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
                            $link = $baseUrl . '/tailor/order_details.php?id=' . (int)$order['id'];
                            $subject = 'Silah: Remaining payment proof submitted (' . $orderNumber . ')';
                            $body =
                                "Hi " . $name . ",\n\n" .
                                "Customer uploaded the 70% remaining payment screenshot for " . $orderNumber . ".\n\n" .
                                "Open order: " . $link . "\n\n" .
                                "Silah Team\n";
                            if (silah_should_email($pdo, 'tailor', $tailorId, $eventKey, 0)) {
                                silah_send_email($to, $subject, $body);
                                silah_record_emailed($pdo, 'tailor', $tailorId, $eventKey);
                            }
                        }
                    }
                } catch (Exception $e) {
                }
            }
        }
    }

    $redir = "order_chat.php?token=" . urlencode($token) . "&paid70=" . ($ok ? "1" : "0");
    if ($return !== '') {
        $redir .= "&return=" . urlencode($return);
    }
    header("Location: " . $redir);
    exit;
}

if (!$is_lookup_mode && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = isset($_POST['message']) ? trim((string)$_POST['message']) : '';
    if ($message !== '') {
        $inserted = false;
        try {
            $ins = $pdo->prepare("INSERT INTO order_messages (order_id, sender_type, sender_name, sender_email, message) VALUES (?, 'customer', ?, ?, ?)");
            $ins->execute([
                (int)$order['id'],
                (string)($order['customer_name'] ?? 'Customer'),
                (string)($order['customer_email'] ?? ''),
                $message
            ]);
            $inserted = true;
        } catch (Exception $e) {
        }

        if (!$inserted) {
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
                $ins = $pdo->prepare("INSERT INTO order_messages (order_id, sender_type, sender_name, sender_email, message) VALUES (?, 'customer', ?, ?, ?)");
                $ins->execute([
                    (int)$order['id'],
                    (string)($order['customer_name'] ?? 'Customer'),
                    (string)($order['customer_email'] ?? ''),
                    $message
                ]);
            } catch (Exception $e) {
            }
        }

        try {
            $tailorId = isset($order['tailor_id']) ? (int)$order['tailor_id'] : 0;
            if ($tailorId > 0) {
                $orderNumber = isset($order['order_number']) && trim((string)$order['order_number']) !== '' ? (string)$order['order_number'] : ('SIL-' . str_pad((string)$order['id'], 4, '0', STR_PAD_LEFT));
                $snippet = function_exists('mb_substr') ? mb_substr($message, 0, 120) : substr($message, 0, 120);
                $eventKey = 'chat_order_' . (int)$order['id'];
                if (silah_should_notify($pdo, 'tailor', $tailorId, $eventKey, 30)) {
                    silah_add_notification(
                        $pdo,
                        'tailor',
                        $tailorId,
                        'New Chat Message',
                        $orderNumber . ': ' . $snippet,
                        'order',
                        'order_details.php?id=' . (int)$order['id']
                    );
                    silah_record_notified($pdo, 'tailor', $tailorId, $eventKey);
                }

                $stmt = $pdo->prepare("SELECT email, name FROM tailors WHERE id = ? LIMIT 1");
                $stmt->execute([$tailorId]);
                $t = $stmt->fetch(PDO::FETCH_ASSOC);
                $to = $t && isset($t['email']) ? trim((string)$t['email']) : '';
                if ($to !== '') {
                    $name = $t && isset($t['name']) ? (string)$t['name'] : 'Tailor';
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
                    $link = $baseUrl . '/tailor/order_details.php?id=' . (int)$order['id'];
                    $subject = 'Silah: New message (' . $orderNumber . ')';
                    $body =
                        "Hi " . $name . ",\n\n" .
                        "You received a new message from customer for " . $orderNumber . ":\n\n" .
                        $message . "\n\n" .
                        "Open chat: " . $link . "\n\n" .
                        "Silah Team\n";
                    if (silah_should_email($pdo, 'tailor', $tailorId, $eventKey, 120)) {
                        silah_send_email($to, $subject, $body);
                        silah_record_emailed($pdo, 'tailor', $tailorId, $eventKey);
                    }
                }
            }
        } catch (Exception $e) {
        }
    }
    $redir = "order_chat.php?token=" . urlencode($token);
    if ($return !== '') {
        $redir .= "&return=" . urlencode($return);
    }
    header("Location: " . $redir);
    exit;
}

$tailor = null;
try {
    if (isset($order['tailor_id']) && (int)$order['tailor_id'] > 0) {
        $stmt = $pdo->prepare("SELECT id, name, email FROM tailors WHERE id = ?");
        $stmt->execute([(int)$order['tailor_id']]);
        $tailor = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
}

$messages = [];
if (!$is_lookup_mode) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM order_messages WHERE order_id = ? ORDER BY created_at ASC, id ASC");
        $stmt->execute([(int)$order['id']]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $messages = [];
    }
}

$title = $is_lookup_mode ? 'Open Your Chat' : ($tailor && isset($tailor['name']) ? ('Chat with ' . (string)$tailor['name']) : 'Chat about your order');
$totalPrice = 0.0;
$advanceAmount = 0.0;
$balanceAmount = 0.0;
$paymentStatus = 'Pending';
$paymentProof = '';
$balanceStatus = 'Pending';
$balanceProof = '';
if (!$is_lookup_mode && $order) {
    $totalPrice = isset($order['total_price']) && $order['total_price'] !== null && $order['total_price'] !== '' ? (float)$order['total_price'] : (isset($order['budget']) ? (float)$order['budget'] : 0.0);
    $advanceAmount = $totalPrice * 0.3;
    $balanceAmount = max(0, $totalPrice - $advanceAmount);
    $paymentStatus = isset($order['payment_status']) && $order['payment_status'] ? (string)$order['payment_status'] : 'Pending';
    $paymentProof = isset($order['payment_proof_image']) ? trim((string)$order['payment_proof_image']) : '';
    $balanceStatus = isset($order['balance_payment_status']) && $order['balance_payment_status'] ? (string)$order['balance_payment_status'] : 'Pending';
    $balanceProof = isset($order['balance_payment_proof_image']) ? trim((string)$order['balance_payment_proof_image']) : '';
}
$emailOrders = [];
$hasEmailLookup = $is_lookup_mode && $email_lookup !== '';
$hasEmailPhoneLookup = $hasEmailLookup && $phone_lookup !== '';
$norm = function ($v) {
    $v = strtolower(trim((string)$v));
    $v = str_replace(' ', '', $v);
    return $v;
};
$normPhone = function ($v) {
    $v = trim((string)$v);
    $v = str_replace([' ', '-', '+', '(', ')'], '', $v);
    return $v;
};

if ($is_lookup_mode && $lookup_error === '' && $hasEmailLookup) {
    try {
        $stmt = $pdo->prepare("SELECT id, customer_email, customer_phone, service_type, status, created_at, chat_token FROM orders WHERE REPLACE(LOWER(TRIM(customer_email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '') ORDER BY id DESC LIMIT 20");
        $stmt->execute([$email_lookup]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $inPhone = $normPhone($phone_lookup);
        foreach ($rows as $r) {
            if ($hasEmailPhoneLookup) {
                $dbPhone = isset($r['customer_phone']) ? (string)$r['customer_phone'] : '';
                if ($inPhone === '' || $normPhone($dbPhone) !== $inPhone) {
                    continue;
                }
            }

            $ct = isset($r['chat_token']) ? trim((string)$r['chat_token']) : '';
            if ($ct === '') {
                $ct = bin2hex(random_bytes(16));
                try {
                    $u = $pdo->prepare("UPDATE orders SET chat_token = ? WHERE id = ?");
                    $u->execute([$ct, (int)$r['id']]);
                } catch (Exception $e) {
                }
            }

            $emailOrders[] = [
                'id' => (int)$r['id'],
                'service_type' => (string)($r['service_type'] ?? ''),
                'status' => (string)($r['status'] ?? ''),
                'created_at' => (string)($r['created_at'] ?? ''),
                'chat_token' => $ct,
            ];
        }
    } catch (Exception $e) {
        $emailOrders = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string)$title) ?> | Silah</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-bg text-text">
    <div class="container py-5">
        <div class="max-w-4xl mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-primary mb-1"><?= htmlspecialchars((string)$title) ?></h1>
                    <?php if (!$is_lookup_mode): ?>
                        <p class="text-sm text-gray-500 mb-0">Order #SIL-<?= str_pad((string)$order['id'], 4, '0', STR_PAD_LEFT) ?> • <?= htmlspecialchars((string)($order['status'] ?? '')) ?></p>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 mb-0">Reopen your bargaining chat using email (and phone for better security).</p>
                    <?php endif; ?>
                </div>
                <?php if ($return !== ''): ?>
                    <a href="<?= htmlspecialchars((string)$return) ?>" class="btn btn-outline rounded-full px-4 py-2">Back</a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-outline rounded-full px-4 py-2">Home</a>
                <?php endif; ?>
            </div>

            <?php if ($new): ?>
                <div class="mb-4 p-4 rounded-3xl bg-green-50 border border-green-100">
                    <p class="text-sm font-semibold text-green-800 mb-0">
                        Request submitted. Your order number is <span class="font-black">SIL-<?= str_pad((string)$order['id'], 4, '0', STR_PAD_LEFT) ?></span>. Use this chat to negotiate price and details.
                    </p>
                </div>
            <?php endif; ?>

            <?php if (!$is_lookup_mode): ?>
                <div class="mb-4 p-4 rounded-3xl bg-amber-50 border border-amber-100">
                    <p class="text-sm font-semibold text-amber-900 mb-1">Notice</p>
                    <p class="text-sm text-amber-900 mb-0">
                        Admin can also read all messages for safety and support. If you want to complain about any tailor, please contact the admin.
                    </p>
                </div>
            <?php endif; ?>

            <?php if (!$is_lookup_mode): ?>
                <?php if (isset($_GET['paid'])): ?>
                    <div class="mb-4 p-4 rounded-3xl <?= $_GET['paid'] == '1' ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' ?> border">
                        <p class="text-sm font-semibold mb-0 <?= $_GET['paid'] == '1' ? 'text-green-800' : 'text-red-800' ?>">
                            <?= $_GET['paid'] == '1' ? 'Payment proof uploaded successfully.' : 'Could not upload payment proof. Please try again.' ?>
                        </p>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['paid70'])): ?>
                    <div class="mb-4 p-4 rounded-3xl <?= $_GET['paid70'] == '1' ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' ?> border">
                        <p class="text-sm font-semibold mb-0 <?= $_GET['paid70'] == '1' ? 'text-green-800' : 'text-red-800' ?>">
                            <?= $_GET['paid70'] == '1' ? 'Remaining payment proof uploaded successfully.' : 'Could not upload remaining payment proof. Please try again.' ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="mb-4 p-4 rounded-3xl bg-white border border-gray-100">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Advance Payment (30%)</p>
                            <p class="text-sm text-gray-700 mb-1">Required Advance: <span class="font-black text-primary">PKR <?= number_format($advanceAmount) ?></span></p>
                            <p class="text-xs text-gray-500 mb-0">Total: PKR <?= number_format($totalPrice) ?></p>
                        </div>
                        <div class="text-end">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Status</p>
                            <span class="text-[10px] font-black uppercase px-3 py-1 rounded-full <?= $paymentStatus === 'Confirmed' ? 'bg-green-100 text-green-700' : ($paymentStatus === 'Submitted' ? 'bg-amber-100 text-amber-700' : ($paymentStatus === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600')) ?>">
                                <?= htmlspecialchars((string)$paymentStatus) ?>
                            </span>
                        </div>
                    </div>

                    <?php $showAdvanceUpload = ($paymentStatus !== 'Confirmed') && ($paymentProof === '' || $paymentStatus === 'Rejected'); ?>
                    <?php if ($paymentProof !== ''): ?>
                        <div class="mt-4">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Payment Screenshot</p>
                            <div class="rounded-3xl overflow-hidden border border-gray-100">
                                <img src="<?= htmlspecialchars((string)$paymentProof) ?>" alt="Payment proof" class="w-100" style="max-height: 420px; object-fit: contain; background: #fff;">
                            </div>
                            <?php if ($paymentStatus === 'Rejected'): ?>
                                <p class="text-xs text-red-700 mt-3 mb-0">Payment was rejected. Please upload a new screenshot.</p>
                            <?php elseif ($paymentStatus !== 'Confirmed'): ?>
                                <p class="text-xs text-gray-500 mt-3 mb-0">Tailor will confirm your payment after review.</p>
                            <?php else: ?>
                                <p class="text-xs text-green-700 mt-3 mb-0">Advance payment confirmed.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($showAdvanceUpload): ?>
                        <div class="mt-4">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Upload Payment Screenshot</p>
                            <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                                <input type="hidden" name="upload_payment" value="1">
                                <div class="col-md-8">
                                    <input type="file" name="payment_proof" class="form-control" accept="image/png,image/jpeg,image/webp" required>
                                    <div class="form-text text-xs">Upload screenshot of the 30% advance payment.</div>
                                </div>
                                <div class="col-md-4 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary rounded-full px-5 py-2.5 font-bold">Upload</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($paymentStatus === 'Confirmed'): ?>
                    <div class="mb-4 p-4 rounded-3xl bg-white border border-gray-100">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Remaining Payment (70%)</p>
                                <p class="text-sm text-gray-700 mb-1">Remaining Amount: <span class="font-black text-primary">PKR <?= number_format($balanceAmount) ?></span></p>
                                <p class="text-xs text-gray-500 mb-0">Total: PKR <?= number_format($totalPrice) ?></p>
                            </div>
                            <div class="text-end">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Status</p>
                                <span class="text-[10px] font-black uppercase px-3 py-1 rounded-full <?= $balanceStatus === 'Confirmed' ? 'bg-green-100 text-green-700' : ($balanceStatus === 'Submitted' ? 'bg-amber-100 text-amber-700' : ($balanceStatus === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600')) ?>">
                                    <?= htmlspecialchars((string)$balanceStatus) ?>
                                </span>
                            </div>
                        </div>

                        <?php $showBalanceUpload = ($balanceStatus !== 'Confirmed') && ($balanceProof === '' || $balanceStatus === 'Rejected'); ?>
                        <?php if ($balanceProof !== ''): ?>
                            <div class="mt-4">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Payment Screenshot</p>
                                <div class="rounded-3xl overflow-hidden border border-gray-100">
                                    <img src="<?= htmlspecialchars((string)$balanceProof) ?>" alt="Remaining payment proof" class="w-100" style="max-height: 420px; object-fit: contain; background: #fff;">
                                </div>
                                <?php if ($balanceStatus === 'Rejected'): ?>
                                    <p class="text-xs text-red-700 mt-3 mb-0">Payment was rejected. Please upload a new screenshot.</p>
                                <?php elseif ($balanceStatus !== 'Confirmed'): ?>
                                    <p class="text-xs text-gray-500 mt-3 mb-0">Tailor will confirm your payment after review.</p>
                                <?php else: ?>
                                    <p class="text-xs text-green-700 mt-3 mb-0">Remaining payment confirmed.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($showBalanceUpload): ?>
                            <div class="mt-4">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Upload Payment Screenshot</p>
                                <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                                    <input type="hidden" name="upload_balance_payment" value="1">
                                    <div class="col-md-8">
                                        <input type="file" name="balance_payment_proof" class="form-control" accept="image/png,image/jpeg,image/webp" required>
                                        <div class="form-text text-xs">Upload screenshot of the 70% remaining payment.</div>
                                    </div>
                                    <div class="col-md-4 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary rounded-full px-5 py-2.5 font-bold">Upload</button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($is_lookup_mode): ?>
                <?php if ($lookup_error !== ''): ?>
                    <div class="mb-4 p-4 rounded-3xl bg-red-50 border border-red-100">
                        <p class="text-sm font-semibold text-red-800 mb-0"><?= htmlspecialchars((string)$lookup_error) ?></p>
                    </div>
                <?php endif; ?>
                <div class="glass-card p-4 p-md-5">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-sm font-semibold text-gray-600">Order Number</label>
                            <input type="number" name="order_id" class="form-control" placeholder="e.g. 12" min="1" required>
                            <div class="form-text text-xs">Use the number from SIL-0000 shown in chat.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-sm font-semibold text-gray-600">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                        </div>
                        <?php if ($return !== ''): ?>
                            <input type="hidden" name="return" value="<?= htmlspecialchars((string)$return) ?>">
                        <?php endif; ?>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary rounded-full px-5 py-2.5 font-bold">Open Chat</button>
                        </div>
                    </form>
                </div>

                <div class="mt-4 glass-card p-4 p-md-5">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Forgot Order Number?</p>
                    <p class="text-sm text-gray-600 mb-4">Enter your email and phone number to see your recent chats.</p>

                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-sm font-semibold text-gray-600">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars((string)$email_lookup) ?>" placeholder="your@email.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-sm font-semibold text-gray-600">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars((string)$phone_lookup) ?>" placeholder="+92xxxxxxxxxx" required>
                        </div>
                        <?php if ($return !== ''): ?>
                            <input type="hidden" name="return" value="<?= htmlspecialchars((string)$return) ?>">
                        <?php endif; ?>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-outline rounded-full px-5 py-2.5 font-bold">Find My Chats</button>
                        </div>
                    </form>

                    <?php if ($hasEmailPhoneLookup): ?>
                        <?php if (empty($emailOrders)): ?>
                            <p class="text-sm text-gray-500 mb-0">No chats found for this email and phone.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Order #</th>
                                            <th class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Services</th>
                                            <th class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Status</th>
                                            <th class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Date</th>
                                            <th class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
                                        ?>
                                        <?php foreach ($emailOrders as $o): ?>
                                            <tr>
                                                <td class="border-0">
                                                    <span class="text-sm font-black text-gray-800">SIL-<?= str_pad((string)$o['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                                </td>
                                                <td class="border-0">
                                                    <span class="text-sm text-gray-700"><?= htmlspecialchars((string)$o['service_type']) ?></span>
                                                </td>
                                                <td class="border-0">
                                                    <span class="text-sm text-gray-700"><?= htmlspecialchars((string)$o['status']) ?></span>
                                                </td>
                                                <td class="border-0">
                                                    <span class="text-sm text-gray-500"><?= htmlspecialchars((string)date('M d, Y', strtotime($o['created_at']))) ?></span>
                                                </td>
                                                <td class="border-0 text-end">
                                                    <?php
                                                        $link = $baseUrl . '/order_chat.php?token=' . urlencode($o['chat_token']);
                                                        if ($return !== '') {
                                                            $link .= '&return=' . urlencode($return);
                                                        }
                                                    ?>
                                                    <a class="btn btn-primary rounded-full px-4 py-2 font-bold" href="<?= htmlspecialchars((string)$link) ?>">Open</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
                    $chatLink = $baseUrl . '/order_chat.php?token=' . urlencode((string)$order['chat_token']);
                    $reopenLink = $baseUrl . '/order_chat.php?order_id=' . urlencode((string)$order['id']) . '&email=' . urlencode((string)$order['customer_email']);
                ?>
                <div class="mb-4 p-4 rounded-3xl bg-white border border-gray-100">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Reopen This Chat Later</p>
                    <div class="row g-2 align-items-end">
                        <div class="col-12">
                            <label class="form-label text-sm font-semibold text-gray-600">Chat Link</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?= htmlspecialchars((string)$chatLink) ?>" readonly id="chatLinkInput">
                                <button type="button" class="btn btn-outline" id="copyChatLinkBtn">Copy</button>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-sm font-semibold text-gray-600">Reopen Link (Order + Email)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?= htmlspecialchars((string)$reopenLink) ?>" readonly id="reopenLinkInput">
                                <button type="button" class="btn btn-outline" id="copyReopenLinkBtn">Copy</button>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-3 mb-0">Save this link or copy it to WhatsApp/Notes.</p>
                </div>
            <?php endif; ?>

            <?php if (!$is_lookup_mode): ?>
            <div class="glass-card p-4 p-md-5 mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Service</p>
                        <p class="text-sm font-bold text-gray-800 mb-0"><?= htmlspecialchars((string)($order['service_type'] ?? '')) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Your Budget</p>
                        <p class="text-sm font-bold text-gray-800 mb-0">PKR <?= number_format(isset($order['budget']) ? (float)$order['budget'] : 0) ?></p>
                    </div>
                    <div class="col-12">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Delivery Address</p>
                        <p class="text-sm text-gray-700 mb-0"><?= htmlspecialchars((string)($order['location_details'] ?? '')) ?></p>
                    </div>
                    <?php if (isset($order['tailor_offer_price']) && $order['tailor_offer_price'] !== null && (float)$order['tailor_offer_price'] > 0): ?>
                        <div class="col-12 mt-2">
                            <div class="p-3 rounded-3xl bg-primary/5 border border-primary/10">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tailor Offer</p>
                                <p class="text-sm font-black text-primary mb-0">PKR <?= number_format((float)$order['tailor_offer_price']) ?></p>
                                <?php if (isset($order['tailor_offer_notes']) && trim((string)$order['tailor_offer_notes']) !== ''): ?>
                                    <p class="text-sm text-gray-600 mb-0 mt-1"><?= nl2br(htmlspecialchars((string)$order['tailor_offer_notes'])) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="glass-card p-4 p-md-5">
                <div class="mb-4" style="max-height: 420px; overflow:auto;">
                    <?php if (empty($messages)): ?>
                        <p class="text-sm text-gray-500 mb-0">No messages yet. Send a message to start bargaining.</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($messages as $m): ?>
                                <?php
                                    $senderType = isset($m['sender_type']) ? (string)$m['sender_type'] : '';
                                    $isCustomer = $senderType === 'customer';
                                    $bubbleClass = $isCustomer ? 'bg-primary text-white' : 'bg-gray-50 text-gray-800 border border-gray-100';
                                    $alignClass = $isCustomer ? 'justify-content-end' : 'justify-content-start';
                                    $name = isset($m['sender_name']) && trim((string)$m['sender_name']) !== '' ? (string)$m['sender_name'] : ucfirst($senderType);
                                ?>
                                <div class="d-flex <?= $alignClass ?>">
                                    <div class="rounded-4 px-4 py-3 <?= $bubbleClass ?>" style="max-width: 85%;">
                                        <div class="d-flex justify-content-between gap-3 mb-1">
                                            <span class="text-[11px] font-extrabold <?= $isCustomer ? 'text-white/90' : 'text-gray-500' ?>"><?= htmlspecialchars((string)$name) ?></span>
                                            <span class="text-[10px] <?= $isCustomer ? 'text-white/70' : 'text-gray-400' ?>"><?= htmlspecialchars((string)date('M d, H:i', strtotime((string)$m['created_at']))) ?></span>
                                        </div>
                                        <?php $msgText = str_replace("\\n", "\n", (string)$m['message']); ?>
                                        <div class="text-sm leading-relaxed"><?= nl2br(htmlspecialchars((string)$msgText)) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST" class="row g-2 align-items-end">
                    <div class="col-12">
                        <label class="form-label text-sm font-semibold text-gray-600">Your Message</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Write your offer / questions..." required></textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary rounded-full px-5 py-2.5 font-bold">Send</button>
                    </div>
                </form>
            </div>

            <p class="text-xs text-gray-400 mt-4 mb-0">Keep this link safe. Anyone with this link can view the chat.</p>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            const copy = async (inputId) => {
                const el = document.getElementById(inputId);
                if (!el) return;
                el.focus();
                el.select();
                try {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        await navigator.clipboard.writeText(el.value);
                    } else {
                        document.execCommand('copy');
                    }
                } catch (e) {
                }
            };

            const btn1 = document.getElementById('copyChatLinkBtn');
            if (btn1) btn1.addEventListener('click', () => copy('chatLinkInput'));
            const btn2 = document.getElementById('copyReopenLinkBtn');
            if (btn2) btn2.addEventListener('click', () => copy('reopenLinkInput'));
        })();
    </script>
</body>
</html>
