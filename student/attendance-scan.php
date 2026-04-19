<?php
/**
 * Student attendance scan/mark page.
 */
require '../config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require '../includes/csrf.php';
require '../includes/app_features.php';

requireRole(ROLE_STUDENT);

$user = getCurrentUser();
$message = '';
$errors = [];

processExpiredAttendanceSessions();

$code = strtoupper(trim($_GET['code'] ?? $_POST['code'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token.';
    } else {
        if ($code === '') {
            $errors[] = 'Attendance code is required.';
        } else {
            $result = markAttendanceFromScan($code, $user['id']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Attendance - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
    <div class="wrapper">
        <aside class="sidebar">
            <div class="sidebar-header"><h2><?php echo APP_NAME; ?></h2></div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><span class="icon">📚</span> Dashboard</a>
                <a href="forms.php" class="nav-item"><span class="icon">📋</span> Forms</a>
                <a href="attendance-scan.php" class="nav-item active"><span class="icon">🧾</span> Attendance</a>
                <a href="/index.php" class="nav-item"><span class="icon">🏠</span> Main Actions</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-title"><h1>Attendance Scan</h1></div>
                <div class="header-user"><a href="/logout.php" class="btn-logout">Logout</a></div>
            </header>

            <div class="content">
                <?php if (!isAttendanceEnabled()): ?>
                    <div class="alert alert-error">Attendance is currently disabled by admin.</div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>

                <div class="card" style="max-width: 620px;">
                    <h2>Mark Attendance</h2>
                    <p style="color:#666;margin-bottom:1rem;">Scan the teacher QR on your phone. If it opens this page, confirm once to mark attendance.</p>

                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="code">Attendance Code</label>
                            <input
                                type="text"
                                id="code"
                                name="code"
                                maxlength="20"
                                value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="Enter or scan code"
                                required
                            >
                        </div>

                        <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-primary" <?php echo !isAttendanceEnabled() ? 'disabled' : ''; ?>>Confirm Attendance</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
