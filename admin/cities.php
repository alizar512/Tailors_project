<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/cities.php';

if (!$pdo) {
    header("Location: index.php");
    exit;
}

silah_get_cities($pdo);

$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = (string)$_POST['action'];
    if ($action === 'add') {
        $name = isset($_POST['city_name']) ? trim((string)$_POST['city_name']) : '';
        if ($name !== '') {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO cities (name, country, is_active) VALUES (?, 'Pakistan', 1)");
                $stmt->execute([$name]);
                $flash = 'added';
            } catch (Exception $e) {
                $flash = 'error';
            }
        } else {
            $flash = 'error';
        }
    }

    if ($action === 'toggle' && isset($_POST['city_id'])) {
        $id = (int)$_POST['city_id'];
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE cities SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ? AND country = 'Pakistan'");
                $stmt->execute([$id]);
                $flash = 'updated';
            } catch (Exception $e) {
                $flash = 'error';
            }
        }
    }

    if ($action === 'delete' && isset($_POST['city_id'])) {
        $id = (int)$_POST['city_id'];
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM cities WHERE id = ? AND country = 'Pakistan'");
                $stmt->execute([$id]);
                $flash = 'deleted';
            } catch (Exception $e) {
                $flash = 'error';
            }
        }
    }

    header("Location: cities.php?flash=" . urlencode($flash));
    exit;
}

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$filter = isset($_GET['filter']) ? trim((string)$_GET['filter']) : 'active';
if ($filter !== 'all' && $filter !== 'inactive' && $filter !== 'active') {
    $filter = 'active';
}

$rows = [];
$counts = ['all' => 0, 'active' => 0, 'inactive' => 0];
try {
    $counts['all'] = (int)$pdo->query("SELECT COUNT(*) FROM cities WHERE country = 'Pakistan'")->fetchColumn();
    $counts['active'] = (int)$pdo->query("SELECT COUNT(*) FROM cities WHERE country = 'Pakistan' AND is_active = 1")->fetchColumn();
    $counts['inactive'] = (int)$pdo->query("SELECT COUNT(*) FROM cities WHERE country = 'Pakistan' AND is_active = 0")->fetchColumn();
} catch (Exception $e) {
}

try {
    $where = "country = 'Pakistan'";
    $params = [];
    if ($q !== '') {
        $where .= " AND name LIKE ?";
        $params[] = '%' . $q . '%';
    }
    if ($filter === 'active') {
        $where .= " AND is_active = 1";
    } elseif ($filter === 'inactive') {
        $where .= " AND is_active = 0";
    }

    $stmt = $pdo->prepare("SELECT id, name, is_active FROM cities WHERE $where ORDER BY name ASC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $rows = [];
}

include 'header.php';
include 'sidebar.php';
?>

<div class="glass-card overflow-hidden">
    <div class="px-8 py-6 border-b border-gray-100 flex flex-wrap items-center justify-between bg-white/50 gap-4">
        <div>
            <h3 class="text-xl font-black text-primary mb-1">Cities</h3>
            <p class="text-xs text-gray-500 font-medium mb-0">Manage Pakistan cities used across dropdowns</p>
        </div>
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <input type="text" name="q" value="<?= htmlspecialchars((string)$q) ?>" class="form-control" style="min-width: 220px;" placeholder="Search city...">
            <select name="filter" class="form-select" style="min-width: 160px;">
                <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Active (<?= (int)$counts['active'] ?>)</option>
                <option value="inactive" <?= $filter === 'inactive' ? 'selected' : '' ?>>Inactive (<?= (int)$counts['inactive'] ?>)</option>
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All (<?= (int)$counts['all'] ?>)</option>
            </select>
            <button class="btn btn-outline !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold" type="submit">Filter</button>
            <a class="btn btn-outline !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold no-underline" href="cities.php">Reset</a>
        </form>
    </div>

    <?php if (isset($_GET['flash']) && $_GET['flash'] !== ''): ?>
        <?php
            $f = (string)$_GET['flash'];
            $isOk = $f === 'added' || $f === 'updated' || $f === 'deleted';
            $txt = $f === 'added' ? 'City saved.' : ($f === 'updated' ? 'City updated.' : ($f === 'deleted' ? 'City deleted.' : 'Could not update city.'));
            $box = $isOk ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100';
            $t2 = $isOk ? 'text-green-800' : 'text-red-800';
        ?>
        <div class="px-8 pt-6">
            <div class="p-4 rounded-2xl border <?= $box ?>">
                <p class="text-sm font-semibold mb-0 <?= $t2 ?>"><?= htmlspecialchars((string)$txt) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="px-8 pt-6">
        <div class="p-4 rounded-2xl border border-gray-100 bg-white">
            <form method="POST" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="add">
                <div class="col-md-8">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Add City</label>
                    <input type="text" name="city_name" class="form-control" placeholder="Type a city name..." required>
                    <div class="form-text text-xs">This adds a Pakistan city to all dropdowns.</div>
                </div>
                <div class="col-md-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary !py-3 !px-8 text-[10px] font-black uppercase tracking-widest rounded-full">Add</button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive mt-6">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">City</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-center">Status</th>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="3" class="py-16 text-center">
                            <p class="text-sm text-gray-400 font-bold uppercase tracking-widest mb-0">No cities found</p>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <?php
                        $active = isset($r['is_active']) && (int)$r['is_active'] === 1;
                        $badge = $active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600';
                        $label = $active ? 'Active' : 'Inactive';
                    ?>
                    <tr class="group hover:bg-primary/5 transition-colors">
                        <td class="px-8 py-5 border-0">
                            <p class="text-sm font-black text-gray-800 mb-0"><?= htmlspecialchars((string)$r['name']) ?></p>
                        </td>
                        <td class="py-5 border-0 text-center">
                            <span class="text-[10px] font-black uppercase px-3 py-1 rounded-full <?= $badge ?>"><?= $label ?></span>
                        </td>
                        <td class="px-8 py-5 border-0 text-end">
                            <div class="d-inline-flex gap-1 flex-nowrap align-items-center">
                                <form method="POST" class="m-0">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="city_id" value="<?= (int)$r['id'] ?>">
                                    <button type="submit" class="portal-action-icon <?= $active ? 'portal-action-icon--pause' : 'portal-action-icon--play' ?>" title="<?= $active ? 'Deactivate' : 'Activate' ?>" aria-label="<?= $active ? 'Deactivate' : 'Activate' ?>">
                                        <i class="fas <?= $active ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" class="m-0" onsubmit="return confirm('Delete this city?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="city_id" value="<?= (int)$r['id'] ?>">
                                    <button type="submit" class="portal-action-icon portal-action-icon--danger" title="Delete" aria-label="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
