<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/cities.php';
$cities = silah_get_cities($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join as a Tailor | Silah</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
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
</head>
<body class="bg-bg text-text">

    <!-- Navigation -->
    <nav class="navbar fixed-top navbar-expand-lg navbar-light bg-white shadow-sm z-50" id="mainNav">
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
                    <li class="nav-item"><a class="nav-link text-dark fw-medium active" href="join_tailor.php">Join as Tailor</a></li>
                    <li class="nav-item dropdown ms-lg-3">
                        <a class="btn btn-outline px-6 py-2 rounded-full d-inline-block dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Portal
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3xl p-2">
                            <li><a class="dropdown-item rounded-2xl py-2 px-3" href="customer/login.php">Client Portal</a></li>
                            <li><a class="dropdown-item rounded-2xl py-2 px-3" href="customer/orders.php">My Orders</a></li>
                            <li><hr class="dropdown-divider my-2"></li>
                            <li><a class="dropdown-item rounded-2xl py-2 px-3" href="admin/login.php">Admin / Tailor Portal</a></li>
                        </ul>
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

    <!-- Header -->
    <header class="pt-28 pb-14">
        <div class="container">
            <div class="max-w-5xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-7" data-aos="fade-up">
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/70 backdrop-blur border border-white/50 text-[11px] font-extrabold uppercase tracking-widest text-primary shadow-sm">
                            <i class="fas fa-scissors"></i>
                            Become a Silah Tailor
                        </span>
                        <h1 class="mt-5 text-4xl md:text-5xl font-extrabold tracking-tight text-primary leading-tight">
                            Grow your tailoring business with premium clients
                        </h1>
                        <p class="mt-4 text-base md:text-lg text-gray-600 leading-relaxed">
                            Apply once, get verified, and start receiving high-quality orders matched to your specialization.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <div class="px-4 py-3 rounded-2xl bg-white/70 border border-white/50 shadow-sm">
                                <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-1">Faster Growth</p>
                                <p class="text-sm font-bold text-gray-800 mb-0">Get discovered by customers</p>
                            </div>
                            <div class="px-4 py-3 rounded-2xl bg-white/70 border border-white/50 shadow-sm">
                                <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-1">Portfolio First</p>
                                <p class="text-sm font-bold text-gray-800 mb-0">Showcase your best work</p>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-5" data-aos="fade-up" data-aos-delay="100">
                        <div class="rounded-3xl bg-white/75 backdrop-blur-xl border border-white/50 shadow-xl p-6">
                            <h3 class="text-lg font-extrabold text-primary mb-4">What happens after you apply?</h3>
                            <div class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-extrabold">1</div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800 mb-0">We review your profile</p>
                                        <p class="text-xs text-gray-500 mb-0">Portfolio, specialization, and contact details.</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-extrabold">2</div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800 mb-0">Approval & onboarding</p>
                                        <p class="text-xs text-gray-500 mb-0">You will appear in Silah's tailor network.</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-extrabold">3</div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800 mb-0">Start getting orders</p>
                                        <p class="text-xs text-gray-500 mb-0">Customers select tailors based on skill and style.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Application Form -->
    <section class="pb-20">
        <div class="container">
            <div class="max-w-4xl mx-auto">
                <div class="rounded-[28px] bg-white/80 backdrop-blur-xl border border-white/60 shadow-2xl p-6 sm:p-10" data-aos="fade-up">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
                        <div>
                            <h2 class="text-2xl sm:text-3xl font-extrabold text-primary tracking-tight mb-1">Tailor Application</h2>
                            <p class="text-sm text-gray-600 mb-0">Fill out the details below to join Silah.</p>
                        </div>
                        <div class="px-4 py-3 rounded-2xl bg-primary/5 border border-primary/10">
                            <p class="text-[10px] font-extrabold text-primary uppercase tracking-widest mb-1">Tip</p>
                            <p class="text-xs text-gray-600 mb-0">Add Instagram or upload portfolio images.</p>
                        </div>
                    </div>
                        
                        <?php if (isset($_GET['status']) && $_GET['status'] == 'db_error'): ?>
                    <div class="mb-8 p-4 rounded-2xl bg-red-50 border border-red-100">
                        <p class="text-xs font-extrabold text-red-600 uppercase tracking-widest mb-1">Database Offline</p>
                        <p class="text-sm font-semibold text-red-800 mb-0">Please start MySQL in WAMP/XAMPP, then try submitting again.</p>
                    </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
                    <div class="mb-8 p-4 rounded-2xl bg-red-50 border border-red-100">
                        <p class="text-xs font-extrabold text-red-600 uppercase tracking-widest mb-1">Submission Failed</p>
                        <p class="text-sm font-semibold text-red-800 mb-0">Something went wrong while submitting your application. Please try again.</p>
                        <?php if (isset($_GET['err']) && $_GET['err'] !== ''): ?>
                            <p class="text-xs text-red-700 mt-2 mb-0 break-all"><?= htmlspecialchars((string)$_GET['err']) ?></p>
                        <?php endif; ?>
                    </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                    <div class="mb-8 p-4 rounded-2xl bg-green-50 border border-green-100">
                        <p class="text-xs font-extrabold text-green-700 uppercase tracking-widest mb-1">Submitted</p>
                        <p class="text-sm font-semibold text-green-800 mb-0">Your application has been submitted. We will review it and contact you shortly.</p>
                    </div>
                        <?php endif; ?>

                    <form action="process_join.php" method="POST" enctype="multipart/form-data" id="tailorAppForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="text-[11px] font-extrabold text-gray-500 uppercase tracking-widest ml-4 mb-2 block">Full Name</label>
                                <div class="relative">
                                    <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="text" name="name" class="w-full rounded-2xl border border-gray-200 bg-white/80 pl-12 pr-5 py-3 text-sm font-semibold text-gray-800 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all" placeholder="Your Name" required>
                                </div>
                            </div>
                            <div>
                                <label class="text-[11px] font-extrabold text-gray-500 uppercase tracking-widest ml-4 mb-2 block">Email Address</label>
                                <div class="relative">
                                    <i class="fas fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="email" name="email" class="w-full rounded-2xl border border-gray-200 bg-white/80 pl-12 pr-5 py-3 text-sm font-semibold text-gray-800 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all" placeholder="you@example.com" required>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-[11px] font-extrabold text-gray-500 uppercase tracking-widest ml-4 mb-2 block">Profile Picture</label>
                                <input type="file" name="profile_image" id="profile_image" class="w-full rounded-2xl border border-dashed border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-700 file:mr-4 file:rounded-xl file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:font-bold file:text-primary hover:border-primary/40 transition-all" accept="image/*" onchange="previewMedia(this, 'profile-preview-grid')">
                                <div id="profile-preview-grid" class="grid grid-cols-4 gap-2 mt-3"></div>
                            </div>
                            <div>
                                <label class="text-[11px] font-extrabold text-gray-500 uppercase tracking-widest ml-4 mb-2 block">Phone Number</label>
                                <div class="relative">
                                    <i class="fas fa-phone absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="tel" name="phone" class="w-full rounded-2xl border border-gray-200 bg-white/80 pl-12 pr-5 py-3 text-sm font-semibold text-gray-800 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all" placeholder="+92 3XX XXXXXXX" required>
                                </div>
                            </div>
                            <div>
                                <label class="text-[11px] font-extrabold text-gray-500 uppercase tracking-widest ml-4 mb-2 block">City</label>
                                <div class="relative">
                                    <i class="fas fa-location-dot absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <select name="location" class="w-full rounded-2xl border border-gray-200 bg-white/80 pl-12 pr-5 py-3 text-sm font-semibold text-gray-800 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all" required data-city-select>
                                        <option value="" selected disabled>Select city...</option>
                                        <?php foreach ($cities as $city): ?>
                                            <option value="<?= htmlspecialchars((string)$city) ?>"><?= htmlspecialchars((string)$city) ?></option>
                                        <?php endforeach; ?>
                                        <option value="__other__">Other (Type City)</option>
                                    </select>
                                </div>
                                <div class="relative mt-3 d-none" data-city-other-wrap>
                                    <i class="fas fa-pen absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="text" name="location_other" class="w-full rounded-2xl border border-gray-200 bg-white/80 pl-12 pr-5 py-3 text-sm font-semibold text-gray-800 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all" placeholder="Type your city name" data-city-other-input>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-[11px] font-extrabold text-gray-500 uppercase tracking-widest ml-4 mb-2 block">Complete Address</label>
                                <div class="relative">
                                    <i class="fas fa-map-pin absolute left-5 top-4 text-gray-400"></i>
                                    <textarea name="address" class="w-full rounded-2xl border border-gray-200 bg-white/80 pl-12 pr-5 py-3 text-sm font-semibold text-gray-800 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all" rows="3" placeholder="Street, Area, House No." required></textarea>
                                </div>
                            </div>
                            <div>
                                <label class="text-[11px] font-extrabold text-gray-500 uppercase tracking-widest ml-4 mb-2 block">Years of Experience</label>
                                <div class="relative">
                                    <i class="fas fa-award absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="number" name="experience_years" class="w-full rounded-2xl border border-gray-200 bg-white/80 pl-12 pr-5 py-3 text-sm font-semibold text-gray-800 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all" placeholder="e.g. 5" min="0" required>
                                </div>
                            </div>
                            <div>
                                <label class="text-[11px] font-extrabold text-gray-500 uppercase tracking-widest ml-4 mb-2 block">Specialization</label>
                                <div class="relative">
                                    <i class="fas fa-scissors absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="text" name="specialization" class="w-full rounded-2xl border border-gray-200 bg-white/80 pl-12 pr-5 py-3 text-sm font-semibold text-gray-800 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all" placeholder="e.g. Suits, Bridal, Gents" required>
                                </div>
                            </div>
                            <div>
                                <label class="text-[11px] font-extrabold text-gray-500 uppercase tracking-widest ml-4 mb-2 block">Starting Price (PKR)</label>
                                <div class="relative">
                                    <i class="fas fa-tag absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="number" name="price_range_min" class="w-full rounded-2xl border border-gray-200 bg-white/80 pl-12 pr-5 py-3 text-sm font-semibold text-gray-800 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all" placeholder="e.g. 5000" min="0" step="0.01" required>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-[11px] font-extrabold text-gray-500 uppercase tracking-widest ml-4 mb-2 block">Instagram Link</label>
                                <div class="relative">
                                    <i class="fab fa-instagram absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="url" name="instagram_link" id="instagram_link" class="w-full rounded-2xl border border-gray-200 bg-white/80 pl-12 pr-5 py-3 text-sm font-semibold text-gray-800 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all" placeholder="https://instagram.com/yourprofile" oninput="validatePortfolioRequirements()">
                                </div>
                                <p class="mt-2 text-xs text-gray-500 ml-4">If not provided, portfolio image upload becomes required.</p>
                            </div>
                                
                                <!-- Portfolio Uploads -->
                            <div class="md:col-span-2">
                                <div id="portfolio-card" class="rounded-3xl bg-white/70 border border-gray-100 p-5 sm:p-6">
                                    <div class="flex items-center justify-between gap-4 mb-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-11 h-11 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                                                <i class="fas fa-images"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-extrabold text-gray-800 mb-0">Portfolio Showcase</p>
                                                <p class="text-xs text-gray-500 mb-0">Add photos/videos of your best work.</p>
                                            </div>
                                        </div>
                                        <span class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest">Optional</span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div>
                                            <label class="text-[11px] font-extrabold text-gray-500 uppercase tracking-widest ml-4 mb-2 block">Upload Images (Max 10)</label>
                                            <input type="file" name="portfolio_images[]" id="portfolio_images" class="w-full rounded-2xl border border-dashed border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-700 file:mr-4 file:rounded-xl file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:font-bold file:text-primary hover:border-primary/40 transition-all" accept="image/*" multiple onchange="previewMedia(this, 'image-preview-grid')">
                                            <div id="image-preview-grid" class="grid grid-cols-4 gap-2 mt-3"></div>
                                        </div>
                                        <div>
                                            <label class="text-[11px] font-extrabold text-gray-500 uppercase tracking-widest ml-4 mb-2 block">Upload Short Videos (Max 3)</label>
                                            <input type="file" name="portfolio_videos[]" id="portfolio_videos" class="w-full rounded-2xl border border-dashed border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-700 file:mr-4 file:rounded-xl file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:font-bold file:text-primary hover:border-primary/40 transition-all" accept="video/*" multiple onchange="previewMedia(this, 'video-preview-grid')">
                                            <div id="video-preview-grid" class="grid grid-cols-4 gap-2 mt-3"></div>
                                        </div>
                                    </div>
                                    <div id="portfolio-error" class="mt-3 px-4 py-3 rounded-2xl bg-red-50 border border-red-100 text-xs font-bold text-red-600 hidden">
                                        Please upload at least one image if Instagram link is not provided.
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-2 pt-2">
                                <button id="joinSubmitBtn" type="submit" class="w-full rounded-2xl bg-primary text-white py-4 font-extrabold uppercase tracking-widest text-xs shadow-xl hover:shadow-primary/20 hover:-translate-y-0.5 transition-all">
                                    Submit Application
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white py-12 border-t border-gray-100">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <a class="flex items-center gap-2 mb-4 text-decoration-none" href="index.php">
                        <img src="images/logo1.png" alt="Silah Logo" class="w-10 h-10 object-contain mix-blend-multiply">
                        <span class="font-bold text-2xl text-primary">SILAH</span>
                    </a>
                    <p class="text-gray-500 mb-6">Connecting you with the finest tailors for bespoke elegance and perfect fits.</p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 rounded-full bg-bg flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-colors"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-bg flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-colors"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-bg flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-colors"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <h5 class="font-bold mb-4 text-lg text-gray-800">Quick Links</h5>
                    <ul class="list-none p-0 space-y-2">
                        <li><a href="index.php" class="text-gray-500 hover:text-primary text-decoration-none">Home</a></li>
                        <li><a href="index.php#tailors" class="text-gray-500 hover:text-primary text-decoration-none">Find Tailors</a></li>
                        <li><a href="join_tailor.php" class="text-gray-500 hover:text-primary text-decoration-none">Join as Tailor</a></li>
                        <li><a href="index.php#contact" class="text-gray-500 hover:text-primary text-decoration-none">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-6">
                    <h5 class="font-bold mb-4 text-lg text-gray-800">Support</h5>
                    <ul class="list-none p-0 space-y-2">
                        <li><a href="#" class="text-gray-500 hover:text-primary text-decoration-none">FAQ</a></li>
                        <li><a href="#" class="text-gray-500 hover:text-primary text-decoration-none">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-500 hover:text-primary text-decoration-none">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="font-bold mb-4 text-lg text-gray-800">Feedback</h5>
                    <p class="text-gray-500 mb-4">Help us improve your experience.</p>
                    <form action="#" class="flex gap-2">
                        <input type="text" class="form-control rounded-full border-gray-200 bg-bg text-gray-800 focus:border-accent focus:ring-0" placeholder="Your feedback...">
                        <button class="btn btn-accent rounded-full w-12 h-12 flex items-center justify-center text-white"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
            <div class="border-t border-gray-100 mt-12 pt-8 text-center text-gray-400 text-sm">
                &copy; 2026 Silah. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });

        function previewMedia(input, gridId) {
            const grid = document.getElementById(gridId);
            grid.innerHTML = '';
            
            if (input.files) {
                Array.from(input.files).forEach(file => {
                    const reader = new FileReader();
                    const col = document.createElement('div');
                    col.className = 'rounded-xl overflow-hidden border border-gray-100 bg-white shadow-sm';
                    
                    reader.onload = function(e) {
                        if (file.type.startsWith('image/')) {
                            col.innerHTML = `<img src="${e.target.result}" class="w-full h-20 object-cover">`;
                        } else if (file.type.startsWith('video/')) {
                            col.innerHTML = `<div class="bg-primary text-white h-20 flex items-center justify-center text-[10px] font-extrabold uppercase tracking-widest"><i class="fas fa-video mr-2"></i> Video</div>`;
                        }
                    }
                    reader.readAsDataURL(file);
                    grid.appendChild(col);
                });
            }
            validatePortfolioRequirements();
        }

        function validatePortfolioRequirements() {
            const instagram = document.getElementById('instagram_link').value;
            const images = document.getElementById('portfolio_images').files;
            const errorMsg = document.getElementById('portfolio-error');
            const imagesInput = document.getElementById('portfolio_images');
            const portfolioCard = document.getElementById('portfolio-card');
            
            if (!instagram && images.length === 0) {
                errorMsg.classList.remove('hidden');
                imagesInput.required = true;
                portfolioCard.classList.add('ring-4', 'ring-red-100');
                return false;
            } else {
                errorMsg.classList.add('hidden');
                imagesInput.required = false;
                portfolioCard.classList.remove('ring-4', 'ring-red-100');
                return true;
            }
        }

        document.getElementById('tailorAppForm').addEventListener('submit', function(e) {
            if (!validatePortfolioRequirements()) {
                e.preventDefault();
                document.getElementById('portfolio-error').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            const form = this;
            const btn = document.getElementById('joinSubmitBtn');
            const profileInput = document.getElementById('profile_image');
            const portfolioInput = document.getElementById('portfolio_images');

            const setDisabled = (text) => {
                if (!btn) return;
                btn.disabled = true;
                btn.classList.add('opacity-80');
                btn.textContent = text;
            };

            const compressImageFile = (file, maxDim, quality, maxBytes) => {
                return new Promise((resolve) => {
                    try {
                        if (!file || !file.type || !file.type.startsWith('image/')) return resolve(file);
                        if (file.size > 0 && file.size <= maxBytes) return resolve(file);
                        const img = new Image();
                        img.onload = () => {
                            try {
                                const w = img.naturalWidth || img.width || 0;
                                const h = img.naturalHeight || img.height || 0;
                                const scale = w > 0 && h > 0 ? Math.min(1, maxDim / Math.max(w, h)) : 1;
                                const nw = Math.max(1, Math.floor(w * scale));
                                const nh = Math.max(1, Math.floor(h * scale));
                                const canvas = document.createElement('canvas');
                                canvas.width = nw;
                                canvas.height = nh;
                                const ctx = canvas.getContext('2d');
                                if (!ctx) return resolve(file);
                                ctx.drawImage(img, 0, 0, nw, nh);
                                const tryQualities = [quality, 0.78, 0.7, 0.62];
                                const tryNext = (i) => {
                                    const q = tryQualities[Math.min(i, tryQualities.length - 1)];
                                    canvas.toBlob((blob) => {
                                        if (!blob) return resolve(file);
                                        if (blob.size <= maxBytes || i >= tryQualities.length - 1) {
                                            const newName = (file.name || 'image').replace(/\.[^/.]+$/, '') + '.jpg';
                                            return resolve(new File([blob], newName, { type: 'image/jpeg' }));
                                        }
                                        return tryNext(i + 1);
                                    }, 'image/jpeg', q);
                                };
                                tryNext(0);
                            } catch (err) {
                                resolve(file);
                            }
                        };
                        img.onerror = () => resolve(file);
                        img.src = URL.createObjectURL(file);
                    } catch (err) {
                        resolve(file);
                    }
                });
            };

            const replaceInputFiles = (input, files) => {
                if (!input) return;
                const dt = new DataTransfer();
                files.forEach(f => dt.items.add(f));
                input.files = dt.files;
            };

            const doSubmit = async () => {
                setDisabled('Compressing images...');
                const tasks = [];
                if (profileInput && profileInput.files && profileInput.files.length === 1) {
                    tasks.push(compressImageFile(profileInput.files[0], 720, 0.82, 800000).then(f => replaceInputFiles(profileInput, [f])));
                }
                if (portfolioInput && portfolioInput.files && portfolioInput.files.length > 0) {
                    const files = Array.from(portfolioInput.files).slice(0, 10);
                    tasks.push(Promise.all(files.map(f => compressImageFile(f, 900, 0.82, 1200000))).then(out => replaceInputFiles(portfolioInput, out)));
                }
                try {
                    await Promise.all(tasks);
                } catch (err) {
                }
                setDisabled('Submitting...');
                form.submit();
            };

            e.preventDefault();
            doSubmit();
        });

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
    
    <!-- Floating WhatsApp Button -->
    <a href="https://wa.me/923367326095" target="_blank" class="fixed bottom-6 right-6 bg-[#25D366] text-white w-14 h-14 rounded-full shadow-2xl flex items-center justify-center hover:scale-110 transition-transform z-50 group">
        <i class="fab fa-whatsapp text-3xl"></i>
        <span class="absolute right-16 bg-white text-gray-800 px-3 py-1 rounded shadow-md text-sm whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none font-medium">Chat with us</span>
    </a>

</body>
</html>
