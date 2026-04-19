<?php
/**
 * Admin Forms Management
 */
require '../config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require '../includes/csrf.php';

// Require admin role
requireRole(ROLE_ADMIN);

$db = getDB();
$user = getCurrentUser();

// Get all forms with teacher info
$forms = $db->fetchAll('
    SELECT f.*, u.name as teacher_name, c.name as class_name, 
           (SELECT COUNT(*) FROM responses WHERE form_id = f.id) as response_count
    FROM forms f
    LEFT JOIN users u ON f.teacher_id = u.id
    LEFT JOIN classes c ON f.class_id = c.id
    ORDER BY f.created_at DESC
');

$csrf_token = generateCsrfToken();
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
                    <span class="icon">📊</span> Dashboard
                </a>
                <a href="users.php" class="nav-item">
                    <span class="icon">👥</span> Users
                </a>
                <a href="classes.php" class="nav-item">
                    <span class="icon">🏫</span> Classes
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
                    <h1>Forms Management</h1>
                </div>
                <div class="header-user">
                    <a href="/logout.php" class="btn-logout">Logout</a>
                </div>
            </header>

            <!-- Page Content -->
            <div class="content">
                <!-- Forms List -->
                <div class="card">
                    <h2>All Forms</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Teacher</th>
                                <th>Class</th>
                                <th>Status</th>
                                <th>Responses</th>
                                <th>Deadline</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($forms)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No forms found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($forms as $form): ?>
                                    <tr>
                                        <td>
                                            <a href="/teacher/form-view.php?id=<?php echo $form['id']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($form['title'], ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($form['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($form['class_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $form['status']; ?>">
                                                <?php echo ucfirst($form['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $form['response_count']; ?></td>
                                        <td>
                                            <?php if ($form['deadline']): ?>
                                                <?php echo formatDateTime($form['deadline']); ?>
                                            <?php else: ?>
                                                <span style="color: #999;">No deadline</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($form['created_at']); ?></td>
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
