<?php
/**
 * Login Page
 */
require 'config.php';
require 'includes/auth.php';
require 'includes/csrf.php';
require 'includes/functions.php';
require 'includes/app_features.php';
require 'includes/encryption.php';

// Start session
startSession();

// If already logged in, redirect to home
if (isLoggedIn()) {
    $redirect_target = getSafeRedirectPath($_GET['redirect'] ?? '/index.php', '/index.php');
    redirect($redirect_target);
}

// Check if remember token is valid
if (!isLoggedIn() && !empty($_COOKIE['remember_token'])) {
    if (checkRememberToken()) {
        $redirect_target = getSafeRedirectPath($_GET['redirect'] ?? '/index.php', '/index.php');
        redirect($redirect_target);
    }
}

// Handle POST request (login form submission)
$errors = [];
$email = '';
$step = $_GET['step'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken()) {
        $errors[] = 'Security token invalid. Please try again.';
    } else {
        $action = $_POST['_action'] ?? 'password_login';

        if ($action === 'verify_2fa') {
            $code = trim($_POST['otp_code'] ?? '');
            $pending_user_id = $_SESSION['pending_2fa_user_id'] ?? null;
            $pending_email = $_SESSION['pending_2fa_user_email'] ?? '';
            $pending_remember = !empty($_SESSION['pending_2fa_remember']);
            $step = 'verify2fa';

            if (!$pending_user_id) {
                $errors[] = 'No pending verification found. Please login again.';
            } elseif (!preg_match('/^[0-9]{6}$/', $code)) {
                $errors[] = 'Please enter a valid 6-digit code.';
            } elseif (!verifyTwoFactorChallenge((int)$pending_user_id, $code)) {
                $errors[] = 'Invalid or expired verification code.';
            } else {
                unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_user_email'], $_SESSION['pending_2fa_remember']);

                if (loginUser((int)$pending_user_id, $pending_remember)) {
                    auditLog('login_2fa_success', 'user', (int)$pending_user_id);
                    redirect('/index.php');
                } else {
                    $errors[] = 'Login failed after verification. Please try again.';
                }
            }

            $email = $pending_email;
        } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';

        // Validate input
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (empty($password)) {
            $errors[] = 'Password is required';
        }

        // Check login attempts
        if (empty($errors) && !checkLoginAttempts($email)) {
            $errors[] = 'Too many login attempts. Please try again later.';
        }

        // Attempt login
        if (empty($errors)) {
            $user = getUserByEmail($email);

            if (!$user || !verifyPassword($password, $user['password'])) {
                recordFailedLoginAttempt($email);
                $errors[] = 'Invalid email or password';
            } else {
                // Clear login attempts on successful login
                clearLoginAttempts($email);

                if (isTwoFactorEnabled()) {
                    createTwoFactorChallenge($user);
                    $_SESSION['pending_2fa_user_id'] = $user['id'];
                    $_SESSION['pending_2fa_user_email'] = $user['email'];
                    $_SESSION['pending_2fa_remember'] = $remember ? 1 : 0;
                    $step = 'verify2fa';
                } else {

                // Log user in
                if (loginUser($user['id'], $remember)) {
                    auditLog('login', 'user', $user['id']);

                    // Redirect to intended page or home
                    $redirect = getSafeRedirectPath($_GET['redirect'] ?? '/index.php', '/index.php');
                    redirect($redirect);
                } else {
                    $errors[] = 'Login failed. Please try again.';
                }
                }
            }
        }
        }
    }
}

// Generate CSRF token
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-box {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-box h1 {
            text-align: center;
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        .login-box p {
            text-align: center;
            color: #666;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        input[type="checkbox"] {
            margin-right: 0.5rem;
            cursor: pointer;
        }
        .checkbox-group label {
            margin: 0;
            font-weight: 400;
            cursor: pointer;
        }
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        .btn-login:hover {
            opacity: 0.9;
        }
        .btn-login:active {
            opacity: 0.8;
        }
        .alerts {
            margin-bottom: 1.5rem;
        }
        .alert {
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1><?php echo APP_NAME; ?></h1>
            <p><?php echo $step === 'verify2fa' ? 'Enter your verification code' : 'Sign in to your account'; ?></p>

            <?php if (!empty($errors)): ?>
                <div class="alerts">
                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['logged_out'])): ?>
                <div class="alerts">
                    <div class="alert alert-info">You have been logged out successfully.</div>
                </div>
            <?php endif; ?>

            <?php if ($step === 'verify2fa'): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="otp_code">6-digit verification code</label>
                    <input
                        type="text"
                        id="otp_code"
                        name="otp_code"
                        placeholder="Enter code sent to your email"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        required
                        autocomplete="one-time-code"
                    >
                    <small style="display:block;color:#666;margin-top:0.4rem;">Code sent to: <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></small>
                </div>

                <input type="hidden" name="_action" value="verify_2fa">
                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                <button type="submit" class="btn-login">Verify & Sign In</button>
                <a href="/login.php" style="display:block;text-align:center;margin-top:1rem;">Back to login</a>
            </form>
            <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>

                <input type="hidden" name="_action" value="password_login">
                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                <button type="submit" class="btn-login">Sign In</button>
            </form>
            <?php endif; ?>

            <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #ddd;">
            <p style="text-align: center; color: #666; font-size: 0.9rem;">
                Demo Credentials: <br>
                Email: admin@college.local <br>
                Password: Admin@123
            </p>
        </div>
    </div>
</body>
</html>
