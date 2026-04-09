<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

$tailor_id = (int)$_SESSION['tailor_id'];

if (!$pdo) {
    header("Location: index.php");
    exit;
}

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tailor_services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tailor_id INT NOT NULL,
            service_name VARCHAR(120) NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tailor_services_tailor_id (tailor_id),
            INDEX idx_tailor_services_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (Exception $e) {
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $names = isset($_POST['service_name']) && is_array($_POST['service_name']) ? $_POST['service_name'] : [];
    $prices = isset($_POST['service_price']) && is_array($_POST['service_price']) ? $_POST['service_price'] : [];
    $active = isset($_POST['service_active']) && is_array($_POST['service_active']) ? $_POST['service_active'] : [];

    try {
        $pdo->prepare("DELETE FROM tailor_services WHERE tailor_id = ?")->execute([$tailor_id]);

        $ins = $pdo->prepare("INSERT INTO tailor_services (tailor_id, service_name, price, is_active) VALUES (?, ?, ?, ?)");
        for ($i = 0; $i < count($names); $i++) {
            $n = isset($names[$i]) ? trim((string)$names[$i]) : '';
            $p = isset($prices[$i]) && is_numeric($prices[$i]) ? (float)$prices[$i] : null;
            $a = isset($active[$i]) && (string)$active[$i] === '1' ? 1 : 0;
            if ($n === '' || $p === null || $p < 0) {
                continue;
            }
            $ins->execute([$tailor_id, $n, $p, $a]);
        }

        header("Location: services.php?saved=1");
        exit;
    } catch (Exception $e) {
        header("Location: services.php?saved=0");
        exit;
    }
}

if (isset($_GET['saved'])) {
    $success = $_GET['saved'] == '1' ? 'Services saved successfully.' : '';
    $error = $_GET['saved'] == '0' ? 'Could not save services.' : '';
}

$services = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM tailor_services WHERE tailor_id = ? ORDER BY id ASC");
    $stmt->execute([$tailor_id]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $services = [];
}

if (empty($services)) {
    $services = [
        ['service_name' => 'Stitching', 'price' => 0, 'is_active' => 1],
        ['service_name' => 'Alteration', 'price' => 0, 'is_active' => 1],
    ];
}

include 'header.php';
include 'sidebar.php';
?>

<div class="glass-card p-8 mt-6">
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-primary mb-1">My Services & Prices</h2>
            <p class="text-xs text-gray-500 font-medium mb-0">Add multiple services and set your price for each.</p>
        </div>
    </div>

    <?php if ($success !== ''): ?>
        <div class="mb-6 p-4 rounded-2xl border bg-green-50 border-green-100">
            <p class="text-xs font-extrabold text-green-700 uppercase tracking-widest mb-1">Saved</p>
            <p class="text-sm font-semibold text-green-800 mb-0"><?= htmlspecialchars((string)$success) ?></p>
        </div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="mb-6 p-4 rounded-2xl border bg-red-50 border-red-100">
            <p class="text-xs font-extrabold text-red-600 uppercase tracking-widest mb-1">Error</p>
            <p class="text-sm font-semibold text-red-800 mb-0"><?= htmlspecialchars((string)$error) ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <div id="servicesRows" class="space-y-3">
            <?php foreach ($services as $s): ?>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end service-row">
                    <div class="md:col-span-7">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Service Name</label>
                        <input type="text" name="service_name[]" class="form-control" value="<?= htmlspecialchars((string)($s['service_name'] ?? '')) ?>" required>
                    </div>
                    <div class="md:col-span-3">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Price (PKR)</label>
                        <input type="number" name="service_price[]" class="form-control" value="<?= htmlspecialchars((string)($s['price'] ?? '0')) ?>" min="0" step="0.01" required>
                    </div>
                    <div class="md:col-span-1">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Active</label>
                        <select name="service_active[]" class="form-select">
                            <option value="1" <?= !isset($s['is_active']) || (int)$s['is_active'] === 1 ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= isset($s['is_active']) && (int)$s['is_active'] === 0 ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                    <div class="md:col-span-1 flex justify-end">
                        <button type="button" class="btn btn-outline !py-3 !px-4 text-[10px] font-black uppercase tracking-widest remove-row">Remove</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="button" class="btn btn-outline !py-3 !px-6 text-[10px] font-black uppercase tracking-widest" id="addServiceRow">Add Service</button>
            <button type="submit" class="btn btn-primary !py-3 !px-8 text-[10px] font-black uppercase tracking-widest">Save Services</button>
        </div>
    </form>
</div>

<script>
    (function() {
        const rows = document.getElementById('servicesRows');
        const addBtn = document.getElementById('addServiceRow');
        if (!rows || !addBtn) return;

        const rowHtml = () => `
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end service-row">
                <div class="md:col-span-7">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Service Name</label>
                    <input type="text" name="service_name[]" class="form-control" required>
                </div>
                <div class="md:col-span-3">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Price (PKR)</label>
                    <input type="number" name="service_price[]" class="form-control" value="0" min="0" step="0.01" required>
                </div>
                <div class="md:col-span-1">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Active</label>
                    <select name="service_active[]" class="form-select">
                        <option value="1" selected>Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="md:col-span-1 flex justify-end">
                    <button type="button" class="btn btn-outline !py-3 !px-4 text-[10px] font-black uppercase tracking-widest remove-row">Remove</button>
                </div>
            </div>
        `;

        addBtn.addEventListener('click', () => {
            const wrap = document.createElement('div');
            wrap.innerHTML = rowHtml();
            rows.appendChild(wrap.firstElementChild);
        });

        rows.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-row');
            if (!btn) return;
            const row = btn.closest('.service-row');
            if (row) row.remove();
        });
    })();
</script>

<?php include __DIR__ . '/../admin/footer.php'; ?>

