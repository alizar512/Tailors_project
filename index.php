<?php
require_once 'includes/db_connect.php';
require_once 'includes/cities.php';

function silah_norm($v) {
    return strtolower(trim((string)$v));
}

$filter_q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$filter_location_raw = isset($_GET['location']) ? trim((string)$_GET['location']) : '';
$filter_location_other = isset($_GET['location_other']) ? trim((string)$_GET['location_other']) : '';
$filter_location = $filter_location_raw === '__other__' ? $filter_location_other : $filter_location_raw;
$filter_min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$filter_max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$filter_min_exp = isset($_GET['min_exp']) && $_GET['min_exp'] !== '' && is_numeric($_GET['min_exp']) ? (int)$_GET['min_exp'] : null;
$hire_tailor_id = isset($_GET['tailor_id']) && is_numeric($_GET['tailor_id']) ? (int)$_GET['tailor_id'] : 0;
$hire_mode = isset($_GET['hire']) && (string)$_GET['hire'] === '1' && $hire_tailor_id > 0;

// Fetch Tailors
$all_tailors = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM tailors ORDER BY created_at DESC");
        $all_tailors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Handle error gracefully
    }
}

$cities = silah_get_cities($pdo);

// Fallback if no tailors found (or DB error)
if (empty($all_tailors)) {
    $all_tailors = [
        [
            'id' => 1,
            'name' => 'Ahmed Al-Farsi',
            'tagline' => 'Master of Bespoke Suits',
            'profile_image' => 'images/stock/unsplash_1596609548086-85bbf8ddb6b9.jpg',
            'location' => 'Downtown, Dubai',
            'price_range_min' => 150,
            'experience_years' => 15
        ],
        [
            'id' => 2,
            'name' => 'Sarah Jenkins',
            'tagline' => 'Elegant Bridal & Evening Wear',
            'profile_image' => 'images/stock/unsplash_1524504388940-b1c1722653e1.jpg',
            'location' => 'New York, USA',
            'price_range_min' => 200,
            'experience_years' => 10
        ],
        [
            'id' => 3,
            'name' => 'Raj Patel',
            'tagline' => 'Traditional & Modern Fusion',
            'profile_image' => 'images/stock/unsplash_1607346256330-dee7af15f7c5.jpg',
            'location' => 'London, UK',
            'price_range_min' => 50,
            'experience_years' => 20
        ],
        [
            'id' => 4,
            'name' => 'Fatima Zahra',
            'tagline' => 'Exquisite Hand Embroidery master',
            'profile_image' => 'images/stock/unsplash_1534528741775-53994a69daeb.jpg',
            'location' => 'Lahore, PK',
            'price_range_min' => 120,
            'experience_years' => 12
        ],
        [
            'id' => 5,
            'name' => 'Michael Chen',
            'tagline' => 'Minimalist Contemporary Tailoring',
            'profile_image' => 'images/stock/unsplash_1472099645785-5658abf4ff4e.jpg',
            'location' => 'Singapore',
            'price_range_min' => 180,
            'experience_years' => 8
        ]
    ];
}

$locations = [];
$filtered = [];
foreach ($all_tailors as $t) {
    $is_active = !isset($t['is_active']) || (int)$t['is_active'] === 1;
    if (!$is_active) {
        continue;
    }

    $loc = isset($t['location']) ? trim((string)$t['location']) : '';
    if ($loc !== '') {
        $locations[silah_norm($loc)] = $loc;
    }

    if ($filter_location !== '' && silah_norm($filter_location) !== 'all') {
        if (silah_norm($loc) !== silah_norm($filter_location)) {
            continue;
        }
    }

    $price_min = isset($t['price_range_min']) && is_numeric($t['price_range_min']) ? (float)$t['price_range_min'] : null;
    $exp = isset($t['experience_years']) && is_numeric($t['experience_years']) ? (int)$t['experience_years'] : 0;

    if ($filter_min_price !== null && ($price_min === null || $price_min < $filter_min_price)) {
        continue;
    }
    if ($filter_max_price !== null && ($price_min === null || $price_min > $filter_max_price)) {
        continue;
    }
    if ($filter_min_exp !== null && $exp < $filter_min_exp) {
        continue;
    }

    if ($filter_q !== '') {
        $hay = silah_norm(($t['name'] ?? '') . ' ' . ($t['tagline'] ?? '') . ' ' . ($t['skills'] ?? '') . ' ' . ($t['location'] ?? ''));
        if (strpos($hay, silah_norm($filter_q)) === false) {
            continue;
        }
    }

    $filtered[] = $t;
}
ksort($locations);

