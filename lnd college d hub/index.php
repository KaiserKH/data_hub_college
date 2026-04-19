<?php
/**
 * Main Index/Dashboard Page
 */
require 'config.php';
require 'includes/auth.php';
require 'includes/functions.php';
require 'includes/app_features.php';

// Require login
requireLogin();

$user = getCurrentUser();
$user_role = getCurrentUserRole();

processExpiredAttendanceSessions();

$notices = getVisibleNotices($user_role, 5);

$actions = [];
if ($user_role === ROLE_ADMIN) {
    $actions = [
        ['label' => 'Admin Dashboard', 'url' => '/admin/dashboard.php'],
        ['label' => 'Security & Access Controls', 'url' => '/admin/settings.php'],
        ['label' => 'Users Management', 'url' => '/admin/users.php'],
        ['label' => 'Posts & Notices', 'url' => '/admin/notices.php'],
    ];
} elseif ($user_role === ROLE_TEACHER) {
    $actions = [
        ['label' => 'Teacher Dashboard', 'url' => '/teacher/dashboard.php'],
        ['label' => 'Start QR Attendance', 'url' => '/teacher/attendance.php'],
        ['label' => 'Manage Forms', 'url' => '/teacher/forms.php'],
        ['label' => 'View Responses', 'url' => '/teacher/responses.php'],
    ];
} elseif ($user_role === ROLE_STUDENT) {
    $actions = [
        ['label' => 'Student Dashboard', 'url' => '/student/dashboard.php'],
        ['label' => 'Scan Attendance QR', 'url' => '/student/attendance-scan.php'],
        ['label' => 'My Forms', 'url' => '/student/forms.php'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Actions - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="content" style="max-width: 960px; margin: 0 auto; padding-top: 3rem;">
        <div class="card">
            <h2>Welcome, <?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <p>You are logged in as <strong><?php echo htmlspecialchars(ucfirst($user_role), ENT_QUOTES, 'UTF-8'); ?></strong>. Choose an action below.</p>
            <p style="margin-top: 1rem;">
                <a class="btn btn-secondary" href="/profile.php">👤 View My Profile</a>
                <a class="btn btn-secondary" href="/logout.php">🚪 Logout</a>
            </p>
        </div>

        <div class="card">
            <h3>Main Actions</h3>
            <div class="action-buttons">
                <?php foreach ($actions as $action): ?>
                    <a class="btn btn-primary" href="<?php echo htmlspecialchars($action['url'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($action['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <h3>Latest Notices</h3>
            <?php if (empty($notices)): ?>
                <p style="color: #666;">No notices available right now.</p>
            <?php else: ?>
                <?php foreach ($notices as $notice): ?>
                    <div style="padding: 0.9rem 0; border-bottom: 1px solid #ddd;">
                        <strong><?php echo htmlspecialchars($notice['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p style="margin: 0.4rem 0;"><?php echo nl2br(htmlspecialchars($notice['content'], ENT_QUOTES, 'UTF-8')); ?></p>
                        <small style="color: #777;">Posted by <?php echo htmlspecialchars($notice['published_by_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?> on <?php echo formatDateTime($notice['created_at']); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
