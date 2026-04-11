<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

if (!$pdo) {
    header("Location: login.php?error=db_error");
    exit;
}

$return = isset($_GET['return']) ? trim((string)$_GET['return']) : '';
$customerId = (int)($_SESSION['customer_id'] ?? 0);
$customerEmail = isset($_SESSION['customer_email']) ? (string)$_SESSION['customer_email'] : '';

$phone = '';
$address = '';
try {
    $stmt = $pdo->prepare("SELECT phone, address FROM customers WHERE id = ? LIMIT 1");
    $stmt->execute([$customerId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $phone = $row && isset($row['phone']) ? trim((string)$row['phone']) : '';
    $address = $row && isset($row['address']) ? trim((string)$row['address']) : '';
} catch (Exception $e) {
}
?>
<?php
$cp_title = 'Complete Profile';
$cp_active = 'profile';
require_once __DIR__ . '/portal_header.php';
?>
    <div class="max-w-xl mx-auto">
        <div class="bg-white rounded-3xl border border-gray-100 p-6 sm:p-8">
            <div class="flex items-center gap-3 mb-6">
                <img src="../images/logo1.png" alt="Silah Logo" class="w-10 h-10 object-contain mix-blend-multiply">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Client Portal</p>
                    <h1 class="text-xl font-black text-gray-900 mb-0">Complete Profile</h1>
                    <p class="text-xs font-bold text-gray-500 mb-0"><?= htmlspecialchars((string)$customerEmail) ?></p>
                </div>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="p-3 rounded-2xl border border-red-100 bg-red-50 mb-5">
                    <p class="text-[11px] font-black text-red-600 mb-0">
                        <?php
                            $m = [
                                'invalid_input' => 'Please fill all fields correctly.',
                                'phone_exists' => 'This phone number is already registered.',
                                'db_error' => 'Database error. Please try again.',
                            ];
                            $k = (string)$_GET['error'];
                            echo isset($m[$k]) ? $m[$k] : 'Error';
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <form action="process_complete_profile.php" method="POST" class="space-y-4">
                <input type="hidden" name="return" value="<?= htmlspecialchars((string)$return) ?>">
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 block">Mobile Number</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars((string)$phone) ?>" class="w-full px-4 py-3 rounded-2xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 focus:outline-none focus:ring-4 focus:ring-pink-100 focus:border-pink-400" placeholder="+92..." required>
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 block">Address</label>
                    <input type="text" name="address" value="<?= htmlspecialchars((string)$address) ?>" class="w-full px-4 py-3 rounded-2xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 focus:outline-none focus:ring-4 focus:ring-pink-100 focus:border-pink-400" placeholder="Your address" required>
                </div>
                <button type="submit" class="w-full px-5 py-3 rounded-2xl bg-pink-600 text-white text-xs font-black uppercase tracking-widest hover:bg-pink-700 transition-all">
                    Save & Continue
                </button>
                <a href="profile.php" class="block text-center text-[11px] font-black uppercase tracking-widest text-gray-500 hover:text-pink-600 no-underline">Skip for now</a>
            </form>
        </div>
    </div>

<?php require_once __DIR__ . '/portal_footer.php'; ?>
