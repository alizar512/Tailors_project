<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

$app_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$app = null;

if ($app_id && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM tailor_applications WHERE id = ?");
        $stmt->execute([$app_id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Log error
    }
}

if (!$app) {
    header("Location: applications.php");
    exit;
}

include 'header.php';
include 'sidebar.php';
?>

<div class="row g-4">
    <!-- Left Column: Details -->
    <div class="col-lg-4">
        <div class="glass-card p-8 sticky top-32">
            <div class="text-center mb-8">
                <?php
                $profileImgRaw = isset($app['profile_image']) ? (string)$app['profile_image'] : '';
                $profileImgRaw = trim($profileImgRaw);
                $hasLocalPath = $profileImgRaw !== '' && !preg_match('#^https?://#i', $profileImgRaw);
                $imgSrc = $profileImgRaw !== ''
                    ? ($hasLocalPath ? '../' . $profileImgRaw : $profileImgRaw)
                    : '';
                ?>
                <?php if ($imgSrc !== ''): ?>
                    <img src="<?= htmlspecialchars((string)$imgSrc) ?>" alt="Profile picture" class="w-24 h-24 rounded-3xl object-cover mx-auto mb-4 border border-gray-100 shadow-sm">
                <?php else: ?>
                    <div class="w-24 h-24 rounded-3xl bg-primary/10 flex items-center justify-center text-primary font-black text-3xl mx-auto mb-4">
                        <?= substr($app['name'], 0, 1) ?>
                    </div>
                <?php endif; ?>
                <h2 class="text-2xl font-black text-primary mb-1"><?= htmlspecialchars((string)$app['name']) ?></h2>
                <p class="text-xs text-gray-500 font-bold uppercase tracking-widest"><?= htmlspecialchars((string)$app['specialization']) ?></p>
            </div>
            
            <div class="space-y-6">
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Contact Details</p>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-sm">
                            <i class="fas fa-envelope text-primary w-4"></i>
                            <span class="text-gray-700"><?= htmlspecialchars((string)$app['email']) ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <i class="fas fa-phone text-primary w-4"></i>
                            <span class="text-gray-700"><?= htmlspecialchars((string)$app['phone']) ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <i class="fas fa-map-marker-alt text-primary w-4"></i>
                            <span class="text-gray-700"><?= htmlspecialchars((string)$app['location']) ?></span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Experience</p>
                    <div class="flex items-center gap-3 text-sm">
                        <i class="fas fa-briefcase text-primary w-4"></i>
                        <span class="text-gray-700"><?= htmlspecialchars((string)$app['experience_years']) ?> Years Professional Experience</span>
                    </div>
                </div>

                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Starting Price (PKR)</p>
                    <div class="flex items-center gap-3 text-sm">
                        <i class="fas fa-tag text-primary w-4"></i>
                        <span class="text-gray-700">PKR <?= number_format((float)isset($app['price_range_min']) && $app['price_range_min'] !== '' ? (float)$app['price_range_min'] : 0) ?></span>
                    </div>
                </div>

                <?php if ($app['instagram_link']): ?>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Social Media</p>
                    <a href="<?= htmlspecialchars((string)$app['instagram_link']) ?>" target="_blank" class="flex items-center gap-3 text-sm text-primary no-underline font-bold">
                        <i class="fab fa-instagram w-4"></i>
                        <span>View Instagram Profile</span>
                    </a>
                </div>
                <?php endif; ?>

                <div class="pt-6 border-t border-gray-100 flex flex-col gap-3">
                    <?php if ($app['status'] === 'pending'): ?>
                        <form action="process_application.php" method="POST" onsubmit="return confirm('Approve this application?');">
                            <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-primary w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs">Approve Application</button>
                        </form>
                        <form action="process_application.php" method="POST" onsubmit="return confirm('Reject this application?');">
                            <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-outline w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs !text-red-500 !border-red-100 hover:!bg-red-50">Reject Application</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Portfolio -->
    <div class="col-lg-8">
        <div class="glass-card p-8 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-xl font-black text-primary mb-1">Portfolio Showcase</h3>
                    <p class="text-xs text-gray-500 font-medium mb-0">Review uploaded work samples before approving.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="px-4 py-2 rounded-2xl bg-primary/5 border border-primary/10">
                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Images</span>
                        <span class="text-sm font-black text-primary ms-2">
                            <?php $imagesCount = is_array(json_decode($app['portfolio_link'], true)) ? count(json_decode($app['portfolio_link'], true)) : 0; ?>
                            <?= (int)$imagesCount ?>
                        </span>
                    </div>
                    <div class="px-4 py-2 rounded-2xl bg-primary/5 border border-primary/10">
                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Videos</span>
                        <span class="text-sm font-black text-primary ms-2">
                            <?php $videosCount = is_array(json_decode($app['portfolio_videos'], true)) ? count(json_decode($app['portfolio_videos'], true)) : 0; ?>
                            <?= (int)$videosCount ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Uploaded Images</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4 mb-8">
                <?php 
                $images = json_decode($app['portfolio_link'], true) ?: [];
                if (empty($images)): ?>
                    <div class="col-span-full"><p class="text-sm text-gray-400 italic mb-0">No images uploaded.</p></div>
                <?php else: foreach ($images as $img): ?>
                    <?php $src = '../' . ltrim((string)$img, '/'); ?>
                    <button type="button" class="group text-left relative overflow-hidden rounded-3xl bg-gray-100 border border-gray-100 shadow-sm hover:shadow-lg transition-all aspect-[4/3]" data-portfolio-open="<?= htmlspecialchars((string)$src) ?>">
                        <img src="<?= htmlspecialchars((string)$src) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" alt="Portfolio image">
                        <div class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="absolute inset-x-0 bottom-0 p-3 bg-gradient-to-t from-black/75 via-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black text-white uppercase tracking-widest">View Large</span>
                                <span class="w-9 h-9 rounded-2xl bg-white text-primary flex items-center justify-center shadow-lg">
                                    <i class="fas fa-expand text-sm"></i>
                                </span>
                            </div>
                        </div>
                    </button>
                <?php endforeach; endif; ?>
            </div>
            
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Uploaded Videos</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php 
                $videos = json_decode($app['portfolio_videos'], true) ?: [];
                if (empty($videos)): ?>
                    <div class="col-span-full"><p class="text-sm text-gray-400 italic mb-0">No videos uploaded.</p></div>
                <?php else: foreach ($videos as $vid): ?>
                    <?php $vsrc = '../' . ltrim((string)$vid, '/'); ?>
                    <div class="rounded-3xl overflow-hidden bg-black aspect-video border border-black/10 shadow-sm">
                        <video controls class="w-full h-full">
                            <source src="<?= htmlspecialchars((string)$vsrc) ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
        
        <div class="glass-card p-8">
            <h3 class="text-xl font-black text-primary mb-4">Full Address</h3>
            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                <p class="text-sm text-gray-700 leading-relaxed mb-0">
                    <?= nl2br(htmlspecialchars((string)$app['address'])) ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div id="portfolioLightbox" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-[99999] p-4">
    <button type="button" id="portfolioLightboxClose" class="absolute top-4 right-4 w-11 h-11 rounded-2xl bg-white text-gray-800 flex items-center justify-center shadow-xl">
        <i class="fas fa-xmark"></i>
    </button>
    <img id="portfolioLightboxImg" src="" alt="Preview" class="max-h-[85vh] max-w-[92vw] rounded-3xl shadow-2xl border border-white/10 bg-white">
</div>

<script>
    (function() {
        const lb = document.getElementById('portfolioLightbox');
        const img = document.getElementById('portfolioLightboxImg');
        const closeBtn = document.getElementById('portfolioLightboxClose');
        if (!lb || !img || !closeBtn) return;

        const close = () => {
            lb.classList.add('hidden');
            lb.classList.remove('flex');
            img.src = '';
        };

        const open = (src) => {
            img.src = src;
            lb.classList.remove('hidden');
            lb.classList.add('flex');
        };

        document.querySelectorAll('[data-portfolio-open]').forEach(btn => {
            btn.addEventListener('click', () => {
                const src = btn.getAttribute('data-portfolio-open') || '';
                if (src) open(src);
            });
        });

        closeBtn.addEventListener('click', close);
        lb.addEventListener('click', (e) => {
            if (e.target === lb) close();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
        });
    })();
</script>

<?php include 'footer.php'; ?>
