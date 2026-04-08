<?php
$current_page = basename($_SERVER['PHP_SELF']);
require_once '../includes/notifications.php';
?>
<aside class="w-72 bg-[#2D1B36] text-white hidden lg:flex flex-col fixed h-full z-50" data-sidebar>
    <div class="p-8">
        <a href="index.php" class="flex items-center gap-3 no-underline group">
            <div class="w-10 h-10 flex items-center justify-center overflow-hidden group-hover:scale-110 transition-transform">
                <img src="../images/logo1.png" alt="Silah Logo" class="w-full h-full object-contain mix-blend-multiply">
            </div>
            <span class="text-2xl font-bold tracking-tight text-white uppercase">Silah Admin</span>
        </a>
    </div>
    
    <nav class="flex-grow px-6 py-4 overflow-y-auto hide-scrollbar">
        <div class="space-y-2">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-4 mb-4">Main Menu</p>
            
            <a href="index.php" data-tooltip="Dashboard" class="sidebar-link flex items-center gap-4 px-4 py-3 text-gray-300 no-underline <?= $current_page == 'index.php' ? 'active text-white' : '' ?>">
                <i class="fas fa-chart-pie w-5"></i>
                <span class="font-medium">Dashboard</span>
            </a>
            
            <a href="orders.php" data-tooltip="Orders" class="sidebar-link flex items-center gap-4 px-4 py-3 text-gray-300 no-underline <?= $current_page == 'orders.php' ? 'active text-white' : '' ?>">
                <i class="fas fa-shopping-bag w-5"></i>
                <span class="font-medium">Orders</span>
            </a>
            
            <a href="tailors.php" data-tooltip="Tailors" class="sidebar-link flex items-center gap-4 px-4 py-3 text-gray-300 no-underline <?= $current_page == 'tailors.php' ? 'active text-white' : '' ?>">
                <i class="fas fa-cut w-5"></i>
                <span class="font-medium">Tailors</span>
            </a>
            
            <a href="applications.php" data-tooltip="Applications" class="sidebar-link flex items-center gap-4 px-4 py-3 text-gray-300 no-underline <?= $current_page == 'applications.php' ? 'active text-white' : '' ?>">
                <i class="fas fa-file-alt w-5"></i>
                <span class="font-medium">Applications</span>
                <span class="ml-auto bg-primary text-[10px] px-2 py-0.5 rounded-full">New</span>
            </a>
            
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-4 mt-8 mb-4">Communication</p>
            
            <a href="messages.php" data-tooltip="Messages" class="sidebar-link flex items-center gap-4 px-4 py-3 text-gray-300 no-underline <?= $current_page == 'messages.php' ? 'active text-white' : '' ?>">
                <i class="fas fa-envelope w-5"></i>
                <span class="font-medium">Messages</span>
            </a>
            
            <a href="notifications.php" data-tooltip="Notifications" class="sidebar-link flex items-center gap-4 px-4 py-3 text-gray-300 no-underline <?= $current_page == 'notifications.php' ? 'active text-white' : '' ?>">
                <i class="fas fa-bell w-5"></i>
                <span class="font-medium">Notifications</span>
                <?php
                if ($pdo) {
                    try {
                        $unreadCount = silah_unread_notifications_count($pdo, 'admin', null);
                        if ($unreadCount > 0) {
                            echo '<span class="ml-auto bg-red-500 text-[10px] px-2 py-0.5 rounded-full font-black">' . $unreadCount . '</span>';
                        }
                    } catch (Exception $e) {
                    }
                }
                ?>
            </a>
            
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-4 mt-8 mb-4">System</p>
            
            <a href="settings.php" data-tooltip="Settings" class="sidebar-link flex items-center gap-4 px-4 py-3 text-gray-300 no-underline <?= $current_page == 'settings.php' ? 'active text-white' : '' ?>">
                <i class="fas fa-cog w-5"></i>
                <span class="font-medium">Settings</span>
            </a>

            <a href="cities.php" data-tooltip="Cities" class="sidebar-link flex items-center gap-4 px-4 py-3 text-gray-300 no-underline <?= $current_page == 'cities.php' ? 'active text-white' : '' ?>">
                <i class="fas fa-city w-5"></i>
                <span class="font-medium">Cities</span>
            </a>

            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-4 mt-8 mb-4">Settings</p>

            <a href="tailor_management.php" data-tooltip="Tailor Management" class="sidebar-link flex items-center gap-4 px-4 py-3 text-gray-300 no-underline <?= $current_page == 'tailor_management.php' ? 'active text-white' : '' ?>">
                <i class="fas fa-user-gear w-5"></i>
                <span class="font-medium">Tailor Management</span>
            </a>
        </div>
    </nav>
    
    <div class="p-6 border-t border-white/10">
        <div class="bg-white/5 rounded-2xl p-4 group hover:bg-white/10 transition-all cursor-pointer">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center font-black text-sm shadow-lg shadow-primary/20">
                    <?= substr($_SESSION['admin_email'] ?? 'A', 0, 1) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-black mb-0 truncate"><?= explode('@', $_SESSION['admin_email'] ?? 'Admin')[0] ?></p>
                    <p class="text-[10px] text-gray-400 mb-0 uppercase tracking-widest font-bold">Super Admin</p>
                </div>
            </div>
            <a href="logout.php" data-tooltip="Logout" class="sidebar-link sidebar-link-logout flex items-center gap-4 px-4 py-3 text-gray-300 no-underline">
                <i class="fas fa-sign-out-alt w-5"></i>
                <span class="font-medium">Logout</span>
            </a>
        </div>
    </div>
