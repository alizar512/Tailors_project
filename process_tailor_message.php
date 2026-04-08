<?php
require_once 'includes/db_connect.php';
require_once 'includes/notifications.php';
require_once 'includes/mailer.php';
require_once 'includes/notifications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$tailor_id = isset($_POST['tailor_id']) && is_numeric($_POST['tailor_id']) ? (int)$_POST['tailor_id'] : 0;
$customer_name = isset($_POST['customer_name']) ? trim((string)$_POST['customer_name']) : '';
$customer_email_raw = isset($_POST['customer_email']) ? trim((string)$_POST['customer_email']) : '';
$customer_email = filter_var($customer_email_raw, FILTER_VALIDATE_EMAIL) ? $customer_email_raw : '';
$customer_phone = isset($_POST['customer_phone']) ? trim((string)$_POST['customer_phone']) : '';
$customer_address = isset($_POST['customer_address']) ? trim((string)$_POST['customer_address']) : '';
$message = isset($_POST['message']) ? trim((string)$_POST['message']) : '';

if ($tailor_id <= 0 || $customer_name === '' || $customer_email === '' || $customer_phone === '' || $customer_address === '' || $message === '') {
    header("Location: tailor_profile.php?id=" . $tailor_id);
    exit;
}

if (!$pdo) {
    header("Location: tailor_profile.php?id=" . $tailor_id);
    exit;
}

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tailor_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tailor_id INT NOT NULL,
            customer_name VARCHAR(100) NOT NULL,
            customer_email VARCHAR(100) NOT NULL,
            customer_phone VARCHAR(30),
            customer_address TEXT,
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $stmt = $pdo->prepare("INSERT INTO tailor_messages (tailor_id, customer_name, customer_email, customer_phone, customer_address, message) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$tailor_id, $customer_name, $customer_email, $customer_phone, $customer_address, $message]);

    try {
        $snippet = function_exists('mb_substr') ? mb_substr($message, 0, 120) : substr($message, 0, 120);
        $eventKey = 'inquiry_tailor_' . (int)$tailor_id;
        if (silah_should_notify($pdo, 'tailor', (int)$tailor_id, $eventKey, 60)) {
            silah_add_notification(
                $pdo,
                'tailor',
                (int)$tailor_id,
                'New Customer Inquiry',
                $customer_name . ': ' . $snippet,
                'tailor',
                'messages.php'
            );
            silah_record_notified($pdo, 'tailor', (int)$tailor_id, $eventKey);
        }

        $stmt = $pdo->prepare("SELECT email, name FROM tailors WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$tailor_id]);
        $t = $stmt->fetch(PDO::FETCH_ASSOC);
        $to = $t && isset($t['email']) ? trim((string)$t['email']) : '';
        if ($to !== '') {
            $name = $t && isset($t['name']) ? (string)$t['name'] : 'Tailor';
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Tailors%20project';
            $link = $baseUrl . '/tailor/messages.php';
            $subject = 'Silah: New customer inquiry';
            $body =
                "Hi " . $name . ",\n\n" .
                "You received a new inquiry:\n\n" .
                "From: " . $customer_name . "\n" .
                "Email: " . $customer_email . "\n" .
                "Phone: " . $customer_phone . "\n\n" .
                $message . "\n\n" .
                "Open inbox: " . $link . "\n\n" .
                "Silah Team\n";
            if (silah_should_email($pdo, 'tailor', (int)$tailor_id, $eventKey, 300)) {
                silah_send_email($to, $subject, $body);
                silah_record_emailed($pdo, 'tailor', (int)$tailor_id, $eventKey);
            }
        }
    } catch (Exception $e) {
    }
} catch (Exception $e) {
}

header("Location: tailor_profile.php?id=" . $tailor_id . "&contact=sent");
exit;
