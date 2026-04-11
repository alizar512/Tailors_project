<?php
require_once __DIR__ . '/../includes/session_init.php';

if (isset($_SESSION['customer_id'])) {
    header("Location: dashboard.php");
    exit;
}

$return = isset($_GET['return']) ? trim((string)$_GET['return']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - Silah</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --brand-primary: #d63384; }
        body { background: linear-gradient(135deg, #d63384 0%, #6f42c1 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; font-family: system-ui, -apple-system, sans-serif; }
        .login-container { width: 100%; max-width: 420px; position: relative; }
        .login-card { background: rgba(255,255,255,0.98); border-radius: 24px; padding: 28px; text-align: center; box-shadow: 0 25px 60px rgba(0,0,0,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); }
        .input-group { position: relative; margin-bottom: 14px; text-align: left; }
        .input-group label { display:block; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 6px; }
        .input-field { width: 100%; padding: 14px 48px 14px 44px; border: 2px solid #e2e8f0; border-radius: 16px; font-size: 14px; transition: all 0.2s ease; background: white; }
        .input-field:focus { outline: none; border-color: var(--brand-primary); box-shadow: 0 0 0 4px rgba(214, 51, 132, 0.1); }
        .input-icon { position:absolute; left: 16px; top: 36px; color:#cbd5e1; transition: all 0.2s ease; font-size: 14px; }
        .password-toggle { position:absolute; right: 16px; top: 36px; color:#cbd5e1; cursor:pointer; transition: all 0.2s ease; font-size: 14px; background: none; border: none; padding: 0; outline: none; }
        .password-toggle:hover { color: var(--brand-primary); }
        .input-field:focus + .input-icon { color: var(--brand-primary); }
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
                <h1 class="text-xl font-bold uppercase tracking-tight mb-1 text-primary">Customer Portal</h1>
                <p class="text-[11px] font-medium tracking-wide text-gray-500">Sign in to see your orders and chats</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="p-2 bg-red-50 border border-red-100 rounded-xl mb-4">
                    <p class="text-[9px] font-bold text-red-500 uppercase tracking-widest m-0">
                        <?php
                            $errorMessages = [
                                'invalid_credentials' => 'Invalid email or password',
                                'db_error' => 'Database connection failed: ' . (isset($_GET['msg']) ? htmlspecialchars((string)$_GET['msg']) : ''),
                                'session_expired' => 'Please login again.',
                                'register_failed' => 'Could not create account.',
                            ];
                            $key = (string)$_GET['error'];
                            echo isset($errorMessages[$key]) ? $errorMessages[$key] : 'An error occurred';
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <form action="process_login.php" method="POST">
                <input type="hidden" name="return" value="<?= htmlspecialchars((string)$return) ?>">
                <div class="input-group">
                    <label>Email or Mobile</label>
                    <input type="text" name="login" class="input-field" placeholder="you@example.com or +92..." required>
                    <i class="fas fa-envelope input-icon"></i>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password" class="input-field" placeholder="••••••••" required>
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" id="togglePassword" class="password-toggle">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>

                <button type="submit" class="submit-btn">Sign In</button>
            </form>

            <div class="link-row">
                <a href="register.php<?= $return !== '' ? ('?return=' . urlencode($return)) : '' ?>">Create account</a>
                <a href="../index.php">Home</a>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            eyeIcon.classList.toggle('fa-eye');
            eyeIcon.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
