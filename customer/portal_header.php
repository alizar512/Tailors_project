<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

$cp_title = isset($cp_title) ? (string)$cp_title : 'Client Portal';
$cp_active = isset($cp_active) ? (string)$cp_active : 'dashboard';
$cp_email = isset($_SESSION['customer_email']) ? (string)$_SESSION['customer_email'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string)$cp_title) ?> - Silah</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <div id="cpOverlay" class="fixed inset-0 bg-black/40 hidden z-40 lg:hidden"></div>
        <aside id="cpSidebar" class="fixed lg:static inset-y-0 left-0 w-72 z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 bg-[#2D1B36] text-white flex flex-col">
            <div class="p-6 border-b border-white/10">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <img src="../images/logo1.png" alt="Silah Logo" class="w-10 h-10 object-contain bg-white/90 rounded-2xl p-1">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-white/60 mb-1">Client Portal</p>
                            <p class="text-sm font-black mb-0">Silah</p>
                        </div>
                    </div>
                    <button id="cpClose" type="button" class="lg:hidden w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/15 transition-all">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
                <p class="text-[11px] font-bold text-white/70 mt-4 mb-0 break-all"><?= htmlspecialchars((string)$cp_email) ?></p>
            </div>

            <nav class="p-4 space-y-2">
                <?php
                    $nav = [
                        ['id' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'fa-solid fa-grid-2', 'label' => 'Dashboard'],
                        ['id' => 'orders', 'href' => 'orders.php', 'icon' => 'fa-solid fa-receipt', 'label' => 'My Orders'],
                        ['id' => 'messages', 'href' => 'messages.php', 'icon' => 'fa-solid fa-comments', 'label' => 'Messages'],
                        ['id' => 'chat', 'href' => 'chat.php', 'icon' => 'fa-solid fa-comment-dots', 'label' => 'Chat'],
                        ['id' => 'profile', 'href' => 'profile.php', 'icon' => 'fa-solid fa-user', 'label' => 'Profile'],
                    ];
                    foreach ($nav as $item):
                        $active = $cp_active === $item['id'];
                ?>
                    <a href="<?= htmlspecialchars((string)$item['href']) ?>"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl no-underline transition-all <?= $active ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' ?>">
                        <i class="<?= htmlspecialchars((string)$item['icon']) ?> w-5"></i>
                        <span class="text-sm font-black"><?= htmlspecialchars((string)$item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="mt-auto p-4 border-t border-white/10">
                <a href="logout.php" class="flex items-center justify-center gap-2 px-4 py-3 rounded-2xl bg-pink-600 hover:bg-pink-700 transition-all text-white no-underline text-xs font-black uppercase tracking-widest">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                </a>
            </div>
        </aside>

        <main class="flex-1 min-w-0">
            <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-gray-100">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <button id="cpOpen" type="button" class="lg:hidden w-10 h-10 rounded-2xl bg-gray-50 border border-gray-200 text-gray-700 hover:border-pink-300 hover:text-pink-600 transition-all">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Client Portal</p>
                            <h1 class="text-lg sm:text-xl font-black text-gray-900 mb-0"><?= htmlspecialchars((string)$cp_title) ?></h1>
                        </div>
                    </div>
                    <a href="../place_order.php#order" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-gray-200 text-xs font-black uppercase tracking-widest text-gray-700 hover:border-pink-500 hover:text-pink-600 transition-all no-underline">
                        <i class="fa-solid fa-plus"></i> New Order
                    </a>
                </div>
            </header>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6">
