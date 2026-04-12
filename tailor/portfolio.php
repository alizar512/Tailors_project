<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/schema_utils.php';

$tailor_id = (int)$_SESSION['tailor_id'];

if (!$pdo) {
    header("Location: index.php");
    exit;
}

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS portfolio_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tailor_id INT,
            image_url VARCHAR(255) NOT NULL,
            description VARCHAR(255),
            FOREIGN KEY (tailor_id) REFERENCES tailors(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS portfolio_videos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tailor_id INT,
            video_url VARCHAR(255) NOT NULL,
            description VARCHAR(255),
            FOREIGN KEY (tailor_id) REFERENCES tailors(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (Exception $e) {
}

silah_ensure_column($pdo, 'portfolio_images', 'image_blob', "ALTER TABLE portfolio_images ADD COLUMN image_blob LONGBLOB NULL");
silah_ensure_column($pdo, 'portfolio_images', 'image_mime', "ALTER TABLE portfolio_images ADD COLUMN image_mime VARCHAR(100) NULL");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = (string)$_POST['action'];

    if ($action === 'add') {
        $description = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
        $image_url = null;
        $image_blob = null;
        $image_mime = null;
        $isServerless = getenv('VERCEL') === '1' || getenv('AWS_LAMBDA_FUNCTION_NAME');

        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $fileType = isset($_FILES['image_file']['type']) ? (string)$_FILES['image_file']['type'] : '';
            $fileSize = isset($_FILES['image_file']['size']) ? (int)$_FILES['image_file']['size'] : 0;
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (in_array($fileType, $allowedTypes, true)) {
                if ($isServerless) {
                    if ($fileSize > 0 && $fileSize <= 1200000) {
                        $bytes = @file_get_contents($_FILES['image_file']['tmp_name']);
                        if ($bytes !== false && $bytes !== '') {
                            $outBytes = $bytes;
                            $outMime = $fileType;
                            if (function_exists('imagecreatefromstring') && function_exists('imagejpeg') && function_exists('imagesx') && function_exists('imagesy')) {
                                try {
                                    $im = @imagecreatefromstring($bytes);
                                    if ($im) {
                                        $w = imagesx($im);
                                        $h = imagesy($im);
                                        $max = 900;
                                        $scale = ($w > 0 && $h > 0) ? min(1, $max / max($w, $h)) : 1;
                                        $nw = (int)max(1, floor($w * $scale));
                                        $nh = (int)max(1, floor($h * $scale));
                                        if (($scale < 1 || $outMime !== 'image/jpeg') && function_exists('imagecreatetruecolor')) {
                                            $dst = imagecreatetruecolor($nw, $nh);
                                            if ($dst) {
                                                imagecopyresampled($dst, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
                                                ob_start();
                                                imagejpeg($dst, null, 82);
                                                $jpeg = ob_get_clean();
                                                imagedestroy($dst);
                                                if (is_string($jpeg) && $jpeg !== '') {
                                                    $outBytes = $jpeg;
                                                    $outMime = 'image/jpeg';
                                                }
                                            }
                                        }
                                        imagedestroy($im);
                                    }
                                } catch (Exception $e) {
                                }
                            }
                            if (strlen($outBytes) <= 1200000) {
                                $image_blob = $outBytes;
                                $image_mime = $outMime;
                                $image_url = '';
                            }
                        }
                    }
                } else {
                    $uploadDir = '../uploads/portfolio/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                    $fileName = uniqid() . '_' . basename($_FILES['image_file']['name']);
                    $uploadPath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadPath)) {
                        $image_url = 'uploads/portfolio/' . $fileName;
                    }
                }
            }
        }

        if ($image_url) {
            try {
                $stmt = $pdo->prepare("INSERT INTO portfolio_images (tailor_id, image_url, description, image_blob, image_mime) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$tailor_id, $image_url, $description !== '' ? $description : null, $image_blob, $image_mime]);
                header("Location: portfolio.php?added=1");
                exit;
            } catch (Exception $e) {
                header("Location: portfolio.php?added=0");
                exit;
            }
        } else {
            header("Location: portfolio.php?added=0");
            exit;
        }
    }

    if ($action === 'delete' && isset($_POST['image_id'])) {
        $image_id = (int)$_POST['image_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM portfolio_images WHERE id = ? AND tailor_id = ?");
            $stmt->execute([$image_id, $tailor_id]);
            header("Location: portfolio.php?deleted=1");
            exit;
        } catch (Exception $e) {
            header("Location: portfolio.php?deleted=0");
            exit;
        }
    }

    if ($action === 'add_video') {
        $description = isset($_POST['video_description']) ? trim((string)$_POST['video_description']) : '';
        $video_url = null;
        $isServerless = getenv('VERCEL') === '1' || getenv('AWS_LAMBDA_FUNCTION_NAME');
        if ($isServerless) {
            $urlRaw = isset($_POST['video_url']) ? trim((string)$_POST['video_url']) : '';
            $video_url = $urlRaw !== '' && filter_var($urlRaw, FILTER_VALIDATE_URL) ? $urlRaw : null;
            if (!$video_url) {
                header("Location: portfolio.php?video_added=0");
                exit;
            }
            try {
                $stmt = $pdo->prepare("INSERT INTO portfolio_videos (tailor_id, video_url, description) VALUES (?, ?, ?)");
                $stmt->execute([$tailor_id, $video_url, $description !== '' ? $description : null]);
                header("Location: portfolio.php?video_added=1");
                exit;
            } catch (Exception $e) {
                header("Location: portfolio.php?video_added=0");
                exit;
            }
        }

        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
            $fileType = isset($_FILES['video_file']['type']) ? (string)$_FILES['video_file']['type'] : '';
            $allowedTypes = ['video/mp4', 'video/webm', 'video/quicktime'];
            if (in_array($fileType, $allowedTypes, true)) {
                $uploadDir = '../uploads/portfolio/videos/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $fileName = uniqid() . '_' . basename($_FILES['video_file']['name']);
                $uploadPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['video_file']['tmp_name'], $uploadPath)) {
                    $video_url = 'uploads/portfolio/videos/' . $fileName;
                }
            }
        }

        if ($video_url) {
            try {
                $stmt = $pdo->prepare("INSERT INTO portfolio_videos (tailor_id, video_url, description) VALUES (?, ?, ?)");
                $stmt->execute([$tailor_id, $video_url, $description !== '' ? $description : null]);
                header("Location: portfolio.php?video_added=1");
                exit;
            } catch (Exception $e) {
                header("Location: portfolio.php?video_added=0");
                exit;
            }
        } else {
            header("Location: portfolio.php?video_added=0");
            exit;
        }
    }

    if ($action === 'delete_video' && isset($_POST['video_id'])) {
        $video_id = (int)$_POST['video_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM portfolio_videos WHERE id = ? AND tailor_id = ?");
            $stmt->execute([$video_id, $tailor_id]);
            header("Location: portfolio.php?video_deleted=1");
            exit;
        } catch (Exception $e) {
            header("Location: portfolio.php?video_deleted=0");
            exit;
        }
    }
}

