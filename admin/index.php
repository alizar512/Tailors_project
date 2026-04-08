<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';

$stats = [
    'total_orders' => 0,
    'pending_orders' => 0,
    'completed_orders' => 0,
    'total_tailors' => 0,
    'pending_apps' => 0,
    'active_tailors' => 0,
    'new_messages' => 0
];

$recent_orders = [];
$activity = [];

if ($pdo) {
    try {
        try { $pdo->exec("ALTER TABLE orders ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"); } catch (Exception $e) {}

        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['total_orders'] += $row['count'];
            if (in_array($row['status'], ['Order Placed', 'Under Review', 'In Progress'])) {
                $stats['pending_orders'] += $row['count'];
            } elseif ($row['status'] == 'Completed') {
                $stats['completed_orders'] += $row['count'];
            }
        }

        $stmt = $pdo->query("SELECT COUNT(*) as count FROM tailors");
        $stats['total_tailors'] = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) as count FROM tailor_applications WHERE status = 'pending'");
        $stats['pending_apps'] = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM contacts");
        $stats['new_messages'] = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 6");
        $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->query("SELECT 'order' as type, customer_name as title, service_type as subtitle, created_at FROM orders ORDER BY created_at DESC LIMIT 3");
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) $activity[] = $row;

        $stmt = $pdo->query("SELECT 'app' as type, name as title, specialization as subtitle, created_at FROM tailor_applications WHERE status = 'pending' ORDER BY created_at DESC LIMIT 3");
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) $activity[] = $row;

        usort($activity, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        $activity = array_slice($activity, 0, 5);

    } catch (Exception $e) {
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div class="animate__animated animate__fadeIn">
    <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-primary tracking-tight mb-1">Dashboard</h2>
            <p class="text-sm text-gray-500 font-medium">Realtime overview of orders, tailors, and support.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <button class="glass-card !rounded-2xl px-4 py-2.5 flex items-center gap-2 hover:bg-white transition-all">
                <i class="fas fa-calendar text-primary"></i>
                <span class="text-xs font-semibold text-gray-700"><?= date('M d, Y') ?></span>
            </button>
            <a href="export_csv.php?type=orders" class="btn btn-primary !rounded-2xl px-5 py-2.5 flex items-center gap-2 text-xs font-extrabold uppercase tracking-widest shadow-xl hover:shadow-primary/20">
                <i class="fas fa-download"></i>
                Generate Report
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
        <div class="glass-card p-5" style="--card-bg: #F7F2FB; --card-border: rgba(134, 82, 148, 0.14); --card-border-hover: rgba(134, 82, 148, 0.25);">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary text-xl">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <span class="text-[10px] font-extrabold text-green-600 bg-green-50 px-2 py-1 rounded-xl uppercase tracking-widest">+8.2%</span>
            </div>
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h3 class="text-3xl sm:text-4xl font-extrabold text-primary mb-1 tracking-tight"><?= $stats['total_orders'] ?></h3>
                    <p class="text-[10px] text-gray-400 font-extrabold uppercase tracking-widest">Total Orders</p>
                </div>
                <div class="w-14 h-7 opacity-30">
                    <svg viewBox="0 0 100 40" class="w-full h-full"><polyline fill="none" stroke="#865294" stroke-width="4" points="0,35 20,15 40,25 60,5 80,15 100,5"/></svg>
                </div>
            </div>
        </div>
        
        <div class="glass-card p-5" style="--card-bg: #FFF7ED; --card-border: rgba(245, 158, 11, 0.18); --card-border-hover: rgba(245, 158, 11, 0.28);">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-500 text-xl">
                    <i class="fas fa-clock"></i>
                </div>
                <span class="text-[10px] font-extrabold text-amber-600 bg-amber-50 px-2 py-1 rounded-xl uppercase tracking-widest">Active</span>
            </div>
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h3 class="text-3xl sm:text-4xl font-extrabold text-amber-500 mb-1 tracking-tight"><?= $stats['pending_orders'] ?></h3>
                    <p class="text-[10px] text-gray-400 font-extrabold uppercase tracking-widest">In Review</p>
                </div>
                <div class="w-14 h-7 opacity-30">
                    <svg viewBox="0 0 100 40" class="w-full h-full"><polyline fill="none" stroke="#f59e0b" stroke-width="4" points="0,5 20,25 40,15 60,35 80,25 100,35"/></svg>
                </div>
            </div>
        </div>

        <div class="glass-card p-5" style="--card-bg: #EFF6FF; --card-border: rgba(59, 130, 246, 0.18); --card-border-hover: rgba(59, 130, 246, 0.28);">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-500 text-xl">
                    <i class="fas fa-user-tie"></i>
                </div>
                <span class="text-[10px] font-extrabold text-blue-600 bg-blue-50 px-2 py-1 rounded-xl uppercase tracking-widest">Network</span>
            </div>
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h3 class="text-3xl sm:text-4xl font-extrabold text-blue-500 mb-1 tracking-tight"><?= $stats['total_tailors'] ?></h3>
                    <p class="text-[10px] text-gray-400 font-extrabold uppercase tracking-widest">Tailors</p>
                </div>
                <div class="w-14 h-7 opacity-30">
                    <svg viewBox="0 0 100 40" class="w-full h-full"><polyline fill="none" stroke="#3b82f6" stroke-width="4" points="0,35 20,30 40,35 60,20 80,10 100,5"/></svg>
                </div>
            </div>
        </div>

        <div class="glass-card p-5" style="--card-bg: #FEF2F2; --card-border: rgba(239, 68, 68, 0.16); --card-border-hover: rgba(239, 68, 68, 0.28);">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center text-red-500 text-xl">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <?php if($stats['new_messages'] > 0): ?>
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                <?php endif; ?>
            </div>
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h3 class="text-3xl sm:text-4xl font-extrabold text-red-500 mb-1 tracking-tight"><?= $stats['new_messages'] ?></h3>
                    <p class="text-[10px] text-gray-400 font-extrabold uppercase tracking-widest">Messages</p>
                </div>
                <div class="w-14 h-7 opacity-30">
                    <svg viewBox="0 0 100 40" class="w-full h-full"><polyline fill="none" stroke="#ef4444" stroke-width="4" points="0,20 20,20 40,20 60,20 80,20 100,20"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 sm:gap-6 mb-8">
        <div class="xl:col-span-8 glass-card p-6 sm:p-8">
            <div class="flex items-start justify-between mb-6 sm:mb-8 gap-4">
                <div>
                    <h4 class="text-lg sm:text-xl font-extrabold text-primary tracking-tight">Platform Growth</h4>
                    <p class="text-xs text-gray-500 font-medium">Weekly orders overview</p>
                </div>
                <div class="flex gap-2">
                    <button class="px-4 py-2 rounded-xl text-[10px] font-extrabold uppercase tracking-widest bg-primary text-white shadow-lg shadow-primary/20">Orders</button>
                    <button class="px-4 py-2 rounded-xl text-[10px] font-extrabold uppercase tracking-widest text-gray-400 hover:bg-gray-100 transition-all">Users</button>
                </div>
            </div>
            <div class="h-[260px] sm:h-[340px]">
                <canvas id="ordersChart"></canvas>
            </div>
        </div>

        <div class="xl:col-span-4 glass-card p-6 sm:p-8 flex flex-col">
            <div class="flex items-center justify-between mb-6 sm:mb-8">
                <h4 class="text-lg sm:text-xl font-extrabold text-primary tracking-tight">Activity</h4>
                <a href="notifications.php" class="text-[10px] font-extrabold text-primary uppercase tracking-widest hover:underline">View All</a>
            </div>
            <div class="space-y-5 flex-grow">
                <?php if (empty($activity)): ?>
                    <div class="text-center py-10">
                        <i class="fas fa-ghost text-3xl text-gray-200 mb-3"></i>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">No activity yet</p>
                    </div>
                <?php endif; ?>
                
                <?php foreach ($activity as $item): ?>
                <div class="flex gap-4 relative">
                    <div class="relative z-10">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xs <?= $item['type'] == 'order' ? 'bg-blue-50 text-blue-500' : 'bg-purple-50 text-purple-500' ?>">
                            <i class="fas <?= $item['type'] == 'order' ? 'fa-shopping-bag' : 'fa-user-plus' ?>"></i>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <p class="text-sm font-black text-gray-800 mb-0"><?= htmlspecialchars($item['title']) ?></p>
                            <span class="text-[10px] font-bold text-gray-400 uppercase"><?= date('H:i', strtotime($item['created_at'])) ?></span>
                        </div>
                        <p class="text-[11px] text-gray-500 font-medium leading-tight"><?= $item['type'] == 'order' ? 'Placed an order for ' : 'Applied as ' ?> <span class="text-primary font-semibold"><?= htmlspecialchars($item['subtitle']) ?></span></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-8 pt-6 border-t border-gray-100">
                <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-4">Quick Actions</p>
                <div class="grid grid-cols-2 gap-3">
                    <a href="tailor_form.php" class="p-4 rounded-2xl bg-primary/5 border border-primary/10 text-center hover:bg-primary/10 transition-all no-underline group">
                        <i class="fas fa-plus text-primary mb-2 block group-hover:scale-110 transition-transform"></i>
                        <span class="text-[10px] font-extrabold text-primary uppercase tracking-widest">Add Tailor</span>
                    </a>
                    <a href="messages.php" class="p-4 rounded-2xl bg-blue-50 border border-blue-100 text-center hover:bg-blue-100 transition-all no-underline group">
                        <i class="fas fa-reply text-blue-500 mb-2 block group-hover:scale-110 transition-transform"></i>
                        <span class="text-[10px] font-extrabold text-blue-500 uppercase tracking-widest">Reply All</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card overflow-hidden">
        <div class="px-6 sm:px-10 py-6 sm:py-8 border-b border-gray-100 flex items-center justify-between bg-white/30">
            <div>
                <h4 class="text-lg sm:text-xl font-extrabold text-primary tracking-tight mb-1">Recent Orders</h4>
                <p class="text-xs text-gray-500 font-medium">Latest transactions</p>
            </div>
            <a href="orders.php" class="btn btn-outline !rounded-2xl px-5 py-2.5 text-[10px] font-extrabold uppercase tracking-widest no-underline">Manage Orders</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th class="px-10 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Customer</th>
                        <th class="py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Service</th>
                        <th class="py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Budget</th>
                        <th class="py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-center">Status</th>
                        <th class="px-10 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-end">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($recent_orders as $order): ?>
                    <tr class="group hover:bg-primary/5 transition-colors">
                        <td class="px-10 py-5 border-0">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-black text-xs">
                                    <?= substr($order['customer_name'], 0, 1) ?>
                                </div>
                                <div>
                                    <p class="text-sm font-black text-gray-800 mb-0"><?= htmlspecialchars($order['customer_name']) ?></p>
                                    <p class="text-[10px] text-gray-400 font-medium"><?= htmlspecialchars($order['customer_email']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="py-5 border-0">
                            <span class="text-[11px] font-bold text-gray-600"><?= htmlspecialchars($order['service_type']) ?></span>
                        </td>
                        <td class="py-5 border-0">
                            <?php $budgetVal = isset($order['budget']) && $order['budget'] !== null && $order['budget'] !== '' ? (float)$order['budget'] : 0.0; ?>
                            <span class="text-sm font-black text-primary tracking-tight">PKR <?= number_format($budgetVal) ?></span>
                        </td>
                        <td class="py-5 border-0 text-center">
                            <?php
                            $statusClasses = [
                                'Completed' => 'bg-green-100 text-green-600',
                                'In Progress' => 'bg-blue-100 text-blue-600',
                                'Under Review' => 'bg-amber-100 text-amber-600',
                            ];
                            $statusKey = isset($order['status']) ? (string)$order['status'] : '';
                            $status_class = isset($statusClasses[$statusKey]) ? $statusClasses[$statusKey] : 'bg-gray-100 text-gray-600';
                            ?>
                            <span class="text-[9px] font-black uppercase px-3 py-1 rounded-full <?= $status_class ?>"><?= $order['status'] ?></span>
                        </td>
                        <td class="px-10 py-5 border-0 text-end">
                            <a href="order_details.php?id=<?= $order['id'] ?>" class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-primary hover:border-primary transition-all shadow-sm ml-auto">
                                <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('ordersChart').getContext('2d');

        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(134, 82, 148, 0.2)');
        gradient.addColorStop(1, 'rgba(134, 82, 148, 0)');

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Platform Orders',
                    data: [0, 0, 0, 0, 0, 0, 0],
                    borderColor: '#865294',
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.45,
                    borderWidth: 4,
                    pointBackgroundColor: '#865294',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#2D1B36',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 13 },
                        padding: 12,
                        cornerRadius: 12,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [5, 5], color: 'rgba(0,0,0,0.05)', drawBorder: false },
                        ticks: { font: { size: 11, weight: '600' }, color: '#94a3b8', padding: 10 }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 11, weight: '600' }, color: '#94a3b8', padding: 10 }
                    }
                }
            }
        });

        const updateChart = async () => {
            try {
                const res = await fetch('api/weekly_orders.php', { cache: 'no-store' });
                const json = await res.json();
                if (!json || !json.ok) return;
                if (Array.isArray(json.labels)) chart.data.labels = json.labels;
                if (Array.isArray(json.data)) chart.data.datasets[0].data = json.data;
                chart.update();
            } catch (e) {
            }
        };

        updateChart();
        setInterval(updateChart, 15000);
    });
</script>

<?php include 'footer.php'; ?>
