<?php
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/theme.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit;
}

$credential = isset($_POST['credential']) ? trim((string)$_POST['credential']) : '';
$return = isset($_POST['return']) ? trim((string)$_POST['return']) : '';

if ($credential === '') {
    header("Location: register.php?error=google_failed");
    exit;
}

$clientId = $pdo ? silah_get_setting($pdo, 'google_client_id', '') : '';
if (trim((string)$clientId) === '') {
    $clientId = getenv('GOOGLE_CLIENT_ID') ?: '';
}
if (trim((string)$clientId) === '' || !$pdo) {
    header("Location: register.php?error=google_not_configured");
    exit;
}

$tokeninfoUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
$json = @file_get_contents($tokeninfoUrl);
if ($json === false) {
    header("Location: register.php?error=google_failed");
    exit;
}
$data = json_decode($json, true);
if (!is_array($data)) {
    header("Location: register.php?error=google_failed");
    exit;
}

$aud = isset($data['aud']) ? (string)$data['aud'] : '';
$email = isset($data['email']) ? (string)$data['email'] : '';
$emailVerified = isset($data['email_verified']) ? $data['email_verified'] : '';
$sub = isset($data['sub']) ? (string)$data['sub'] : '';
$name = isset($data['name']) ? (string)$data['name'] : '';
$exp = isset($data['exp']) ? (int)$data['exp'] : 0;

if ($aud !== (string)$clientId || $email === '' || $sub === '' || ($exp > 0 && $exp < time())) {
    header("Location: register.php?error=google_failed");
    exit;
}
if ($emailVerified !== true && $emailVerified !== 'true') {
    header("Location: register.php?error=google_failed");
    exit;
}

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(160) NOT NULL UNIQUE,
            phone VARCHAR(40),
            address VARCHAR(255),
            password_hash VARCHAR(255) NOT NULL,
            auth_provider VARCHAR(20) NOT NULL DEFAULT 'password',
            google_sub VARCHAR(64),
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customers_email (email),
            UNIQUE KEY uq_customers_phone (phone),
            UNIQUE KEY uq_customers_google_sub (google_sub)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    try { $pdo->exec("ALTER TABLE customers ADD COLUMN address VARCHAR(255)"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE customers ADD COLUMN auth_provider VARCHAR(20) NOT NULL DEFAULT 'password'"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE customers ADD COLUMN google_sub VARCHAR(64)"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE customers ADD UNIQUE KEY uq_customers_phone (phone)"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE customers ADD UNIQUE KEY uq_customers_google_sub (google_sub)"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN customer_id INT NULL"); } catch (Exception $e) {}

    $stmt = $pdo->prepare("SELECT * FROM customers WHERE (google_sub IS NOT NULL AND google_sub = ?) OR email = ? LIMIT 1");
    $stmt->execute([$sub, $email]);
    $cust = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cust) {
        $customerId = isset($cust['id']) ? (int)$cust['id'] : 0;
        $custEmail = isset($cust['email']) ? (string)$cust['email'] : $email;
        if ($customerId > 0) {
            $existingSub = isset($cust['google_sub']) ? trim((string)$cust['google_sub']) : '';
            if ($existingSub === '') {
                try {
                    $u = $pdo->prepare("UPDATE customers SET google_sub = ?, auth_provider = 'google' WHERE id = ?");
                    $u->execute([$sub, $customerId]);
                } catch (Exception $e) {
                }
            }
        }
    } else {
        $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO customers (name, email, phone, address, password_hash, auth_provider, google_sub) VALUES (?, ?, NULL, NULL, ?, 'google', ?)");
        $ins->execute([$name !== '' ? $name : 'Customer', $email, $hash, $sub]);
        $customerId = (int)$pdo->lastInsertId();
        $custEmail = $email;
    }

    if (!isset($customerId) || (int)$customerId <= 0) {
        header("Location: register.php?error=google_failed");
        exit;
    }

    try {
        $u = $pdo->prepare("UPDATE orders SET customer_id = ? WHERE (customer_id IS NULL OR customer_id = 0) AND REPLACE(LOWER(TRIM(customer_email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '')");
        $u->execute([(int)$customerId, $custEmail]);
    } catch (Exception $e) {
    }

    session_regenerate_id(true);
    $_SESSION['customer_id'] = (int)$customerId;
    $_SESSION['customer_email'] = $custEmail;
    $_SESSION['role'] = 'customer';
    session_write_close();

    $safeReturn = $return !== '' &&
        strpos($return, '://') === false &&
        strpos($return, "\n") === false &&
        strpos($return, "\r") === false &&
        strpos($return, '..') === false;

    $needsProfile = false;
    try {
        $stmt = $pdo->prepare("SELECT phone, address FROM customers WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$customerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $p = $row && isset($row['phone']) ? trim((string)$row['phone']) : '';
        $a = $row && isset($row['address']) ? trim((string)$row['address']) : '';
        $needsProfile = ($p === '' || $a === '');
    } catch (Exception $e) {
        $needsProfile = true;
    }

    if ($needsProfile) {
        $r = $safeReturn ? $return : '';
        $redir = "complete_profile.php";
        if ($r !== '') {
            $redir .= "?return=" . urlencode($r);
        }
        header("Location: " . $redir);
        exit;
    }

    if ($safeReturn) {
        header("Location: " . $return);
    } else {
        header("Location: orders.php");
    }
    exit;
} catch (Exception $e) {
    header("Location: register.php?error=google_failed");
    exit;
}
?>
