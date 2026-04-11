<?php
$current_page = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/notifications.php';

$sidebar_tailor_id = $_SESSION['tailor_id'];
$sidebar_tailor = null;
if ($pdo) {
    $stmt = $pdo->prepare("SELECT id, name, email, profile_image FROM tailors WHERE id = ?");
    $stmt->execute([$sidebar_tailor_id]);
    $sidebar_tailor = $stmt->fetch(PDO::FETCH_ASSOC);
}
$tailor_name = $sidebar_tailor && isset($sidebar_tailor['name']) ? (string)$sidebar_tailor['name'] : 'Tailor';
$tailor_email = $sidebar_tailor && isset($sidebar_tailor['email']) ? (string)$sidebar_tailor['email'] : null;
$tailor_avatar_raw = $sidebar_tailor && isset($sidebar_tailor['profile_image']) && $sidebar_tailor['profile_image'] ? (string)$sidebar_tailor['profile_image'] : '';
$tailor_avatar_fallback = 'https://ui-avatars.com/api/?name=' . urlencode($tailor_name) . '&background=865294&color=fff';
$tailor_avatar = $tailor_avatar_raw !== '' ? $tailor_avatar_raw : $tailor_avatar_fallback;
if (strpos($tailor_avatar, 'http://') !== 0 && strpos($tailor_avatar, 'https://') !== 0) {
    $rel = ltrim((string)$tailor_avatar, '/');
    if (!file_exists(__DIR__ . '/../' . $rel)) {
        $tailor_avatar = $tailor_avatar_fallback;
    } else {
        $tailor_avatar = '../' . $rel;
    }
}

$unread_messages = 0;
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tailor_messages WHERE tailor_id = ? AND is_read = 0");
        $stmt->execute([(int)$sidebar_tailor_id]);
        $unread_messages = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        $unread_messages = 0;
    }
}

$unread_notifications = $pdo ? silah_unread_notifications_count($pdo, 'tailor', (int)$sidebar_tailor_id) : 0;
$recent_notifications = $pdo ? silah_get_recent_notifications($pdo, 'tailor', (int)$sidebar_tailor_id, 5) : [];
?>
<!-- Sidebar -->
<aside class="w-72 bg-[#2D1B36] text-white hidden lg:flex flex-col fixed h-full z-50" style="border-right: 1px solid rgba(255,255,255,0.08);" data-sidebar>
    <div class="p-8">
        <a href="index.php" class="flex items-center gap-3 no-underline group">
            <div class="w-10 h-10 flex items-center justify-center overflow-hidden group-hover:scale-110 transition-transform">
                <img src="../images/logo1.png" alt="Silah Logo" class="w-full h-full object-contain mix-blend-multiply">
            </div>
            <span class="text-2xl font-bold tracking-tight text-white uppercase">Silah Tailor</span>
        </a>
    </div>
    
    <nav class="flex-grow px-6 py-4 overflow-y-auto hide-scrollbar">
        <div class="space-y-2">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-4 mb-4">Main Menu</p>
            
            <a href="index.php" data-tooltip="Dashboard" class="sidebar-link flex items-center gap-4 px-4 py-3 text-gray-300 no-underline <?= $current_page == 'index.php' ? 'active text-white' : '' ?>">
                <i class="fas fa-chart-pie w-5"></i>
                <span class="font-medium">Dashboard</span>
            </a>
            
            <a href="my_orders.php" data-tooltip="My Orders" class="sidebar-link flex items-center gap-4 px-4 py-3 text-gray-300 no-underline <?= $current_page == 'my_orders.php' ? 'active text-white' : '' ?>">
                <i class="fas fa-shopping-bag w-5"></i>
                <span class="font-medium">My Orders</span>
            </a>
            
            <a href="portfolio.php" data-tooltip="Portfolio" class="sidebar-link flex items-center gap-4 px-4 py-3 text-gray-300 no-underline <?= $current_page == 'portfolio.php' ? 'active text-white' : '' ?>">
                <i class="fas fa-images w-5"></i>
                <span class="font-medium">Portfolio</span>
            </a>

            <a href="services.php" data-tooltip="Services & Prices" class="sidebar-link flex items-center gap-4 px-4 py-3 text-gray-300 no-underline <?= $current_page == 'services.php' ? 'active text-white' : '' ?>">
                <i class="fas fa-tags w-5"></i>
                <span class="font-medium">Services & Prices</span>
            </a>

            <a href="messages.php" data-tooltip="Messages" class="sidebar-link flex items-center gap-4 px-4 py-3 text-gray-300 no-underline <?= $current_page == 'messages.php' ? 'active text-white' : '' ?>">
                <i class="fas fa-envelope w-5"></i>
                <span class="font-medium">Messages</span>
                <?php if ($unread_messages > 0): ?>
                    <span class="ml-auto bg-red-500 text-[10px] px-2 py-0.5 rounded-full font-black"><?= $unread_messages ?></span>
                <?php endif; ?>
            </a>
            
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-4 mt-8 mb-4">Account</p>
            
            <a href="profile.php" data-tooltip="My Profile" class="sidebar-link flex items-center gap-4 px-4 py-3 text-gray-300 no-underline <?= $current_page == 'profile.php' ? 'active text-white' : '' ?>">
                <i class="fas fa-user w-5"></i>
                <span class="font-medium">My Profile</span>
            </a>
        </div>
    </nav>
    
    <div class="p-6 border-t border-white/10">
        <div class="bg-white/5 rounded-2xl p-4 group hover:bg-white/10 transition-all cursor-pointer">
            <div class="flex items-center gap-3 mb-3">
                <img src="<?= htmlspecialchars((string)$tailor_avatar) ?>" class="w-10 h-10 rounded-xl border-2 border-white/10 object-cover" alt="Profile">
                <div class="overflow-hidden">
                    <p class="text-xs font-black mb-0 truncate"><?= htmlspecialchars((string)$tailor_name) ?></p>
                    <p class="text-[10px] text-gray-400 mb-0 uppercase tracking-widest font-bold truncate"><?= htmlspecialchars((string)$tailor_email ?? 'Tailor') ?></p>
                </div>
            </div>
            <a href="../admin/logout.php" data-tooltip="Logout" class="sidebar-link sidebar-link-logout flex items-center gap-4 px-4 py-3 text-gray-300 no-underline">
                <i class="fas fa-sign-out-alt w-5"></i>
                <span class="font-medium">Logout</span>
            </a>
        </div>
    </div>