$images = [];
$videos = [];
$tailor_email = null;
try {
    $stmt = $pdo->prepare("SELECT email FROM tailors WHERE id = ?");
    $stmt->execute([$tailor_id]);
    $tailor_email = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM portfolio_images WHERE tailor_id = ? ORDER BY id DESC");
    $stmt->execute([$tailor_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM portfolio_videos WHERE tailor_id = ? ORDER BY id DESC");
    $stmt->execute([$tailor_id]);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($videos) && $tailor_email) {
        $stmt = $pdo->prepare("SELECT portfolio_videos FROM tailor_applications WHERE email = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([(string)$tailor_email]);
        $portfolio_videos_json = $stmt->fetchColumn();
        $appVideos = json_decode(is_string($portfolio_videos_json) ? $portfolio_videos_json : '', true);
        if (is_array($appVideos)) {
            foreach ($appVideos as $vidPath) {
                $vidPath = is_string($vidPath) ? trim($vidPath) : '';
                if ($vidPath !== '') {
                    $videos[] = ['id' => 0, 'video_url' => $vidPath, 'description' => 'Portfolio video'];
                }
            }
        }
    }
} catch (Exception $e) {
}

include 'header.php';
include 'sidebar.php';
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass-card p-8 sticky top-32">
            <h3 class="text-xl font-black text-primary mb-2">Add Portfolio Image</h3>
            <p class="text-xs text-gray-500 font-medium mb-6">Upload a photo of your work</p>

            <?php if (isset($_GET['added'])): ?>
                <div class="mb-6 p-4 rounded-2xl border <?= $_GET['added'] == '1' ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' ?>">
                    <p class="text-xs font-extrabold uppercase tracking-widest mb-1 <?= $_GET['added'] == '1' ? 'text-green-700' : 'text-red-600' ?>">
                        <?= $_GET['added'] == '1' ? 'Added' : 'Upload Failed' ?>
                    </p>
                    <p class="text-sm font-semibold mb-0 <?= $_GET['added'] == '1' ? 'text-green-800' : 'text-red-800' ?>">
                        <?= $_GET['added'] == '1' ? 'Portfolio image added.' : 'Could not add your image.' ?>
                    </p>
                </div>
            <?php endif; ?>

            <form action="portfolio.php" method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="action" value="add">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Image</label>
                    <input type="file" name="image_file" class="form-control" accept="image/*" required>
                </div>
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Description</label>
                    <input type="text" name="description" class="form-control" placeholder="e.g. Bridal Dress, Suit, Sherwani">
                </div>
                <button type="submit" class="btn btn-primary w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs shadow-lg hover:shadow-primary/20 transition-all">Upload</button>
            </form>

            <div class="mt-10 pt-8 border-t border-gray-100">
                <h3 class="text-xl font-black text-primary mb-2">Add Portfolio Video</h3>
                <p class="text-xs text-gray-500 font-medium mb-6">Upload a short video of your work</p>

                <?php if (isset($_GET['video_added'])): ?>
                    <div class="mb-6 p-4 rounded-2xl border <?= $_GET['video_added'] == '1' ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' ?>">
                        <p class="text-xs font-extrabold uppercase tracking-widest mb-1 <?= $_GET['video_added'] == '1' ? 'text-green-700' : 'text-red-600' ?>">
                            <?= $_GET['video_added'] == '1' ? 'Added' : 'Upload Failed' ?>
                        </p>
                        <p class="text-sm font-semibold mb-0 <?= $_GET['video_added'] == '1' ? 'text-green-800' : 'text-red-800' ?>">
                            <?= $_GET['video_added'] == '1' ? 'Portfolio video added.' : 'Could not add your video.' ?>
                        </p>
                    </div>
                <?php endif; ?>

                <form action="portfolio.php" method="POST" enctype="multipart/form-data" class="space-y-5">
                    <input type="hidden" name="action" value="add_video">
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Video</label>
                        <?php $isServerless = getenv('VERCEL') === '1' || getenv('AWS_LAMBDA_FUNCTION_NAME'); ?>
                        <?php if ($isServerless): ?>
                            <input type="text" name="video_url" class="form-control" placeholder="Paste video link (YouTube/Instagram)" required>
                            <div class="form-text text-xs">On Vercel, upload videos as links for better performance.</div>
                        <?php else: ?>
                            <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/quicktime" required>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Description</label>
                        <input type="text" name="video_description" class="form-control" placeholder="e.g. Stitching process, Bridal outfit">
                    </div>
                    <button type="submit" class="btn btn-primary w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs shadow-lg hover:shadow-primary/20 transition-all"><?= $isServerless ? 'Add Video Link' : 'Upload Video' ?></button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="glass-card overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white/50">
                <div>
                    <h3 class="text-xl font-black text-primary mb-1">My Portfolio</h3>
                    <p class="text-xs text-gray-500 font-medium mb-0">Images and videos visible in your profile</p>
                </div>
            </div>

            <div class="p-8">
                <?php if (empty($images)): ?>
                    <div class="py-20 text-center">
                        <div class="text-gray-300 mb-4 text-4xl"><i class="fas fa-images"></i></div>
                        <p class="text-sm text-gray-400 font-bold uppercase tracking-widest">No images yet</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($images as $img): ?>
                            <?php
                                $imgSrc = '../portfolio_media.php?id=' . (int)$img['id'];
                                $imgLink = $imgSrc;
                            ?>
                            <div class="col-md-4 col-6">
                                <div class="group relative aspect-square overflow-hidden rounded-2xl bg-gray-100 border border-gray-100">
                                    <img src="<?= htmlspecialchars((string)$imgSrc) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                                        <a href="<?= htmlspecialchars((string)$imgLink) ?>" target="_blank" class="w-10 h-10 rounded-full bg-white text-primary flex items-center justify-center shadow-xl hover:scale-110 transition-transform">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="portfolio.php" method="POST" onsubmit="return confirm('Delete this portfolio image?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
                                            <button type="submit" class="w-10 h-10 rounded-full bg-white text-red-500 flex items-center justify-center shadow-xl hover:scale-110 transition-transform">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <?php if (isset($img['description']) && $img['description']): ?>
                                        <div class="absolute bottom-0 left-0 right-0 bg-white/90 backdrop-blur px-3 py-2">
                                            <p class="text-[10px] font-black text-gray-700 mb-0 truncate"><?= htmlspecialchars((string)$img['description']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="px-8 pb-8">
                <h4 class="text-lg font-black text-primary mb-4">Videos</h4>
                <?php if (empty($videos)): ?>
                    <div class="py-12 text-center bg-white rounded-2xl border border-dashed border-gray-200">
                        <p class="text-sm text-gray-400 font-bold uppercase tracking-widest mb-0">No videos yet</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($videos as $vid): ?>
                            <div class="col-md-6">
                                <div class="rounded-2xl overflow-hidden bg-black aspect-video shadow-lg relative group">
                                    <video controls class="w-full h-full">
                                        <source src="../<?= htmlspecialchars((string)$vid['video_url']) ?>" type="video/mp4">
                                    </video>
                                    <?php if (isset($vid['id']) && (int)$vid['id'] > 0): ?>
                                        <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <form action="portfolio.php" method="POST" onsubmit="return confirm('Delete this portfolio video?');">
                                                <input type="hidden" name="action" value="delete_video">
                                                <input type="hidden" name="video_id" value="<?= (int)$vid['id'] ?>">
                                                <button type="submit" class="w-10 h-10 rounded-full bg-white text-red-500 flex items-center justify-center shadow-xl hover:scale-110 transition-transform">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <p class="mt-2 text-[11px] text-gray-500 font-medium mb-0"><?= htmlspecialchars((string)($vid['description'] ?? 'Portfolio video')) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../admin/footer.php'; ?>
