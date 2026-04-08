<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';

if (!$pdo) {
    $_SESSION['error'] = 'Database connection failed.';
    header("Location: tailor_management.php");
    exit;
}

$tailor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tailor = null;

try {
    $stmt = $pdo->prepare("SELECT id, name, email, username FROM tailors WHERE id = ?");
    $stmt->execute([$tailor_id]);
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
    $username = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);

    if ($username === '' || strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } else {
        try {
            try {
                $pdo->exec("ALTER TABLE tailors ADD COLUMN username VARCHAR(50) UNIQUE");
            } catch (Exception $e) {
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tailors WHERE username = ? AND id <> ?");
            $stmt->execute([$username, (int)$tailor['id']]);
            if ((int)$stmt->fetchColumn() > 0) {
                $error = 'Username already exists. Please choose another.';
            } else {
                $stmt = $pdo->prepare("UPDATE tailors SET username = ? WHERE id = ?");
                $stmt->execute([$username, (int)$tailor['id']]);
                $success = 'Username updated successfully.';
                $tailor['username'] = $username;
            }
        } catch (Exception $e) {
            $error = 'Could not update username.';
        }
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div class="glass-card p-10 max-w-3xl mx-auto">
    <div class="flex items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-black text-primary mb-1">Edit Tailor Username</h2>
            <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mb-0"><?= htmlspecialchars((string)$tailor['name']) ?> (ID: <?= (int)$tailor['id'] ?>)</p>
        </div>
        <a href="tailor_management.php" class="btn btn-outline !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold no-underline">Back</a>
    </div>

    <?php if ($success !== ''): ?>
        <div class="mb-8 p-4 rounded-2xl border bg-green-50 border-green-100">
            <p class="text-xs font-extrabold text-green-700 uppercase tracking-widest mb-1">Success</p>
            <p class="text-sm font-semibold text-green-800 mb-0"><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="mb-8 p-4 rounded-2xl border bg-red-50 border-red-100">
            <p class="text-xs font-extrabold text-red-600 uppercase tracking-widest mb-1">Error</p>
            <p class="text-sm font-semibold text-red-800 mb-0"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <form action="edit_tailor_username.php?id=<?= (int)$tailor['id'] ?>" method="POST" class="space-y-6">
        <div>
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Username (unique)</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars((string)($tailor['username'] ?? '')) ?>" placeholder="e.g. tailor_ahmed" required>
            <p class="text-[10px] text-gray-400 mt-2 mb-0">Allowed: letters, numbers, underscore</p>
        </div>

        <div class="pt-2">
            <button type="submit" class="btn btn-primary w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs shadow-lg hover:shadow-primary/20 transition-all">Save Username</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
