<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/notifications.php';

// Fetch Notifications
$notifications = [];
if ($pdo) {
    try {
        silah_ensure_notifications_table($pdo);
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE recipient_type = 'admin' AND recipient_id IS NULL ORDER BY created_at DESC");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mark all as read when visiting this page
        silah_mark_notifications_read($pdo, 'admin', null);
    } catch (Exception $e) {
        // Log error
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div class="glass-card overflow-hidden max-w-4xl mx-auto">
    <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white/50">
        <div>
            <h3 class="text-xl font-black text-primary mb-1">System Notifications</h3>
            <p class="text-xs text-gray-500 font-medium">Stay updated with platform activities</p>
        </div>
    </div>

    <div class="p-8">
        <?php if (empty($notifications)): ?>
            <div class="py-20 text-center">
                <div class="text-gray-300 mb-4 text-4xl"><i class="fas fa-bell-slash"></i></div>
                <p class="text-sm text-gray-400 font-bold uppercase tracking-widest">No notifications yet</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($notifications as $n): ?>
                    <?php
                        $isRead = isset($n['is_read']) && (int)$n['is_read'] === 1;
                        $type = isset($n['type']) ? (string)$n['type'] : 'system';
                        $link = isset($n['link']) ? trim((string)$n['link']) : '';
                        $card = $isRead ? 'bg-gray-50 border-gray-100' : 'bg-primary/5 border-primary/10';
                        if ($type === 'order') $card = $isRead ? 'bg-blue-50 border-blue-100' : 'bg-blue-50 border-blue-200';
                        if ($type === 'tailor') $card = $isRead ? 'bg-purple-50 border-purple-100' : 'bg-purple-50 border-purple-200';
                        $iconWrap = $type === 'order' ? 'bg-blue-600 text-white' : ($type === 'tailor' ? 'bg-purple-600 text-white' : 'bg-gray-700 text-white');
                        $icon = $type === 'order' ? 'fa-shopping-bag' : ($type === 'tailor' ? 'fa-user-tie' : 'fa-bell');
                    ?>
                    <div class="p-6 rounded-3xl border <?= $card ?> flex items-start gap-4 transition-all hover:shadow-lg hover:-translate-y-0.5">
                        <div class="w-11 h-11 rounded-2xl flex-shrink-0 flex items-center justify-center <?= $iconWrap ?> shadow-sm">
                            <i class="fas <?= $icon ?> text-sm"></i>
                        </div>
                        <div class="flex-grow">
                            <div class="flex justify-between items-start gap-3 mb-1">
                                <h4 class="text-base font-black text-gray-900 mb-0"><?= htmlspecialchars((string)($n['title'] ?? 'Notification')) ?></h4>
                                <span class="text-[10px] font-black text-gray-500 uppercase whitespace-nowrap"><?= isset($n['created_at']) ? date('M d, H:i', strtotime((string)$n['created_at'])) : '' ?></span>
                            </div>
                            <p class="text-sm text-gray-700 leading-relaxed mb-0"><?= htmlspecialchars((string)($n['message'] ?? '')) ?></p>
                            <?php if ($link !== ''): ?>
                                <div class="mt-4">
                                    <a href="<?= htmlspecialchars($link) ?>" class="btn btn-outline !py-2 !px-4 text-[10px] uppercase tracking-widest font-black no-underline">Open</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
