<?php
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['tailor_id']) || $_SESSION['role'] !== 'tailor') {
    header("Location: ../admin/login.php");
    exit;
}

if (isset($_SESSION['tailor_id']) && $pdo) {
    try {
        try {
            $pdo->exec("ALTER TABLE tailors ADD COLUMN password_reset_required TINYINT(1) NOT NULL DEFAULT 0");
        } catch (Exception $e) {
        }
        try {
            $pdo->exec("ALTER TABLE tailors ADD COLUMN profile_completed TINYINT(1) NOT NULL DEFAULT 0");
        } catch (Exception $e) {
        }

        $email = isset($_SESSION['tailor_email']) && $_SESSION['tailor_email'] ? trim((string)$_SESSION['tailor_email']) : '';
        if ($email === '') {
            try {
                $stmt = $pdo->prepare("SELECT email FROM tailors WHERE id = ?");
                $stmt->execute([(int)$_SESSION['tailor_id']]);
                $email = trim((string)$stmt->fetchColumn());
                if ($email !== '') {
                    $_SESSION['tailor_email'] = $email;
                }
            } catch (Exception $e) {
            }
        }

        if ($email !== '') {
            try {
                $stmt = $pdo->prepare("SELECT id FROM tailors WHERE REPLACE(LOWER(TRIM(email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '') ORDER BY id DESC LIMIT 1");
                $stmt->execute([$email]);
                $latestId = (int)$stmt->fetchColumn();
                if ($latestId > 0 && $latestId !== (int)$_SESSION['tailor_id']) {
                    $_SESSION['tailor_id'] = $latestId;
                }
            } catch (Exception $e) {
            }
        }

        $stmt = $pdo->prepare("SELECT password_reset_required, profile_completed FROM tailors WHERE id = ?");
        $stmt->execute([(int)$_SESSION['tailor_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $required = $row ? (int)$row['password_reset_required'] : 0;
        $profileCompleted = $row ? (int)$row['profile_completed'] : 0;
        $_SESSION['password_reset_required'] = $required;
        $_SESSION['profile_completed'] = $profileCompleted;
        $currentScript = basename($_SERVER['PHP_SELF']);
        if ($required === 1 && $currentScript !== 'change_password.php') {
            header("Location: change_password.php");
            exit;
        }
    } catch (Exception $e) {
    }
}
?>
