<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

$tailor_id = (int)$_SESSION['tailor_id'];

if (!$pdo) {
    header("Location: index.php");
    exit;
}

try {
    try {
        $pdo->exec("ALTER TABLE tailors ADD COLUMN address TEXT");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE tailors ADD COLUMN skills TEXT");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE tailors ADD COLUMN instagram_link VARCHAR(255)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE tailors ADD COLUMN price_range_min DECIMAL(10,2)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE tailors ADD COLUMN profile_completed TINYINT(1) NOT NULL DEFAULT 0");
    } catch (Exception $e) {
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
    } catch (Exception $e) {
    }
} catch (Exception $e) {
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim((string)$_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim((string)$_POST['address']) : '';
    $experience_years = isset($_POST['experience_years']) && is_numeric($_POST['experience_years']) ? (int)$_POST['experience_years'] : 0;
    $skills = isset($_POST['skills']) ? trim((string)$_POST['skills']) : '';
    $price_range_min = isset($_POST['price_range_min']) && is_numeric($_POST['price_range_min']) ? (float)$_POST['price_range_min'] : null;
    $instagram_link_raw = isset($_POST['instagram_link']) ? trim((string)$_POST['instagram_link']) : '';
    $instagram_link = $instagram_link_raw !== '' && filter_var($instagram_link_raw, FILTER_VALIDATE_URL) ? $instagram_link_raw : '';

    if ($name === '' || $address === '' || $skills === '' || $price_range_min === null) {
        $error = 'Please fill in Full Name, Address, Skills, and Starting Price.';
    } else {
        $profile_image = null;
        $profile_blob = null;
        $profile_mime = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $fileType = isset($_FILES['profile_image']['type']) ? (string)$_FILES['profile_image']['type'] : '';
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (in_array($fileType, $allowedTypes, true)) {
                $isServerless = getenv('VERCEL') === '1' || getenv('AWS_LAMBDA_FUNCTION_NAME');
                if ($isServerless) {
                    $bytes = @file_get_contents($_FILES['profile_image']['tmp_name']);
                    if ($bytes !== false && $bytes !== '') {
                        $profile_blob = $bytes;
                        $profile_mime = $fileType;
                    }
                } else {
                    $uploadDir = '../uploads/profile/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $fileName = uniqid() . '_' . basename($_FILES['profile_image']['name']);
                    $uploadPath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                        $profile_image = 'uploads/profile/' . $fileName;
                    }
                }
            }
        }

        try {
            try { $pdo->exec("ALTER TABLE tailors ADD COLUMN profile_image_blob LONGBLOB NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE tailors ADD COLUMN profile_image_mime VARCHAR(100) NULL"); } catch (Exception $e) {}

            if ($profile_blob !== null) {
                $stmt = $pdo->prepare("UPDATE tailors SET name = ?, phone = ?, address = ?, experience_years = ?, skills = ?, instagram_link = ?, price_range_min = ?, profile_image_blob = ?, profile_image_mime = ?, profile_completed = 1 WHERE id = ?");
                $stmt->execute([$name, $phone, $address, $experience_years, $skills, $instagram_link, $price_range_min, $profile_blob, $profile_mime, $tailor_id]);
            } else if ($profile_image) {
                $stmt = $pdo->prepare("UPDATE tailors SET name = ?, phone = ?, address = ?, experience_years = ?, skills = ?, instagram_link = ?, price_range_min = ?, profile_image = ?, profile_completed = 1 WHERE id = ?");
                $stmt->execute([$name, $phone, $address, $experience_years, $skills, $instagram_link, $price_range_min, $profile_image, $tailor_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE tailors SET name = ?, phone = ?, address = ?, experience_years = ?, skills = ?, instagram_link = ?, price_range_min = ?, profile_completed = 1 WHERE id = ?");
                $stmt->execute([$name, $phone, $address, $experience_years, $skills, $instagram_link, $price_range_min, $tailor_id]);
            }

            if (isset($_FILES['portfolio_images']) && isset($_FILES['portfolio_images']['tmp_name']) && is_array($_FILES['portfolio_images']['tmp_name'])) {
                $uploadDir = '../uploads/portfolio/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $imgInsertStmt = $pdo->prepare("INSERT INTO portfolio_images (tailor_id, image_url, description) VALUES (?, ?, ?)");

                foreach ($_FILES['portfolio_images']['tmp_name'] as $key => $tmpName) {
                    if (!isset($_FILES['portfolio_images']['error'][$key]) || $_FILES['portfolio_images']['error'][$key] !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    $fileType = isset($_FILES['portfolio_images']['type'][$key]) ? (string)$_FILES['portfolio_images']['type'][$key] : '';
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                    if (!in_array($fileType, $allowedTypes, true)) {
                        continue;
                    }
                    $fileName = uniqid() . '_' . basename($_FILES['portfolio_images']['name'][$key]);
                    $uploadPath = $uploadDir . $fileName;
                    if (move_uploaded_file($tmpName, $uploadPath)) {
                        $imgInsertStmt->execute([$tailor_id, 'uploads/portfolio/' . $fileName, null]);
                    }
                }
            }

            $_SESSION['profile_completed'] = 1;
            header("Location: index.php?profile_completed=1");
            exit;
        } catch (Exception $e) {
            $error = 'Could not save your profile. Please try again.';
        }
    }
}

$tailor = null;
try {
    try { $pdo->exec("ALTER TABLE tailors ADD COLUMN profile_image_blob LONGBLOB NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE tailors ADD COLUMN profile_image_mime VARCHAR(100) NULL"); } catch (Exception $e) {}
    $stmt = $pdo->prepare("SELECT name, phone, address, experience_years, skills, instagram_link, price_range_min, profile_image, profile_image_blob FROM tailors WHERE id = ?");
    $stmt->execute([$tailor_id]);
    $tailor = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

if (!$tailor) {
    header("Location: ../admin/logout.php");
    exit;
}

$has_blob = isset($tailor['profile_image_blob']) && $tailor['profile_image_blob'] !== null && $tailor['profile_image_blob'] !== '';
$tailor_avatar = $has_blob ? ('../image.php?kind=tailor&id=' . (int)$tailor_id) : (isset($tailor['profile_image']) && $tailor['profile_image'] ? (string)$tailor['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode((string)$tailor['name']) . '&background=865294&color=fff');
if (strpos($tailor_avatar, '../image.php?') === 0) {
} else if (strpos($tailor_avatar, 'http://') !== 0 && strpos($tailor_avatar, 'https://') !== 0) {
    $tailor_avatar = '../' . ltrim($tailor_avatar, '/');
}

include 'header.php';
include 'sidebar.php';
?>

<div class="glass-card p-10 max-w-4xl mx-auto mt-8">
    <div class="flex items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-black text-primary mb-1">Complete Profile</h2>
            <p class="text-xs text-gray-500 font-medium mb-0">Complete your profile to access the tailor dashboard.</p>
        </div>
        <img src="<?= htmlspecialchars((string)$tailor_avatar) ?>" class="w-12 h-12 rounded-2xl border border-gray-100 object-cover" alt="Profile">
    </div>

    <?php if (isset($_GET['password_changed']) && $_GET['password_changed'] == '1'): ?>
        <div class="mb-8 p-4 rounded-2xl border bg-green-50 border-green-100">
            <p class="text-xs font-extrabold text-green-700 uppercase tracking-widest mb-1">Password Updated</p>
            <p class="text-sm font-semibold text-green-800 mb-0">Now complete your profile to continue.</p>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="mb-8 p-4 rounded-2xl border bg-red-50 border-red-100">
            <p class="text-xs font-extrabold text-red-600 uppercase tracking-widest mb-1">Error</p>
            <p class="text-sm font-semibold text-red-800 mb-0"><?= htmlspecialchars((string)$error) ?></p>
        </div>
    <?php endif; ?>

    <form action="complete_profile.php" method="POST" enctype="multipart/form-data" class="space-y-6">
        <div class="row g-4">
            <div class="col-md-6">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars((string)($tailor['name'] ?? '')) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars((string)($tailor['phone'] ?? '')) ?>">
            </div>
            <div class="col-12">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Address</label>
                <textarea name="address" class="form-control" rows="3" required><?= htmlspecialchars((string)($tailor['address'] ?? '')) ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Experience (years)</label>
                <input type="number" name="experience_years" class="form-control" value="<?= htmlspecialchars((string)($tailor['experience_years'] ?? '0')) ?>" min="0">
            </div>
            <div class="col-md-6">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Social Media (optional)</label>
                <input type="url" name="instagram_link" class="form-control" value="<?= htmlspecialchars((string)($tailor['instagram_link'] ?? '')) ?>" placeholder="https://instagram.com/yourprofile">
            </div>
            <div class="col-md-6">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Starting Price (PKR)</label>
                <input type="number" name="price_range_min" class="form-control" value="<?= htmlspecialchars((string)($tailor['price_range_min'] ?? '')) ?>" min="0" step="0.01" required>
            </div>
            <div class="col-12">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Skills</label>
                <input type="text" name="skills" class="form-control" value="<?= htmlspecialchars((string)($tailor['skills'] ?? '')) ?>" placeholder="e.g. Suits, Bridal, Gents" required>
            </div>
            <div class="col-12">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Profile Picture (optional)</label>
                <input type="file" name="profile_image" class="form-control" accept="image/*">
                <p class="text-[11px] text-gray-500 mt-2 mb-0 font-bold">On Vercel, please upload a photo once so it saves permanently.</p>
            </div>
            <div class="col-12">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Upload Images (optional)</label>
                <input type="file" name="portfolio_images[]" class="form-control" accept="image/*" multiple>
            </div>
        </div>

        <div class="pt-2">
            <button type="submit" class="btn btn-primary w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs shadow-lg hover:shadow-primary/20 transition-all">Save & Continue</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../admin/footer.php'; ?>
