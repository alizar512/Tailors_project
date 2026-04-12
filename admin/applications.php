<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/schema_utils.php';

// Fetch Applications
$applications = [];
if ($pdo) {
    try {
        silah_ensure_column($pdo, 'tailor_applications', 'profile_image_blob', "ALTER TABLE tailor_applications ADD COLUMN profile_image_blob LONGBLOB NULL");
        silah_ensure_column($pdo, 'tailor_applications', 'profile_image_mime', "ALTER TABLE tailor_applications ADD COLUMN profile_image_mime VARCHAR(100) NULL");
        $stmt = $pdo->query("SELECT * FROM tailor_applications ORDER BY created_at DESC");
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Log error
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div class="glass-card overflow-hidden">
    <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white/50">
        <div>
            <h3 class="text-xl font-black text-primary mb-1">Tailor Applications</h3>
            <p class="text-xs text-gray-500 font-medium">Review and manage professional applications</p>
        </div>
        <div class="flex gap-2">
            <button class="btn btn-outline !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold">Export CSV</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Applicant</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Contact Info</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Specialization</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-center">Status</th>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($applications)): ?>
                <tr>
                    <td colspan="5" class="py-20 text-center">
                        <div class="text-gray-300 mb-4 text-4xl"><i class="fas fa-folder-open"></i></div>
                        <p class="text-sm text-gray-400 font-bold uppercase tracking-widest">No applications found</p>
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($applications as $app): ?>
                <tr class="group hover:bg-primary/5 transition-colors">
                    <td class="px-8 py-5 border-0">
                        <div class="flex items-center gap-4">
                            <?php
                                $hasPhoto = (isset($app['profile_image_blob']) && $app['profile_image_blob'] !== null && $app['profile_image_blob'] !== '') || (isset($app['profile_image']) && trim((string)$app['profile_image']) !== '');
                                $photoSrc = '../application_media.php?app_id=' . (int)$app['id'] . '&type=profile';
                            ?>
                            <?php if ($hasPhoto): ?>
                                <img src="<?= htmlspecialchars((string)$photoSrc) ?>" class="w-12 h-12 rounded-2xl object-cover shadow-sm border-2 border-white" alt="Profile" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary font-black text-lg hidden">
                                    <?= htmlspecialchars((string)substr((string)$app['name'], 0, 1)) ?>
                                </div>
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary font-black text-lg">
                                    <?= htmlspecialchars((string)substr((string)$app['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <p class="text-sm font-black text-gray-800 mb-0"><?= htmlspecialchars((string)$app['name']) ?></p>
                                <p class="text-[11px] text-gray-500 font-medium"><?= htmlspecialchars((string)$app['location']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="py-5 border-0">
                        <p class="text-[13px] font-bold text-gray-700 mb-0"><?= htmlspecialchars((string)$app['email']) ?></p>
                        <p class="text-[11px] text-gray-400 font-medium"><?= htmlspecialchars((string)$app['phone']) ?></p>
                    </td>
                    <td class="py-5 border-0">
                        <div class="flex flex-wrap gap-1">
                            <?php 
                            $specs = explode(',', $app['specialization']);
                            foreach($specs as $spec): 
                            ?>
                            <span class="text-[9px] font-black uppercase px-2 py-0.5 bg-gray-100 text-gray-500 rounded-md"><?= trim($spec) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td class="py-5 border-0 text-center">
                        <?php 
                        $status_class = [
                            'pending' => 'bg-amber-100 text-amber-600',
                            'approved' => 'bg-green-100 text-green-600',
                            'rejected' => 'bg-red-100 text-red-600'
                        ][$app['status']] ?? 'bg-gray-100 text-gray-600';
                        ?>
                        <span class="text-[10px] font-black uppercase px-3 py-1 rounded-full <?= $status_class ?>">
                            <?= $app['status'] ?>
                        </span>
                    </td>
                    <td class="px-8 py-5 border-0 text-end">
                        <div class="flex justify-end gap-2">
                            <button class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-primary hover:border-primary transition-all shadow-sm" title="View Portfolio" onclick="window.location.href='application_details.php?id=<?= $app['id'] ?>'">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                            <?php if ($app['status'] === 'pending'): ?>
                                <form action="process_application.php" method="POST" onsubmit="return confirm('Approve this application?');">
                                    <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-green-500 hover:border-green-500 transition-all shadow-sm" title="Approve">
                                        <i class="fas fa-check text-sm"></i>
                                    </button>
                                </form>
                                <form action="process_application.php" method="POST" onsubmit="return confirm('Reject this application?');">
                                    <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-red-500 hover:border-red-500 transition-all shadow-sm" title="Reject">
                                        <i class="fas fa-times text-sm"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
