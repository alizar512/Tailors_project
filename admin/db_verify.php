<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

function getColumns(PDO $pdo, string $table): array {
    $stmt = $pdo->prepare(
        "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
         ORDER BY ORDINAL_POSITION"
    );
    $stmt->execute([$table]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function countRows(PDO $pdo, string $table): int {
    $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
    return (int)$stmt->fetchColumn();
}

function safeCountNonEmpty(PDO $pdo, string $table, string $column): ?int {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$table` WHERE `$column` IS NOT NULL AND TRIM(CAST(`$column` AS CHAR)) <> ''");
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return null;
    }
}

$checks = [
    'tailor_applications' => [
        'name', 'email', 'phone', 'location', 'address', 'experience_years', 'specialization', 'price_range_min',
        'instagram_link', 'portfolio_link', 'portfolio_videos', 'profile_image', 'status', 'created_at'
    ],
    'tailors' => [
        'id', 'name', 'username', 'email', 'phone', 'location', 'address', 'experience_years', 'tagline', 'skills',
        'instagram_link', 'description', 'profile_image', 'price_range_min', 'password', 'password_reset_required',
        'is_active', 'profile_completed', 'created_at'
    ],
    'portfolio_images' => ['id', 'tailor_id', 'image_url', 'description'],
    'portfolio_videos' => ['id', 'tailor_id', 'video_url', 'description'],
    'orders' => [
        'id', 'customer_name', 'customer_email', 'customer_phone', 'tailor_id', 'preferred_tailors', 'service_type',
        'budget', 'location_details', 'expected_delivery', 'reference_image', 'notes', 'measurements', 'status', 'created_at'
    ],
    'tailor_messages' => ['id', 'tailor_id', 'customer_name', 'customer_email', 'customer_phone', 'customer_address', 'message', 'is_read', 'created_at'],
    'notifications' => ['id', 'title', 'message', 'type', 'link', 'is_read', 'created_at'],
    'admins' => ['id', 'email', 'password', 'created_at'],
];

$results = [];
$emailQuery = isset($_GET['email']) ? trim((string)$_GET['email']) : '';
$tailorsByEmail = [];
$applicationsByEmail = [];
$tailorByEmail = null;
$applicationByEmail = null;
$createdMessage = null;

if ($pdo) {
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS portfolio_videos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tailor_id INT,
                video_url VARCHAR(255) NOT NULL,
                description VARCHAR(255),
                INDEX idx_portfolio_videos_tailor_id (tailor_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    } catch (Exception $e) {
    }

    if (isset($_GET['create']) && $_GET['create'] === 'portfolio_videos') {
        try {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS portfolio_videos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    tailor_id INT,
                    video_url VARCHAR(255) NOT NULL,
                    description VARCHAR(255),
                    INDEX idx_portfolio_videos_tailor_id (tailor_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
            $createdMessage = "portfolio_videos table created.";
        } catch (Exception $e) {
            $createdMessage = "Failed to create portfolio_videos: " . $e->getMessage();
        }
    }

    foreach ($checks as $table => $expectedCols) {
        $entry = [
            'table' => $table,
            'exists' => false,
            'row_count' => 0,
            'missing_cols' => [],
            'columns' => [],
        ];

        try {
            $entry['exists'] = tableExists($pdo, $table);
            if ($entry['exists']) {
                $entry['row_count'] = countRows($pdo, $table);
                $cols = getColumns($pdo, $table);
                $entry['columns'] = $cols;
                $existing = [];
                foreach ($cols as $colRow) {
                    if (isset($colRow['COLUMN_NAME'])) {
                        $existing[] = (string)$colRow['COLUMN_NAME'];
                    }
                }
                foreach ($expectedCols as $c) {
                    if (!in_array($c, $existing, true)) {
                        $entry['missing_cols'][] = $c;
                    }
                }
            }
        } catch (Exception $e) {
        }
        $results[] = $entry;
    }

    if ($emailQuery !== '') {
        try {
            if (tableExists($pdo, 'tailors')) {
                $stmt = $pdo->prepare("SELECT * FROM tailors WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) OR username = ? ORDER BY id DESC LIMIT 25");
                $stmt->execute([$emailQuery, $emailQuery]);
                $tailorsByEmail = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $tailorByEmail = !empty($tailorsByEmail) ? $tailorsByEmail[0] : null;
            }
        } catch (Exception $e) {
        }
        try {
            if (tableExists($pdo, 'tailor_applications')) {
                $stmt = $pdo->prepare("SELECT * FROM tailor_applications WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) ORDER BY created_at DESC LIMIT 25");
                $stmt->execute([$emailQuery]);
                $applicationsByEmail = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $applicationByEmail = !empty($applicationsByEmail) ? $applicationsByEmail[0] : null;
            }
        } catch (Exception $e) {
        }
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div class="glass-card p-10 max-w-6xl mx-auto">
    <div class="flex items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-black text-primary mb-1">Database Verify</h2>
            <p class="text-xs text-gray-500 font-medium mb-0">Checks tables, columns, and basic completeness.</p>
        </div>
        <form method="GET" action="db_verify.php" class="flex items-center gap-2">
            <input type="text" name="email" value="<?= htmlspecialchars((string)$emailQuery) ?>" class="form-control !py-2 !px-4" placeholder="Search tailor email/username">
            <button type="submit" class="btn btn-primary !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold">Search</button>
            <a href="db_verify.php" class="btn btn-outline !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold no-underline">Clear</a>
        </form>
    </div>

    <?php if (!$pdo): ?>
        <div class="p-4 rounded-2xl border bg-red-50 border-red-100">
            <p class="text-sm font-semibold text-red-800 mb-0">Database connection failed. Check includes/db_connect.php and MySQL service.</p>
        </div>
    <?php else: ?>
        <?php if ($createdMessage): ?>
            <div class="mb-6 p-4 rounded-2xl border bg-green-50 border-green-100">
                <p class="text-xs font-extrabold text-green-700 uppercase tracking-widest mb-1">Action</p>
                <p class="text-sm font-semibold text-green-800 mb-0"><?= htmlspecialchars((string)$createdMessage) ?></p>
            </div>
        <?php endif; ?>
        <?php if ($emailQuery !== ''): ?>
            <div class="mb-10">
                <h3 class="text-lg font-black text-primary mb-3">Lookup Result</h3>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="p-6 rounded-2xl border border-gray-100 bg-white">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Tailors Table</p>
                            <?php if (!$tailorByEmail): ?>
                                <p class="text-sm text-gray-500 mb-0">No tailor found by email/username.</p>
                            <?php else: ?>
                                <div class="text-sm text-gray-700">
                                    <div><span class="font-black">Matches:</span> <?= count($tailorsByEmail) ?></div>
                                    <div><span class="font-black">ID:</span> <?= (int)$tailorByEmail['id'] ?></div>
                                    <div><span class="font-black">Name:</span> <?= htmlspecialchars((string)($tailorByEmail['name'] ?? '')) ?></div>
                                    <div><span class="font-black">Email:</span> <?= htmlspecialchars((string)($tailorByEmail['email'] ?? '')) ?></div>
                                    <div><span class="font-black">Phone:</span> <?= htmlspecialchars((string)($tailorByEmail['phone'] ?? '')) ?></div>
                                    <div><span class="font-black">City:</span> <?= htmlspecialchars((string)($tailorByEmail['location'] ?? '')) ?></div>
                                    <div><span class="font-black">Address:</span> <?= htmlspecialchars((string)($tailorByEmail['address'] ?? '')) ?></div>
                                    <div><span class="font-black">Price:</span> <?= htmlspecialchars((string)($tailorByEmail['price_range_min'] ?? '')) ?></div>
                                    <div><span class="font-black">Tagline:</span> <?= htmlspecialchars((string)($tailorByEmail['tagline'] ?? '')) ?></div>
                                    <div><span class="font-black">Skills:</span> <?= htmlspecialchars((string)($tailorByEmail['skills'] ?? '')) ?></div>
                                    <div><span class="font-black">Profile Completed:</span> <?= isset($tailorByEmail['profile_completed']) ? (int)$tailorByEmail['profile_completed'] : '-' ?></div>
                                </div>
                                <?php if (count($tailorsByEmail) > 1): ?>
                                    <div class="mt-4">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">All Matches</p>
                                        <div class="text-xs text-gray-700 space-y-1">
                                            <?php foreach ($tailorsByEmail as $t): ?>
                                                <div>
                                                    <span class="font-black">#<?= (int)$t['id'] ?></span>
                                                    <span class="text-gray-500">email:</span> <?= htmlspecialchars((string)($t['email'] ?? '')) ?>,
                                                    <span class="text-gray-500">price:</span> <?= htmlspecialchars((string)($t['price_range_min'] ?? '')) ?>,
                                                    <span class="text-gray-500">exp:</span> <?= htmlspecialchars((string)($t['experience_years'] ?? '')) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="p-6 rounded-2xl border border-gray-100 bg-white">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Tailor Applications Table</p>
                            <?php if (!$applicationByEmail): ?>
                                <p class="text-sm text-gray-500 mb-0">No application found for this email.</p>
                            <?php else: ?>
                                <div class="text-sm text-gray-700">
                                    <div><span class="font-black">Matches:</span> <?= count($applicationsByEmail) ?></div>
                                    <div><span class="font-black">App ID:</span> <?= (int)$applicationByEmail['id'] ?></div>
                                    <div><span class="font-black">Name:</span> <?= htmlspecialchars((string)($applicationByEmail['name'] ?? '')) ?></div>
                                    <div><span class="font-black">Email:</span> <?= htmlspecialchars((string)($applicationByEmail['email'] ?? '')) ?></div>
                                    <div><span class="font-black">Phone:</span> <?= htmlspecialchars((string)($applicationByEmail['phone'] ?? '')) ?></div>
                                    <div><span class="font-black">City:</span> <?= htmlspecialchars((string)($applicationByEmail['location'] ?? '')) ?></div>
                                    <div><span class="font-black">Address:</span> <?= htmlspecialchars((string)($applicationByEmail['address'] ?? '')) ?></div>
                                    <div><span class="font-black">Price:</span> <?= htmlspecialchars((string)($applicationByEmail['price_range_min'] ?? '')) ?></div>
                                    <div><span class="font-black">Specialization:</span> <?= htmlspecialchars((string)($applicationByEmail['specialization'] ?? '')) ?></div>
                                    <div><span class="font-black">Status:</span> <?= htmlspecialchars((string)($applicationByEmail['status'] ?? '')) ?></div>
                                </div>
                                <?php if (count($applicationsByEmail) > 1): ?>
                                    <div class="mt-4">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">All Matches</p>
                                        <div class="text-xs text-gray-700 space-y-1">
                                            <?php foreach ($applicationsByEmail as $a): ?>
                                                <div>
                                                    <span class="font-black">#<?= (int)$a['id'] ?></span>
                                                    <span class="text-gray-500">status:</span> <?= htmlspecialchars((string)($a['status'] ?? '')) ?>,
                                                    <span class="text-gray-500">price:</span> <?= htmlspecialchars((string)($a['price_range_min'] ?? '')) ?>,
                                                    <span class="text-gray-500">exp:</span> <?= htmlspecialchars((string)($a['experience_years'] ?? '')) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Table</th>
                        <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-center">Exists</th>
                        <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-center">Rows</th>
                        <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Missing Columns</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td class="px-8 py-5 border-0 font-black text-primary text-xs"><?= htmlspecialchars((string)$r['table']) ?></td>
                            <td class="py-5 border-0 text-center">
                                <span class="text-[10px] font-black uppercase px-3 py-1 rounded-full <?= $r['exists'] ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?>">
                                    <?= $r['exists'] ? 'Yes' : 'No' ?>
                                </span>
                            </td>
                            <td class="py-5 border-0 text-center text-sm font-bold text-gray-700"><?= (int)$r['row_count'] ?></td>
                            <td class="py-5 border-0">
                                <?php if (!$r['exists']): ?>
                                    <span class="text-sm text-gray-500">Table missing</span>
                                <?php elseif (empty($r['missing_cols'])): ?>
                                    <span class="text-sm text-green-700 font-semibold">OK</span>
                                <?php else: ?>
                                    <span class="text-sm text-red-600 font-semibold"><?= htmlspecialchars((string)implode(', ', $r['missing_cols'])) ?></span>
                                    <?php if ($r['table'] === 'portfolio_videos' && !$r['exists']): ?>
                                        <div class="mt-2">
                                            <a href="db_verify.php?create=portfolio_videos" class="btn btn-primary !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold no-underline">Create Table</a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
