<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

$tailor_id = (int)$_SESSION['tailor_id'];

if (!$pdo) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id, email, password, password_reset_required FROM tailors WHERE id = ?");
$stmt->execute([$tailor_id]);
$tailor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tailor) {
    header("Location: ../admin/logout.php");
    exit;
}

$resetRequired = isset($tailor['password_reset_required']) ? (int)$tailor['password_reset_required'] : 0;
if ($resetRequired !== 1) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = isset($_POST['current_password']) ? (string)$_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';

    if (!password_verify($current_password, (string)$tailor['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new_password) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirm password do not match.';
    } else {
        try {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE tailors SET password = ?, password_reset_required = 0 WHERE id = ?");
            $update->execute([$hash, $tailor_id]);
            $_SESSION['password_reset_required'] = 0;
            header("Location: index.php?password_changed=1");
            exit;
        } catch (Exception $e) {
            $error = 'Could not update password. Please try again.';
        }
    }
}

include 'header.php';
?>

<?php include 'sidebar.php'; ?>

<div class="w-full max-w-lg mx-auto py-10">
    <div class="glass-card p-8">
        <div class="mb-6">
            <h2 class="text-2xl font-black text-primary mb-1">Change Password</h2>
            <p class="text-xs text-gray-500 font-medium mb-0">For security, please set a new password before continuing.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="mb-6 p-4 rounded-2xl border bg-red-50 border-red-100">
                <p class="text-xs font-extrabold text-red-600 uppercase tracking-widest mb-1">Update Failed</p>
                <p class="text-sm font-semibold text-red-800 mb-0"><?= htmlspecialchars((string)$error) ?></p>
            </div>
        <?php endif; ?>

        <form action="change_password.php" method="POST" class="space-y-5">
            <div class="relative">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Current Password</label>
                <div class="relative">
                    <input type="password" name="current_password" id="current_password" class="form-control pr-10" required>
                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary transition-colors focus:outline-none border-none bg-transparent" onclick="togglePass('current_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">New Password</label>
                <div class="relative">
                    <input type="password" name="new_password" id="new_password" class="form-control pr-10" required>
                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary transition-colors focus:outline-none border-none bg-transparent" onclick="togglePass('new_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Confirm New Password</label>
                <div class="relative">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control pr-10" required>
                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary transition-colors focus:outline-none border-none bg-transparent" onclick="togglePass('confirm_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs shadow-lg hover:shadow-primary/20 transition-all">Update Password</button>
        </form>

        <script>
        function togglePass(id, btn) {
            const input = document.getElementById(id);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        </script>

        <div class="mt-6 pt-6 border-t border-gray-100">
            <a href="../admin/logout.php" class="btn btn-outline w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs no-underline">Logout</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../admin/footer.php'; ?>
