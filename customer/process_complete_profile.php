<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: complete_profile.php");
    exit;
}

if (!$pdo) {
    header("Location: complete_profile.php?error=db_error");
    exit;
}

$customerId = (int)($_SESSION['customer_id'] ?? 0);
$phone = isset($_POST['phone']) ? trim((string)$_POST['phone']) : '';
$address = isset($_POST['address']) ? trim((string)$_POST['address']) : '';
$return = isset($_POST['return']) ? trim((string)$_POST['return']) : '';

if ($customerId <= 0 || $phone === '' || $address === '') {
    header("Location: complete_profile.php?error=invalid_input");
    exit;
}

try {
    try { $pdo->exec("ALTER TABLE customers ADD COLUMN address VARCHAR(255)"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE customers ADD UNIQUE KEY uq_customers_phone (phone)"); } catch (Exception $e) {}

    $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ? AND id <> ? LIMIT 1");
    $stmt->execute([$phone, $customerId]);
    if ($stmt->fetch()) {
        header("Location: complete_profile.php?error=phone_exists");
        exit;
    }

    $stmt = $pdo->prepare("UPDATE customers SET phone = ?, address = ? WHERE id = ?");
    $stmt->execute([$phone, $address, $customerId]);

    $safeReturn = $return !== '' &&
        strpos($return, '://') === false &&
        strpos($return, "\n") === false &&
        strpos($return, "\r") === false &&
        strpos($return, '..') === false;

    if ($safeReturn) {
        header("Location: " . $return);
    } else {
        header("Location: orders.php");
    }
    exit;
} catch (Exception $e) {
    header("Location: complete_profile.php?error=db_error");
    exit;
}
?>

