<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';

// Fetch Orders with filters
$status_filter = $_GET['status'] ?? 'all';
$orders = [];
$chatCounts = [];

if ($pdo) {
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_status VARCHAR(30) DEFAULT 'Pending'");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN tailor_offer_price DECIMAL(10,2)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN tailor_offer_notes TEXT");
    } catch (Exception $e) {
    }

    try {
        $query = "SELECT * FROM orders";
        if ($status_filter !== 'all') {
            $query .= " WHERE status = :status";
        }
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($query);
        if ($status_filter !== 'all') {
            $stmt->bindParam(':status', $status_filter);
        }
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Log error
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS order_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                sender_type VARCHAR(20) NOT NULL,
                sender_name VARCHAR(100),
                sender_email VARCHAR(120),
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_order_messages_order_id (order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    } catch (Exception $e) {
    }

    try {
        $ids = [];
        foreach ($orders as $o) {
            if (isset($o['id']) && is_numeric($o['id'])) {
                $ids[] = (int)$o['id'];
            }
        }
        $ids = array_values(array_unique(array_filter($ids)));
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT order_id, COUNT(*) AS cnt FROM order_messages WHERE order_id IN ($placeholders) GROUP BY order_id");
            $stmt->execute($ids);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $chatCounts[(int)$r['order_id']] = (int)$r['cnt'];
            }
        }
    } catch (Exception $e) {
        $chatCounts = [];
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div class="glass-card overflow-hidden">
    <div class="px-8 py-6 border-b border-gray-100 flex flex-wrap items-center justify-between bg-white/50 gap-4">
        <div>
            <h3 class="text-xl font-black text-primary mb-1">Order Management</h3>
            <p class="text-xs text-gray-500 font-medium">Track and process customer tailoring requests</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="export_csv.php?type=orders" class="btn btn-outline !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold me-4">Export CSV</a>
            <a href="?status=all" class="btn <?= $status_filter == 'all' ? 'btn-primary' : 'btn-outline' ?> !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold">All</a>
            <a href="?status=Order Placed" class="btn <?= $status_filter == 'Order Placed' ? 'btn-primary' : 'btn-outline' ?> !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold">New</a>
            <a href="?status=Under Review" class="btn <?= $status_filter == 'Under Review' ? 'btn-primary' : 'btn-outline' ?> !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold">Reviewing</a>
            <a href="?status=In Progress" class="btn <?= $status_filter == 'In Progress' ? 'btn-primary' : 'btn-outline' ?> !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold">Active</a>
            <a href="?status=Completed" class="btn <?= $status_filter == 'Completed' ? 'btn-primary' : 'btn-outline' ?> !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold">Done</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Order #</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Customer</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Service</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Tailor Offer</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-center">Chat</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-center">Status</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Payment</th>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="8" class="py-20 text-center">
                        <div class="text-gray-300 mb-4 text-4xl"><i class="fas fa-shopping-bag"></i></div>
                        <p class="text-sm text-gray-400 font-bold uppercase tracking-widest">No orders found</p>
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($orders as $order): ?>
                <tr class="group hover:bg-primary/5 transition-colors">
                    <td class="px-8 py-5 border-0 font-black text-primary text-xs">
                        #SIL-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?>
                    </td>
                    <td class="py-5 border-0">
                        <p class="text-sm font-black text-gray-800 mb-0"><?= htmlspecialchars($order['customer_name']) ?></p>
                        <p class="text-[11px] text-gray-500 font-medium"><?= htmlspecialchars($order['customer_email']) ?></p>
                    </td>
                    <td class="py-5 border-0">
                        <p class="text-[12px] font-bold text-gray-700 mb-0"><?= htmlspecialchars($order['service_type']) ?></p>
                        <p class="text-[10px] text-gray-400 font-medium italic">PKR <?= number_format($order['budget']) ?> Budget</p>
                    </td>
                    <td class="py-5 border-0">
                        <?php if (isset($order['tailor_offer_price']) && $order['tailor_offer_price'] !== null && (float)$order['tailor_offer_price'] > 0): ?>
                            <p class="text-[12px] font-black text-gray-800 mb-0">PKR <?= number_format((float)$order['tailor_offer_price']) ?></p>
                            <?php if (isset($order['tailor_offer_notes']) && trim((string)$order['tailor_offer_notes']) !== ''): ?>
                                <p class="text-[10px] text-gray-400 font-medium mb-0 truncate" style="max-width: 220px;"><?= htmlspecialchars((string)$order['tailor_offer_notes']) ?></p>
                            <?php else: ?>
                                <p class="text-[10px] text-gray-400 font-medium mb-0">—</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-[11px] text-gray-400 font-medium mb-0">—</p>
                        <?php endif; ?>
                    </td>
                    <td class="py-5 border-0 text-center">
                        <?php $cnt = isset($chatCounts[(int)$order['id']]) ? (int)$chatCounts[(int)$order['id']] : 0; ?>
                        <?php if ($cnt > 0): ?>
                            <a href="order_details.php?id=<?= (int)$order['id'] ?>#chat" class="text-[11px] font-black text-primary no-underline">View (<?= $cnt ?>)</a>
                        <?php else: ?>
                            <span class="text-[11px] text-gray-400 font-medium">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-5 border-0 text-center">
                        <?php 
                        $status_colors = [
                            'Order Placed' => 'bg-blue-100 text-blue-600',
                            'Under Review' => 'bg-amber-100 text-amber-600',
                            'Price Updated' => 'bg-purple-100 text-purple-600',
                            'Tailor Selected' => 'bg-indigo-100 text-indigo-600',
                            'In Progress' => 'bg-orange-100 text-orange-600',
                            'Completed' => 'bg-green-100 text-green-600'
                        ];
                        $status_class = $status_colors[$order['status']] ?? 'bg-gray-100 text-gray-600';
                        ?>
                        <span class="text-[9px] font-black uppercase px-2 py-1 rounded-full <?= $status_class ?>">
                            <?= $order['status'] ?>
                        </span>
                    </td>
                    <td class="py-5 border-0">
                        <?php $paymentStatus = isset($order['payment_status']) && $order['payment_status'] ? (string)$order['payment_status'] : 'Pending'; ?>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full <?= $paymentStatus == 'Pending' ? 'bg-red-400' : 'bg-green-400' ?>"></div>
                            <span class="text-[11px] font-bold text-gray-600"><?= htmlspecialchars($paymentStatus) ?></span>
                        </div>
                    </td>
                    <td class="px-8 py-5 border-0 text-end">
                        <a href="order_details.php?id=<?= $order['id'] ?>" class="w-10 h-10 rounded-xl bg-white border border-gray-100 inline-flex items-center justify-center text-gray-400 hover:text-primary hover:border-primary transition-all shadow-sm no-underline">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
