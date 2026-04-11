<?php
$cp_title = 'Profile';
$cp_active = 'profile';
require_once __DIR__ . '/portal_header.php';

if (!$pdo) {
    echo '<div class="bg-white rounded-3xl border border-gray-100 p-6"><p class="text-sm font-black text-red-600 mb-0">Database connection failed.</p></div>';
    require_once __DIR__ . '/portal_footer.php';
    exit;
}

$customerId = (int)($_SESSION['customer_id'] ?? 0);
$customerEmail = isset($_SESSION['customer_email']) ? (string)$_SESSION['customer_email'] : '';
$msg = '';
$err = '';

try { $pdo->exec("ALTER TABLE customers ADD COLUMN address VARCHAR(255)"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE customers ADD UNIQUE KEY uq_customers_phone (phone)"); } catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim((string)$_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim((string)$_POST['address']) : '';

    if ($name === '' || $phone === '' || $address === '') {
        $err = 'Please fill all fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ? AND id <> ? LIMIT 1");
            $stmt->execute([$phone, $customerId]);
            if ($stmt->fetch()) {
                $err = 'This mobile number is already registered.';
            } else {
                $stmt = $pdo->prepare("UPDATE customers SET name = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $address, $customerId]);
                $msg = 'Profile updated.';
            }
        } catch (Exception $e) {
            $err = 'Could not update profile.';
        }
    }
}

$name = '';
$phone = '';
$address = '';
try {
    $stmt = $pdo->prepare("SELECT name, phone, address, email FROM customers WHERE id = ? LIMIT 1");
    $stmt->execute([$customerId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $name = $row && isset($row['name']) ? (string)$row['name'] : '';
    $phone = $row && isset($row['phone']) ? (string)$row['phone'] : '';
    $address = $row && isset($row['address']) ? (string)$row['address'] : '';
    $customerEmail = $row && isset($row['email']) ? (string)$row['email'] : $customerEmail;
} catch (Exception $e) {
}
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-3xl border border-gray-100 p-6">
            <div class="flex items-center justify-between gap-3 mb-6">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Account</p>
                    <p class="text-sm font-bold text-gray-600 mb-0">Update your details</p>
                </div>
                <?php if ($msg !== ''): ?>
                    <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-black uppercase tracking-widest"><?= htmlspecialchars((string)$msg) ?></span>
                <?php endif; ?>
            </div>

            <?php if ($err !== ''): ?>
                <div class="p-3 rounded-2xl border border-red-100 bg-red-50 mb-5">
                    <p class="text-[11px] font-black text-red-600 mb-0"><?= htmlspecialchars((string)$err) ?></p>
                </div>
            <?php endif; ?>

            <form action="profile.php" method="POST" class="space-y-4">
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 block">Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars((string)$name) ?>" class="w-full px-4 py-3 rounded-2xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 focus:outline-none focus:ring-4 focus:ring-pink-100 focus:border-pink-400" required>
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 block">Email</label>
                    <input type="email" value="<?= htmlspecialchars((string)$customerEmail) ?>" class="w-full px-4 py-3 rounded-2xl border border-gray-100 bg-gray-50 text-sm font-semibold text-gray-600" disabled>
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 block">Mobile Number</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars((string)$phone) ?>" class="w-full px-4 py-3 rounded-2xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 focus:outline-none focus:ring-4 focus:ring-pink-100 focus:border-pink-400" required>
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 block">Address</label>
                    <input type="text" name="address" value="<?= htmlspecialchars((string)$address) ?>" class="w-full px-4 py-3 rounded-2xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 focus:outline-none focus:ring-4 focus:ring-pink-100 focus:border-pink-400" required>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 rounded-2xl bg-pink-600 text-white text-xs font-black uppercase tracking-widest hover:bg-pink-700 transition-all">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-3xl border border-gray-100 p-6">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3">Quick Links</p>
            <div class="space-y-2">
                <a href="orders.php" class="flex items-center justify-between gap-3 px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 hover:border-pink-200 transition-all no-underline">
                    <span class="text-xs font-black text-gray-800">My Orders</span>
                    <i class="fa-solid fa-arrow-right text-gray-400"></i>
                </a>
                <a href="messages.php" class="flex items-center justify-between gap-3 px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 hover:border-pink-200 transition-all no-underline">
                    <span class="text-xs font-black text-gray-800">Messages</span>
                    <i class="fa-solid fa-arrow-right text-gray-400"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/portal_footer.php'; ?>

