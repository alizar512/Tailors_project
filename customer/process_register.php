<?php
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit;
}

$name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
$emailRaw = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
$email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL) ? $emailRaw : '';
$phone = isset($_POST['phone']) ? trim((string)$_POST['phone']) : '';
$address = isset($_POST['address']) ? trim((string)$_POST['address']) : '';
$password = isset($_POST['password']) ? (string)$_POST['password'] : '';
$confirmPassword = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';
$return = isset($_POST['return']) ? trim((string)$_POST['return']) : '';

if ($name === '' || $email === '' || $phone === '' || $address === '' || strlen($password) < 8 || $password !== $confirmPassword) {
    $redir = "register.php?error=invalid_input";
    if ($return !== '') {
        $redir .= "&return=" . urlencode($return);
    }
    if ($email !== '') {
        $redir .= "&email=" . urlencode($email);
    }
    if ($phone !== '') {
        $redir .= "&phone=" . urlencode($phone);
    }
    header("Location: " . $redir);
    exit;
}

if (!$pdo) {
    $redir = "register.php?error=db_error";
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
            address VARCHAR(255),
            password_hash VARCHAR(255) NOT NULL,
            auth_provider VARCHAR(20) NOT NULL DEFAULT 'password',
            google_sub VARCHAR(64),
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customers_email (email),
            UNIQUE KEY uq_customers_phone (phone)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    try {
        $pdo->exec("ALTER TABLE customers ADD COLUMN address VARCHAR(255)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE customers ADD UNIQUE KEY uq_customers_phone (phone)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE customers ADD COLUMN auth_provider VARCHAR(20) NOT NULL DEFAULT 'password'");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE customers ADD COLUMN google_sub VARCHAR(64)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE customers ADD UNIQUE KEY uq_customers_google_sub (google_sub)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN customer_id INT NULL");
    } catch (Exception $e) {
    }

    $stmt = $pdo->prepare("SELECT id, email, phone FROM customers WHERE email = ? OR phone = ? LIMIT 1");
    $stmt->execute([$email, $phone]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($exists) {
        $err = 'invalid_input';
        if (isset($exists['email']) && strtolower(trim((string)$exists['email'])) === strtolower(trim((string)$email))) {
            $err = 'email_exists';
        } elseif (isset($exists['phone']) && trim((string)$exists['phone']) === trim((string)$phone)) {
            $err = 'phone_exists';
        }
        $redir = "register.php?error=" . urlencode($err);
        if ($return !== '') {
            $redir .= "&return=" . urlencode($return);
        }
        $redir .= "&email=" . urlencode($email);
        $redir .= "&phone=" . urlencode($phone);
        header("Location: " . $redir);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare("INSERT INTO customers (name, email, phone, address, password_hash) VALUES (?, ?, ?, ?, ?)");
    $ins->execute([$name, $email, $phone, $address, $hash]);
    $customerId = (int)$pdo->lastInsertId();

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
    $redir = "register.php?error=db_error&msg=" . urlencode($e->getMessage());
    if ($return !== '') {
        $redir .= "&return=" . urlencode($return);
    }
    header("Location: " . $redir);
    exit;
}
?>
