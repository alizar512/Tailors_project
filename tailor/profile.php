<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/cities.php';

$tailor_id = (int)$_SESSION['tailor_id'];
$cities = silah_get_cities($pdo);
$currentLoc = '';
$citySet = [];
foreach ($cities as $c) {
    $citySet[strtolower(trim((string)$c))] = true;
}
$is_edit = isset($_GET['edit']) && (string)$_GET['edit'] === '1';
$is_debug = isset($_GET['debug']) && (string)$_GET['debug'] === '1';

if (!$pdo) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim((string)$_POST['phone']) : '';
    $location = isset($_POST['location']) ? trim((string)$_POST['location']) : '';
    if ($location === '__other__') {
        $location = isset($_POST['location_other']) ? trim((string)$_POST['location_other']) : '';
    }
    $address = isset($_POST['address']) ? trim((string)$_POST['address']) : '';
    $skills = isset($_POST['skills']) ? trim((string)$_POST['skills']) : '';
    $instagram_link_raw = isset($_POST['instagram_link']) ? trim((string)$_POST['instagram_link']) : '';
    $instagram_link = $instagram_link_raw !== '' && filter_var($instagram_link_raw, FILTER_VALIDATE_URL) ? $instagram_link_raw : '';
    $tagline = isset($_POST['tagline']) ? trim((string)$_POST['tagline']) : '';
    $description = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
    $price_range_min = isset($_POST['price_range_min']) && is_numeric($_POST['price_range_min']) ? (float)$_POST['price_range_min'] : null;

    try {
        if ($location !== '') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO cities (name, country, is_active) VALUES (?, 'Pakistan', 1)");
            $stmt->execute([$location]);
        }
    } catch (Exception $e) {
    }

    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileType = isset($_FILES['profile_image']['type']) ? (string)$_FILES['profile_image']['type'] : '';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (in_array($fileType, $allowedTypes, true)) {
            $uploadDir = '../uploads/profile/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileName = uniqid() . '_' . basename($_FILES['profile_image']['name']);
            $uploadPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                $profile_image = 'uploads/profile/' . $fileName;
            }
        }
    }

    if ($name !== '') {
        try {
            try {
                $pdo->exec("ALTER TABLE tailors ADD COLUMN price_range_min DECIMAL(10,2)");
            } catch (Exception $e) {
            }
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

            if ($profile_image) {
                $stmt = $pdo->prepare("UPDATE tailors SET name = ?, phone = ?, location = ?, address = ?, skills = ?, instagram_link = ?, tagline = ?, description = ?, price_range_min = ?, profile_image = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $location, $address, $skills, $instagram_link !== '' ? $instagram_link : null, $tagline, $description, $price_range_min, $profile_image, $tailor_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE tailors SET name = ?, phone = ?, location = ?, address = ?, skills = ?, instagram_link = ?, tagline = ?, description = ?, price_range_min = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $location, $address, $skills, $instagram_link !== '' ? $instagram_link : null, $tagline, $description, $price_range_min, $tailor_id]);
            }
            header("Location: profile.php?saved=1");
            exit;
        } catch (Exception $e) {
            header("Location: profile.php?saved=0");
            exit;
        }
    }
}

$tailor = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM tailors WHERE id = ?");
    $stmt->execute([$tailor_id]);
    $tailor = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

if (!$tailor) {
    header("Location: index.php");
    exit;
}

$currentLoc = isset($tailor['location']) ? trim((string)$tailor['location']) : '';
$isOtherCity = $currentLoc !== '' && !isset($citySet[strtolower($currentLoc)]);

