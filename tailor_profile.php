<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/schema_utils.php';

$tailor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tailor = null;
$portfolio = [];
$portfolio_videos = [];

if ($tailor_id && $pdo) {
    try {
        // Fetch Tailor Details
        $stmt = $pdo->prepare("SELECT * FROM tailors WHERE id = ?");
        $stmt->execute([$tailor_id]);
        $tailor = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch Portfolio
        if ($tailor) {
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

            silah_ensure_column($pdo, 'portfolio_images', 'image_blob', "ALTER TABLE portfolio_images ADD COLUMN image_blob LONGBLOB NULL");
            silah_ensure_column($pdo, 'portfolio_images', 'image_mime', "ALTER TABLE portfolio_images ADD COLUMN image_mime VARCHAR(100) NULL");

            $stmt = $pdo->prepare("SELECT * FROM portfolio_images WHERE tailor_id = ?");
            $stmt->execute([$tailor_id]);
            $db_portfolio = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Transform DB images into expected structure
            foreach ($db_portfolio as $item) {
                $hasBlob = isset($item['image_blob']) && $item['image_blob'] !== null && $item['image_blob'] !== '';
                $portfolio[] = [
                    'image_url' => $hasBlob ? ('portfolio_media.php?id=' . (int)$item['id']) : $item['image_url'],
                    'description' => $item['description'] ?? 'Portfolio work'
                ];
            }

            if (empty($portfolio) && isset($tailor['email']) && $tailor['email']) {
                $stmt = $pdo->prepare("SELECT portfolio_link FROM tailor_applications WHERE email = ? ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([(string)$tailor['email']]);
                $portfolio_link = $stmt->fetchColumn();
                $appImages = json_decode(is_string($portfolio_link) ? $portfolio_link : '', true);
                if (is_array($appImages)) {
                    foreach ($appImages as $imgPath) {
                        $imgPath = is_string($imgPath) ? trim($imgPath) : '';
                        if ($imgPath !== '') {
                            $portfolio[] = [
                                'image_url' => $imgPath,
                                'description' => 'Portfolio work'
                            ];
                        }
                    }
                }
            }

            $stmt = $pdo->prepare("SELECT * FROM portfolio_videos WHERE tailor_id = ?");
            $stmt->execute([$tailor_id]);
            $db_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($db_videos as $item) {
                $portfolio_videos[] = [
                    'video_url' => $item['video_url'],
                    'description' => $item['description'] ?? 'Portfolio video'
                ];
            }

            if (empty($portfolio_videos) && isset($tailor['email']) && $tailor['email']) {
                $stmt = $pdo->prepare("SELECT portfolio_videos FROM tailor_applications WHERE email = ? ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([(string)$tailor['email']]);
                $portfolio_videos_json = $stmt->fetchColumn();
                $appVideos = json_decode(is_string($portfolio_videos_json) ? $portfolio_videos_json : '', true);
                if (is_array($appVideos)) {
                    foreach ($appVideos as $vidPath) {
                        $vidPath = is_string($vidPath) ? trim($vidPath) : '';
                        if ($vidPath !== '') {
                            $portfolio_videos[] = [
                                'video_url' => $vidPath,
                                'description' => 'Portfolio video'
                            ];
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Handle error
    }
}

// Fallback for Demo/IDs 1-5 if DB is empty or ID not found
if (!$tailor && $tailor_id <= 5) {
    $fallback_tailors = [
        1 => [
            'id' => 1,
            'name' => 'Ahmed Al-Farsi',
            'tagline' => 'Master of Bespoke Suits',
            'email' => 'ahmed@example.com',
            'profile_image' => 'images/stock/unsplash_1596609548086-85bbf8ddb6b9.jpg',
            'location' => 'Downtown, Dubai',
            'price_range_min' => 150,
            'price_range_max' => 500,
            'experience_years' => 15,
            'description' => 'With over 15 years of experience in luxury tailoring, Ahmed specializes in creating perfectly fitted bespoke suits for the modern gentleman.'
        ],
        2 => [
            'id' => 2,
            'name' => 'Sarah Jenkins',
            'tagline' => 'Elegant Bridal & Evening Wear',
            'email' => 'sarah@example.com',
            'profile_image' => 'images/stock/unsplash_1524504388940-b1c1722653e1.jpg',
            'location' => 'New York, USA',
            'price_range_min' => 200,
            'price_range_max' => 1200,
            'experience_years' => 10,
            'description' => 'Sarah brings a touch of Manhattan elegance to every bridal gown and evening dress she creates.'
        ],
        3 => [
            'id' => 3,
            'name' => 'Raj Patel',
            'tagline' => 'Traditional & Modern Fusion',
            'email' => 'raj@example.com',
            'profile_image' => 'images/stock/unsplash_1607346256330-dee7af15f7c5.jpg',
            'location' => 'London, UK',
            'price_range_min' => 50,
            'price_range_max' => 300,
            'experience_years' => 20,
            'description' => 'Specializing in fusion wear that blends traditional Eastern patterns with modern Western cuts.'
        ],
        4 => [
            'id' => 4,
            'name' => 'Fatima Zahra',
            'tagline' => 'Exquisite Hand Embroidery master',
            'email' => 'fatima@example.com',
            'profile_image' => 'images/stock/unsplash_1534528741775-53994a69daeb.jpg',
            'location' => 'Lahore, PK',
            'price_range_min' => 120,
            'price_range_max' => 800,
            'experience_years' => 12,
            'description' => 'A master of traditional hand embroidery techniques passed down through generations.'
        ],
        5 => [
            'id' => 5,
            'name' => 'Michael Chen',
            'tagline' => 'Minimalist Contemporary Tailoring',
            'email' => 'michael@example.com',
            'profile_image' => 'images/stock/unsplash_1472099645785-5658abf4ff4e.jpg',
            'location' => 'Singapore',
            'price_range_min' => 180,
            'price_range_max' => 600,
            'experience_years' => 8,
            'description' => 'Focused on clean lines, high-quality fabrics, and minimalist aesthetic for the modern urbanite.'
        ]
    ];
    
    if (isset($fallback_tailors[$tailor_id])) {
        $tailor = $fallback_tailors[$tailor_id];
        // Mock portfolio
        $portfolio = [
            ['image_url' => 'images/stock/unsplash_1594938298603-c8148c4dae35.jpg', 'description' => 'Bespoke Suit Design'],
            ['image_url' => 'images/stock/unsplash_1544441893-675973e31985.jpg', 'description' => 'Evening Wear Collection'],
            ['image_url' => 'images/stock/unsplash_1507679799987-c73779587ccf.jpg', 'description' => 'Traditional Fusion']
        ];
    }
}

// Redirect if tailor not found
if (!$tailor) {
    header("Location: index.php");
    exit;
}

$tailor_name = isset($tailor['name']) ? (string)$tailor['name'] : 'Tailor';
$has_blob = isset($tailor['profile_image_blob']) && $tailor['profile_image_blob'] !== null && $tailor['profile_image_blob'] !== '';
$tailor_avatar = $has_blob ? ('image.php?kind=tailor&id=' . (int)$tailor_id) : (isset($tailor['profile_image']) && trim((string)$tailor['profile_image']) !== '' ? (string)$tailor['profile_image'] : '');
$tailor_avatar_fallback = 'https://ui-avatars.com/api/?name=' . urlencode($tailor_name) . '&background=865294&color=fff';
if ($tailor_avatar === '') {
    $tailor_avatar = $tailor_avatar_fallback;
} else if (strpos($tailor_avatar, 'image.php?') === 0) {
} else if (strpos($tailor_avatar, 'http://') !== 0 && strpos($tailor_avatar, 'https://') !== 0) {
    $rel = ltrim($tailor_avatar, '/');
    if (!file_exists(__DIR__ . '/' . $rel)) {
        $tailor_avatar = $tailor_avatar_fallback;
    } else {
        $tailor_avatar = $rel;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string)$tailor['name']) ?> | Silah</title>
    
    <!-- Fonts & CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Lightbox -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800">

    <!-- Navbar -->
    <nav class="navbar fixed-top navbar-expand-lg navbar-light bg-white shadow-sm py-2 z-50">
        <div class="container">
            <a class="navbar-brand flex items-center gap-2" href="index.php">
                <img src="images/logo1.png" alt="Silah Logo" class="w-10 h-10 object-contain mix-blend-multiply">
                <span>SILAH</span>
            </a>
            <button class="navbar-toggler border-0 d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse d-lg-flex justify-content-end" id="navbarNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-3">
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="index.php#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="index.php#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="index.php#tailors">Tailors</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="index.php#contact">Contact</a></li>
                    <li class="nav-item">
                        <a href="join_tailor.php" class="nav-link text-dark fw-medium">Join as Tailor</a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <a href="place_order.php#order" class="btn btn-accent text-white px-6 py-2 rounded-full shadow-lg d-inline-block">Book Now</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header/Profile Info -->
    <section class="pt-32 pb-12 bg-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4 text-center md:text-start">
                    <div class="relative inline-block">
                        <img src="<?= htmlspecialchars((string)$tailor_avatar) ?>" class="w-48 h-48 rounded-full object-cover border-4 border-white shadow-2xl">
                        <div class="absolute bottom-2 right-2 bg-green-500 w-6 h-6 rounded-full border-2 border-white"></div>
                    </div>
                </div>
                <div class="col-md-8 mt-6 md:mt-0 text-center md:text-start">
                    <span class="text-accent font-bold tracking-widest uppercase text-sm mb-2 block">Premium Tailor</span>
                    <h1 class="text-4xl font-display font-bold text-primary mb-2"><?= htmlspecialchars((string)$tailor['name']) ?></h1>
                    <p class="text-xl text-gray-500 mb-4"><?= htmlspecialchars((string)$tailor['tagline']) ?></p>
                    
                    <div class="flex flex-wrap justify-center md:justify-start gap-6 text-gray-600 mb-6">
                        <span class="flex items-center"><i class="fas fa-map-marker-alt text-accent mr-2"></i> <?= htmlspecialchars((string)$tailor['location']) ?></span>
                        <span class="flex items-center"><i class="fas fa-briefcase text-accent mr-2"></i> <?= htmlspecialchars((string)$tailor['experience_years']) ?>+ Years Exp.</span>
                        <span class="flex items-center">
                            <i class="fas fa-tag text-accent mr-2"></i>
                            <?php
                                $min = isset($tailor['price_range_min']) && $tailor['price_range_min'] !== '' ? (float)$tailor['price_range_min'] : 0;
                                $max = isset($tailor['price_range_max']) && $tailor['price_range_max'] !== null && $tailor['price_range_max'] !== '' ? (float)$tailor['price_range_max'] : null;
                            ?>
                            <?php if ($max !== null && $max > 0): ?>
                                PKR <?= number_format((float)$min) ?> - PKR <?= number_format((float)$max) ?>
                            <?php else: ?>
                                Starting from PKR <?= number_format((float)$min) ?>
                            <?php endif; ?>
                        </span>
                        <?php if (isset($tailor['email']) && $tailor['email']): ?>
                            <span class="flex items-center"><i class="fas fa-envelope text-accent mr-2"></i> <?= htmlspecialchars((string)$tailor['email']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($_GET['status']) && $_GET['status'] === 'order_success'): ?>
                        <div class="mt-6 p-4 rounded-2xl bg-green-50 border border-green-100 text-green-800 font-semibold">
                            Your request has been submitted successfully. We will contact you soon.
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['contact']) && $_GET['contact'] === 'sent'): ?>
                        <div class="mt-6 p-4 rounded-2xl bg-green-50 border border-green-100 text-green-800 font-semibold">
                            Your message has been sent to the tailor.
                        </div>
                    <?php endif; ?>

                    <div class="flex flex-wrap justify-center md:justify-start gap-4">
                        <button type="button" class="btn btn-primary px-8 py-3 rounded-full shadow-lg hover:shadow-xl transition-all" data-bs-toggle="modal" data-bs-target="#hireModal">
                            Hire Me
                        </button>
                        <button type="button" class="btn btn-outline px-6 py-3 rounded-full" data-bs-toggle="modal" data-bs-target="#contactModal">
                            Contact
                        </button>
                        <?php if (isset($tailor['instagram_link']) && $tailor['instagram_link']): ?>
                            <a href="<?= htmlspecialchars((string)$tailor['instagram_link']) ?>" target="_blank" class="btn btn-outline px-6 py-3 rounded-full">
                                Instagram
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="hireModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden">
                <div class="modal-header bg-white border-0 p-4">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Hire Tailor</p>
                        <h5 class="modal-title text-2xl font-bold text-primary mb-0"><?= htmlspecialchars((string)$tailor['name']) ?></h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 p-md-5 bg-gray-50">
                    <form action="process_order.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="hire_tailor_id" value="<?= (int)$tailor['id'] ?>">
                        <input type="hidden" name="return_url" value="tailor_profile.php?id=<?= (int)$tailor['id'] ?>">
                        <input type="hidden" name="preferred_tailors[]" value="<?= (int)$tailor['id'] ?>">

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-sm text-gray-600">Full Name</label>
                                <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-sm text-gray-600">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-sm text-gray-600">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" placeholder="+92 3XX XXXXXXX" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-semibold text-sm text-gray-600">Service Needed</label>
                                <input type="hidden" name="selected_tailor_id" value="<?= (int)$tailor['id'] ?>">
                                <div class="p-4 rounded-2xl bg-white border border-gray-100 shadow-sm" data-services-wrap>
                                    <p class="text-xs text-gray-500 mb-0 italic">Loading services...</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-sm text-gray-600">Budget (PKR)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-xs">PKR</span>
                                    <input type="number" name="budget" class="form-control pl-12 rounded-xl bg-gray-50 border-gray-100 font-black text-primary" min="0" step="0.01" data-total-price readonly required>
                                </div>
                                <div class="form-text text-[10px] text-gray-400 mt-1 uppercase tracking-widest font-bold">Auto-calculated from selected services.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-sm text-gray-600">Expected Timeline</label>
                                <input type="date" name="expected_delivery" class="form-control rounded-xl bg-gray-50 border-gray-100 font-bold text-gray-700" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-semibold text-sm text-gray-600 mb-2">Clothing Option</label>
                                <div class="flex flex-col sm:flex-row gap-4">
                                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl border border-gray-100 bg-white hover:bg-primary/5 hover:border-primary/20 transition-all group flex-1">
                                        <input type="radio" name="is_own_clothing" value="0" class="form-check-input mt-0" checked>
                                        <span class="text-sm font-bold text-gray-700 group-hover:text-primary transition-colors">Buy Fabric from Us</span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl border border-gray-100 bg-white hover:bg-primary/5 hover:border-primary/20 transition-all group flex-1">
                                        <input type="radio" name="is_own_clothing" value="1" class="form-check-input mt-0">
                                        <span class="text-sm font-bold text-gray-700 group-hover:text-primary transition-colors">Send your own/familiar clothing</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-semibold text-sm text-gray-600">Address</label>
                                <textarea name="location_details" class="form-control" rows="2" placeholder="Area, Street, House No." required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-semibold text-sm text-gray-600">Style Preference / Further Description</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Any specific details or deadlines?" required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-semibold text-sm text-gray-600">Measurements (Optional)</label>
                                <input type="hidden" name="measurements" data-measure-output>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label font-semibold text-xs text-gray-400 uppercase tracking-widest mb-1">Template</label>
                                        <select class="form-select rounded-xl bg-gray-50 border-gray-100 text-sm font-bold text-gray-700" data-measure-type>
                                            <option value="none">Select Template</option>
                                            <option value="men">Men</option>
                                            <option value="women">Women</option>
                                            <option value="kids">Kids</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label font-semibold text-xs text-gray-400 uppercase tracking-widest mb-1">Unit</label>
                                        <select class="form-select rounded-xl bg-gray-50 border-gray-100 text-sm font-bold text-gray-700" data-measure-unit>
                                            <option value="in">Inches (in)</option>
                                            <option value="cm">Centimeters (cm)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 flex items-end">
                                        <label class="flex items-center gap-2 text-xs font-bold text-gray-500 cursor-pointer bg-white border border-gray-100 p-2.5 rounded-xl w-full hover:bg-amber-50 hover:border-amber-200 transition-all">
                                            <input type="checkbox" class="form-check-input mt-0" data-measure-auto>
                                            <span>Home Visit Measure</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="mt-3" data-measure-fields>
                                    <div class="row g-3 d-none" data-measure-template="men">
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Chest</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Chest">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Waist</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Waist">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Hip</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Hip">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Shoulder</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Shoulder">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Sleeve</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Sleeve">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Length</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Length">
                                        </div>
                                    </div>

                                    <div class="row g-3 d-none" data-measure-template="women">
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Bust</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Bust">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Waist</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Waist">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Hip</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Hip">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Shoulder</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Shoulder">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Sleeve</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Sleeve">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Dress Length</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Dress Length">
                                        </div>
                                    </div>

                                    <div class="row g-3 d-none" data-measure-template="kids">
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Age</label>
                                            <input type="number" step="1" min="0" class="form-control" data-measure-field="Age">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Height</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Height">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Chest</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Chest">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Waist</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Waist">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Sleeve</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Sleeve">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-sm text-gray-600">Length</label>
                                            <input type="number" step="0.01" min="0" class="form-control" data-measure-field="Length">
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label text-sm text-gray-600">Additional Notes (optional)</label>
                                    <input type="text" class="form-control" data-measure-notes placeholder="e.g. Slim fit, loose fitting, any special request">
                                </div>
                                <div class="mt-3">
                                    <label class="form-label text-sm text-gray-600">Preview</label>
                                    <textarea class="form-control bg-white" rows="3" readonly data-measure-preview></textarea>
                                </div>
                                <div class="mt-3 d-none" data-measure-guides>
                                    <label class="form-label text-sm text-gray-600">Measurement Guide</label>
                                    <div class="bg-white rounded-2xl border border-gray-100 p-3">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <img data-measure-guide="women" src="images/womens measurment.jpg" class="w-full rounded-xl border border-gray-100 d-none" alt="Women measurement guide">
                                            <img data-measure-guide="men" src="images/mens measurment.png" class="w-full rounded-xl border border-gray-100 d-none" alt="Men measurement guide">
                                        </div>
                                        <p class="text-[11px] text-gray-500 mt-3 mb-0">Use the guide images to measure correctly. If you’re unsure, select “Tailor will take measurements”.</p>
                                    </div>
                                </div>
                                <div class="form-text text-xs">If you don’t know your measurements, enable “Tailor will take measurements”.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-semibold text-sm text-gray-600">Upload Image (optional)</label>
                                <input type="file" name="reference_image" class="form-control" accept="image/*">
                                <div class="form-text text-xs">Upload a photo of the design you want.</div>
                            </div>
                            <div class="col-12 pt-2">
                                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-full shadow-lg hover:shadow-xl transition-all">
                                    Submit Request
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden">
                <div class="modal-header bg-white border-0 p-4">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Contact Tailor</p>
                        <h5 class="modal-title text-2xl font-bold text-primary mb-0"><?= htmlspecialchars((string)$tailor['name']) ?></h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 p-md-5 bg-gray-50">
                    <form action="process_tailor_message.php" method="POST">
                        <input type="hidden" name="tailor_id" value="<?= (int)$tailor['id'] ?>">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-sm text-gray-600">Full Name</label>
                                <input type="text" name="customer_name" class="form-control" placeholder="Your Name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-sm text-gray-600">Email</label>
                                <input type="email" name="customer_email" class="form-control" placeholder="you@example.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-sm text-gray-600">Contact Number</label>
                                <input type="tel" name="customer_phone" class="form-control" placeholder="+92 3XX XXXXXXX" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold text-sm text-gray-600">Address</label>
                                <input type="text" name="customer_address" class="form-control" placeholder="Area, Street, House No." required>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-semibold text-sm text-gray-600">Message</label>
                                <textarea name="message" class="form-control" rows="4" placeholder="Write your message..." required></textarea>
                            </div>
                            <div class="col-12 pt-2">
                                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-full shadow-lg hover:shadow-xl transition-all">
                                    Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- About & Portfolio -->
    <section class="py-16">
        <div class="container">
            <div class="row g-5">
                <!-- Left Column: About -->
                <div class="col-lg-4">
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 mb-8 sticky top-32">
                        <h3 class="text-xl font-bold mb-4 border-b pb-2">About Me</h3>
                        <p class="text-gray-600 leading-relaxed mb-6">
                            <?= nl2br(htmlspecialchars((string)$tailor['description'])) ?>
                        </p>
                        
                        <h4 class="font-bold mb-3 mt-6">Specialties</h4>
                        <div class="flex flex-wrap gap-2">
                            <?php
                                $skillsRaw = isset($tailor['skills']) ? (string)$tailor['skills'] : '';
                                $skillsList = array_values(array_filter(array_map('trim', preg_split('/,|\n/', $skillsRaw))));
                                if (empty($skillsList) && isset($tailor['tagline']) && $tailor['tagline']) {
                                    $skillsList = [(string)$tailor['tagline']];
                                }
                                $skillsList = array_slice($skillsList, 0, 10);
                            ?>
                            <?php foreach ($skillsList as $skill): ?>
                                <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm"><?= htmlspecialchars((string)$skill) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Gallery -->
                <div class="col-lg-8">
                    <h3 class="text-2xl font-bold mb-6">Portfolio & Recent Work</h3>
                    
                    <?php if (empty($portfolio)): ?>
                        <div class="text-center py-10 bg-white rounded-2xl border border-dashed border-gray-300">
                            <p class="text-gray-500">No portfolio images uploaded yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($portfolio as $item): ?>
                            <div class="col-md-6">
                                <a href="<?= htmlspecialchars((string)$item['image_url']) ?>" data-lightbox="portfolio" data-title="<?= htmlspecialchars((string)$item['description']) ?>" class="block group relative overflow-hidden rounded-xl shadow-sm hover:shadow-lg transition-all">
                                    <div class="aspect-w-4 aspect-h-3 h-64">
                                        <img src="<?= htmlspecialchars((string)$item['image_url']) ?>" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500">
                                    </div>
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center">
                                        <div class="opacity-0 group-hover:opacity-100 transform translate-y-4 group-hover:translate-y-0 transition-all duration-300">
                                            <span class="bg-white text-primary w-10 h-10 rounded-full flex items-center justify-center shadow-lg">
                                                <i class="fas fa-plus"></i>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                                <p class="mt-2 text-sm text-gray-500 font-medium"><?= htmlspecialchars((string)$item['description']) ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-10">
                        <h4 class="text-xl font-bold mb-4">Portfolio Videos</h4>
                        <?php if (empty($portfolio_videos)): ?>
                            <div class="text-center py-10 bg-white rounded-2xl border border-dashed border-gray-300">
                                <p class="text-gray-500">No portfolio videos uploaded yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-4">
                                <?php foreach ($portfolio_videos as $vid): ?>
                                    <div class="col-md-6">
                                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                            <div class="aspect-w-16 aspect-h-9">
                                                <video controls class="w-full h-full">
                                                    <source src="<?= htmlspecialchars((string)$vid['video_url']) ?>" type="video/mp4">
                                                </video>
                                            </div>
                                            <div class="p-4">
                                                <p class="text-sm text-gray-500 font-medium mb-0"><?= htmlspecialchars((string)($vid['description'] ?? 'Portfolio video')) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-dark mt-auto">
        <div class="container text-center text-sm text-gray-500">
            <p>&copy; 2026 Silah Marketplace. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- Floating WhatsApp Button -->
    <a href="https://wa.me/923367326095" target="_blank" class="fixed bottom-6 right-6 bg-[#25D366] text-white w-14 h-14 rounded-full shadow-2xl flex items-center justify-center hover:scale-110 transition-transform z-50 group">
        <i class="fab fa-whatsapp text-3xl"></i>
        <span class="absolute right-16 bg-white text-gray-800 px-3 py-1 rounded shadow-md text-sm whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none font-medium">Chat with us</span>
    </a>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script>
        function initMeasurements(form) {
            if (!form) return;
            const typeSelect = form.querySelector('[data-measure-type]');
            const unitSelect = form.querySelector('[data-measure-unit]');
            const autoCheck = form.querySelector('[data-measure-auto]');
            const output = form.querySelector('[data-measure-output]');
            const preview = form.querySelector('[data-measure-preview]');
            const notes = form.querySelector('[data-measure-notes]');
            const templates = Array.from(form.querySelectorAll('[data-measure-template]'));
            const fieldsWrap = form.querySelector('[data-measure-fields]');
            const guidesWrap = form.querySelector('[data-measure-guides]');
            const guideMen = form.querySelector('[data-measure-guide="men"]');
            const guideWomen = form.querySelector('[data-measure-guide="women"]');

            if (!typeSelect || !unitSelect || !autoCheck || !output || !preview || !fieldsWrap) return;

            function setTemplateVisibility() {
                const type = typeSelect.value;
                templates.forEach(tpl => {
                    if (tpl.getAttribute('data-measure-template') === type) {
                        tpl.classList.remove('d-none');
                    } else {
                        tpl.classList.add('d-none');
                    }
                });
            }

            function setGuideVisibility() {
                if (!guidesWrap) return;
                const type = typeSelect.value;
                const auto = autoCheck.checked;
                if (auto || type === 'none') {
                    guidesWrap.classList.add('d-none');
                    if (guideMen) guideMen.classList.add('d-none');
                    if (guideWomen) guideWomen.classList.add('d-none');
                    return;
                }
                guidesWrap.classList.remove('d-none');
                if (guideMen) {
                    if (type === 'men') {
                        guideMen.classList.remove('d-none');
                    } else {
                        guideMen.classList.add('d-none');
                    }
                }
                if (guideWomen) {
                    if (type === 'women') {
                        guideWomen.classList.remove('d-none');
                    } else {
                        guideWomen.classList.add('d-none');
                    }
                }
            }

            function setFieldsEnabled(enabled) {
                const inputs = fieldsWrap.querySelectorAll('input[data-measure-field], select[data-measure-field]');
                inputs.forEach(i => {
                    i.disabled = !enabled;
                });
                if (notes) notes.disabled = !enabled;
                typeSelect.disabled = !enabled;
                unitSelect.disabled = !enabled;
            }

            function buildText() {
                const auto = autoCheck.checked;
                if (auto) {
                    output.value = 'Tailor will take measurements on visit';
                    preview.value = output.value;
                    setFieldsEnabled(false);
                    setGuideVisibility();
                    return;
                }

                setFieldsEnabled(true);
                const type = typeSelect.value;
                if (type === 'none') {
                    output.value = '';
                    preview.value = '';
                    setTemplateVisibility();
                    setGuideVisibility();
                    return;
                }

                setTemplateVisibility();
                setGuideVisibility();
                const unit = unitSelect.value === 'cm' ? 'cm' : 'in';
                const tpl = form.querySelector('[data-measure-template="' + type + '"]');
                const lines = [];
                lines.push('Template: ' + (type === 'men' ? 'Men' : type === 'women' ? 'Women' : 'Kids'));
                lines.push('Unit: ' + unit);
                if (tpl) {
                    const inputs = Array.from(tpl.querySelectorAll('input[data-measure-field]'));
                    inputs.forEach(i => {
                        const label = i.getAttribute('data-measure-field');
                        const val = i.value;
                        if (val !== '') {
                            lines.push(label + ': ' + val);
                        }
                    });
                }
                if (notes && notes.value.trim() !== '') {
                    lines.push('Notes: ' + notes.value.trim());
                }
                output.value = lines.join('\n');
                preview.value = output.value;
            }

            typeSelect.addEventListener('change', buildText);
            unitSelect.addEventListener('change', buildText);
            autoCheck.addEventListener('change', buildText);
            fieldsWrap.addEventListener('input', buildText);
            if (notes) notes.addEventListener('input', buildText);

            setTemplateVisibility();
            setGuideVisibility();
            buildText();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('#hireModal form[action="process_order.php"]');
            initMeasurements(form);

            if (!form) return;
            const wrap = form.querySelector('[data-services-wrap]');
            const totalInput = form.querySelector('[data-total-price]');
            const tailorIdInput = form.querySelector('input[name="selected_tailor_id"]');
            if (!wrap || !totalInput || !tailorIdInput) return;
            const tailorId = parseInt(tailorIdInput.value || '0', 10) || 0;
            if (!tailorId) return;

            const renderServices = (services) => {
                if (!Array.isArray(services) || services.length === 0) {
                    wrap.innerHTML = '<p class="text-xs text-gray-500 mb-0">This tailor has no services/prices yet.</p>';
                    totalInput.value = '';
                    return;
                }
                const list = document.createElement('div');
                list.className = 'grid grid-cols-1 md:grid-cols-2 gap-2';
                services.forEach(s => {
                    const row = document.createElement('label');
                    row.className = 'flex items-center justify-between gap-3 p-2 rounded-xl bg-gray-50 border border-gray-100 cursor-pointer';
                    row.innerHTML =
                        '<div class="flex items-center gap-2">' +
                        '<input type="checkbox" name="service_ids[]" value="' + String(s.id) + '" class="form-check-input mt-0" data-service-price="' + String(s.price) + '" data-service-name="' + String(s.name).replace(/"/g, '&quot;') + '">' +
                        '<span class="text-sm font-semibold text-gray-700">' + String(s.name) + '</span>' +
                        '</div>' +
                        '<span class="text-sm font-black text-primary">PKR ' + Number(s.price).toLocaleString() + '</span>';
                    list.appendChild(row);
                });
                const summary = document.createElement('div');
                summary.className = 'mt-4 p-4 rounded-2xl bg-primary/5 border border-primary/10 shadow-sm';
                summary.innerHTML =
                    '<div class="flex items-center justify-between mb-3 pb-2 border-b border-primary/10">' +
                    '<span class="text-[10px] font-black text-primary uppercase tracking-widest">Selected Services</span>' +
                    '<span class="text-[10px] font-black text-gray-400 uppercase tracking-widest" data-count-label>0 Selected</span>' +
                    '</div>' +
                    '<div class="text-xs font-bold text-gray-600 mb-4 flex flex-wrap gap-2" data-selected-services>None</div>' +
                    '<div class="flex items-center justify-between pt-2">' +
                    '<span class="text-xs font-black text-gray-400 uppercase tracking-widest">Total Budget</span>' +
                    '<span class="text-lg font-black text-primary" data-total-label>PKR 0</span>' +
                    '</div>';
                wrap.innerHTML = '';
                wrap.appendChild(list);
                wrap.appendChild(summary);

                const recalc = () => {
                    const checks = wrap.querySelectorAll('input[name="service_ids[]"]:checked');
                    let total = 0;
                    const chips = [];
                    checks.forEach(ch => {
                        const p = parseFloat(ch.getAttribute('data-service-price') || '0') || 0;
                        total += p;
                        const n = ch.getAttribute('data-service-name') || '';
                        chips.push('<span class="px-2 py-1 bg-white border border-gray-100 rounded-lg shadow-sm">' + n + '</span>');
                    });
                    const selectedEl = wrap.querySelector('[data-selected-services]');
                    const totalLabel = wrap.querySelector('[data-total-label]');
                    const countLabel = wrap.querySelector('[data-count-label]');
                    if (selectedEl) selectedEl.innerHTML = chips.length ? chips.join('') : '<span class="italic text-gray-400">No services selected</span>';
                    if (totalLabel) totalLabel.textContent = 'PKR ' + total.toLocaleString();
                    if (countLabel) countLabel.textContent = checks.length + ' Selected';
                    totalInput.value = String(total);
                };
                wrap.addEventListener('change', function(e) {
                    if (e.target && e.target.name === 'service_ids[]') recalc();
                });
                recalc();
            };

            fetch('api/tailor_services.php?tailor_id=' + encodeURIComponent(String(tailorId)), { cache: 'no-store' })
                .then(r => r.json())
                .then(d => renderServices(d && d.success ? d.services : []))
                .catch(() => renderServices([]));
        });
    </script>
</body>
</html>
