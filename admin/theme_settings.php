<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/theme.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $primary = isset($_POST['primary_color']) ? trim((string)$_POST['primary_color']) : '';
    $bg = isset($_POST['bg_color']) ? trim((string)$_POST['bg_color']) : '';
    $text = isset($_POST['text_color']) ? trim((string)$_POST['text_color']) : '';
    $adminPrimary = isset($_POST['admin_primary_color']) ? trim((string)$_POST['admin_primary_color']) : '';
    $adminBg = isset($_POST['admin_bg_color']) ? trim((string)$_POST['admin_bg_color']) : '';
    $adminSidebar = isset($_POST['admin_sidebar_color']) ? trim((string)$_POST['admin_sidebar_color']) : '';

    silah_set_setting($pdo, 'primary_color', $primary);
    silah_set_setting($pdo, 'bg_color', $bg);
    silah_set_setting($pdo, 'text_color', $text);
    silah_set_setting($pdo, 'admin_primary_color', $adminPrimary);
    silah_set_setting($pdo, 'admin_bg_color', $adminBg);
    silah_set_setting($pdo, 'admin_sidebar_color', $adminSidebar);

    $uploadDir = __DIR__ . '/../uploads/theme/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

    if (isset($_FILES['hero_image']) && is_uploaded_file($_FILES['hero_image']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['hero_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $name = 'hero_' . uniqid() . '.' . $ext;
            if (@move_uploaded_file($_FILES['hero_image']['tmp_name'], $uploadDir . $name)) {
                silah_set_setting($pdo, 'hero_image', 'uploads/theme/' . $name);
            }
        }
    }
    if (isset($_FILES['admin_banner']) && is_uploaded_file($_FILES['admin_banner']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['admin_banner']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $name = 'admin_banner_' . uniqid() . '.' . $ext;
            if (@move_uploaded_file($_FILES['admin_banner']['tmp_name'], $uploadDir . $name)) {
                silah_set_setting($pdo, 'admin_banner', 'uploads/theme/' . $name);
            }
        }
    }
    $msg = 'Saved';
}

$primary = $pdo ? silah_get_setting($pdo, 'primary_color', '#d63384') : '#d63384';
$bg = $pdo ? silah_get_setting($pdo, 'bg_color', '#ffffff') : '#ffffff';
$text = $pdo ? silah_get_setting($pdo, 'text_color', '#0f172a') : '#0f172a';
$adminPrimary = $pdo ? silah_get_setting($pdo, 'admin_primary_color', '#865294') : '#865294';
$adminBg = $pdo ? silah_get_setting($pdo, 'admin_bg_color', '#ffffff') : '#ffffff';
$adminSidebar = $pdo ? silah_get_setting($pdo, 'admin_sidebar_color', '#2D1B36') : '#2D1B36';
$heroImage = $pdo ? silah_get_setting($pdo, 'hero_image', '') : '';
$adminBanner = $pdo ? silah_get_setting($pdo, 'admin_banner', '') : '';

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
            <?php if ($msg !== ''): ?>
                <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-black uppercase tracking-widest">Saved</span>
            <?php endif; ?>
        </div>

        <form action="theme_settings.php" method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="p-5 rounded-3xl border border-gray-100 bg-gray-50">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3">Public Site</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-bold text-gray-600 mb-1">Primary Color</label>
                            <input type="color" name="primary_color" value="<?= htmlspecialchars((string)$primary) ?>" class="form-control form-control-color w-100">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-600 mb-1">Background</label>
                            <input type="color" name="bg_color" value="<?= htmlspecialchars((string)$bg) ?>" class="form-control form-control-color w-100">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-600 mb-1">Text Color</label>
                            <input type="color" name="text_color" value="<?= htmlspecialchars((string)$text) ?>" class="form-control form-control-color w-100">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-600 mb-1">Hero Image</label>
                            <input type="file" name="hero_image" accept="image/*" class="form-control">
                            <?php if ($heroImage !== ''): ?>
                                <p class="text-[11px] mt-2"><a class="no-underline" href="../<?= htmlspecialchars((string)$heroImage) ?>" target="_blank">Current</a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="p-5 rounded-3xl border border-gray-100 bg-gray-50">
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
                            <?php if ($adminBanner !== ''): ?>
                                <p class="text-[11px] mt-2"><a class="no-underline" href="../<?= htmlspecialchars((string)$adminBanner) ?>" target="_blank">Current</a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary px-6 py-2.5 text-xs font-extrabold uppercase tracking-widest">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
