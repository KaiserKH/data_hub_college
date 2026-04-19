<?php
/**
 * Admin posts and notices management.
 */
require '../config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require '../includes/csrf.php';
require '../includes/app_features.php';

requireRole(ROLE_ADMIN);

$db = getDB();
$user = getCurrentUser();
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token.';
    } else {
        $action = $_POST['_action'] ?? '';

        if ($action === 'create') {
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $audience = $_POST['audience'] ?? 'all';

            if ($title === '' || $content === '') {
                $errors[] = 'Title and content are required.';
            }

            if (!in_array($audience, ['all', 'admin', 'teacher', 'student'], true)) {
                $errors[] = 'Invalid audience selection.';
            }

            if (empty($errors)) {
                createNoticePost($title, $content, $audience, $user['id']);
                $message = 'Notice posted successfully.';
                auditLog('notice_created', 'notice', null, $title);
            }
        }

        if ($action === 'toggle') {
            $notice_id = (int)($_POST['notice_id'] ?? 0);
            $new_state = (int)($_POST['new_state'] ?? 1);
            $db->query('UPDATE notices SET is_active = ? WHERE id = ?', [$new_state, $notice_id]);
            $message = 'Notice updated successfully.';
        }
    }
}

$notices = $db->fetchAll('SELECT n.*, u.name AS publisher_name FROM notices n LEFT JOIN users u ON n.published_by = u.id ORDER BY n.created_at DESC LIMIT 100');
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts and Notices - <?php echo APP_NAME; ?></title>
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
                <a href="notices.php" class="nav-item active"><span class="icon">📢</span> Posts & Notices</a>
                <a href="settings.php" class="nav-item"><span class="icon">🔐</span> Security Settings</a>
                <a href="/index.php" class="nav-item"><span class="icon">🏠</span> Main Actions</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-title"><h1>Posts and Notices</h1></div>
                <div class="header-user"><a href="/logout.php" class="btn-logout">Logout</a></div>
            </header>

            <div class="content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>

                <div class="card">
                    <h2>Create New Notice</h2>
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="audience">Audience</label>
                            <select id="audience" name="audience" required>
                                <option value="all">All Users</option>
                                <option value="admin">Admins Only</option>
                                <option value="teacher">Teachers Only</option>
                                <option value="student">Students Only</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="content">Content</label>
                            <textarea id="content" name="content" required></textarea>
                        </div>

                        <input type="hidden" name="_action" value="create">
                        <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-primary">Publish Notice</button>
                    </form>
                </div>

                <div class="card">
                    <h2>Published Notices</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Audience</th>
                                <th>Posted By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($notices)): ?>
                                <tr><td colspan="6" class="text-center">No notices yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($notices as $notice): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($notice['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <div style="color:#666;margin-top:0.3rem;"><?php echo nl2br(htmlspecialchars($notice['content'], ENT_QUOTES, 'UTF-8')); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars(ucfirst($notice['audience']), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($notice['publisher_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo formatDateTime($notice['created_at']); ?></td>
                                        <td>
                                            <span class="badge <?php echo (int)$notice['is_active'] === 1 ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo (int)$notice['is_active'] === 1 ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="_action" value="toggle">
                                                <input type="hidden" name="notice_id" value="<?php echo (int)$notice['id']; ?>">
                                                <input type="hidden" name="new_state" value="<?php echo (int)$notice['is_active'] === 1 ? 0 : 1; ?>">
                                                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <button class="btn btn-secondary" type="submit"><?php echo (int)$notice['is_active'] === 1 ? 'Deactivate' : 'Activate'; ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
