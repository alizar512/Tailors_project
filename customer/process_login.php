<?php
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$emailRaw = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
$email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL) ? $emailRaw : '';
$password = isset($_POST['password']) ? (string)$_POST['password'] : '';
$return = isset($_POST['return']) ? trim((string)$_POST['return']) : '';

if ($email === '' || $password === '') {
    $redir = "login.php?error=invalid_credentials";
    if ($return !== '') {
        $redir .= "&return=" . urlencode($return);
    }
    header("Location: " . $redir);
    exit;
}

if (!$pdo) {
    $redir = "login.php?error=db_error";
    if ($return !== '') {
        $redir .= "&return=" . urlencode($return);
    }
    header("Location: " . $redir);
    exit;
}

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(160) NOT NULL UNIQUE,
            phone VARCHAR(40),
            password_hash VARCHAR(255) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customers_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN customer_id INT NULL");
    } catch (Exception $e) {
    }

    $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $cust = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cust || !isset($cust['password_hash']) || !password_verify($password, (string)$cust['password_hash'])) {
        $redir = "login.php?error=invalid_credentials";
        if ($return !== '') {
            $redir .= "&return=" . urlencode($return);
        }
        header("Location: " . $redir);
        exit;
    }
    if (isset($cust['is_active']) && (int)$cust['is_active'] === 0) {
        $redir = "login.php?error=invalid_credentials";
        if ($return !== '') {
            $redir .= "&return=" . urlencode($return);
        }
        header("Location: " . $redir);
        exit;
    }

    $customerId = isset($cust['id']) ? (int)$cust['id'] : 0;
    if ($customerId > 0) {
        try {
            $u = $pdo->prepare("UPDATE orders SET customer_id = ? WHERE (customer_id IS NULL OR customer_id = 0) AND REPLACE(LOWER(TRIM(customer_email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '')");
            $u->execute([$customerId, $email]);
        } catch (Exception $e) {
        }
    }

    session_regenerate_id(true);
    $_SESSION['customer_id'] = $customerId;
    $_SESSION['customer_email'] = $email;
    $_SESSION['role'] = 'customer';
    session_write_close();

    if ($return !== '') {
        header("Location: " . $return);
    } else {
        header("Location: orders.php");
    }
    exit;
} catch (PDOException $e) {
    $redir = "login.php?error=db_error&msg=" . urlencode($e->getMessage());
    if ($return !== '') {
        $redir .= "&return=" . urlencode($return);
    }
    header("Location: " . $redir);
    exit;
}
?>
