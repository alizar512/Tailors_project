<?php
// tailor_dashboard.php
require_once __DIR__ . '/includes/db_connect.php';
// For demo, we'll assume a tailor is logged in
$tailor_id = 1; 

if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE preferred_tailors LIKE ? ORDER BY created_at DESC");
    $stmt->execute(['%"'.$tailor_id.'"%']);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tailor Dashboard | Silah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background-color: var(--color-bg); }
        .dashboard-card { background: white; border-radius: var(--radius-lg); padding: 1.5rem; border: none; box-shadow: var(--shadow-md); margin-bottom: 1.5rem; }
        .status-badge { padding: 0.5rem 1rem; border-radius: 50px; font-weight: 600; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="container py-12">
        <div class="d-flex justify-content-between align-items-center mb-8">
            <h1 class="font-bold text-3xl">Tailor Dashboard</h1>
            <a href="index.php" class="btn btn-outline">Back to Website</a>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="dashboard-card">
                    <h3 class="font-bold mb-4">New Order Requests</h3>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Budget</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                <tr><td colspan="5" class="text-center py-4">No orders assigned yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <div class="font-bold"><?= htmlspecialchars((string)$order['customer_name']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars((string)$order['customer_email']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars((string)$order['service_type']) ?></td>
                                    <td><?= htmlspecialchars((string)$order['budget']) ?> PKR</td>
                                    <td>
                                        <span class="status-badge bg-primary-soft text-primary"><?= htmlspecialchars((string)$order['status']) ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary">Update Status</button>
                                        <button class="btn btn-sm btn-outline">View Details</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>