</aside>

<div class="sidebar-backdrop hidden" data-sidebar-backdrop></div>

<header class="lg:hidden fixed top-0 left-0 right-0 h-16 bg-[#2D1B36] text-white px-4 flex items-center justify-between z-50">
    <div class="flex items-center gap-3">
        <div class="w-9 h-9 flex items-center justify-center overflow-hidden">
            <img src="../images/logo1.png" alt="Silah Logo" class="w-full h-full object-contain mix-blend-multiply">
        </div>
        <span class="text-sm font-extrabold uppercase tracking-widest">Silah Admin</span>
    </div>
    <button class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center" type="button" data-sidebar-toggle>
        <i class="fas fa-bars"></i>
    </button>
</header>

<main class="flex-grow lg:ml-72 pt-16 lg:pt-0">
    <div class="hidden lg:flex items-center justify-between px-4 sm:px-6 lg:px-10 py-5 sticky top-0 bg-white/90 backdrop-blur-md z-40 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <button type="button" class="w-10 h-10 rounded-2xl bg-white flex items-center justify-center text-gray-500 shadow-sm hover:text-primary transition-all" data-sidebar-toggle>
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-xl font-extrabold text-primary uppercase tracking-tight mb-0">
                <?php 
                switch($current_page) {
                    case 'index.php': echo 'Dashboard Overview'; break;
                    case 'orders.php': echo 'Manage Orders'; break;
                    case 'tailors.php': echo 'Approved Tailors'; break;
                    case 'applications.php': echo 'Tailor Applications'; break;
                    case 'messages.php': echo 'Customer Inquiries'; break;
                    case 'cities.php': echo 'Manage Cities'; break;
                    default: echo 'Admin Panel';
                }
                ?>
            </h1>
            <p class="text-xs text-gray-500 font-medium mb-0">Quick overview of the platform.</p>
        </div>
        
        <div class="flex items-center gap-4">
            <div class="relative group">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" class="bg-white border border-gray-100 rounded-2xl py-2.5 pl-10 pr-4 text-sm w-64 shadow-sm focus:ring-2 focus:ring-primary/20 transition-all" placeholder="Search orders, tailors...">
            </div>
            
            <?php
                $topUnread = $pdo ? silah_unread_notifications_count($pdo, 'admin', null) : 0;
                $recentNotifs = $pdo ? silah_get_recent_notifications($pdo, 'admin', null, 5) : [];
            ?>
            <div class="portal-bell-wrap relative">
                <button type="button" class="portal-bell-btn w-10 h-10 rounded-2xl bg-white border border-gray-100 flex items-center justify-center text-gray-500 shadow-sm hover:text-primary transition-all relative" data-bell-toggle="admin" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($topUnread > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full"><?= (int)$topUnread ?></span>
                    <?php endif; ?>
                </button>
                <div class="portal-bell-menu p-3 shadow-lg border border-gray-100 rounded-3xl hidden" data-bell-menu="admin">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-0">Notifications</p>
                        <a href="notifications.php" class="text-[10px] font-black text-primary uppercase tracking-widest no-underline">View All</a>
                    </div>
                    <?php if (empty($recentNotifs)): ?>
                        <div class="py-3 text-center">
                            <p class="text-xs text-gray-500 mb-0">No notifications yet</p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php $i = 0; foreach ($recentNotifs as $n): $i++; ?>
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
                                <a href="<?= htmlspecialchars($href) ?>" class="portal-bell-item <?= $typeClass ?> <?= $altClass ?> <?= $readClass ?> text-decoration-none">
                                    <div class="d-flex justify-content-between gap-3 mb-1">
                                        <p class="text-sm font-black text-gray-800 mb-0"><?= htmlspecialchars((string)($n['title'] ?? 'Notification')) ?></p>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase"><?= isset($n['created_at']) ? date('M d, H:i', strtotime((string)$n['created_at'])) : '' ?></span>
                                    </div>
                                    <p class="text-xs text-gray-600 mb-0"><?= htmlspecialchars($msg) ?></p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="h-8 w-[1px] bg-gray-300"></div>
            
            <div class="flex items-center gap-3">
                <span class="text-sm font-bold text-gray-700"><?= htmlspecialchars($_SESSION['admin_email'] ?? 'Silah Admin') ?></span>
                <?php
                    $adminAvatar = 'https://ui-avatars.com/api/?name=Admin&background=865294&color=fff';
                    if ($pdo && isset($_SESSION['admin_id'])) {
                        try { $pdo->exec("ALTER TABLE admins ADD COLUMN profile_image VARCHAR(255) NULL"); } catch (Exception $e) {}
                        try {
                            $stmt = $pdo->prepare("SELECT profile_image FROM admins WHERE id = ? LIMIT 1");
                            $stmt->execute([(int)$_SESSION['admin_id']]);
                            $p = $stmt->fetch(PDO::FETCH_ASSOC);
                            $raw = $p && isset($p['profile_image']) ? trim((string)$p['profile_image']) : '';
                            if ($raw !== '') {
                                $adminAvatar = preg_match('#^https?://#i', $raw) ? $raw : ('../' . ltrim($raw, '/'));
                            }
                        } catch (Exception $e) {
                        }
                    }
                ?>
                <a href="settings.php" class="no-underline" title="Edit Profile">
                    <img src="<?= htmlspecialchars($adminAvatar) ?>" class="w-10 h-10 rounded-full border-2 border-white shadow-md object-cover">
                </a>
            </div>
        </div>
    </div>
    
    <div class="px-4 sm:px-6 lg:px-10 pb-12 w-full">
