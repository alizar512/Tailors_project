<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';

if (!$pdo) {
    header("Location: index.php");
    exit;
}

try { $pdo->exec("ALTER TABLE admins ADD COLUMN profile_image VARCHAR(255) NULL"); } catch (Exception $e) {}

$adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
$admin = null;
try {
    $stmt = $pdo->prepare("SELECT id, username, email, profile_image FROM admins WHERE id = ? LIMIT 1");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_image'])) {
    if (!isset($_FILES['profile_image']) || !is_array($_FILES['profile_image'])) {
        $error = 'Please choose an image file.';
    } else {
        $f = $_FILES['profile_image'];
        $err = isset($f['error']) ? (int)$f['error'] : 1;
        if ($err !== 0) {
            $error = 'Upload failed. Please try again.';
        } else {
            $tmp = isset($f['tmp_name']) ? (string)$f['tmp_name'] : '';
            $size = isset($f['size']) ? (int)$f['size'] : 0;
            if ($tmp === '' || $size <= 0) {
                $error = 'Invalid upload.';
            } elseif ($size > 2 * 1024 * 1024) {
                $error = 'Image is too large (max 2MB).';
            } else {
                $info = @getimagesize($tmp);
                $mime = is_array($info) && isset($info['mime']) ? (string)$info['mime'] : '';
                $ext = '';
                if ($mime === 'image/jpeg') $ext = 'jpg';
                if ($mime === 'image/png') $ext = 'png';
                if ($mime === 'image/webp') $ext = 'webp';

                if ($ext === '') {
                    $error = 'Only JPG, PNG, or WEBP images are allowed.';
                } else {
                    $dirAbs = __DIR__ . '/../uploads/admin_profiles';
                    if (!is_dir($dirAbs)) {
                        @mkdir($dirAbs, 0775, true);
                    }

                    $rand = bin2hex(random_bytes(8));
                    $fileName = 'admin_' . $adminId . '_' . $rand . '.' . $ext;
                    $destAbs = $dirAbs . '/' . $fileName;
                    $destRel = 'uploads/admin_profiles/' . $fileName;

                    if (!@move_uploaded_file($tmp, $destAbs)) {
                        $error = 'Could not save image. Please check folder permissions.';
                    } else {
                        try {
                            $stmt = $pdo->prepare("UPDATE admins SET profile_image = ? WHERE id = ?");
                            $stmt->execute([$destRel, $adminId]);
                            $success = 'Profile image updated.';
                            $admin['profile_image'] = $destRel;
                        } catch (Exception $e) {
                            $error = 'Could not update profile image.';
                        }
                    }
                }
            }
        }
    }
}

$avatarRaw = $admin && isset($admin['profile_image']) ? trim((string)$admin['profile_image']) : '';
$avatarSrc = 'https://ui-avatars.com/api/?name=Admin&background=865294&color=fff';
if ($avatarRaw !== '') {
    $avatarSrc = preg_match('#^https?://#i', $avatarRaw) ? $avatarRaw : ('../' . ltrim($avatarRaw, '/'));
}

include 'header.php';
include 'sidebar.php';
?>

<?php if ($error !== ''): ?>
    <div class="mb-6 p-4 rounded-3xl bg-red-50 border border-red-100">
        <p class="text-sm font-semibold text-red-800 mb-0"><?= htmlspecialchars($error) ?></p>
    </div>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <div class="mb-6 p-4 rounded-3xl bg-green-50 border border-green-100">
        <p class="text-sm font-semibold text-green-800 mb-0"><?= htmlspecialchars($success) ?></p>
    </div>
<?php endif; ?>

<div class="glass-card overflow-hidden">
    <div class="px-8 py-6 border-b border-gray-100 flex flex-wrap items-center justify-between bg-white/50 gap-4">
        <div>
            <h3 class="text-xl font-black text-primary mb-1">Settings</h3>
            <p class="text-xs text-gray-500 font-medium mb-0">Update your admin profile</p>
        </div>
    </div>

    <div class="p-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="p-6 rounded-3xl border border-gray-100 bg-white">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Profile Image</p>
                <div class="flex items-center gap-4">
                    <img src="<?= htmlspecialchars($avatarSrc) ?>" class="w-20 h-20 rounded-3xl border border-gray-100 shadow-sm object-cover" alt="Admin profile image">
                    <div class="flex-grow">
                        <p class="text-sm font-black text-gray-900 mb-1"><?= htmlspecialchars((string)($admin['username'] ?? 'Admin')) ?></p>
                        <p class="text-xs text-gray-500 mb-0"><?= htmlspecialchars((string)($admin['email'] ?? '')) ?></p>
                    </div>
                </div>
                <form method="POST" enctype="multipart/form-data" class="mt-5">
                    <input type="hidden" name="update_profile_image" value="1">
                    <input type="file" name="profile_image" class="form-control" accept="image/png,image/jpeg,image/webp" required>
                    <button type="submit" class="btn btn-primary !py-2.5 !px-6 text-[10px] uppercase tracking-widest font-black rounded-full mt-3">Upload</button>
                </form>
                <p class="text-[11px] text-gray-400 mt-3 mb-0">Max 2MB. JPG/PNG/WEBP.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

