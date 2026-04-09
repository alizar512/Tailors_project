<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';

$tailor_id = $_SESSION['tailor_id'];

// Fetch Statistics
$stats = [
    'my_orders' => 0,
    'pending_orders' => 0,
    'completed_orders' => 0,
    'total_earnings' => 0
];
$recent_orders = [];

if ($pdo) {
    try {
        // Orders Stats
        $stmt = $pdo->prepare("SELECT status, COUNT(*) as count, SUM(total_price) as total FROM orders WHERE tailor_id = ? GROUP BY status");
        $stmt->execute([$tailor_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['my_orders'] += $row['count'];
            if ($row['status'] == 'In Progress' || $row['status'] == 'Tailor Selected') {
                $stats['pending_orders'] += $row['count'];
            } elseif ($row['status'] == 'Completed') {
                $stats['completed_orders'] += $row['count'];
                $stats['total_earnings'] += $row['total'];
            }
        }

        // Recent Orders
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE tailor_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$tailor_id]);
        $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        // Log error
        $recent_orders = [];
    }
}

include 'header.php';
include 'sidebar.php';
?>

<!-- Dashboard Content -->
<?php if (isset($_GET['password_changed']) && $_GET['password_changed'] == '1'): ?>
    <div class="mb-6 p-4 rounded-2xl border" style="background: rgba(134, 82, 148, 0.10); border-color: rgba(134, 82, 148, 0.22);">
        <p class="text-xs font-extrabold uppercase tracking-widest mb-1" style="color: rgba(243, 232, 255, 0.98);">Password Updated</p>
        <p class="text-sm font-semibold mb-0">Your password has been updated successfully.</p>
    </div>
<?php endif; ?>

<?php if (isset($_GET['profile_completed']) && $_GET['profile_completed'] == '1'): ?>
    <div class="mb-6 p-4 rounded-2xl border" style="background: rgba(134, 82, 148, 0.10); border-color: rgba(134, 82, 148, 0.22);">
        <p class="text-xs font-extrabold uppercase tracking-widest mb-1" style="color: rgba(243, 232, 255, 0.98);">Profile Completed</p>
        <p class="text-sm font-semibold mb-0">Your profile has been completed successfully.</p>
    </div>
