<?php
/**
 * Main Landing Page with Role Selection
 */
require 'config.php';
require 'includes/auth.php';
require 'includes/functions.php';
require 'includes/encryption.php';

// If already logged in, redirect to home
if (isLoggedIn()) {
    redirect('/index.php');
}

$available_roles = [
    'admin' => [
        'label' => 'Administrator',
        'desc' => 'Manage courses, users, security, and access controls',
        'icon' => '🔑',
        'color' => '#667eea'
    ],
    'teacher' => [
        'label' => 'Teacher',
        'desc' => 'Create forms, manage attendance, and track responses',
        'icon' => '👨‍🏫',
        'color' => '#764ba2'
    ],
    'student' => [
        'label' => 'Student',
        'desc' => 'Submit forms, scan attendance, and view courses',
        'icon' => '👨‍🎓',
        'color' => '#51cf66'
    ]
];

$selected_role = $_GET['role'] ?? '';
if (!array_key_exists($selected_role, $available_roles)) {
    $selected_role = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .landing-container {
            width: 100%;
            max-width: 900px;
        }

        .login-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
            padding: 2rem;
            text-align: center;
        }

        .login-header {
            margin-bottom: 2rem;
        }

        .login-header h1 {
            font-size: 2.2rem;
            color: var(--primary-color);
            margin: 0;
        }

        .login-header p {
            color: #666;
            margin: 0.5rem 0 0 0;
            font-size: 1.1rem;
        }

        .role-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .role-card {
            padding: 1.5rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
        }

        .role-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        .role-card.active {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }

        .role-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .role-card h3 {
            font-size: 1.3rem;
            margin: 0.5rem 0;
            color: var(--text-color);
        }

        .role-card p {
            font-size: 0.9rem;
            color: #666;
            margin: 0.5rem 0;
        }

        .login-form {
            background: var(--bg-light);
            padding: 2rem;
            border-radius: 8px;
            margin-top: 2rem;
            display: none;
        }

        .login-form.active {
            display: block;
        }

        .form-group {
            margin-bottom: 1rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-secondary {
            background: white;
            border: 1px solid var(--border-color);
            color: var(--text-color);
        }

        .btn-secondary:hover {
            background: var(--bg-light);
        }

        .alerts {
            margin-bottom: 1.5rem;
            text-align: left;
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

        .demo-info {
            margin-top: 2rem;
            padding: 1rem;
            background: #f0f7ff;
            border-radius: 8px;
            border-left: 4px solid var(--info-color);
            text-align: left;
            font-size: 0.9rem;
            color: #0c5460;
        }

        .demo-info strong {
            display: block;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .role-selector {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="landing-container">
        <div class="login-box">
            <div class="login-header">
                <h1><?php echo APP_NAME; ?></h1>
                <p>College Management System - BCA Course</p>
            </div>

            <div style="text-align: center; margin-bottom: 1rem;">
                <p style="color: #666; font-weight: 500;">👋 Welcome! Select your role to login</p>
            </div>

            <div class="role-selector">
                <?php foreach ($available_roles as $role => $info): ?>
                    <a href="?role=<?php echo urlencode($role); ?>#login" class="role-card <?php echo $selected_role === $role ? 'active' : ''; ?>">
                        <div class="role-icon"><?php echo $info['icon']; ?></div>
                        <h3><?php echo $info['label']; ?></h3>
                        <p><?php echo $info['desc']; ?></p>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($selected_role): ?>
                <div id="login" class="login-form active">
                    <h3 style="text-align:center;margin-bottom:1.5rem;">Login as <?php echo htmlspecialchars($available_roles[$selected_role]['label'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    
                    <form method="POST" action="/login.php">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="remember"> Remember me
                            </label>
                        </div>

                        <input type="hidden" name="role" value="<?php echo htmlspecialchars($selected_role, ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="btn-group">
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='/'">Back</button>
                            <button type="submit" class="btn btn-primary">Sign In</button>
                        </div>
                    </form>

                    <div class="demo-info">
                        <strong>Demo Credentials:</strong>
                        Email: admin@college.local<br>
                        Password: Admin@123
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
