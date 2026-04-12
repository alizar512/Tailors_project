<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/theme.php';
require_once __DIR__ . '/../includes/file_store.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    if (isset($_POST['reset_admin_theme']) && (string)$_POST['reset_admin_theme'] === '1') {
        silah_set_setting($pdo, 'admin_primary_color', '#865294');
        silah_set_setting($pdo, 'admin_bg_color', '#ffffff');
        silah_set_setting($pdo, 'admin_sidebar_color', '#2D1B36');
        silah_set_setting($pdo, 'admin_banner', '');
        silah_set_setting($pdo, 'admin_banner_enabled', '0');
        silah_site_file_clear($pdo, 'admin_banner');
        $msg = 'Admin theme reset';
    } else {
    $adminPrimary = isset($_POST['admin_primary_color']) ? trim((string)$_POST['admin_primary_color']) : '';
    $adminBg = isset($_POST['admin_bg_color']) ? trim((string)$_POST['admin_bg_color']) : '';
    $adminSidebar = isset($_POST['admin_sidebar_color']) ? trim((string)$_POST['admin_sidebar_color']) : '';

    silah_set_setting($pdo, 'admin_primary_color', $adminPrimary);
    silah_set_setting($pdo, 'admin_bg_color', $adminBg);
    silah_set_setting($pdo, 'admin_sidebar_color', $adminSidebar);

    if (isset($_FILES['admin_banner']) && is_uploaded_file($_FILES['admin_banner']['tmp_name'])) {
        $tmp = (string)$_FILES['admin_banner']['tmp_name'];
        $mime = isset($_FILES['admin_banner']['type']) ? (string)$_FILES['admin_banner']['type'] : '';
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (in_array($mime, $allowed, true)) {
            $isServerless = getenv('VERCEL') === '1' || getenv('AWS_LAMBDA_FUNCTION_NAME');
            if ($isServerless) {
                $bytes = @file_get_contents($tmp);
                if ($bytes !== false && $bytes !== '') {
                    silah_site_file_set($pdo, 'admin_banner', $mime, $bytes);
                    silah_set_setting($pdo, 'admin_banner_enabled', '1');
                    silah_set_setting($pdo, 'admin_banner', '');
                }
            } else {
                $ext = strtolower(pathinfo((string)($_FILES['admin_banner']['name'] ?? ''), PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $uploadDir = __DIR__ . '/../uploads/theme/';
                    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                    $name = 'admin_banner_' . uniqid() . '.' . $ext;
                    if (@move_uploaded_file($tmp, $uploadDir . $name)) {
                        silah_set_setting($pdo, 'admin_banner', 'uploads/theme/' . $name);
                        silah_set_setting($pdo, 'admin_banner_enabled', '0');
                    }
                }
            }
        }
    }
    $msg = 'Saved';
    }
}

$adminPrimary = $pdo ? silah_get_setting($pdo, 'admin_primary_color', '#865294') : '#865294';
$adminBg = $pdo ? silah_get_setting($pdo, 'admin_bg_color', '#ffffff') : '#ffffff';
$adminSidebar = $pdo ? silah_get_setting($pdo, 'admin_sidebar_color', '#2D1B36') : '#2D1B36';
$adminBanner = $pdo ? silah_get_setting($pdo, 'admin_banner', '') : '';
$adminBannerEnabled = $pdo ? silah_get_setting($pdo, 'admin_banner_enabled', '0') : '0';

include 'header.php';
include 'sidebar.php';
?>
<div class="px-4 sm:px-6 lg:px-10 py-8 space-y-6">
    <div class="glass-card p-6 sm:p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-extrabold text-primary tracking-tight mb-0">Theme Settings</h2>
                <p class="text-xs text-gray-500 font-medium">Customize colors and images for site and admin</p>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($msg !== ''): ?>
                    <?php
                        $isReset = $msg === 'Admin theme reset';
                        $box = $isReset ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700';
                    ?>
                    <span class="px-3 py-1 rounded-full <?= $box ?> text-[10px] font-black uppercase tracking-widest"><?= htmlspecialchars((string)$msg) ?></span>
                <?php endif; ?>
                <form action="theme_settings.php" method="POST" class="m-0">
                    <button type="submit" name="reset_admin_theme" value="1" class="btn btn-outline px-4 py-2 text-[10px] font-extrabold uppercase tracking-widest">
                        <i class="fas fa-rotate-left me-1"></i> Reset Admin Theme
                    </button>
                </form>
            </div>
        </div>

        <form action="theme_settings.php" method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="p-5 rounded-3xl border border-gray-100 bg-gray-50 lg:col-span-2">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3">Admin Portal</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-bold text-gray-600 mb-1">Primary Color</label>
                            <input type="color" name="admin_primary_color" value="<?= htmlspecialchars((string)$adminPrimary) ?>" class="form-control form-control-color w-100">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-600 mb-1">Background</label>
                            <input type="color" name="admin_bg_color" value="<?= htmlspecialchars((string)$adminBg) ?>" class="form-control form-control-color w-100">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-600 mb-1">Sidebar</label>
                            <input type="color" name="admin_sidebar_color" value="<?= htmlspecialchars((string)$adminSidebar) ?>" class="form-control form-control-color w-100">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-600 mb-1">Dashboard Banner</label>
                            <input type="file" name="admin_banner" accept="image/*" class="form-control">
                            <?php if ($adminBannerEnabled === '1'): ?>
                                <p class="text-[11px] mt-2"><a class="no-underline" href="../site_file.php?key=admin_banner" target="_blank">Current</a></p>
                            <?php elseif ($adminBanner !== ''): ?>
                                <p class="text-[11px] mt-2"><a class="no-underline" href="../<?= htmlspecialchars((string)$adminBanner) ?>" target="_blank">Current</a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary px-6 py-2.5 text-xs font-extrabold uppercase tracking-widest">
                    <i class="fas fa-floppy-disk me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
