<?php
/**
 * Teacher QR attendance management.
 */
require '../config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require '../includes/csrf.php';
require '../includes/app_features.php';

requireRole(ROLE_TEACHER);

$user = getCurrentUser();
$db = getDB();
$message = '';
$errors = [];

processExpiredAttendanceSessions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token.';
    } else {
        $action = $_POST['_action'] ?? '';

        if ($action === 'start') {
            $title = trim($_POST['title'] ?? '');
            $class_id = (int)($_POST['class_id'] ?? 0);
            $duration = (int)($_POST['duration_minutes'] ?? 15);

            if ($title === '' || $class_id <= 0) {
                $errors[] = 'Title and class are required.';
            } else {
                $result = startAttendanceSession($user['id'], $class_id, $title, $duration);
                if ($result['success']) {
                    $message = 'Attendance started successfully. Share the QR code with students.';
                } else {
                    $errors[] = $result['message'];
                }
            }
        }

        if ($action === 'end') {
            $session_id = (int)($_POST['session_id'] ?? 0);
            if ($session_id > 0 && finalizeAttendanceSession($session_id, $user['id'])) {
                $message = 'Attendance session ended. Missed students were notified by email.';
            } else {
                $errors[] = 'Unable to close this attendance session.';
            }
        }
    }
}

$classes = getAllClasses();
$sessions = getTeacherAttendanceSessions($user['id'], 30);
$csrf_token = generateCsrfToken();

$base_scan_url = rtrim(BASE_URL, '/') . '/student/attendance-scan.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Attendance - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
    <div class="wrapper">
        <aside class="sidebar">
            <div class="sidebar-header"><h2><?php echo APP_NAME; ?></h2></div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><span class="icon">📊</span> Dashboard</a>
                <a href="forms.php" class="nav-item"><span class="icon">📋</span> My Forms</a>
                <a href="responses.php" class="nav-item"><span class="icon">📈</span> Responses</a>
                <a href="attendance.php" class="nav-item active"><span class="icon">🧾</span> QR Attendance</a>
                <a href="/index.php" class="nav-item"><span class="icon">🏠</span> Main Actions</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-title"><h1>QR Attendance</h1></div>
                <div class="header-user"><a href="/logout.php" class="btn-logout">Logout</a></div>
            </header>

            <div class="content">
                <?php if (!isAttendanceEnabled()): ?>
                    <div class="alert alert-error">Attendance is currently disabled by admin. Please contact admin to activate it.</div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>

                <div class="card">
                    <h2>Start Attendance Session</h2>
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="title">Session Title</label>
                            <input type="text" id="title" name="title" placeholder="Example: Monday Morning Lecture" required>
                        </div>
                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label for="class_id">Class</label>
                                <select id="class_id" name="class_id" required>
                                    <option value="">Select class</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo (int)$class['id']; ?>"><?php echo htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="duration_minutes">Duration (minutes)</label>
                                <input type="number" min="1" max="180" id="duration_minutes" name="duration_minutes" value="15" required>
                            </div>
                        </div>

                        <input type="hidden" name="_action" value="start">
                        <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-primary" <?php echo !isAttendanceEnabled() ? 'disabled' : ''; ?>>Start & Generate QR</button>
                    </form>
                </div>

                <div class="card">
                    <h2>My Attendance Sessions</h2>
                    <?php if (empty($sessions)): ?>
                        <p style="color:#666;">No attendance sessions created yet.</p>
                    <?php else: ?>
                        <?php foreach ($sessions as $session): ?>
                            <?php
                            $scan_url = $base_scan_url . '?code=' . urlencode($session['session_code']);
                            $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($scan_url);
                            ?>
                            <div style="border:1px solid #ddd;border-radius:8px;padding:1rem;margin-bottom:1rem;">
                                <h3 style="margin-bottom:0.5rem;"><?php echo htmlspecialchars($session['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p style="margin:0.2rem 0;color:#666;">Class: <?php echo htmlspecialchars($session['class_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                                <p style="margin:0.2rem 0;color:#666;">Code: <strong><?php echo htmlspecialchars($session['session_code'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                                <p style="margin:0.2rem 0;color:#666;">Present: <?php echo (int)$session['present_count']; ?> / <?php echo (int)$session['total_count']; ?></p>
                                <p style="margin:0.2rem 0;color:#666;">Ends at: <?php echo formatDateTime($session['ends_at']); ?></p>

                                <?php if ((int)$session['is_active'] === 1): ?>
                                    <div style="margin:0.75rem 0;display:flex;gap:1.25rem;align-items:flex-start;flex-wrap:wrap;">
                                        <img src="<?php echo htmlspecialchars($qr_url, ENT_QUOTES, 'UTF-8'); ?>" alt="Attendance QR" width="220" height="220" style="border:1px solid #ddd;border-radius:8px;">
                                        <div>
                                            <p>Student scan URL:</p>
                                            <p><a href="<?php echo htmlspecialchars($scan_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($scan_url, ENT_QUOTES, 'UTF-8'); ?></a></p>
                                            <form method="POST" style="margin-top:1rem;">
                                                <input type="hidden" name="_action" value="end">
                                                <input type="hidden" name="session_id" value="<?php echo (int)$session['id']; ?>">
                                                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <button type="submit" class="btn btn-secondary">End Session</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="badge badge-success">Closed</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
