<?php
require_once __DIR__ . '/../includes/session_init.php';
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Silah</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/tailwind.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --brand-primary: #865294;
            --brand-bg: #FFF3E1;
        }
        body {
            background-color: #865294;
            font-family: 'Plus Jakarta Sans', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 32px;
            padding: 2.5rem 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .login-card h1 {
            color: var(--brand-primary) !important;
        }
        .login-card p {
            color: #64748b !important;
        }

        .role-selector {
            background: #f1f5f9;
            padding: 4px;
            border-radius: 16px;
            display: flex;
            position: relative;
            margin-bottom: 1.5rem;
        }
        .role-btn {
            flex: 1;
            padding: 8px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            z-index: 2;
            transition: all 0.3s ease;
            color: #94a3b8;
        }
        .role-slider {
            position: absolute;
            height: calc(100% - 8px);
            width: calc(50% - 4px);
            background: white;
            border-radius: 12px;
            top: 4px;
            left: 4px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            z-index: 1;
        }
        input[value="admin"]:checked ~ .role-slider { left: 4px; }
        input[value="tailor"]:checked ~ .role-slider { left: calc(50%); }
        input[value="admin"]:checked ~ .role-btn[for="admin"],
        input[value="tailor"]:checked ~ .role-btn[for="tailor"] {
            color: var(--brand-primary);
        }

        .input-group {
            position: relative;
            text-align: left;
            margin-bottom: 1rem;
        }
        .input-group label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-left: 12px;
            margin-bottom: 6px;
            display: block;
        }
        .input-field {
            width: 100%;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 12px 16px 12px 44px;
            font-size: 13px;
            font-weight: 500;
            color: #1e293b;
            transition: all 0.2s ease;
            outline: none;
        }
        .input-field:focus {
            background: white;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(134, 82, 148, 0.05);
        }
        .input-icon {
            position: absolute;
            left: 16px;
            top: 36px;
            color: #cbd5e1;
            transition: all 0.2s ease;
            font-size: 14px;
        }
        .input-field:focus + .input-icon {
            color: var(--brand-primary);
        }

        .submit-btn {
            width: 100%;
            background: var(--brand-primary);
            color: white;
            padding: 14px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 1rem;
        }
        .submit-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 1.5rem;
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .back-link:hover {
            color: var(--brand-primary);
        }
        
        /* Compact spacing */
        .login-card div {
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card" data-aos="fade-up">
            <div class="mb-6">
                <div class="w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <img src="../images/logo1.png" alt="Silah Logo" class="w-full h-full object-contain mix-blend-multiply">
                </div>
                <h1 class="text-xl font-bold uppercase tracking-tight mb-1 text-primary">Silah Portal</h1>
                <p class="text-[11px] font-medium tracking-wide text-gray-500">Sign in to continue</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="p-2 bg-red-50 border border-red-100 rounded-xl mb-4">
                    <p class="text-[9px] font-bold text-red-500 uppercase tracking-widest m-0">
                        <?php 
                            $errorMessages = [
                                'invalid_credentials' => 'Invalid email or password',
                                'db_error' => 'Database connection failed',
                                'no_connection' => 'Connection error',
                                'deactivated' => 'Your account has been deactivated by admin',
                            ];
                            $key = (string)$_GET['error'];
                            echo isset($errorMessages[$key]) ? $errorMessages[$key] : 'An error occurred';
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <form action="process_login.php" method="POST">
                <div class="role-selector">
                    <input type="radio" name="role" value="admin" id="admin" class="hidden" checked>
                    <label for="admin" class="role-btn">Admin</label>
                    
                    <input type="radio" name="role" value="tailor" id="tailor" class="hidden">
                    <label for="tailor" class="role-btn">Tailor</label>
                    
                    <div class="role-slider"></div>
                </div>

                <div class="input-group">
                    <label>Email / Username</label>
                    <input type="text" name="email" class="input-field" placeholder="email or username" required>
                    <i class="fas fa-envelope input-icon"></i>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" class="input-field" placeholder="••••••••" required>
                    <i class="fas fa-lock input-icon"></i>
                </div>

                <button type="submit" class="submit-btn">
                    Sign In
                </button>
            </form>

            <a href="../index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Home
            </a>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>AOS.init();</script>
</body>
</html>
