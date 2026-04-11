<?php
require_once __DIR__ . '/includes/db_connect.php';

function silah_norm($v) {
    return strtolower(trim((string)$v));
}

$tailors = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM tailors ORDER BY created_at DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $t) {
            $is_active = !isset($t['is_active']) || (int)$t['is_active'] === 1;
            if (!$is_active) {
                continue;
            }
            $tailors[] = $t;
        }
    } catch (Exception $e) {
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order | Silah</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-bg text-text">
    <nav class="navbar fixed-top navbar-expand-lg navbar-light transition-all duration-300 z-50 bg-white shadow-sm" id="mainNav">
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
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="join_tailor.php">Join as Tailor</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="index.php#contact">Contact</a></li>
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
                        <a href="#order" class="btn btn-accent text-white px-6 py-2 rounded-full shadow-lg hover:shadow-xl transition-all d-inline-block">Place Order</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="pt-32 pb-10 bg-white">
        <div class="container">
            <div class="max-w-4xl mx-auto text-center" data-aos="fade-up">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary/10 text-primary text-[11px] font-extrabold uppercase tracking-widest">
                    <i class="fas fa-scissors"></i>
                    Order Request
                </span>
                <h1 class="mt-5 text-4xl md:text-5xl font-extrabold tracking-tight text-primary leading-tight">Place Your Order</h1>
                <p class="mt-4 text-gray-600 text-base md:text-lg">Select a tailor, choose multiple services, and chat to negotiate.</p>
            </div>
        </div>
    </header>

    <section id="order" class="py-16 bg-gray-50">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9 col-xl-8">
                    <div class="bg-white p-8 md:p-12 rounded-[24px] shadow-xl border border-gray-100" data-aos="zoom-in">
                        <form action="process_order.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="return_url" value="place_order.php">
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
                                    <input type="tel" name="phone" class="form-control" placeholder="+92..." required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label font-semibold text-sm text-gray-600 mb-2">Select Tailor (Required for pricing)</label>
                                    <select name="selected_tailor_id" class="form-select" data-selected-tailor required>
                                        <option value="" selected disabled>Select a tailor...</option>
                                        <?php foreach ($tailors as $t): ?>
                                            <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars((string)$t['name']) ?> (<?= htmlspecialchars((string)($t['location'] ?? '')) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text text-xs">Prices are based on the selected tailor.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label font-semibold text-sm text-gray-600 mb-2">Select Services</label>
                                    <div class="p-4 rounded-2xl border border-gray-100 bg-gray-50" data-services-wrap>
                                        <p class="text-xs text-gray-500 mb-0"><?= empty($tailors) ? 'No tailors available right now.' : 'Select a tailor to load services and prices.' ?></p>
                                    </div>
                                    <div class="form-text text-xs">You can select multiple services.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label font-semibold text-sm text-gray-600">Total Price (PKR)</label>
                                    <input type="number" name="budget" class="form-control" data-total-price readonly required>
                                    <div class="form-text text-xs">Auto-calculated from selected services.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label font-semibold text-sm text-gray-600 mb-2">Clothing Option</label>
                                    <div class="flex gap-4">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="is_own_clothing" value="0" checked>
                                            <span>Buy Fabric from Us</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="is_own_clothing" value="1">
                                            <span>Send your own/familiar clothing</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-semibold text-sm text-gray-600">Expected Timeline for Delivery</label>
                                    <input type="date" name="expected_delivery" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label font-semibold text-sm text-gray-600">Upload Image of Design/Dress</label>
                                    <input type="file" name="reference_image" id="order_image_input" class="form-control" accept="image/*">
                                    <div class="form-text text-xs">Upload a photo of the design you want.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label font-semibold text-sm text-gray-600" id="notes-label">Style Preference / Further Description</label>
                                    <textarea name="notes" id="order_notes" class="form-control" rows="3" placeholder="Any specific details or deadlines?" required></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label font-semibold text-sm text-gray-600">Measurements (Optional)</label>
                                    <input type="hidden" name="measurements" data-measure-output>
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label font-semibold text-sm text-gray-600 mb-1">Template</label>
                                            <select class="form-select" data-measure-type>
                                                <option value="none">I don't know</option>
                                                <option value="men">Men</option>
                                                <option value="women">Women</option>
                                                <option value="kids">Kids</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label font-semibold text-sm text-gray-600 mb-1">Unit</label>
                                            <select class="form-select" data-measure-unit>
                                                <option value="in">Inches</option>
                                                <option value="cm">Centimeters</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600">
                                                <input type="checkbox" data-measure-auto>
                                                Tailor will take measurements
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mt-3" data-measure-fields>
                                        <div class="row g-3 d-none" data-measure-template="men">
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Chest</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Chest"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Waist</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Waist"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Hip</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Hip"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Shoulder</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Shoulder"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Sleeve</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Sleeve"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Length</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Length"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Neck</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Neck"></div>
                                        </div>
                                        <div class="row g-3 d-none" data-measure-template="women">
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Bust</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Bust"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Waist</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Waist"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Hip</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Hip"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Shoulder</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Shoulder"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Sleeve</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Sleeve"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Dress Length</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Dress Length"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Armhole</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Armhole"></div>
                                        </div>
                                        <div class="row g-3 d-none" data-measure-template="kids">
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Age</label><input type="number" step="1" min="0" class="form-control" data-measure-field="Age"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Height</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Height"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Chest</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Chest"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Waist</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Waist"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Shoulder</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Shoulder"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Sleeve</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Sleeve"></div>
                                            <div class="col-md-4"><label class="form-label text-sm text-gray-600">Length</label><input type="number" step="0.01" min="0" class="form-control" data-measure-field="Length"></div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="form-label text-sm text-gray-600">Additional Notes (optional)</label>
                                        <input type="text" class="form-control" data-measure-notes placeholder="e.g. Slim fit, loose fitting, any special request">
                                    </div>
                                    <div class="mt-3">
                                        <label class="form-label text-sm text-gray-600">Preview</label>
                                        <textarea class="form-control bg-gray-50" rows="3" readonly data-measure-preview></textarea>
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
                                    <label class="form-label font-semibold text-sm text-gray-600">Address (Mandatory)</label>
                                    <textarea name="location_details" class="form-control" rows="2" placeholder="Area, Street, House No." required></textarea>
                                </div>

                                <div class="col-12 text-center mt-6">
                                    <div class="mb-4 p-3 bg-primary-soft rounded-lg border border-primary text-primary font-bold">👉 30% advance payment is required after tailor selection.</div>
                                    <button type="submit" class="btn btn-primary btn-lg w-full md:w-auto px-12 rounded-full shadow-lg hover:shadow-xl transition-all">Submit Order Request</button>
                                </div>
                            </div>
                        </form>

                        <div class="mt-6 p-4 rounded-2xl border border-gray-100 bg-gray-50">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Reopen Bargaining Chat</p>
                            <p class="text-xs text-gray-600 mb-3">If you already submitted an order and went back, open your chat again using Order # and Email. If you forgot the order number, use Email + Phone on the chat page.</p>
                            <form action="order_chat.php" method="GET" class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-sm font-semibold text-gray-600">Order Number</label>
                                    <input type="number" name="order_id" class="form-control" min="1" placeholder="e.g. 12" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-sm font-semibold text-gray-600">Email</label>
                                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline rounded-full px-5 py-2.5 font-bold">Open Chat</button>
                                </div>
                            </form>
                            <div class="mt-3">
                                <a href="order_chat.php" class="text-xs font-bold text-primary no-underline">Forgot order number? Find chat by Email + Phone</a>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-6">
                        <a href="index.php" class="text-xs font-bold text-gray-500 no-underline">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 900, once: true });

        async function loadTailorServicesForForm(form) {
            if (!form) return;
            const tailorSelect = form.querySelector('[data-selected-tailor]');
            const wrap = form.querySelector('[data-services-wrap]');
            const totalInput = form.querySelector('[data-total-price]');
            const notesField = form.querySelector('#order_notes');
            const notesLabel = form.querySelector('#notes-label');

            if (!tailorSelect || !wrap || !totalInput) return;

            const tailorId = parseInt(tailorSelect.value || '0', 10) || 0;
            wrap.innerHTML = '';
            totalInput.value = '';
            if (!tailorId) {
                wrap.innerHTML = '<p class="text-xs text-gray-500 mb-0">Select a tailor to load services and prices.</p>';
                return;
            }

            wrap.innerHTML = '<p class="text-xs text-gray-500 mb-0">Loading services...</p>';
            try {
                const res = await fetch('api/tailor_services.php?tailor_id=' + encodeURIComponent(String(tailorId)), { cache: 'no-store' });
                const data = await res.json();
                const services = data && data.success && Array.isArray(data.services) ? data.services : [];

                if (services.length === 0) {
                    wrap.innerHTML = '<p class="text-xs text-gray-500 mb-0">This tailor has no services/prices yet.</p>';
                    return;
                }

                const list = document.createElement('div');
                list.className = 'grid grid-cols-1 md:grid-cols-2 gap-3';
                services.forEach(s => {
                    const row = document.createElement('label');
                    row.className = 'flex items-center justify-between gap-3 p-3 rounded-xl bg-white border border-gray-100 cursor-pointer';
                    row.innerHTML =
                        '<div class="flex items-center gap-3">' +
                        '<input type="checkbox" name="service_ids[]" value="' + String(s.id) + '" class="form-check-input mt-0" data-service-price="' + String(s.price) + '" data-service-name="' + String(s.name).replace(/"/g, '&quot;') + '">' +
                        '<div>' +
                        '<div class="text-sm font-bold text-gray-800">' + String(s.name) + '</div>' +
                        '<div class="text-[11px] text-gray-500">PKR ' + Number(s.price).toLocaleString() + '</div>' +
                        '</div>' +
                        '</div>' +
                        '<div class="text-sm font-black text-primary">PKR ' + Number(s.price).toLocaleString() + '</div>';
                    list.appendChild(row);
                });

                const summary = document.createElement('div');
                summary.className = 'mt-4 p-3 rounded-2xl bg-white border border-gray-100';
                summary.innerHTML =
                    '<p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Selected Services</p>' +
                    '<div class="text-sm text-gray-700" data-selected-services>None</div>' +
                    '<div class="mt-2 flex items-center justify-between">' +
                    '<span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total</span>' +
                    '<span class="text-sm font-black text-primary" data-total-label>PKR 0</span>' +
                    '</div>';

                wrap.innerHTML = '';
                wrap.appendChild(list);
                wrap.appendChild(summary);

                const recalc = () => {
                    const checks = wrap.querySelectorAll('input[name="service_ids[]"]:checked');
                    let total = 0;
                    const lines = [];
                    checks.forEach(ch => {
                        const p = parseFloat(ch.getAttribute('data-service-price') || '0') || 0;
                        total += p;
                        const n = ch.getAttribute('data-service-name') || '';
                        lines.push(n + ' (PKR ' + p.toLocaleString() + ')');
                    });
                    const selectedEl = wrap.querySelector('[data-selected-services]');
                    const totalLabel = wrap.querySelector('[data-total-label]');
                    if (selectedEl) selectedEl.textContent = lines.length ? lines.join(', ') : 'None';
                    if (totalLabel) totalLabel.textContent = 'PKR ' + total.toLocaleString();
                    totalInput.value = String(total);

                    const hasOther = Array.from(checks).some(ch => (ch.getAttribute('data-service-name') || '').toLowerCase() === 'other');
                    if (notesField && notesLabel) {
                        if (hasOther) {
                            notesField.required = true;
                            notesLabel.innerHTML = 'Style Preference / Further Description <span class="text-danger">*Mandatory for \"Other\" service</span>';
                        } else {
                            notesLabel.textContent = 'Style Preference / Further Description';
                        }
                    }
                };

                wrap.addEventListener('change', function(e) {
                    if (e.target && e.target.name === 'service_ids[]') {
                        recalc();
                    }
                });

                recalc();
            } catch (e) {
                wrap.innerHTML = '<p class="text-xs text-gray-500 mb-0">Could not load services.</p>';
            }
        }

        function initMeasurements(form) {
            if (!form) return;
            const typeSelect = form.querySelector('[data-measure-type]');
            const unitSelect = form.querySelector('[data-measure-unit]');
            const autoCheck = form.querySelector('[data-measure-auto]');
            const fieldsWrap = form.querySelector('[data-measure-fields]');
            const output = form.querySelector('[data-measure-output]');
            const notes = form.querySelector('[data-measure-notes]');
            const preview = form.querySelector('[data-measure-preview]');
            const guidesWrap = form.querySelector('[data-measure-guides]');
            const guideMen = form.querySelector('[data-measure-guide=\"men\"]');
            const guideWomen = form.querySelector('[data-measure-guide=\"women\"]');
            if (!typeSelect || !unitSelect || !autoCheck || !fieldsWrap || !output || !preview) return;

            const templates = Array.from(fieldsWrap.querySelectorAll('[data-measure-template]'));

            const getFields = () => {
                const type = typeSelect.value;
                const tpl = templates.find(t => t.getAttribute('data-measure-template') === type);
                if (!tpl) return [];
                return Array.from(tpl.querySelectorAll('[data-measure-field]'));
            };

            const setTemplateVisibility = () => {
                const type = typeSelect.value;
                templates.forEach(tpl => {
                    if (tpl.getAttribute('data-measure-template') === type) {
                        tpl.classList.remove('d-none');
                    } else {
                        tpl.classList.add('d-none');
                    }
                });
            };

            const setGuideVisibility = () => {
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
            };

            const buildText = () => {
                const unit = unitSelect.value;
                const auto = autoCheck.checked;
                const type = typeSelect.value;
                const map = {};

                if (auto) {
                    output.value = 'Tailor will take measurements.';
                    preview.value = output.value;
                    return;
                }

                const fields = getFields();
                fields.forEach(el => {
                    const k = el.getAttribute('data-measure-field');
                    const v = el.value;
                    if (k && v !== '') {
                        map[k] = v;
                    }
                });

                const extra = notes ? notes.value : '';
                const keys = Object.keys(map);
                if (type === 'none' || keys.length === 0) {
                    output.value = '';
                    preview.value = '';
                    return;
                }

                let txt = 'Template: ' + type + '\n';
                txt += 'Unit: ' + unit + '\n';
                keys.forEach(k => {
                    txt += k + ': ' + map[k] + '\n';
                });
                if (extra && extra.trim() !== '') {
                    txt += 'Notes: ' + extra.trim() + '\n';
                }

                output.value = txt.trim();
                preview.value = output.value;
            };

            typeSelect.addEventListener('change', function() {
                setTemplateVisibility();
                setGuideVisibility();
                buildText();
            });
            unitSelect.addEventListener('change', buildText);
            autoCheck.addEventListener('change', function() {
                setGuideVisibility();
                buildText();
            });
            fieldsWrap.addEventListener('input', buildText);
            if (notes) notes.addEventListener('input', buildText);

            setTemplateVisibility();
            setGuideVisibility();
            buildText();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('section#order form[action=\"process_order.php\"]');
            if (!form) return;
            initMeasurements(form);
            const tailorSelect = form.querySelector('[data-selected-tailor]');
            if (tailorSelect) {
                tailorSelect.addEventListener('change', function() {
                    loadTailorServicesForForm(form);
                });
            }
        });

        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (!form || !(form instanceof HTMLFormElement)) return;
            if (form.getAttribute('action') !== 'process_order.php') return;

            const tailorSelect = form.querySelector('[data-selected-tailor]');
            const totalInput = form.querySelector('[data-total-price]');
            const selectedTailor = tailorSelect ? (parseInt(tailorSelect.value || '0', 10) || 0) : 0;
            const checks = form.querySelectorAll('input[name=\"service_ids[]\"]:checked');
            const total = totalInput ? (parseFloat(totalInput.value || '0') || 0) : 0;

            if (!selectedTailor) {
                e.preventDefault();
                alert('Please select a tailor first.');
                return;
            }
            if (!checks || checks.length === 0) {
                e.preventDefault();
                alert('Please select at least one service.');
                return;
            }
            if (!(total > 0)) {
                e.preventDefault();
                alert('Total price is 0. Please select services with prices.');
                return;
            }
        }, true);
    </script>
</body>
</html>
