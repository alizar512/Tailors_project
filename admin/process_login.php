<?php
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $password = $_POST['password'];
    $role = $_POST['role'] ?? 'admin';

    if ($pdo) {
        try {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS admins (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL,
                    email VARCHAR(100) NULL,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );

            $checkColumnStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
            );

            $adminColumnsToEnsure = [
                'username' => "ALTER TABLE admins ADD COLUMN username VARCHAR(50) NOT NULL",
                'email' => "ALTER TABLE admins ADD COLUMN email VARCHAR(100) NULL",
                'password' => "ALTER TABLE admins ADD COLUMN password VARCHAR(255) NOT NULL",
                'created_at' => "ALTER TABLE admins ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
                'profile_image' => "ALTER TABLE admins ADD COLUMN profile_image VARCHAR(255) NULL",
            ];

            foreach ($adminColumnsToEnsure as $columnName => $ddl) {
                $checkColumnStmt->execute(['admins', $columnName]);
                if ((int)$checkColumnStmt->fetchColumn() === 0) {
                    $pdo->exec($ddl);
                }
            }

            $defaultEmail = 'admin@silah.com';
            $defaultUsername = 'admin';
            $defaultPasswordHash = password_hash('admin123', PASSWORD_DEFAULT);

            $adminStmt = $pdo->prepare("SELECT id, username, email, password FROM admins WHERE email = ? OR username = ? LIMIT 1");
            $adminStmt->execute([$defaultEmail, $defaultUsername]);
            $existingAdmin = $adminStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingAdmin) {
                if (!isset($existingAdmin['email']) || $existingAdmin['email'] === null || $existingAdmin['email'] === '') {
                    $updateEmailStmt = $pdo->prepare("UPDATE admins SET email = ? WHERE id = ?");
                    $updateEmailStmt->execute([$defaultEmail, $existingAdmin['id']]);
                }
            } else {
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
                $checkColumnStmt->execute(['tailors', 'password_reset_required']);
                if ((int)$checkColumnStmt->fetchColumn() === 0) {
                    $pdo->exec("ALTER TABLE tailors ADD COLUMN password_reset_required TINYINT(1) NOT NULL DEFAULT 0");
                }
                $checkColumnStmt->execute(['tailors', 'is_active']);
                if ((int)$checkColumnStmt->fetchColumn() === 0) {
                    $pdo->exec("ALTER TABLE tailors ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
                }
                $checkColumnStmt->execute(['tailors', 'username']);
                if ((int)$checkColumnStmt->fetchColumn() === 0) {
                    $pdo->exec("ALTER TABLE tailors ADD COLUMN username VARCHAR(50) UNIQUE");
                }

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
                    if ((int)$_SESSION['password_reset_required'] === 1) {
                        session_write_close();
                        header("Location: ../tailor/change_password.php");
                    } else {
                        session_write_close();
                        header("Location: ../tailor/index.php");
                    }
                    exit;
                }
            }

            // Default Admin Fallback (only for admin role)
            if ($role === 'admin' && $email === 'admin@silah.com' && $password === 'admin123') {
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
        // Fallback for demo without DB (Admin only)
        if ($role === 'admin' && $email === 'admin@silah.com' && $password === 'admin123') {
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
