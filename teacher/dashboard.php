<?php
/**
 * Teacher Dashboard
 */
require '../config.php';
require '../includes/auth.php';
require '../includes/functions.php';

// Require teacher role
requireRole(ROLE_TEACHER);

$user = getCurrentUser();
$db = getDB();

// Get teacher's forms
$forms = $db->fetchAll('
    SELECT f.*, c.name as class_name,
           (SELECT COUNT(*) FROM responses WHERE form_id = f.id) as response_count
    FROM forms f
    LEFT JOIN classes c ON f.class_id = c.id
    WHERE f.teacher_id = ?
    ORDER BY f.created_at DESC
', [$user['id']]);

// Get statistics
$total_forms = count($forms);
$active_forms = count(array_filter($forms, function($f) { return $f['status'] === FORM_STATUS_ACTIVE; }));
$total_responses = array_sum(array_column($forms, 'response_count'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - <?php echo APP_NAME; ?></title>
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
                <a href="dashboard.php" class="nav-item active">
                    <span class="icon">📊</span> Dashboard
                </a>
                <a href="forms.php" class="nav-item">
                    <span class="icon">📋</span> My Forms
                </a>
                <a href="responses.php" class="nav-item">
                    <span class="icon">📊</span> Responses
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-title">
                    <h1>Dashboard</h1>
                </div>
                <div class="header-user">
                    <span class="user-name"><?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="/logout.php" class="btn-logout">Logout</a>
                </div>
            </header>

            <!-- Page Content -->
            <div class="content">
                <h2>Welcome, <?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>!</h2>

                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Forms</div>
                        <div class="stat-value"><?php echo $total_forms; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Active Forms</div>
                        <div class="stat-value"><?php echo $active_forms; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Total Responses</div>
                        <div class="stat-value"><?php echo $total_responses; ?></div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons">
                        <a href="form-create.php" class="btn btn-primary">➕ Create Form</a>
                        <a href="forms.php" class="btn btn-secondary">📋 View All Forms</a>
                        <a href="responses.php" class="btn btn-secondary">📊 View Responses</a>
                    </div>
                </div>

                <!-- Recent Forms -->
                <div class="card">
                    <h3>Recent Forms</h3>
                    <?php if (empty($forms)): ?>
                        <p style="color: #666;">No forms created yet.</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                    <th>Responses</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($forms, 0, 5) as $form): ?>
                                    <tr>
                                        <td>
                                            <a href="form-edit.php?id=<?php echo $form['id']; ?>">
                                                <?php echo htmlspecialchars($form['title'], ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($form['class_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $form['status']; ?>">
                                                <?php echo ucfirst($form['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $form['response_count']; ?></td>
                                        <td><?php echo formatDate($form['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