</aside>

<div class="sidebar-backdrop hidden" data-sidebar-backdrop></div>

<!-- Main Content Wrapper -->
<main class="flex-grow lg:ml-72">
    <!-- Top Navbar -->
    <div class="sticky top-0 z-40 border-b border-gray-100 bg-white">
        <div class="w-full px-4 sm:px-6 lg:px-10 py-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div>
                    <button type="button" class="w-10 h-10 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-600" data-sidebar-toggle>
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                <div>
                    <h1 class="text-lg sm:text-xl font-extrabold text-primary uppercase tracking-tight mb-0">
                        <?php 
                        switch($current_page) {
                            case 'index.php': echo 'Dashboard Overview'; break;
                            case 'my_orders.php': echo 'My Orders'; break;
                            case 'portfolio.php': echo 'My Portfolio'; break;
                            case 'messages.php': echo 'Messages'; break;
                            case 'profile.php': echo 'My Profile'; break;
                            case 'order_details.php': echo 'Order Details'; break;
                            default: echo 'Tailor Portal';
                        }
                        ?>
                    </h1>
                    <p class="hidden sm:block text-xs text-gray-500 font-medium mb-0">Quick overview of your work.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3 sm:gap-4">
                <div class="relative group hidden md:block">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" class="bg-white border border-gray-100 rounded-2xl py-2.5 pl-10 pr-4 text-sm w-64 shadow-sm focus:ring-2 focus:ring-primary/20 transition-all" placeholder="Search orders...">
                </div>
                
                <div class="portal-bell-wrap relative">
                    <button type="button" class="portal-bell-btn w-10 h-10 rounded-2xl bg-white border border-gray-100 flex items-center justify-center text-gray-500 shadow-sm hover:text-primary transition-all relative" data-bell-toggle="tailor" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full"><?= (int)$unread_notifications ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="portal-bell-menu p-3 shadow-lg border border-gray-100 rounded-3xl hidden" data-bell-menu="tailor">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-0">Notifications</p>
                            <a href="notifications.php" class="text-[10px] font-black text-primary uppercase tracking-widest no-underline">View All</a>
                        </div>
                        <?php if (empty($recent_notifications)): ?>
                            <div class="py-3 text-center">
                                <p class="text-xs text-gray-500 mb-0">No notifications yet</p>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2">
                                <?php $i = 0; foreach ($recent_notifications as $n): $i++; ?>
                                    <?php
                                        $isRead = isset($n['is_read']) && (int)$n['is_read'] === 1;
                                        $href = isset($n['link']) && trim((string)$n['link']) !== '' ? (string)$n['link'] : 'notifications.php';
                                        $msg = isset($n['message']) ? (string)$n['message'] : '';
                                        $len = function_exists('mb_strlen') ? mb_strlen($msg) : strlen($msg);
                                        $msg = $len > 90 ? ((function_exists('mb_substr') ? mb_substr($msg, 0, 90) : substr($msg, 0, 90)) . '...') : $msg;
                                        $type = isset($n['type']) ? (string)$n['type'] : 'system';
                                        $typeClass = $type === 'order' ? 'portal-bell-item--order' : ($type === 'tailor' ? 'portal-bell-item--tailor' : 'portal-bell-item--system');
                                        $altClass = ($i % 2 === 0) ? 'portal-bell-item--alt' : '';
                                        $readClass = $isRead ? 'portal-bell-item--read' : '';
                                    ?>
                                    <a href="<?= htmlspecialchars((string)$href) ?>" class="portal-bell-item <?= $typeClass ?> <?= $altClass ?> <?= $readClass ?> text-decoration-none">
                                        <div class="d-flex justify-content-between gap-3 mb-1">
                                            <p class="text-sm font-black text-gray-800 mb-0"><?= htmlspecialchars((string)($n['title'] ?? 'Notification')) ?></p>
                                            <span class="text-[10px] font-bold text-gray-400 uppercase"><?= isset($n['created_at']) ? date('M d, H:i', strtotime((string)$n['created_at'])) : '' ?></span>
                                        </div>
                                        <p class="text-xs text-gray-600 mb-0"><?= htmlspecialchars((string)$msg) ?></p>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="hidden sm:block h-8 w-[1px] bg-gray-300"></div>
                
                <div class="flex items-center gap-3">
                    <span class="hidden sm:block text-sm font-bold text-gray-700"><?= htmlspecialchars((string)$tailor_email ?? $tailor_name) ?></span>
                    <img src="<?= htmlspecialchars((string)$tailor_avatar) ?>" class="w-10 h-10 rounded-full border border-gray-100 shadow-sm object-cover">
                </div>
            </div>
        </div>
    </div>
    
    <div class="px-4 sm:px-6 lg:px-10 pb-12 w-full">
