<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

$tailor_id = (int)$_SESSION['tailor_id'];
$status_filter = isset($_GET['status']) ? (string)$_GET['status'] : 'all';
$orders = [];

if ($pdo) {
    try {
        $query = "SELECT * FROM orders WHERE tailor_id = :tailor_id";
        if ($status_filter !== 'all') {
            $query .= " AND status = :status";
        }
        $query .= " ORDER BY created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':tailor_id', $tailor_id, PDO::PARAM_INT);
        if ($status_filter !== 'all') {
            $stmt->bindValue(':status', $status_filter);
        }
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div class="glass-card overflow-hidden">
    <div class="px-8 py-6 border-b border-gray-100 flex flex-wrap items-center justify-between bg-white/50 gap-4">
        <div>
            <h3 class="text-xl font-black text-primary mb-1">My Orders</h3>
            <p class="text-xs text-gray-500 font-medium">Track orders assigned to you</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="?status=all" class="btn <?= $status_filter === 'all' ? 'btn-primary' : 'btn-outline' ?> !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold">All</a>
            <a href="?status=Tailor Selected" class="btn <?= $status_filter === 'Tailor Selected' ? 'btn-primary' : 'btn-outline' ?> !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold">Assigned</a>
            <a href="?status=In Progress" class="btn <?= $status_filter === 'In Progress' ? 'btn-primary' : 'btn-outline' ?> !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold">In Progress</a>
            <a href="?status=Completed" class="btn <?= $status_filter === 'Completed' ? 'btn-primary' : 'btn-outline' ?> !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold">Completed</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Order #</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Customer</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Service</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-center">Status</th>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="5" class="py-20 text-center">
                        <div class="text-gray-300 mb-4 text-4xl"><i class="fas fa-shopping-bag"></i></div>
                        <p class="text-sm text-gray-400 font-bold uppercase tracking-widest">No orders found</p>
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($orders as $order): ?>
                <tr class="group hover:bg-primary/5 transition-colors">
                    <td class="px-8 py-5 border-0 font-black text-primary text-xs">
                        #SIL-<?= str_pad((string)$order['id'], 4, '0', STR_PAD_LEFT) ?>
                    </td>
                    <td class="py-5 border-0">
                        <p class="text-sm font-black text-gray-800 mb-0"><?= htmlspecialchars((string)isset($order['customer_name']) ? $order['customer_name'] : '') ?></p>
                        <p class="text-[11px] text-gray-500 font-medium mb-0"><?= htmlspecialchars((string)isset($order['customer_email']) ? $order['customer_email'] : '') ?></p>
                    </td>
                    <td class="py-5 border-0">
                        <p class="text-[12px] font-bold text-gray-700 mb-0"><?= htmlspecialchars((string)isset($order['service_type']) ? $order['service_type'] : '') ?></p>
                        <p class="text-[10px] text-gray-400 font-medium italic mb-0">PKR <?= number_format((float)(isset($order['budget']) ? $order['budget'] : 0)) ?> Budget</p>
                    </td>
                    <td class="py-5 border-0 text-center">
                        <?php
                        $status_colors = [
                            'Tailor Selected' => 'bg-indigo-100 text-indigo-600',
                            'In Progress' => 'bg-orange-100 text-orange-600',
                            'Completed' => 'bg-green-100 text-green-600',
                        ];
                        $status = isset($order['status']) ? (string)$order['status'] : '';
                        $status_class = isset($status_colors[$status]) ? $status_colors[$status] : 'bg-gray-100 text-gray-600';
                        ?>
                        <span class="text-[9px] font-black uppercase px-2 py-1 rounded-full <?= $status_class ?>">
                            <?= htmlspecialchars((string)$status !== '' ? $status : 'Unknown') ?>
                        </span>
                    </td>
                    <td class="px-8 py-5 border-0 text-end">
                        <a href="order_details.php?id=<?= (int)$order['id'] ?>" class="w-10 h-10 rounded-xl bg-white border border-gray-100 inline-flex items-center justify-center text-gray-400 hover:text-primary hover:border-primary transition-all shadow-sm no-underline">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../admin/footer.php'; ?>
