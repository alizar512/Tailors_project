<?php
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $password = $_POST['password'];
    $role = $_POST['role'] ?? 'admin';

    if ($pdo) {
        try {
            // Setup default admin if not exists
            $defaultEmail = 'admin@silah.com';
            $defaultUsername = 'admin';
            $defaultPasswordHash = password_hash('admin123', PASSWORD_DEFAULT);

            $adminStmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? OR username = ? LIMIT 1");
            $adminStmt->execute([$defaultEmail, $defaultUsername]);
            if (!$adminStmt->fetch()) {
                $insertAdminStmt = $pdo->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
                $insertAdminStmt->execute([$defaultUsername, $defaultEmail, $defaultPasswordHash]);
            }

            if ($role === 'admin') {
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? OR username = ? LIMIT 1");
                $stmt->execute([$email, $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_email'] = isset($user['email']) ? $user['email'] : null;
                    $_SESSION['role'] = 'admin';
                    session_write_close();
                    header("Location: index.php");
                    exit;
                }
            } else {
                // Tailor Login
                $stmt = $pdo->prepare("SELECT * FROM tailors WHERE email = ? OR username = ? ORDER BY id DESC LIMIT 1");
                $stmt->execute([$email, $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    if (isset($user['is_active']) && (int)$user['is_active'] === 0) {
                        session_write_close();
                        header("Location: login.php?error=deactivated");
                        exit;
                    }
                    $_SESSION['tailor_id'] = $user['id'];
                    $_SESSION['tailor_email'] = $user['email'];
                    $_SESSION['role'] = 'tailor';
                    $_SESSION['password_reset_required'] = isset($user['password_reset_required']) ? (int)$user['password_reset_required'] : 0;
                    
                    session_write_close();
                    if ((int)$_SESSION['password_reset_required'] === 1) {
                        header("Location: ../tailor/change_password.php");
                    } else {
                        header("Location: ../tailor/index.php");
                    }
                    exit;
                }
            }

            // Default Admin Fallback
            $isAdminFallback = (($email === 'admin@silah.com' || $email === 'admin') && $password === 'admin123');
            if ($role === 'admin' && $isAdminFallback) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = 1;
                $_SESSION['admin_email'] = 'admin@silah.com';
                $_SESSION['role'] = 'admin';
                session_write_close();
                header("Location: index.php");
                exit;
            }

            session_write_close();
            header("Location: login.php?error=invalid_credentials");
            exit;

        } catch (PDOException $e) {
            $msg = urlencode($e->getMessage());
            header("Location: login.php?error=db_error&msg=$msg");
            exit;
        }
    } else {
        // Fallback for demo without DB
        $isAdminFallback = (($email === 'admin@silah.com' || $email === 'admin') && $password === 'admin123');
        if ($role === 'admin' && $isAdminFallback) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = 1;
            $_SESSION['admin_email'] = 'admin@silah.com';
            $_SESSION['role'] = 'admin';
            session_write_close();
            header("Location: index.php");
            exit;
        }
        header("Location: login.php?error=no_connection");
        exit;
    }
}
?>
