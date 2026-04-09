<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/cities.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tailor = [
    'name' => '', 'tagline' => '', 'description' => '', 
    'location' => '', 'profile_image' => '', 
    'price_range_min' => '', 'price_range_max' => '', 
    'experience_years' => '', 'email' => '', 'phone' => ''
];

if ($id && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM tailors WHERE id = ?");
        $stmt->execute([$id]);
        $tailor = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Log error
    }
}

$cities = silah_get_cities($pdo);
$currentLoc = isset($tailor['location']) ? trim((string)$tailor['location']) : '';
$citySet = [];
foreach ($cities as $c) {
    $citySet[strtolower(trim((string)$c))] = true;
}
$isOtherCity = $currentLoc !== '' && !isset($citySet[strtolower($currentLoc)]);

include 'header.php';
include 'sidebar.php';
?>

<div class="glass-card p-10 max-w-4xl mx-auto">
    <div class="flex items-center gap-4 mb-10">
        <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
            <i class="fas fa-user-edit text-xl"></i>
        </div>
        <div>
            <h2 class="text-2xl font-black text-primary mb-1"><?= $id ? 'Edit Tailor Profile' : 'Register New Tailor' ?></h2>
            <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Update professional information and pricing</p>
        </div>
    </div>

    <form action="save_tailor.php" method="POST" class="space-y-8">
        <input type="hidden" name="id" value="<?= $id ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Full Name</label>
                <input type="text" name="name" class="form-control rounded-2xl border-gray-100 bg-gray-50/50 py-3 px-5 text-sm font-bold text-gray-700" value="<?= htmlspecialchars((string)$tailor['name']) ?>" required>
            </div>
            
            <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Short Tagline</label>
                <input type="text" name="tagline" class="form-control rounded-2xl border-gray-100 bg-gray-50/50 py-3 px-5 text-sm font-bold text-gray-700" value="<?= htmlspecialchars((string)$tailor['tagline']) ?>" placeholder="e.g. Master of Bespoke Suits">
            </div>
            
            <div class="col-span-full space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Profile Image URL</label>
                <input type="url" name="profile_image" class="form-control rounded-2xl border-gray-100 bg-gray-50/50 py-3 px-5 text-sm font-bold text-gray-700" value="<?= htmlspecialchars((string)$tailor['profile_image']) ?>" placeholder="https://images.unsplash.com/..." required>
            </div>
            
            <div class="col-span-full space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Professional Biography</label>
                <textarea name="description" class="form-control rounded-2xl border-gray-100 bg-gray-50/50 py-4 px-5 text-sm font-medium text-gray-600 leading-relaxed" rows="4"><?= htmlspecialchars((string)$tailor['description']) ?></textarea>
            </div>
            
            <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">City / Location</label>
                <select name="location" class="form-select rounded-2xl border-gray-100 bg-gray-50/50 py-3 px-5 text-sm font-bold text-gray-700" required data-city-select>
                    <option value="" <?= trim((string)$tailor['location']) === '' ? 'selected' : '' ?> disabled>Select city...</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= htmlspecialchars((string)$city) ?>" <?= strtolower(trim((string)$tailor['location'])) === strtolower(trim((string)$city)) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$city) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="__other__" <?= $isOtherCity ? 'selected' : '' ?>>Other (Type City)</option>
                </select>
                <div class="mt-3 d-none" data-city-other-wrap>
                    <input type="text" name="location_other" class="form-control rounded-2xl border-gray-100 bg-gray-50/50 py-3 px-5 text-sm font-bold text-gray-700" value="<?= $isOtherCity ? htmlspecialchars((string)$currentLoc) : '' ?>" placeholder="Type city name" data-city-other-input>
                </div>
            </div>
            
            <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Experience (Years)</label>
                <input type="number" name="experience_years" class="form-control rounded-2xl border-gray-100 bg-gray-50/50 py-3 px-5 text-sm font-bold text-gray-700" value="<?= htmlspecialchars((string)$tailor['experience_years']) ?>">
            </div>
            
            <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Starting Price (PKR)</label>
                <input type="number" step="0.01" name="price_range_min" class="form-control rounded-2xl border-gray-100 bg-gray-50/50 py-3 px-5 text-sm font-bold text-gray-700" value="<?= htmlspecialchars((string)$tailor['price_range_min']) ?>">
            </div>
            
            <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Max Price (PKR)</label>
                <input type="number" step="0.01" name="price_range_max" class="form-control rounded-2xl border-gray-100 bg-gray-50/50 py-3 px-5 text-sm font-bold text-gray-700" value="<?= htmlspecialchars((string)$tailor['price_range_max']) ?>">
            </div>
            
            <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Contact Email</label>
                <input type="email" name="email" class="form-control rounded-2xl border-gray-100 bg-gray-50/50 py-3 px-5 text-sm font-bold text-gray-700" value="<?= htmlspecialchars((string)$tailor['email']) ?>">
            </div>
            
            <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Phone Number</label>
                <input type="text" name="phone" class="form-control rounded-2xl border-gray-100 bg-gray-50/50 py-3 px-5 text-sm font-bold text-gray-700" value="<?= htmlspecialchars((string)$tailor['phone']) ?>">
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Login Password (leave blank to keep current)</label>
                <input type="password" name="password" class="form-control rounded-2xl border-gray-100 bg-gray-50/50 py-3 px-5 text-sm font-bold text-gray-700" placeholder="••••••••">
            </div>
        </div>
        
        <div class="pt-10 border-t border-gray-100 flex justify-end gap-4">
            <a href="tailors.php" class="btn btn-outline !py-3 !px-8 text-[10px] font-black uppercase tracking-widest no-underline">Cancel</a>
            <button type="submit" class="btn btn-primary !py-3 !px-10 text-[10px] font-black uppercase tracking-widest shadow-xl shadow-primary/20">Save Professional Profile</button>
        </div>
    </form>
</div>

<script>
    (function() {
        const select = document.querySelector('[data-city-select]');
        const wrap = document.querySelector('[data-city-other-wrap]');
        const input = document.querySelector('[data-city-other-input]');
        if (!select || !wrap || !input) return;

        const toggle = () => {
            const isOther = select.value === '__other__';
            if (isOther) {
                wrap.classList.remove('d-none');
                input.required = true;
                input.focus();
            } else {
                wrap.classList.add('d-none');
                input.required = false;
                input.value = '';
            }
        };

        select.addEventListener('change', toggle);
        toggle();
    })();
</script>

<?php include 'footer.php'; ?>