<?php endif; ?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
    <div class="glass-card p-5 stat-card" style="--card-bg: #2D1B36; --card-border: rgba(45, 27, 54, 0.18); --card-border-hover: rgba(134, 82, 148, 0.30); color: rgba(255,255,255,0.92);">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl" style="background: rgba(255, 255, 255, 0.14); color: rgba(255, 255, 255, 0.95); border: 1px solid rgba(255, 255, 255, 0.18);">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <span class="text-[10px] font-extrabold px-2 py-1 rounded-xl uppercase tracking-widest" style="background: rgba(255, 255, 255, 0.14); color: rgba(255, 255, 255, 0.95); border: 1px solid rgba(255, 255, 255, 0.16);">Total</span>
        </div>
        <div class="flex items-end justify-between gap-3">
            <div>
                <h3 class="text-3xl sm:text-4xl font-extrabold mb-1 tracking-tight text-white"><?= $stats['my_orders'] ?></h3>
                <p class="text-[10px] font-extrabold uppercase tracking-widest mb-0" style="color: rgba(255,255,255,0.78);">My Orders</p>
            </div>
            <div class="w-14 h-7 opacity-30">
                <svg viewBox="0 0 100 40" class="w-full h-full"><polyline fill="none" stroke="rgba(255, 255, 255, 0.70)" stroke-width="4" points="0,30 20,25 40,30 60,18 80,12 100,10"/></svg>
            </div>
        </div>
    </div>

    <div class="glass-card p-5 stat-card" style="--card-bg: #1E1B4B; --card-border: rgba(30, 27, 75, 0.18); --card-border-hover: rgba(67, 56, 202, 0.30); color: rgba(255,255,255,0.92);">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl" style="background: rgba(255, 255, 255, 0.14); color: rgba(255, 255, 255, 0.95); border: 1px solid rgba(255, 255, 255, 0.18);">
                <i class="fas fa-clock"></i>
            </div>
            <span class="text-[10px] font-extrabold px-2 py-1 rounded-xl uppercase tracking-widest" style="background: rgba(255, 255, 255, 0.14); color: rgba(255, 255, 255, 0.95); border: 1px solid rgba(255, 255, 255, 0.16);">Active</span>
        </div>
        <div class="flex items-end justify-between gap-3">
            <div>
                <h3 class="text-3xl sm:text-4xl font-extrabold mb-1 tracking-tight text-white"><?= $stats['pending_orders'] ?></h3>
                <p class="text-[10px] font-extrabold uppercase tracking-widest mb-0" style="color: rgba(255,255,255,0.78);">In Progress</p>
            </div>
            <div class="w-14 h-7 opacity-30">
                <svg viewBox="0 0 100 40" class="w-full h-full"><polyline fill="none" stroke="rgba(255, 255, 255, 0.70)" stroke-width="4" points="0,10 20,18 40,12 60,22 80,18 100,25"/></svg>
            </div>
        </div>
    </div>

    <div class="glass-card p-5 stat-card" style="--card-bg: #064E3B; --card-border: rgba(6, 78, 59, 0.18); --card-border-hover: rgba(16, 185, 129, 0.30); color: rgba(255,255,255,0.92);">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl" style="background: rgba(255, 255, 255, 0.14); color: rgba(255, 255, 255, 0.95); border: 1px solid rgba(255, 255, 255, 0.18);">
                <i class="fas fa-check-circle"></i>
            </div>
            <span class="text-[10px] font-extrabold px-2 py-1 rounded-xl uppercase tracking-widest" style="background: rgba(255, 255, 255, 0.14); color: rgba(255, 255, 255, 0.95); border: 1px solid rgba(255, 255, 255, 0.16);">Done</span>
        </div>
        <div class="flex items-end justify-between gap-3">
            <div>
                <h3 class="text-3xl sm:text-4xl font-extrabold mb-1 tracking-tight text-white"><?= $stats['completed_orders'] ?></h3>
                <p class="text-[10px] font-extrabold uppercase tracking-widest mb-0" style="color: rgba(255,255,255,0.78);">Completed</p>
            </div>
            <div class="w-14 h-7 opacity-30">
                <svg viewBox="0 0 100 40" class="w-full h-full"><polyline fill="none" stroke="rgba(255, 255, 255, 0.70)" stroke-width="4" points="0,28 20,20 40,24 60,14 80,8 100,6"/></svg>
            </div>
        </div>
    </div>

    <div class="glass-card p-5 stat-card" style="--card-bg: #0F172A; --card-border: rgba(15, 23, 42, 0.18); --card-border-hover: rgba(51, 65, 85, 0.30); color: rgba(255,255,255,0.92);">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl" style="background: rgba(255, 255, 255, 0.14); color: rgba(255, 255, 255, 0.95); border: 1px solid rgba(255, 255, 255, 0.18);">
                <i class="fas fa-wallet"></i>
            </div>
            <span class="text-[10px] font-extrabold px-2 py-1 rounded-xl uppercase tracking-widest" style="background: rgba(255, 255, 255, 0.14); color: rgba(255, 255, 255, 0.95); border: 1px solid rgba(255, 255, 255, 0.16);">Earnings</span>
        </div>
        <div class="flex items-end justify-between gap-3">
            <div>
                <h3 class="text-3xl sm:text-4xl font-extrabold mb-1 tracking-tight text-white">PKR <?= number_format($stats['total_earnings']) ?></h3>
                <p class="text-[10px] font-extrabold uppercase tracking-widest mb-0" style="color: rgba(255,255,255,0.78);">Total Earnings</p>
            </div>
            <div class="w-14 h-7 opacity-30">
                <svg viewBox="0 0 100 40" class="w-full h-full"><polyline fill="none" stroke="rgba(255, 255, 255, 0.70)" stroke-width="4" points="0,30 20,28 40,30 60,22 80,18 100,12"/></svg>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="glass-card overflow-hidden">
            <div class="px-8 py-6 flex items-center justify-between" style="border-bottom: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.04);">
                <h3 class="text-lg font-black text-primary mb-0 uppercase tracking-tight">Recent Orders Assigned</h3>
                <a href="my_orders.php" class="text-[10px] font-black text-primary uppercase tracking-widest no-underline hover:underline">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Order #</th>
                            <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Customer</th>
                            <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-center">Status</th>
                            <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                        <tr>
                            <td colspan="4" class="py-12 text-center text-gray-400 text-xs font-bold uppercase tracking-widest">No recent orders</td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr class="group hover:bg-primary/5 transition-colors">
                            <td class="px-8 py-4 border-0 font-black text-primary text-xs">#SIL-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td class="py-4 border-0 text-xs font-bold text-gray-700"><?= htmlspecialchars((string)$order['customer_name']) ?></td>
                            <td class="py-4 border-0 text-center">
                                <span class="text-[9px] font-black uppercase px-2 py-1 rounded-full bg-blue-100 text-blue-600"><?= $order['status'] ?></span>
                            </td>
                            <td class="px-8 py-4 border-0 text-end">
                                <a href="order_details.php?id=<?= $order['id'] ?>" class="text-primary hover:text-accent"><i class="fas fa-arrow-right"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="glass-card p-8">
            <h3 class="text-lg font-black text-primary mb-6 uppercase tracking-tight">Profile Completeness</h3>
            <div class="space-y-6">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Portfolio Strength</span>
                        <span class="text-xs font-black text-primary">85%</span>
                    </div>
                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-primary" style="width: 85%"></div>
                    </div>
                </div>
                <p class="text-xs text-gray-500 italic leading-relaxed">"Keep your portfolio updated with your latest work to attract more clients!"</p>
                <a href="portfolio.php" class="btn btn-primary w-full py-3 text-[10px] font-black uppercase tracking-widest no-underline text-center text-white">Update Portfolio</a>
            </div>
        </div>
    </div>
</div>

<?php include '../admin/footer.php'; ?>
