<?php
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/theme.php';

if (isset($_SESSION['customer_id'])) {
    header("Location: orders.php");
    exit;
}

$return = isset($_GET['return']) ? trim((string)$_GET['return']) : '';
$prefillEmailRaw = isset($_GET['email']) ? trim((string)$_GET['email']) : '';
$prefillEmail = filter_var($prefillEmailRaw, FILTER_VALIDATE_EMAIL) ? $prefillEmailRaw : '';
$prefillPhone = isset($_GET['phone']) ? trim((string)$_GET['phone']) : '';
$googleClientId = $pdo ? silah_get_setting($pdo, 'google_client_id', '') : '';
if (trim((string)$googleClientId) === '') {
    $googleClientId = getenv('GOOGLE_CLIENT_ID') ?: '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Silah</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --brand-primary: #d63384; }
        body { background: linear-gradient(135deg, #d63384 0%, #6f42c1 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; font-family: system-ui, -apple-system, sans-serif; }
        .login-container { width: 100%; max-width: 460px; position: relative; }
        .login-card { background: rgba(255,255,255,0.98); border-radius: 24px; padding: 28px; text-align: center; box-shadow: 0 25px 60px rgba(0,0,0,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); }
        .input-group { position: relative; margin-bottom: 14px; text-align: left; }
        .input-group label { display:block; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 6px; }
        .input-field { width: 100%; padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 16px; font-size: 14px; transition: all 0.2s ease; background: white; }
        .input-field:focus { outline: none; border-color: var(--brand-primary); box-shadow: 0 0 0 4px rgba(214, 51, 132, 0.1); }
        .submit-btn { width: 100%; background: var(--brand-primary); color:white; padding: 14px; border-radius: 16px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; border: none; cursor: pointer; transition: all 0.2s ease; margin-top: 4px; }
        .submit-btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .link-row { margin-top: 14px; display:flex; justify-content: space-between; gap: 10px; }
        .link-row a { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; text-decoration: none; color: #64748b; }
        .link-row a:hover { color: var(--brand-primary); }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card" data-aos="fade-up">
            <div class="mb-6">
                <div class="w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <img src="../images/logo1.png" alt="Silah Logo" class="w-full h-full object-contain mix-blend-multiply">
                </div>
                <h1 class="text-xl font-bold uppercase tracking-tight mb-1 text-primary">Create Account</h1>
                <p class="text-[11px] font-medium tracking-wide text-gray-500">Use the same email you used for your orders</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="p-2 bg-red-50 border border-red-100 rounded-xl mb-4">
                    <p class="text-[9px] font-bold text-red-500 uppercase tracking-widest m-0">
                        <?php
                            $errorMessages = [
                                'email_exists' => 'This email is already registered. Please login.',
                                'phone_exists' => 'This phone number is already registered. Please login.',
                                'invalid_input' => 'Please check your details.',
                                'db_error' => 'Database connection failed: ' . (isset($_GET['msg']) ? htmlspecialchars((string)$_GET['msg']) : ''),
                                'google_not_configured' => 'Google login is not configured yet.',
                                'google_failed' => 'Google login failed. Please try again.',
                            ];
                            $key = (string)$_GET['error'];
                            echo isset($errorMessages[$key]) ? $errorMessages[$key] : 'An error occurred';
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <form id="googleLoginForm" action="process_google_login.php" method="POST">
                <input type="hidden" name="credential" id="googleCredential">
                <input type="hidden" name="return" value="<?= htmlspecialchars((string)$return) ?>">
            </form>
            <div class="mb-4">
                <?php if (trim((string)$googleClientId) !== ''): ?>
                    <div id="g_id_onload"
                        data-client_id="<?= htmlspecialchars((string)$googleClientId) ?>"
                        data-callback="handleCredentialResponse"
                        data-auto_prompt="false">
                    </div>
                    <div class="g_id_signin"
                        data-type="standard"
                        data-shape="pill"
                        data-theme="outline"
                        data-text="continue_with"
                        data-size="large"
                        data-width="360">
                    </div>
                <?php else: ?>
                    <a href="register.php?error=google_not_configured<?= $return !== '' ? ('&return=' . urlencode($return)) : '' ?>" class="block w-full px-5 py-3 rounded-full bg-white border-2 border-gray-200 text-gray-700 text-sm font-bold no-underline hover:border-pink-400 hover:text-pink-600 transition-all">
                        Continue with Google
                    </a>
                <?php endif; ?>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-3 mb-0 text-center">Or create with email</p>
            </div>

            <form action="process_register.php" method="POST">
                <input type="hidden" name="return" value="<?= htmlspecialchars((string)$return) ?>">
                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="input-field" placeholder="Your name" required>
                </div>

                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" class="input-field" placeholder="you@example.com" value="<?= htmlspecialchars((string)$prefillEmail) ?>" required>
                </div>

                <div class="input-group">
                    <label>Mobile Number</label>
                    <input type="text" name="phone" class="input-field" placeholder="+92..." value="<?= htmlspecialchars((string)$prefillPhone) ?>" required>
                </div>

                <div class="input-group">
                    <label>Address</label>
                    <input type="text" name="address" class="input-field" placeholder="Your address" required>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" class="input-field" placeholder="Min 8 characters" minlength="8" required>
                </div>

                <div class="input-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="input-field" placeholder="Repeat password" minlength="8" required>
                </div>

                <button type="submit" class="submit-btn">Create Account</button>
            </form>

            <div class="link-row">
                <a href="login.php<?= $return !== '' ? ('?return=' . urlencode($return)) : '' ?>">Already have account</a>
                <a href="../index.php">Home</a>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>AOS.init();</script>
    <?php if (trim((string)$googleClientId) !== ''): ?>
        <script src="https://accounts.google.com/gsi/client" async defer></script>
        <script>
            function handleCredentialResponse(response) {
                const form = document.getElementById('googleLoginForm');
                const input = document.getElementById('googleCredential');
                if (!form || !input) return;
                input.value = response.credential || '';
                form.submit();
            }
        </script>
    <?php endif; ?>
</body>
</html>
