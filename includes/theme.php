<?php
function silah_get_setting($pdo, $key, $default = '') {
    if (!$pdo) return $default;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (skey VARCHAR(120) PRIMARY KEY, svalue TEXT)");
        $stmt = $pdo->prepare("SELECT svalue FROM site_settings WHERE skey = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string)$row['svalue'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function silah_set_setting($pdo, $key, $value) {
    if (!$pdo) return false;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (skey VARCHAR(120) PRIMARY KEY, svalue TEXT)");
        $stmt = $pdo->prepare("REPLACE INTO site_settings (skey, svalue) VALUES (?, ?)");
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        return false;
    }
}

function silah_theme_styles($pdo, $context = 'public') {
    $primary = silah_get_setting($pdo, $context === 'admin' ? 'admin_primary_color' : 'primary_color', $context === 'admin' ? '#865294' : '#d63384');
    $bg = silah_get_setting($pdo, $context === 'admin' ? 'admin_bg_color' : 'bg_color', $context === 'admin' ? '#ffffff' : '#ffffff');
    $text = silah_get_setting($pdo, 'text_color', '#0f172a');
    $sidebar = silah_get_setting($pdo, 'admin_sidebar_color', '#2D1B36');
    $heroImage = silah_get_setting($pdo, 'hero_image', '');
    $adminBanner = silah_get_setting($pdo, 'admin_banner', '');

    $css = "";
    if ($context === 'public') {
        $css .= ":root{--brand-primary: {$primary};}\n";
        $css .= "body{background-color: {$bg}; color: {$text};}\n";
        if ($heroImage !== '') {
            $safe = htmlspecialchars($heroImage, ENT_QUOTES);
            $css .= "#heroBg{background-image:url('{$safe}'); background-size:cover; background-position:center;}\n";
        }
    } else {
        $css .= ":root{--admin-primary: {$primary}; --admin-bg: {$bg};}\n";
        $css .= "body{background-color: var(--admin-bg);} .w-72.bg-\\[\\#2D1B36\\]{background-color: {$sidebar} !important;}\n";
        $css .= ".text-primary{color: var(--admin-primary) !important;}\n";
        $css .= ".bg-primary{background-color: var(--admin-primary) !important;}\n";
        $css .= ".border-primary{border-color: var(--admin-primary) !important;}\n";
        $css .= ".btn-primary{background-color: var(--admin-primary) !important; border-color: var(--admin-primary) !important; color: #fff !important;}\n";
        $css .= ".btn-primary:hover{filter: brightness(0.95);}\n";
        $css .= ".btn-outline{border-color: var(--admin-primary) !important; color: var(--admin-primary) !important;}\n";
        $css .= ".btn-outline:hover{background-color: rgba(0,0,0,0.03);}\n";
        if ($adminBanner !== '') {
            $safe = htmlspecialchars($adminBanner, ENT_QUOTES);
            $css .= ".admin-banner{background-image:url('{$safe}'); background-size:cover; background-position:center;}\n";
        }
    }
    return "<style>\n{$css}</style>\n";
}
?>
