<?php
/**
 * Admin security and access settings.
 */
require '../config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require '../includes/csrf.php';
require '../includes/app_features.php';

requireRole(ROLE_ADMIN);

$user = getCurrentUser();
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token.';
    } else {
        $security_controls = isset($_POST['security_controls_enabled']) ? '1' : '0';
        $two_factor = isset($_POST['two_factor_enabled']) ? '1' : '0';
        $attendance = isset($_POST['attendance_enabled']) ? '1' : '0';

        if ($security_controls === '0') {
            $two_factor = '0';
        }

        setAppSetting('security_controls_enabled', $security_controls, $user['id']);
        setAppSetting('two_factor_enabled', $two_factor, $user['id']);
        setAppSetting('attendance_enabled', $attendance, $user['id']);

        auditLog('settings_updated', 'app_settings', null, json_encode([
            'security_controls_enabled' => $security_controls,
            'two_factor_enabled' => $two_factor,
            'attendance_enabled' => $attendance,
        ]));

        $message = 'Settings updated successfully.';
    }
}

$security_enabled = isSecurityControlsEnabled();
$two_factor_enabled = isTwoFactorEnabled();
$attendance_enabled = isAttendanceEnabled();
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Settings - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
    <div class="wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><?php echo APP_NAME; ?></h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><span class="icon">📊</span> Dashboard</a>
                <a href="users.php" class="nav-item"><span class="icon">👥</span> Users</a>
                <a href="forms.php" class="nav-item"><span class="icon">📋</span> Forms</a>
                <a href="notices.php" class="nav-item"><span class="icon">📢</span> Posts & Notices</a>
                <a href="settings.php" class="nav-item active"><span class="icon">🔐</span> Security Settings</a>
                <a href="/index.php" class="nav-item"><span class="icon">🏠</span> Main Actions</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-title"><h1>Security and Access Controls</h1></div>
                <div class="header-user">
                    <span class="user-name"><?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="/logout.php" class="btn-logout">Logout</a>
                </div>
            </header>

            <div class="content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>

                <div class="card">
                    <h2>Feature Toggles</h2>
                    <p style="color:#666;margin-bottom:1rem;">System access remains available by default. Enable Security Controls to enforce role-based page protection and optional 2FA.</p>

                    <form method="POST" class="form">
                        <div class="form-group" style="flex-direction: row; align-items: center; gap: 0.75rem;">
                            <input type="checkbox" id="security_controls_enabled" name="security_controls_enabled" <?php echo $security_enabled ? 'checked' : ''; ?>>
                            <label for="security_controls_enabled" style="margin:0;">Enable Role-Based Page Protection & Security Controls</label>
                        </div>

                        <div class="form-group" style="flex-direction: row; align-items: center; gap: 0.75rem;">
                            <input type="checkbox" id="two_factor_enabled" name="two_factor_enabled" <?php echo $two_factor_enabled ? 'checked' : ''; ?> <?php echo !$security_enabled ? 'disabled' : ''; ?>>
                            <label for="two_factor_enabled" style="margin:0;">Enable 2FA (Email OTP on login)</label>
                        </div>

                        <div class="form-group" style="flex-direction: row; align-items: center; gap: 0.75rem;">
                            <input type="checkbox" id="attendance_enabled" name="attendance_enabled" <?php echo $attendance_enabled ? 'checked' : ''; ?>>
                            <label for="attendance_enabled" style="margin:0;">Enable QR Attendance System</label>
                        </div>

                        <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
    const securityCheckbox = document.getElementById('security_controls_enabled');
    const twoFactorCheckbox = document.getElementById('two_factor_enabled');

    securityCheckbox.addEventListener('change', function () {
        twoFactorCheckbox.disabled = !this.checked;
        if (!this.checked) {
            twoFactorCheckbox.checked = false;
        }
    });
    </script>
</body>
</html>
