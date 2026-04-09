<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/mailer.php';

if (!$pdo) {
    $_SESSION['error'] = 'Database connection failed.';
    header("Location: tailor_management.php");
    exit;
}

$tailor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tailor_email = isset($_GET['email']) ? trim((string)$_GET['email']) : '';
$tailor = null;

try {
    if ($tailor_id > 0) {
        $stmt = $pdo->prepare("SELECT id, name, email, username FROM tailors WHERE id = ?");
        $stmt->execute([$tailor_id]);
    } elseif ($tailor_email !== '') {
        $stmt = $pdo->prepare("SELECT id, name, email, username FROM tailors WHERE email = ? LIMIT 1");
        $stmt->execute([$tailor_email]);
    }
    $tailor = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

if (!$tailor) {
    $_SESSION['error'] = 'Tailor not found.';
    header("Location: tailor_management.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = isset($_POST['mode']) ? (string)$_POST['mode'] : 'auto';
    $manual_password = isset($_POST['manual_password']) ? (string)$_POST['manual_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';
    $send_email = isset($_POST['send_email']) ? (int)$_POST['send_email'] === 1 : false;

    $plain_password = '';
    if ($mode === 'manual') {
        if (strlen($manual_password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($manual_password !== $confirm_password) {
            $error = 'Password and confirm password do not match.';
        } else {
            $plain_password = $manual_password;
        }
    } else {
        $plain_password = bin2hex(random_bytes(4));
    }

    if ($error === '') {
        try {
            try {
                $pdo->exec("ALTER TABLE tailors ADD COLUMN password_reset_required TINYINT(1) NOT NULL DEFAULT 0");
            } catch (Exception $e) {
            }

            $hash = password_hash($plain_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE tailors SET password = ?, password_reset_required = 1 WHERE id = ?");
            $stmt->execute([$hash, (int)$tailor['id']]);

            if ($send_email && isset($tailor['email']) && $tailor['email']) {
                $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
                $loginUrl = $baseUrl . '/admin/login.php';
                $to = (string)$tailor['email'];
                $subject = 'Silah: Your password has been reset';
                $message =
                    "Hi " . (string)$tailor['name'] . ",\n\n" .
                    "Your Silah Tailor account password has been reset by admin.\n\n" .
                    "Login details:\n" .
                    "Login page: " . $loginUrl . "\n" .
                    "Role: Tailor\n" .
                    "Email/Username: " . ($tailor['username'] ? (string)$tailor['username'] : $to) . "\n" .
                    "Temporary password: " . $plain_password . "\n\n" .
                    "You will be required to change your password after logging in.\n\n" .
                    "Thank you,\n" .
                    "Silah Team\n";
                silah_send_email($to, $subject, $message);
            }

            $success = 'Password reset successfully. Temporary password: ' . $plain_password;
        } catch (Exception $e) {
            $error = 'Could not reset password.';
        }
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div class="glass-card p-10 max-w-3xl mx-auto">
    <div class="flex items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-black text-primary mb-1">Reset Tailor Password</h2>
            <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mb-0"><?= htmlspecialchars((string)$tailor['name']) ?> (ID: <?= (int)$tailor['id'] ?>)</p>
        </div>
        <a href="tailor_management.php" class="btn btn-outline !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold no-underline">Back</a>
    </div>

    <?php if ($success !== ''): ?>
        <div class="mb-8 p-4 rounded-2xl border bg-green-50 border-green-100">
            <p class="text-xs font-extrabold text-green-700 uppercase tracking-widest mb-1">Success</p>
            <p class="text-sm font-semibold text-green-800 mb-0 break-all"><?= htmlspecialchars((string)$success) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="mb-8 p-4 rounded-2xl border bg-red-50 border-red-100">
            <p class="text-xs font-extrabold text-red-600 uppercase tracking-widest mb-1">Error</p>
            <p class="text-sm font-semibold text-red-800 mb-0"><?= htmlspecialchars((string)$error) ?></p>
        </div>
    <?php endif; ?>

    <?php
        $actionUrl = "reset_tailor_password.php?id=" . (int)$tailor['id'];
        if ($tailor_id <= 0 && $tailor_email !== '') {
            $actionUrl = "reset_tailor_password.php?email=" . urlencode($tailor_email);
        }
    ?>
    <form action="<?= htmlspecialchars((string)$actionUrl) ?>" method="POST" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Mode</label>
                <select name="mode" class="form-select">
                    <option value="auto">Auto-generate password</option>
                    <option value="manual">Manually set password</option>
                </select>
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-xs font-bold text-gray-600">
                    <input type="checkbox" name="send_email" value="1">
                    Send password via email (optional)
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">New Password (manual)</label>
                <input type="password" name="manual_password" class="form-control" placeholder="Min 8 characters">
            </div>
            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password">
            </div>
        </div>

        <div class="pt-2">
            <button type="submit" class="btn btn-primary w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs shadow-lg hover:shadow-primary/20 transition-all">Reset Password</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