try {
    $debugInfo = [
        'db' => null,
        'port' => null,
        'session_tailor_id' => (int)$_SESSION['tailor_id'],
        'resolved_tailor_id' => $tailor_id,
        'resolved_email' => null,
        'tailor_row' => null,
        'app_row' => null,
    ];
    if ($is_debug) {
        try {
            $debugInfo['db'] = (string)$pdo->query("SELECT DATABASE()")->fetchColumn();
        } catch (Exception $e) {
        }
        try {
            $debugInfo['port'] = (string)$pdo->query("SELECT @@port")->fetchColumn();
        } catch (Exception $e) {
        }
    }

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
        $pdo->exec("ALTER TABLE tailors ADD COLUMN tagline VARCHAR(255)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE tailors ADD COLUMN description TEXT");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE tailors ADD COLUMN profile_image VARCHAR(255)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE tailors ADD COLUMN phone VARCHAR(20)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE tailors ADD COLUMN email VARCHAR(100)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE tailors ADD COLUMN location VARCHAR(100)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE tailors ADD COLUMN experience_years INT DEFAULT 0");
    } catch (Exception $e) {
    }

    $lookupEmail = isset($_SESSION['tailor_email']) && $_SESSION['tailor_email'] ? trim((string)$_SESSION['tailor_email']) : (isset($tailor['email']) ? trim((string)$tailor['email']) : '');

    if ($lookupEmail !== '') {
        $stmt = $pdo->prepare("SELECT id FROM tailors WHERE REPLACE(LOWER(TRIM(email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '') ORDER BY id DESC LIMIT 1");
        $stmt->execute([$lookupEmail]);
        $latestTailorId = (int)$stmt->fetchColumn();
        if ($latestTailorId > 0 && $latestTailorId !== $tailor_id) {
            $tailor_id = $latestTailorId;
            $_SESSION['tailor_id'] = $latestTailorId;
            $stmt = $pdo->prepare("SELECT * FROM tailors WHERE id = ?");
            $stmt->execute([$tailor_id]);
            $tailor = $stmt->fetch(PDO::FETCH_ASSOC) ?: $tailor;
        }
    }

    $lookupName = isset($tailor['name']) ? trim((string)$tailor['name']) : '';
    $lookupPhone = isset($tailor['phone']) ? trim((string)$tailor['phone']) : '';
    if ($is_debug) {
        $debugInfo['resolved_tailor_id'] = (int)$tailor_id;
        $debugInfo['resolved_email'] = $lookupEmail;
        $debugInfo['tailor_row'] = [
            'id' => isset($tailor['id']) ? (int)$tailor['id'] : null,
            'email' => $tailor['email'] ?? null,
            'phone' => $tailor['phone'] ?? null,
            'location' => $tailor['location'] ?? null,
            'address' => $tailor['address'] ?? null,
            'price_range_min' => $tailor['price_range_min'] ?? null,
            'experience_years' => $tailor['experience_years'] ?? null,
        ];
    }

    $app = null;
    if ($lookupEmail !== '') {
        $stmt = $pdo->prepare("SELECT * FROM tailor_applications WHERE REPLACE(LOWER(TRIM(email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '') ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$lookupEmail]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$app && $lookupName !== '') {
        $stmt = $pdo->prepare("SELECT * FROM tailor_applications WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$lookupName]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$app && $lookupPhone !== '') {
        $stmt = $pdo->prepare("SELECT * FROM tailor_applications WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(?), ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$lookupPhone]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($app) {
        if ($is_debug) {
            $debugInfo['app_row'] = [
                'id' => isset($app['id']) ? (int)$app['id'] : null,
                'email' => $app['email'] ?? null,
                'phone' => $app['phone'] ?? null,
                'location' => $app['location'] ?? null,
                'address' => $app['address'] ?? null,
                'price_range_min' => $app['price_range_min'] ?? null,
                'experience_years' => $app['experience_years'] ?? null,
                'status' => $app['status'] ?? null,
            ];
        }
        $shouldOverwrite = function ($current, $next) {
            if ($next === null) return false;
            if (is_string($next) && trim($next) === '') return false;

            if ($current === null) return true;
            if (is_string($current) && trim($current) === '') return true;
            if (is_numeric($current) && (float)$current === 0.0 && is_numeric($next) && (float)$next > 0.0) return true;
            if (is_numeric($current) && (int)$current === 0 && is_numeric($next) && (int)$next > 0) return true;
            return false;
        };

        $setIfNeeded = function ($key, $value) use (&$tailor, $shouldOverwrite) {
            $current = $tailor[$key] ?? null;
            if ($shouldOverwrite($current, $value)) {
                $tailor[$key] = $value;
            }
        };

        $setIfNeeded('phone', $app['phone'] ?? null);
        $setIfNeeded('location', $app['location'] ?? null);
        $setIfNeeded('address', $app['address'] ?? null);
        $setIfNeeded('price_range_min', $app['price_range_min'] ?? null);
        $setIfNeeded('instagram_link', $app['instagram_link'] ?? null);
        $setIfNeeded('tagline', $app['specialization'] ?? null);
        $setIfNeeded('skills', $app['specialization'] ?? null);
        $setIfNeeded('description', isset($app['specialization']) && $app['specialization'] ? ('Expert in ' . (string)$app['specialization']) : null);
        $setIfNeeded('profile_image', $app['profile_image'] ?? null);
        $setIfNeeded('experience_years', $app['experience_years'] ?? null);

            $updates = [];
            $params = [];
            $fields = [
                'phone' => $tailor['phone'] ?? null,
                'location' => $tailor['location'] ?? null,
                'address' => $tailor['address'] ?? null,
                'price_range_min' => $tailor['price_range_min'] ?? null,
                'instagram_link' => $tailor['instagram_link'] ?? null,
                'tagline' => $tailor['tagline'] ?? null,
                'skills' => $tailor['skills'] ?? null,
                'description' => $tailor['description'] ?? null,
                'profile_image' => $tailor['profile_image'] ?? null,
                'experience_years' => $tailor['experience_years'] ?? null,
            ];
            foreach ($fields as $k => $v) {
                if ($v !== null && (!(is_string($v)) || trim($v) !== '')) {
                    $updates[] = $k . " = ?";
                    $params[] = $v;
                }
            }
            if (!empty($updates)) {
                $params[] = $tailor_id;
                $stmt = $pdo->prepare("UPDATE tailors SET " . implode(', ', $updates) . " WHERE id = ?");
                $stmt->execute($params);

                $stmt = $pdo->prepare("SELECT * FROM tailors WHERE id = ?");
                $stmt->execute([$tailor_id]);
                $tailor = $stmt->fetch(PDO::FETCH_ASSOC) ?: $tailor;
            }
    }
} catch (Exception $e) {
}

$tailor_avatar = isset($tailor['profile_image']) && $tailor['profile_image'] ? (string)$tailor['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode((string)$tailor['name']) . '&background=865294&color=fff';
if (strpos($tailor_avatar, 'http://') !== 0 && strpos($tailor_avatar, 'https://') !== 0) {
    $tailor_avatar = '../' . ltrim($tailor_avatar, '/');
}

include 'header.php';
include 'sidebar.php';
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass-card p-8 sticky top-32">
            <div class="text-center mb-8">
                <img src="<?= htmlspecialchars((string)$tailor_avatar) ?>" class="w-24 h-24 rounded-3xl object-cover mx-auto mb-4 border border-gray-100" alt="Profile">
                <h2 class="text-2xl font-black text-primary mb-1"><?= htmlspecialchars((string)$tailor['name']) ?></h2>
                <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mb-0"><?= htmlspecialchars((string)($tailor['tagline'] ?? '')) ?></p>
            </div>

            <div class="space-y-6">
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Contact</p>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-sm">
                            <i class="fas fa-envelope text-primary w-4"></i>
                            <span class="text-gray-700"><?= htmlspecialchars((string)($tailor['email'] ?? '')) ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <i class="fas fa-phone text-primary w-4"></i>
                            <span class="text-gray-700"><?= htmlspecialchars((string)($tailor['phone'] ?? '')) ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <i class="fas fa-map-marker-alt text-primary w-4"></i>
                            <span class="text-gray-700"><?= htmlspecialchars((string)($tailor['location'] ?? '')) ?></span>
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Experience</p>
                    <div class="flex items-center gap-3 text-sm">
                        <i class="fas fa-briefcase text-primary w-4"></i>
                        <span class="text-gray-700"><?= htmlspecialchars((string)($tailor['experience_years'] ?? '0')) ?> Years</span>
                    </div>
                </div>

                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Pricing</p>
                    <div class="flex items-center gap-3 text-sm">
                        <i class="fas fa-tag text-primary w-4"></i>
                        <span class="text-gray-700">
                            <?php
                                $minPrice = isset($tailor['price_range_min']) && $tailor['price_range_min'] !== '' ? (float)$tailor['price_range_min'] : 0;
                            ?>
                            Starting from PKR <?= number_format($minPrice) ?>
                        </span>
                    </div>
                </div>

                <div class="pt-2">
                    <?php if ($is_edit): ?>
                        <a href="profile.php" class="btn btn-outline w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs no-underline">Cancel</a>
                    <?php else: ?>
                        <a href="profile.php?edit=1" class="btn btn-primary w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs shadow-lg hover:shadow-primary/20 transition-all no-underline">Edit Profile</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="glass-card p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-black text-primary mb-1"><?= $is_edit ? 'Edit Profile' : 'Profile Details' ?></h3>
                    <p class="text-xs text-gray-500 font-medium mb-0"><?= $is_edit ? 'Update your public profile details' : 'Your saved profile information' ?></p>
                </div>
            </div>

            <?php if ($is_debug): ?>
                <div class="mb-8 p-4 rounded-2xl border bg-white border-gray-100">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Debug</p>
                    <pre class="text-xs text-gray-700 mb-0 whitespace-pre-wrap"><?= htmlspecialchars((string)json_encode($debugInfo, JSON_PRETTY_PRINT)) ?></pre>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['saved'])): ?>
                <div class="mb-8 p-4 rounded-2xl border <?= $_GET['saved'] == '1' ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' ?>">
                    <p class="text-xs font-extrabold uppercase tracking-widest mb-1 <?= $_GET['saved'] == '1' ? 'text-green-700' : 'text-red-600' ?>">
                        <?= $_GET['saved'] == '1' ? 'Saved' : 'Save Failed' ?>
                    </p>
                    <p class="text-sm font-semibold mb-0 <?= $_GET['saved'] == '1' ? 'text-green-800' : 'text-red-800' ?>">
                        <?= $_GET['saved'] == '1' ? 'Profile updated successfully.' : 'Could not update your profile.' ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if (!$is_edit): ?>
                <div class="row g-4">
                    <div class="col-md-6">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Full Name</p>
                        <div class="form-control bg-gray-50"><?= htmlspecialchars((string)$tailor['name']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Email</p>
                        <div class="form-control bg-gray-50"><?= htmlspecialchars((string)($tailor['email'] ?? '')) ?></div>
                    </div>
                    <div class="col-md-6">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Phone</p>
                        <div class="form-control bg-gray-50"><?= htmlspecialchars((string)($tailor['phone'] ?? '')) ?></div>
                    </div>
                    <div class="col-md-6">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">City</p>
                        <div class="form-control bg-gray-50"><?= htmlspecialchars((string)($tailor['location'] ?? '')) ?></div>
                    </div>
                    <div class="col-12">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Address</p>
                        <div class="form-control bg-gray-50"><?= nl2br(htmlspecialchars((string)($tailor['address'] ?? ''))) ?></div>
                    </div>
                    <div class="col-md-6">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Starting Price (PKR)</p>
                        <div class="form-control bg-gray-50">PKR <?= number_format(isset($tailor['price_range_min']) && $tailor['price_range_min'] !== '' ? (float)$tailor['price_range_min'] : 0) ?></div>
                    </div>
                    <div class="col-md-6">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Instagram</p>
                        <?php if (isset($tailor['instagram_link']) && $tailor['instagram_link']): ?>
                            <a class="form-control bg-gray-50 text-primary font-bold no-underline" href="<?= htmlspecialchars((string)$tailor['instagram_link']) ?>" target="_blank"><?= htmlspecialchars((string)$tailor['instagram_link']) ?></a>
                        <?php else: ?>
                            <div class="form-control bg-gray-50 text-gray-500">Not added</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Tagline</p>
                        <div class="form-control bg-gray-50"><?= htmlspecialchars((string)($tailor['tagline'] ?? '')) ?></div>
                    </div>
                    <div class="col-md-6">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Experience</p>
                        <div class="form-control bg-gray-50"><?= htmlspecialchars((string)($tailor['experience_years'] ?? '0')) ?> Years</div>
                    </div>
                    <div class="col-12">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Skills</p>
                        <div class="form-control bg-gray-50"><?= nl2br(htmlspecialchars((string)($tailor['skills'] ?? ''))) ?></div>
                    </div>
                    <div class="col-12">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Description</p>
                        <div class="form-control bg-gray-50"><?= nl2br(htmlspecialchars((string)($tailor['description'] ?? ''))) ?></div>
                    </div>
                </div>
            <?php else: ?>
                <form action="profile.php?edit=1" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars((string)$tailor['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars((string)($tailor['phone'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Starting Price (PKR)</label>
                            <input type="number" name="price_range_min" class="form-control" value="<?= htmlspecialchars((string)($tailor['price_range_min'] ?? '')) ?>" min="0" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">City</label>
                            <select name="location" class="form-select" data-city-select>
                                <option value="" <?= $currentLoc === '' ? 'selected' : '' ?> disabled>Select city...</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= htmlspecialchars((string)$city) ?>" <?= strtolower($currentLoc) === strtolower(trim((string)$city)) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string)$city) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="__other__" <?= $isOtherCity ? 'selected' : '' ?>>Other (Type City)</option>
                            </select>
                            <div class="mt-3 d-none" data-city-other-wrap>
                                <input type="text" name="location_other" class="form-control" value="<?= $isOtherCity ? htmlspecialchars((string)$currentLoc) : '' ?>" placeholder="Type city name" data-city-other-input>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Address</label>
                            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars((string)($tailor['address'] ?? '')) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Instagram Link</label>
                            <input type="url" name="instagram_link" class="form-control" value="<?= htmlspecialchars((string)($tailor['instagram_link'] ?? '')) ?>" placeholder="https://instagram.com/yourprofile">
                        </div>
                        <div class="col-md-6">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Tagline</label>
                            <input type="text" name="tagline" class="form-control" value="<?= htmlspecialchars((string)($tailor['tagline'] ?? '')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Skills</label>
                            <input type="text" name="skills" class="form-control" value="<?= htmlspecialchars((string)($tailor['skills'] ?? '')) ?>" placeholder="e.g. Suits, Bridal, Gents">
                        </div>
                        <div class="col-12">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Description</label>
                            <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars((string)($tailor['description'] ?? '')) ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 block">Profile Picture</label>
                            <input type="file" name="profile_image" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn btn-primary w-full rounded-xl py-3 font-black uppercase tracking-widest text-xs shadow-lg hover:shadow-primary/20 transition-all">Save Changes</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
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

<?php include '../admin/footer.php'; ?>
