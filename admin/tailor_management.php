<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

if ($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS tailors (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $checkColumnStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );

        $columnsToEnsure = [
            'username' => "ALTER TABLE tailors ADD COLUMN username VARCHAR(50) UNIQUE",
            'email' => "ALTER TABLE tailors ADD COLUMN email VARCHAR(100)",
            'password' => "ALTER TABLE tailors ADD COLUMN password VARCHAR(255)",
            'password_reset_required' => "ALTER TABLE tailors ADD COLUMN password_reset_required TINYINT(1) NOT NULL DEFAULT 0",
            'is_active' => "ALTER TABLE tailors ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1",
            'profile_completed' => "ALTER TABLE tailors ADD COLUMN profile_completed TINYINT(1) NOT NULL DEFAULT 0",
        ];

        foreach ($columnsToEnsure as $col => $ddl) {
            $checkColumnStmt->execute(['tailors', $col]);
            if ((int)$checkColumnStmt->fetchColumn() === 0) {
                $pdo->exec($ddl);
            }
        }
    } catch (Exception $e) {
    }
}

$tailors = [];
if ($pdo) {
    try {
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        if ($q !== '') {
            $stmt = $pdo->prepare("SELECT id, name, email, username, is_active FROM tailors WHERE name LIKE ? OR email LIKE ? OR username LIKE ? ORDER BY id DESC");
            $like = '%' . $q . '%';
            $stmt->execute([$like, $like, $like]);
            $tailors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->query("SELECT id, name, email, username, is_active FROM tailors ORDER BY id DESC");
            $tailors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $tailors = [];
    }
}

$success = isset($_SESSION['success']) ? (string)$_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? (string)$_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);

include 'header.php';
include 'sidebar.php';
?>

<?php if ($success !== ''): ?>
    <div class="mb-6 p-4 rounded-2xl border bg-green-50 border-green-100">
        <p class="text-xs font-extrabold text-green-700 uppercase tracking-widest mb-1">Success</p>
        <p class="text-sm font-semibold text-green-800 mb-0"><?= htmlspecialchars((string)$success) ?></p>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="mb-6 p-4 rounded-2xl border bg-red-50 border-red-100">
        <p class="text-xs font-extrabold text-red-600 uppercase tracking-widest mb-1">Error</p>
        <p class="text-sm font-semibold text-red-800 mb-0"><?= htmlspecialchars((string)$error) ?></p>
    </div>
<?php endif; ?>

<div class="glass-card overflow-hidden">
    <div class="px-8 py-6 border-b border-gray-100 flex flex-wrap items-center justify-between bg-white/50 gap-4">
        <div>
            <h3 class="text-xl font-black text-primary mb-1">Tailor Management</h3>
            <p class="text-xs text-gray-500 font-medium mb-0">Manage all tailor accounts and access</p>
        </div>
        <form method="GET" action="tailor_management.php" class="flex items-center gap-2">
            <input type="text" name="q" value="<?= htmlspecialchars((string)isset($_GET['q']) ? (string)$_GET['q'] : '') ?>" class="form-control !py-2 !px-4" placeholder="Search name/email/username">
            <button type="submit" class="btn btn-primary !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold">Search</button>
            <a href="tailor_management.php" class="btn btn-outline !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold no-underline">Clear</a>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Tailor ID</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Name</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Email</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Username</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-center">Status</th>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($tailors)): ?>
                <tr>
                    <td colspan="6" class="py-20 text-center">
                        <div class="text-gray-300 mb-4 text-4xl"><i class="fas fa-user-tie"></i></div>
                        <p class="text-sm text-gray-400 font-bold uppercase tracking-widest">No tailors found</p>
                    </td>
                </tr>
                <?php endif; ?>

                <?php foreach ($tailors as $t): ?>
                <?php
                    $active = !isset($t['is_active']) || (int)$t['is_active'] === 1;
                    $statusClass = $active ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600';
                    $statusText = $active ? 'Active' : 'Deactivated';
                ?>
                <tr class="group hover:bg-primary/5 transition-colors">
                    <td class="px-8 py-5 border-0 font-black text-primary text-xs">#<?= (int)$t['id'] ?></td>
                    <td class="py-5 border-0">
                        <p class="text-sm font-black text-gray-800 mb-0"><?= htmlspecialchars((string)$t['name']) ?></p>
                    </td>
                    <td class="py-5 border-0">
                        <p class="text-[12px] font-bold text-gray-700 mb-0"><?= htmlspecialchars((string)($t['email'] ?? '')) ?></p>
                    </td>
                    <td class="py-5 border-0">
                        <p class="text-[12px] font-bold text-gray-700 mb-0"><?= htmlspecialchars((string)($t['username'] ?? '')) ?></p>
                    </td>
                    <td class="py-5 border-0 text-center">
                        <span class="text-[10px] font-black uppercase px-3 py-1 rounded-full <?= $statusClass ?>"><?= $statusText ?></span>
                    </td>
                    <td class="px-8 py-5 border-0 text-end">
                        <div class="flex justify-end gap-2">
                            <form action="toggle_tailor_status.php" method="POST" onsubmit="return confirm('<?= $active ? 'Deactivate' : 'Activate' ?> this tailor?');">
                                <input type="hidden" name="tailor_id" value="<?= (int)$t['id'] ?>">
                                <input type="hidden" name="to" value="<?= $active ? 0 : 1 ?>">
                                <button type="submit" class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 <?= $active ? 'hover:text-red-500 hover:border-red-500' : 'hover:text-green-500 hover:border-green-500' ?> transition-all shadow-sm" title="<?= $active ? 'Deactivate' : 'Activate' ?>">
                                    <i class="fas <?= $active ? 'fa-user-slash' : 'fa-user-check' ?> text-sm"></i>
                                </button>
                            </form>

                            <a href="reset_tailor_password.php?id=<?= (int)$t['id'] ?>" class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-amber-600 hover:border-amber-500 transition-all shadow-sm no-underline" title="Reset Password">
                                <i class="fas fa-key text-sm"></i>
                            </a>

                            <a href="edit_tailor_username.php?id=<?= (int)$t['id'] ?>" class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-primary hover:border-primary transition-all shadow-sm no-underline" title="Edit Username">
                                <i class="fas fa-user-pen text-sm"></i>
                            </a>

                            <a href="../tailor_profile.php?id=<?= (int)$t['id'] ?>" target="_blank" class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-blue-600 hover:border-blue-500 transition-all shadow-sm no-underline" title="View Profile">
                                <i class="fas fa-eye text-sm"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
