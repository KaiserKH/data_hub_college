<?php
/**
 * Student Forms List
 */
require '../config.php';
require '../includes/auth.php';
require '../includes/functions.php';

// Require student role
requireRole(ROLE_STUDENT);

$user = getCurrentUser();
$db = getDB();

// Get student's class
$class = getUserClass($user['id']);

// Get all available forms for the student's class
$forms = $db->fetchAll('
    SELECT f.*, u.name as teacher_name,
           (SELECT COUNT(*) FROM responses WHERE form_id = f.id AND user_id = ?) as has_responded
    FROM forms f
    LEFT JOIN users u ON f.teacher_id = u.id
    WHERE f.class_id = ?
    ORDER BY f.deadline ASC
', [$user['id'], $class ? $class['id'] : 0]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forms - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><?php echo APP_NAME; ?></h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <span class="icon">📚</span> Dashboard
                </a>
                <a href="forms.php" class="nav-item active">
                    <span class="icon">📋</span> Forms
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-title">
                    <h1>Forms</h1>
                </div>
                <div class="header-user">
                    <a href="/logout.php" class="btn-logout">Logout</a>
                </div>
            </header>

            <!-- Page Content -->
            <div class="content">
                <?php if (empty($forms)): ?>
                    <div class="card">
                        <p style="color: #666; text-align: center; padding: 2rem;">
                            No forms available at the moment.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Teacher</th>
                                    <th>Status</th>
                                    <th>Deadline</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($forms as $form): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($form['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($form['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <?php if ($form['has_responded']): ?>
                                                <span class="badge badge-success">✓ Submitted</span>
                                            <?php else: ?>
                                                <span class="badge" style="background: #ffeaa7; color: #2d3436;">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($form['deadline']): ?>
                                                <?php 
                                                $deadline_date = strtotime($form['deadline']);
                                                $now = time();
                                                if ($deadline_date <= $now) {
                                                    echo '<span style="color: #d63031;">Closed</span>';
                                                } else {
                                                    echo formatDate($form['deadline']);
                                                }
                                                ?>
                                            <?php else: ?>
                                                <span style="color: #999;">No deadline</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$form['has_responded'] && strtotime($form['deadline'] ?? 'now') > time()): ?>
                                                <a href="form-submit.php?uuid=<?php echo urlencode($form['uuid']); ?>" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Start</a>
                                            <?php elseif ($form['has_responded']): ?>
                                                <span style="color: #999; font-size: 0.9rem;">Completed</span>
                                            <?php else: ?>
                                                <span style="color: #d63031; font-size: 0.9rem;">Closed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
