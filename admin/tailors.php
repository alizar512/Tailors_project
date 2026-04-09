<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';

// Fetch Approved Tailors
$tailors = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM tailors ORDER BY created_at DESC");
        $tailors = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h3 class="text-xl font-black text-primary mb-1">Approved Tailors</h3>
            <p class="text-xs text-gray-500 font-medium">Manage active professionals on the platform</p>
        </div>
        <div class="flex gap-2">
            <a href="export_csv.php?type=tailors" class="btn btn-outline !py-2 !px-4 text-[10px] uppercase tracking-widest font-bold me-2">Export CSV</a>
            <a href="tailor_form.php" class="btn btn-primary !py-2 !px-6 text-[10px] uppercase tracking-widest font-black rounded-full shadow-lg hover:shadow-primary/20 transition-all no-underline">Add New Tailor</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Tailor</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0">Location</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-center">Experience</th>
                    <th class="py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-center">Price Range</th>
                    <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-0 text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($tailors)): ?>
                <tr>
                    <td colspan="5" class="py-20 text-center">
                        <div class="text-gray-300 mb-4 text-4xl"><i class="fas fa-cut"></i></div>
                        <p class="text-sm text-gray-400 font-bold uppercase tracking-widest">No tailors found</p>
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($tailors as $t): ?>
                <tr class="group hover:bg-primary/5 transition-colors">
                    <td class="px-8 py-5 border-0">
                        <div class="flex items-center gap-4">
                            <img src="../<?= htmlspecialchars((string)$t['profile_image']) ?>" class="w-12 h-12 rounded-2xl object-cover shadow-sm border-2 border-white group-hover:scale-110 transition-transform duration-500">
                            <div>
                                <p class="text-sm font-black text-gray-800 mb-0"><?= htmlspecialchars((string)$t['name']) ?></p>
                                <p class="text-[11px] text-gray-500 font-medium"><?= htmlspecialchars((string)$t['tagline']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="py-5 border-0">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-primary text-[10px]"></i>
                            <span class="text-[12px] font-bold text-gray-700"><?= htmlspecialchars((string)$t['location']) ?></span>
                        </div>
                    </td>
                    <td class="py-5 border-0 text-center">
                        <span class="text-[10px] font-black uppercase px-2 py-1 bg-amber-50 text-amber-600 rounded-lg">
                            <?= htmlspecialchars((string)$t['experience_years']) ?> Years
                        </span>
                    </td>
                    <td class="py-5 border-0 text-center">
                        <p class="text-[11px] font-black text-primary mb-0">PKR <?= number_format($t['price_range_min']) ?>+</p>
                    </td>
                    <td class="px-8 py-5 border-0 text-end">
                        <div class="flex justify-end gap-2">
                            <a href="tailor_form.php?id=<?= $t['id'] ?>" class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-primary hover:border-primary transition-all shadow-sm no-underline">
                                <i class="fas fa-edit text-sm"></i>
                            </a>
                            <a href="delete_tailor.php?id=<?= $t['id'] ?>" class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-red-500 hover:border-red-500 transition-all shadow-sm no-underline" onclick="return confirm('Are you sure you want to delete this tailor profile?')">
                                <i class="fas fa-trash text-sm"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