$tailors = $filtered;
$tailors_total = count($filtered);
$hire_tailor = null;
if ($hire_mode) {
    foreach ($tailors as $t) {
        if (isset($t['id']) && (int)$t['id'] === $hire_tailor_id) {
            $hire_tailor = $t;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silah | Connect with Professional Tailors</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="css/tailwind.css">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <?php if ($hire_mode): ?>
    <script>
        window.__SILAH_HIRE_TAILOR_ID__ = <?= (int)$hire_tailor_id ?>;
        window.__SILAH_HIRE_TAILOR_NAME__ = <?= json_encode($hire_tailor ? (string)$hire_tailor['name'] : '') ?>;
    </script>
    <?php endif; ?>
</head>
<body class="bg-gray-50 text-gray-800">
    <!-- Language Selection Popup -->
    <div id="langModal" class="lang-modal-overlay d-none">
        <div class="lang-modal">
            <h3 class="mb-2">Welcome to SILAH</h3>
            <p class="text-gray-500 mb-6">Please select your preferred language</p>
            <div class="lang-options">
                <div class="lang-option" onclick="setLanguage('en')">
                    <span>English</span>
                    <i class="fas fa-chevron-right text-sm"></i>
                </div>
                <div class="lang-option" onclick="setLanguage('ur')">
                    <span dir="rtl">اردو (Urdu)</span>
                    <i class="fas fa-chevron-right text-sm"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'order_success'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            alert('Thank you! Your order request has been submitted successfully. We will contact you shortly.');
            window.location.href = 'index.php'; // Clean URL
        });
    </script>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="navbar fixed-top navbar-expand-lg navbar-light transition-all duration-300 z-50 navbar-hero" id="mainNav">
        <div class="container">
            <a class="navbar-brand flex items-center gap-2" href="#">
                <img src="images/logo1.png" alt="Silah Logo" class="w-10 h-10 object-contain mix-blend-multiply">
                <span>SILAH</span>
            </a>
            <button class="navbar-toggler border-0 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="d-none d-lg-flex justify-content-end">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-3">
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="#tailors">Tailors</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="join_tailor.php">Join as Tailor</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="#contact">Contact</a></li>
                    <li class="nav-item ms-lg-3">
                        <a href="admin/login.php" class="btn btn-outline px-6 py-2 rounded-full d-inline-block">
                            Login
                        </a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <a href="place_order.php#order" class="btn btn-accent text-white px-6 py-2 rounded-full shadow-lg hover:shadow-xl transition-all d-inline-block">
                            Book Now
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title font-black text-primary" id="mobileNavLabel">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="d-grid gap-2 mb-4">
                <a class="btn btn-accent text-white rounded-full py-3 font-bold" href="place_order.php#order">Place Order</a>
                <a class="btn btn-outline rounded-full py-3 font-bold" href="admin/login.php">Login</a>
            </div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action py-3" href="#home" data-bs-dismiss="offcanvas">Home</a>
                <a class="list-group-item list-group-item-action py-3" href="#how-it-works" data-bs-dismiss="offcanvas">How It Works</a>
                <a class="list-group-item list-group-item-action py-3" href="#tailors" data-bs-dismiss="offcanvas">Tailors</a>
                <a class="list-group-item list-group-item-action py-3" href="join_tailor.php">Join as Tailor</a>
                <a class="list-group-item list-group-item-action py-3" href="#contact" data-bs-dismiss="offcanvas">Contact</a>
                <a class="list-group-item list-group-item-action py-3" href="order_chat.php">Open Chat</a>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <header id="home" class="hero-section flex items-center relative min-h-[100vh]">
        <div class="hero-bg" id="heroBg"></div>
        <div class="hero-overlay"></div>
        <div class="container relative z-10 pt-20">
            <div class="row align-items-center">
                <div class="col-lg-8 col-xl-7" data-aos="fade-right">
                    <span class="hero-tag text-white font-black tracking-widest uppercase mb-4 inline-flex items-center gap-2 px-4 py-2 rounded-full">
                        Premium Tailoring Marketplace
                    </span>
                    <h1 class="display-3 font-display font-extrabold text-white mb-6 leading-tight hero-title">
                        <span id="heroTypewriter" class="hero-typewriter" data-phrases="Find Your Perfect|Find Your Dream|Find Your Style">Find Your Perfect</span><span class="typewriter-cursor" aria-hidden="true">|</span><br>
                        <span class="hero-accent">Tailor Today</span>
                    </h1>
                    <p class="lead text-white/80 mb-8 text-lg hero-subtitle">
                        Silah connects you with professional tailors for bespoke suits, dresses, and alterations. Experience quality craftsmanship delivered to your measurements.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <a href="#tailors" class="btn btn-accent text-white px-8 py-3 rounded-full shadow-lg hover:shadow-xl transition-all">
                            Browse Tailors
                        </a>
                        <a href="#how-it-works" class="btn btn-outline px-8 py-3 rounded-full hero-outline">
                            How It Works
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Section 1: About / How It Works -->
    <section id="how-it-works" class="py-24 bg-white relative">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <span>Simple Process</span>
                <h2>How Silah Works</h2>
            </div>

            <div class="row g-5 items-center mb-20">
                <div class="col-lg-5" data-aos="fade-right">
                    <h3 class="text-3xl font-bold mb-6 text-primary">Connecting You with Master Craftsmen</h3>
                    <p class="text-gray-600 mb-6 text-lg leading-relaxed">
                        Silah is more than just a directory; it's a bridge between your style aspirations and the skilled hands that can bring them to life. We vet every tailor to ensure quality, reliability, and professionalism.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-center text-gray-700">
                            <i class="fas fa-check-circle text-accent mr-3 text-xl"></i> Verified Professionals
                        </li>
                        <li class="flex items-center text-gray-700">
                            <i class="fas fa-check-circle text-accent mr-3 text-xl"></i> Transparent Pricing
                        </li>
                        <li class="flex items-center text-gray-700">
                            <i class="fas fa-check-circle text-accent mr-3 text-xl"></i> Quality Guarantee
                        </li>
                    </ul>
                </div>
                <div class="col-lg-7">
                    <div class="row g-4">
                        <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                            <div class="step-card group">
                                <div class="step-icon  group-hover:text-black transition-colors">
                                    <i class="fas fa-search"></i>
                                </div>
                                <h4 class="text-xl font-bold mb-3">1. Browse Tailors</h4>
                                <p class="text-gray-500 text-sm">Explore profiles, view portfolios, and check ratings to find your match.</p>
                            </div>
                        </div>
                        <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
                            <div class="step-card group">
                                <div class="step-icon group-hover:text-black transition-colors">
                                    <i class="fas fa-eye"></i>
                                </div>
                                <h4 class="text-xl font-bold mb-3">2. View Portfolio</h4>
                                <p class="text-gray-500 text-sm">See their past work and starting prices to ensure they fit your budget.</p>
                            </div>
                        </div>
                        <div class="col-md-6" data-aos="fade-up" data-aos-delay="300">
                            <div class="step-card group">
                                <div class="step-icon group-hover:text-black transition-colors">
                                    <i class="fas fa-tape"></i>
                                </div>
                                <h4 class="text-xl font-bold mb-3">3. Place Order</h4>
                                <p class="text-gray-500 text-sm">Fill out the order form with your details and specific requirements.</p>
                            </div>
                        </div>
                        <div class="col-md-6" data-aos="fade-up" data-aos-delay="400">
                            <div class="step-card group">
                                <div class="step-icon group-hover:text-black transition-colors">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h4 class="text-xl font-bold mb-3">4. We Connect You</h4>
                                <p class="text-gray-500 text-sm">The tailor will contact you to confirm measurements and start the work.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 2: Tailor Profiles -->
    <section id="tailors" class="py-24 relative overflow-hidden bg-gray-50">
        <!-- Decorative Background Elements -->
        <div class="blob-decoration blob-1"></div>
        <div class="blob-decoration blob-2"></div>

        <div class="container relative z-10">
            <div class="section-title" data-aos="fade-up">
                <span>Our Professionals</span>
                <h2>Meet The Tailors</h2>
            </div>

            <div class="bg-white rounded-[24px] shadow-lg border border-gray-100 p-6 md:p-8 mb-10" data-aos="fade-up">
                <form method="GET" action="index.php#tailors" class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label font-semibold text-sm text-gray-600">Search</label>
                        <input type="text" name="q" value="<?= htmlspecialchars((string)($filter_q ?? '')) ?>" class="form-control" placeholder="Name, skills, or specialization">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label font-semibold text-sm text-gray-600">Location</label>
                        <select name="location" class="form-select" data-city-select>
                            <option value="all">All Locations</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= htmlspecialchars((string)$city) ?>" <?= silah_norm($filter_location) === silah_norm((string)$city) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$city) ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="__other__" <?= $filter_location_raw === '__other__' ? 'selected' : '' ?>>Other (Type City)</option>
                        </select>
                        <div class="mt-2 d-none" data-city-other-wrap>
                            <input type="text" name="location_other" value="<?= htmlspecialchars((string)($filter_location_other ?? '')) ?>" class="form-control" placeholder="Type city name" data-city-other-input>
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label font-semibold text-sm text-gray-600">Min Price (PKR)</label>
                        <input type="number" name="min_price" value="<?= $filter_min_price !== null ? htmlspecialchars((string)$filter_min_price) : '' ?>" class="form-control" min="0" step="0.01" placeholder="0">
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label font-semibold text-sm text-gray-600">Min Experience</label>
                        <input type="number" name="min_exp" value="<?= $filter_min_exp !== null ? htmlspecialchars((string)$filter_min_exp) : '' ?>" class="form-control" min="0" step="1" placeholder="0">
                    </div>
                    <div class="col-lg-1 d-grid">
                        <button type="submit" class="btn btn-primary px-4 py-2 rounded-full shadow-md">Apply</button>
                    </div>
                    <div class="col-12 d-flex justify-content-between align-items-center pt-2">
                        <p class="text-sm text-gray-500 mb-0 font-medium">
                            Showing <span class="font-bold text-primary"><?= (int)$tailors_total ?></span> tailors
                        </p>
                        <a href="index.php#tailors" class="text-sm font-bold text-gray-500 hover:text-primary text-decoration-none">Reset Filters</a>
                    </div>
                </form>
            </div>

            <!-- Horizontal Slider Container -->
            <div class="relative group/slider px-4 overflow-visible">
                <!-- Navigation Buttons (Always Visible on Sides) -->
                <button onclick="scrollTailors('left')" class="absolute -left-2 md:-left-8 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white shadow-xl border border-gray-100 flex items-center justify-center text-primary z-30 hover:bg-primary hover:text-white transition-all duration-300 cursor-pointer group/btn">
                    <i class="fas fa-chevron-left group-hover/btn:-translate-x-0.5 transition-transform"></i>
                </button>
                <button onclick="scrollTailors('right')" class="absolute -right-2 md:-right-8 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white shadow-xl border border-gray-100 flex items-center justify-center text-primary z-30 hover:bg-primary hover:text-white transition-all duration-300 cursor-pointer group/btn">
                    <i class="fas fa-chevron-right group-hover/btn:translate-x-0.5 transition-transform"></i>
                </button>

                <div id="tailorSlider" class="flex flex-nowrap overflow-x-auto gap-6 pt-12 pb-12 px-4 snap-x hide-scrollbar scroll-smooth">
                    <?php if (empty($tailors)): ?>
                        <div class="w-full py-10 text-center">
                            <p class="text-sm text-gray-500 font-bold uppercase tracking-widest mb-2">No tailors match your filters</p>
                            <a href="index.php#tailors" class="btn btn-outline px-8 py-3 rounded-full">Clear Filters</a>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($tailors as $tailor): ?>
                    <div class="min-w-[280px] md:min-w-[320px] snap-center h-full" data-aos="fade-up">
                        <a href="tailor_profile.php?id=<?= $tailor['id'] ?>" class="block h-full text-decoration-none">
                            <div class="tailor-card group">
                                <div class="tailor-img-container">
                                    <img src="<?= htmlspecialchars((string)($tailor['profile_image'] ?? '')) ?>" alt="<?= htmlspecialchars((string)($tailor['name'] ?? 'Tailor')) ?>" class="tailor-img">
                                    <div class="absolute top-4 right-4 z-10">
                                        <span class="bg-white/95 backdrop-blur-md text-primary text-[10px] font-bold px-3 py-1.5 rounded-full shadow-sm flex items-center gap-1.5 border border-gray-100">
                                            <i class="fas fa-star text-yellow-400"></i> 4.9
                                        </span>
                                    </div>
                                    <div class="overlay-action">
                                        <span class="view-btn">Explore Portfolio</span>
                                    </div>
                                </div>
                                <div class="tailor-info">
                                    <div class="tailor-badge-row">
                                        <span class="glass-badge"><?= htmlspecialchars((string)($tailor['location'] ?? 'Remote')) ?></span>
                                        <span class="price-tag"><?= htmlspecialchars((string)($tailor['experience_years'] ?? '0')) ?>Y Exp</span>
                                    </div>
                                    <h3 class="text-xl font-extrabold text-primary mb-2 group-hover:text-accent transition-colors duration-300"><?= htmlspecialchars((string)($tailor['name'] ?? 'Tailor')) ?></h3>
                                    <p class="text-sm text-gray-500 mb-6 line-clamp-2 leading-relaxed italic">"<?= htmlspecialchars((string)($tailor['tagline'] ?? 'Quality Tailoring')) ?>"</p>
                                    
                                    <div class="mt-auto flex items-center justify-between pt-5 border-t border-gray-50">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Starting Price</span>
                                            <div class="flex items-baseline gap-1">
                                                <span class="text-lg font-black text-primary">PKR <?= number_format($tailor['price_range_min'] ?? 0) ?></span>
                                            </div>
                                        </div>
                                        <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center transform group-hover:rotate-[360deg] group-hover:bg-accent transition-all duration-700 shadow-lg">
                                            <i class="fas fa-arrow-right text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 bg-white relative overflow-hidden">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="bg-white p-8 md:p-12 rounded-[24px] shadow-xl border border-gray-100 text-center" data-aos="fade-up">
                        <span class="text-accent font-bold tracking-widest uppercase mb-3 block">Ready to place an order?</span>
                        <h2 class="text-3xl md:text-4xl font-display font-extrabold text-primary mb-4">Open the Order Page</h2>
                        <p class="text-gray-600 mb-6">A cleaner experience with tailor-specific services, live total price, and bargaining chat.</p>
                        <div class="flex flex-wrap justify-center gap-3">
                            <a href="place_order.php#order" class="btn btn-accent text-white px-8 py-3 rounded-full shadow-lg hover:shadow-xl transition-all">Place Order</a>
                            <a href="order_chat.php" class="btn btn-outline px-8 py-3 rounded-full">Open Chat</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 4: Track Your Order -->
    <section id="track" class="py-24 bg-primary-soft">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="section-title" data-aos="fade-up">
                        <span>Check Status</span>
                        <h2>Track Your Order</h2>
                    </div>
                    
                    <div class="bg-white p-8 md:p-10 rounded-[24px] shadow-xl border border-gray-100" data-aos="zoom-in">
                        <form id="trackOrderForm" class="mb-8">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="text" id="trackOrderNumber" class="form-control" placeholder="Order Number (e.g. SIL-0001)" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="email" id="trackEmail" class="form-control" placeholder="Order Email" required>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary px-8">Track</button>
                                </div>
                            </div>
                        </form>

                        <div id="trackingResult" class="d-none">
                            <div class="tracking-steps">
                                <div class="step-item" id="step-placed">
                                    <div class="step-dot"><i class="fas fa-file-invoice"></i></div>
                                    <div class="step-label">Order Placed</div>
                                </div>
                                <div class="step-item" id="step-review">
                                    <div class="step-dot"><i class="fas fa-search"></i></div>
                                    <div class="step-label">Under Review</div>
                                </div>
                                <div class="step-item" id="step-selected">
                                    <div class="step-dot"><i class="fas fa-user-check"></i></div>
                                    <div class="step-label">Tailor Selected</div>
                                </div>
                                <div class="step-item" id="step-progress">
                                    <div class="step-dot"><i class="fas fa-cut"></i></div>
                                    <div class="step-label">In Progress</div>
                                </div>
                                <div class="step-item" id="step-completed">
                                    <div class="step-dot"><i class="fas fa-check-double"></i></div>
                                    <div class="step-label">Completed</div>
                                </div>
                            </div>
                            
                            <div id="orderStatusDetail" class="text-center p-4 bg-gray-50 rounded-xl border border-gray-100">
                                <!-- Status details will be injected here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 5: Contact Us -->
    <section id="contact" class="py-24 bg-gray-50">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-5" data-aos="fade-right">
                    <div class="section-title text-start">
                        <span>Get In Touch</span>
                        <h2>Contact Us</h2>
                    </div>
                    <p class="text-gray-600 mb-8">Have questions? We're here to help you find the perfect fit.</p>
                    
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center text-accent mr-4">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h5 class="font-bold text-primary">Email Us</h5>
                                <p class="text-gray-500">silah.orders@gmail.com</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center text-accent mr-4">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <h5 class="font-bold text-primary">Call Us</h5>
                                <p class="text-gray-500">+92 336 7326095</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center text-accent mr-4">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <div>
                                <h5 class="font-bold text-primary">WhatsApp</h5>
                                <p class="text-gray-500">+92 336 7326095</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-10">
                        <a href="https://wa.me/923367326095" class="btn btn-success rounded-full px-8 py-3 shadow-lg hover:shadow-xl transition-all flex items-center gap-2 w-max">
                            <i class="fab fa-whatsapp text-xl"></i> Chat on WhatsApp
                        </a>
                    </div>
                </div>
                <div class="col-lg-7" data-aos="fade-left">
                    <div class="bg-white p-8 rounded-[24px] shadow-lg border border-gray-100 h-full">
                        <h3 class="text-2xl font-bold mb-6">Send a Message</h3>
                        <form action="process_contact.php" method="POST">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <input type="text" name="name" class="form-control" placeholder="Your Name" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="email" name="email" class="form-control" placeholder="Your Email" required>
                                </div>
                                <div class="col-12">
                                    <input type="text" name="subject" class="form-control" placeholder="Subject" required>
                                </div>
                                <div class="col-12">
                                    <textarea name="message" class="form-control" rows="5" placeholder="How can we help?" required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary w-full rounded-full py-3">Send Message</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-dark">
        <div class="container">
            <div class="row g-5 mb-12">
                <div class="col-lg-4">
                    <a class="flex items-center gap-2 text-white text-2xl font-bold mb-6" href="#">
                        <span class="text-accent"><i class="fas fa-layer-group"></i></span>
                        <span>SILAH</span>
                    </a>
                    <p class="text-gray-400 mb-6 leading-relaxed">
                        The premium marketplace connecting discerning clients with the world's finest tailors. Quality, trust, and elegance in every stitch.
                    </p>
                    <div class="flex">
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <h5 class="text-white font-bold mb-6">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#home" class="footer-link">Home</a></li>
                        <li><a href="#how-it-works" class="footer-link">How It Works</a></li>
                        <li><a href="#tailors" class="footer-link">Find Tailors</a></li>
                        <li><a href="join_tailor.php" class="footer-link">Join as Tailor</a></li>
                        <li><a href="tailor_dashboard.php" class="footer-link text-accent">Tailor Dashboard</a></li>
                        <li><a href="admin/login.php" class="footer-link">Admin Login</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-6">
                    <h5 class="text-white font-bold mb-6">Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="#contact" class="footer-link">Contact Us</a></li>
                        <li><a href="#" class="footer-link">FAQs</a></li>
                        <li><a href="#" class="footer-link">Privacy Policy</a></li>
                        <li><a href="#" class="footer-link">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="text-white font-bold mb-6">Feedback</h5>
                    <p class="text-gray-400 mb-4 text-sm">We value your opinion. Let us know how we can improve.</p>
                    <form action="#" class="relative">
                        <input type="text" class="w-full bg-white/10 border border-white/20 rounded-full py-3 px-5 text-white placeholder-gray-400 focus:outline-none focus:border-accent" placeholder="Your Feedback">
                        <button class="absolute right-2 top-1.5 w-10 h-10 bg-accent rounded-full flex items-center justify-center text-white hover:bg-white hover:text-accent transition-all">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="border-t border-white/10 pt-8 text-center text-gray-500 text-sm">
                <p>&copy; 2026 Silah Marketplace. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp Button -->
    <a href="https://wa.me/923367326095" target="_blank" class="fixed bottom-6 right-6 bg-[#25D366] text-white w-14 h-14 rounded-full shadow-2xl flex items-center justify-center hover:scale-110 transition-transform z-50 group">
        <i class="fab fa-whatsapp text-3xl"></i>
        <span class="absolute right-16 bg-white text-gray-800 px-3 py-1 rounded shadow-md text-sm whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none font-medium">Chat with us</span>
    </a>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Language Selection Logic
        document.addEventListener('DOMContentLoaded', function() {
            const selectedLang = localStorage.getItem('silah_lang');
            if (!selectedLang) {
                document.getElementById('langModal').classList.remove('d-none');
            } else {
                applyLanguage(selectedLang);
            }
        });

        function setLanguage(lang) {
            localStorage.setItem('silah_lang', lang);
            document.getElementById('langModal').classList.add('d-none');
            applyLanguage(lang);
        }

        function applyLanguage(lang) {
            // For now, we'll just log it. Real implementation would swap text.
            console.log('Language set to:', lang);
            if (lang === 'ur') {
                document.body.classList.add('rtl-active');
                // You could add a simple translation mapping here for key elements
            } else {
                document.body.classList.remove('rtl-active');
            }
        }

        // Tailor Slider Navigation
        function scrollTailors(direction) {
            const slider = document.getElementById('tailorSlider');
            const cardWidth = slider.querySelector('.min-w-\\[280px\\]').offsetWidth + 24; // card + gap (24px is gap-6)
            
            if (direction === 'left') {
                slider.scrollBy({ left: -cardWidth, behavior: 'smooth' });
            } else {
                slider.scrollBy({ left: cardWidth, behavior: 'smooth' });
            }
        }

        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });

        (function() {
            const heroBg = document.getElementById('heroBg');
            if (!heroBg) return;
            const images = [
                'images/stock/unsplash_1594938298603-c8148c4dae35.jpg',
                'images/stock/unsplash_1544441893-675973e31985.jpg',
                'images/stock/unsplash_1507679799987-c73779587ccf.jpg',
                'images/stock/unsplash_1441984904996-e0b6ba687e04.jpg'
            ];
            let idx = 0;

            const show = (src) => {
                heroBg.style.opacity = '0';
                const img = new Image();
                img.onload = () => {
                    heroBg.style.backgroundImage = `url('${src}')`;
                    requestAnimationFrame(() => {
                        heroBg.style.opacity = '1';
                    });
                };
                img.onerror = () => {
                    heroBg.style.backgroundImage = '';
                    heroBg.style.backgroundColor = '#0a0612';
                    requestAnimationFrame(() => {
                        heroBg.style.opacity = '1';
                    });
                };
                img.src = src;
            };

            show(images[idx]);
            setInterval(() => {
                idx = (idx + 1) % images.length;
                show(images[idx]);
            }, 5500);
        })();

        (function() {
            const el = document.getElementById('heroTypewriter');
            if (!el) return;
            const raw = el.getAttribute('data-phrases') || '';
            const phrases = raw.split('|').map(s => s.trim()).filter(Boolean);
            if (!phrases.length) return;

            let phraseIndex = 0;
            let charIndex = 0;
            let isDeleting = false;

            const type = () => {
                const phrase = phrases[phraseIndex] || '';
                const text = phrase.substring(0, charIndex);
                el.textContent = text;

                const typingSpeed = isDeleting ? 45 : 75;
                const pauseAfterTyped = 900;
                const pauseAfterDeleted = 250;

                if (!isDeleting && charIndex < phrase.length) {
                    charIndex += 1;
                    setTimeout(type, typingSpeed);
                    return;
                }

                if (!isDeleting && charIndex >= phrase.length) {
                    isDeleting = true;
                    setTimeout(type, pauseAfterTyped);
                    return;
                }

                if (isDeleting && charIndex > 0) {
                    charIndex -= 1;
                    setTimeout(type, typingSpeed);
                    return;
                }

                if (isDeleting && charIndex === 0) {
                    isDeleting = false;
                    phraseIndex = (phraseIndex + 1) % phrases.length;
                    setTimeout(type, pauseAfterDeleted);
                    return;
                }
            };

            type();
        })();

        // Custom Tailor Dropdown Logic
        function toggleTailorDropdown() {
            const menu = document.getElementById('tailorDropdownMenu');
            const arrow = document.getElementById('dropdownArrow');
            const trigger = document.getElementById('tailorDropdownTrigger');
            
            const isOpen = !menu.classList.contains('d-none');
            
            if (isOpen) {
                menu.classList.add('d-none');
                arrow.style.transform = 'rotate(0deg)';
                trigger.classList.remove('border-primary', 'ring-4', 'ring-purple-100');
            } else {
                menu.classList.remove('d-none');
                arrow.style.transform = 'rotate(180deg)';
                trigger.classList.add('border-primary', 'ring-4', 'ring-purple-100');
                document.getElementById('tailorSearch').focus();
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.querySelector('.custom-tailor-dropdown');
            if (!dropdown) return;
            if (!dropdown.contains(e.target)) {
                const menu = document.getElementById('tailorDropdownMenu');
                const arrow = document.getElementById('dropdownArrow');
                const trigger = document.getElementById('tailorDropdownTrigger');
                if (menu) menu.classList.add('d-none');
                if (arrow) arrow.style.transform = 'rotate(0deg)';
                if (trigger) trigger.classList.remove('border-primary', 'ring-4', 'ring-purple-100');
            }
        });

        function filterTailors() {
            const query = document.getElementById('tailorSearch').value.toLowerCase();
            const options = document.querySelectorAll('.tailor-option');
            
            options.forEach(opt => {
                const name = opt.getAttribute('data-name');
                const location = opt.getAttribute('data-location');
                if (name.includes(query) || location.includes(query)) {
                    opt.classList.remove('d-none');
                } else {
                    opt.classList.add('d-none');
                }
            });
        }

        function toggleTailorSelection(id, name, img, event) {
            if (event) event.stopPropagation();
            
            const checkbox = document.getElementById('check-tailor-' + id);
            const option = checkbox.closest('.tailor-option');
            const checkIcon = option.querySelector('.selection-check');
            const selectedList = document.getElementById('selectedTailorsList');
            
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                option.classList.add('bg-primary-soft');
                checkIcon.classList.remove('scale-0');
                checkIcon.classList.add('scale-100');
            } else {
                option.classList.remove('bg-primary-soft');
                checkIcon.classList.remove('scale-100');
                checkIcon.classList.add('scale-0');
            }
            
            updateSelectedBadges();
            validateTailorSelection();
        }

        function updateSelectedBadges() {
            const checkboxes = document.querySelectorAll('.tailor-checkbox');
            const selectedList = document.getElementById('selectedTailorsList');
            const checked = Array.from(checkboxes).filter(cb => cb.checked);
            
            if (checked.length === 0) {
                selectedList.innerHTML = '<span class="text-gray-400 text-sm">Choose your preferred tailors...</span>';
                return;
            }
            
            selectedList.innerHTML = '';
            checked.forEach(cb => {
                const option = cb.closest('.tailor-option');
                const name = option.querySelector('.font-bold').textContent;
                const imgSrc = option.querySelector('img').src;
                
                const badge = document.createElement('div');
                badge.className = 'flex items-center gap-1.5 bg-primary text-white pl-1 pr-2 py-1 rounded-full text-[10px] font-bold shadow-sm animate-in fade-in zoom-in duration-300';
                badge.innerHTML = `
                    <img src="${imgSrc}" class="w-5 h-5 rounded-full object-cover border border-white/20">
                    <span>${name}</span>
                    <i class="fas fa-times-circle cursor-pointer hover:text-red-200" onclick="toggleTailorSelection('${cb.value}', '', '', event)"></i>
                `;
                selectedList.appendChild(badge);
            });
        }

        function validateTailorSelection() {
            const trigger = document.getElementById('tailorDropdownTrigger');
            if (!trigger) return true;
            const checkboxes = document.querySelectorAll('.tailor-checkbox');
            const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
            const errorMsg = document.getElementById('tailor-selection-error');

            const hireId = typeof window.__SILAH_HIRE_TAILOR_ID__ === 'number' ? window.__SILAH_HIRE_TAILOR_ID__ : 0;
            const required = hireId > 0 ? 1 : 3;
            if (errorMsg && errorMsg.textContent === '') {
                errorMsg.textContent = required === 1 ? 'Please select at least 1 tailor.' : 'Please select at least 3 tailors.';
            }

            if (checkedCount < required) {
                errorMsg.classList.remove('d-none');
                trigger.classList.add('border-danger');
                return false;
            } else {
                errorMsg.classList.add('d-none');
                trigger.classList.remove('border-danger');
                return true;
            }
        }

        function preselectHireTailor() {
            const hireId = typeof window.__SILAH_HIRE_TAILOR_ID__ === 'number' ? window.__SILAH_HIRE_TAILOR_ID__ : 0;
            if (!hireId) return;
            const checkbox = document.getElementById('check-tailor-' + hireId);
            if (!checkbox) return;
            if (!checkbox.checked) {
                checkbox.checked = true;
                const option = checkbox.closest('.tailor-option');
                if (option) {
                    option.classList.add('bg-primary-soft');
                    const checkIcon = option.querySelector('.selection-check');
                    if (checkIcon) {
                        checkIcon.classList.remove('scale-0');
                        checkIcon.classList.add('scale-100');
                    }
                }
            }
            updateSelectedBadges();
            validateTailorSelection();
        }

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

        // Add form submission validation (legacy preferred-tailor dropdown)
        const _orderForm = document.querySelector('form[action="process_order.php"]');
        if (_orderForm) {
            _orderForm.addEventListener('submit', function(e) {
                const trigger = document.getElementById('tailorDropdownTrigger');
                if (!trigger) return;
                if (!validateTailorSelection()) {
                    e.preventDefault();
                    const orderSection = document.getElementById('order');
                    if (orderSection) orderSection.scrollIntoView({ behavior: 'smooth' });
                    trigger.classList.add('animate-shake');
                    setTimeout(() => trigger.classList.remove('animate-shake'), 500);
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            preselectHireTailor();
        });

        // Navbar Scroll Effect
        const updateNavbar = () => {
            const nav = document.getElementById('mainNav');
            if (!nav) return;
            if (window.scrollY > 50) {
                nav.classList.remove('navbar-hero');
            } else {
                nav.classList.add('navbar-hero');
            }
            
            // Active Link Scroll Spy
            let sections = document.querySelectorAll('section, header');
            let navLinks = document.querySelectorAll('.nav-link');
            
            let current = '';
            
            sections.forEach(section => {
                if (!section.getAttribute('id')) return;
                const sectionTop = section.offsetTop;
                // const sectionHeight = section.clientHeight; // Unused
                if (window.scrollY >= (sectionTop - 150)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') && link.getAttribute('href').includes(current) && current !== '') {
                    link.classList.add('active');
                }
            });
        };
        window.addEventListener('scroll', updateNavbar);
        document.addEventListener('DOMContentLoaded', updateNavbar);

        (function() {
            const nav = document.getElementById('mainNav');
            const offcanvasEl = document.getElementById('mobileNav');
            if (!nav || !offcanvasEl) return;
            offcanvasEl.addEventListener('show.bs.offcanvas', function() {
                nav.classList.add('navbar-open');
                nav.classList.remove('navbar-hero');
            });
            offcanvasEl.addEventListener('hidden.bs.offcanvas', function() {
                nav.classList.remove('navbar-open');
                updateNavbar();
            });
        })();

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

        // Order Tracking Frontend Logic
        document.getElementById('trackOrderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const orderNumber = document.getElementById('trackOrderNumber').value;
            const email = document.getElementById('trackEmail').value;
            const resultDiv = document.getElementById('trackingResult');
            const statusDetail = document.getElementById('orderStatusDetail');

            fetch(`check_status.php?order_number=${encodeURIComponent(orderNumber)}&email=${encodeURIComponent(email)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.classList.remove('d-none');
                        const status = data.status;
                        const steps = ['Order Placed', 'Under Review', 'Tailor Selected', 'In Progress', 'Completed'];
                        const stepIds = ['step-placed', 'step-review', 'step-selected', 'step-progress', 'step-completed'];
                        
                        const currentIndex = steps.indexOf(status);
                        stepIds.forEach((id, index) => {
                            const el = document.getElementById(id);
                            if (index <= currentIndex) {
                                el.classList.add('active');
                            } else {
                                el.classList.remove('active');
                            }
                        });

                        const payment = data.payment_status ? data.payment_status : 'Pending';
                        const total = typeof data.total_price === 'number' ? data.total_price : 0;
                        const advance = typeof data.advance_required === 'number' ? data.advance_required : (total * 0.3);
                        const chatUrl = data.chat_url ? data.chat_url : '';
                        const cargoCompany = data.cargo_company ? data.cargo_company : '';
                        const cargoTrack = data.cargo_tracking_number ? data.cargo_tracking_number : '';
                        const cargoReceipt = data.cargo_receipt_image ? data.cargo_receipt_image : '';

                        statusDetail.innerHTML = `
                            <h4 class="font-bold text-primary mb-1">${data.order_number || ''}</h4>
                            <p class="text-sm font-bold text-gray-700 mb-2">${status}</p>
                            <p class="text-gray-500 text-sm mb-3">Last update: ${data.date}</p>
                            <div class="d-flex justify-content-center gap-3 flex-wrap mb-4">
                                <span class="badge bg-light text-dark border">Payment: ${payment}</span>
                                <span class="badge bg-light text-dark border">Total: PKR ${Number(total).toLocaleString()}</span>
                                <span class="badge bg-light text-dark border">Advance (30%): PKR ${Number(advance).toLocaleString()}</span>
                            </div>

                            ${cargoCompany ? `
                                <div class="p-4 bg-green-50 border border-green-100 rounded-3xl mb-4 text-start max-w-sm mx-auto shadow-sm">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Shipment Tracking</p>
                                    <div class="mb-3">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Cargo Name</p>
                                        <p class="text-sm font-black text-gray-800 mb-0">${cargoCompany}</p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Cargo Number</p>
                                        <p class="text-sm font-black text-gray-800 mb-0">${cargoTrack}</p>
                                    </div>
                                    ${cargoReceipt ? `
                                        <div>
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Transcript</p>
                                            <div class="rounded-2xl overflow-hidden border border-gray-100 bg-white shadow-sm">
                                                <img src="${cargoReceipt}" alt="Cargo Receipt" class="w-full h-auto">
                                            </div>
                                        </div>
                                    ` : ''}
                                </div>
                            ` : ''}

                            ${chatUrl ? `<div class="mt-3"><a class="btn btn-outline rounded-full px-6 py-2" href="${chatUrl}">Open Chat</a></div>` : ''}
                            ${data.demo ? '<div class="badge bg-warning text-dark mt-3">Demo Mode: No DB connected</div>' : ''}
                        `;
                    } else {
                        alert(data.message);
                        resultDiv.classList.add('d-none');
                    }
                })
                .catch(err => alert('Error checking status. Please try again.'));
        });
    </script>
</body>
</html>
