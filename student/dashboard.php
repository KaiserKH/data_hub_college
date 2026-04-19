<?php
/**
 * Student Dashboard
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

// Get available forms for the student's class
$available_forms = $db->fetchAll('
    SELECT f.*, u.name as teacher_name,
           (SELECT COUNT(*) FROM responses WHERE form_id = f.id AND user_id = ?) as has_responded
    FROM forms f
    LEFT JOIN users u ON f.teacher_id = u.id
    WHERE f.class_id = ? AND f.status = ?
    ORDER BY f.deadline ASC
', [$user['id'], $class ? $class['id'] : 0, FORM_STATUS_ACTIVE]);

// Separate forms
$pending_forms = array_filter($available_forms, function($f) { return !$f['has_responded']; });
$completed_forms = array_filter($available_forms, function($f) { return $f['has_responded']; });

$total_forms = count($available_forms);
$pending_count = count($pending_forms);
$completed_count = count($completed_forms);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo APP_NAME; ?></title>
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
                    <span class="icon">📚</span> Dashboard
                </a>
                <a href="forms.php" class="nav-item">
                    <span class="icon">📋</span> Forms
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

                <?php if ($class): ?>
                    <p style="color: #666;">Class: <strong><?php echo htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                <?php endif; ?>

                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Available Forms</div>
                        <div class="stat-value"><?php echo $total_forms; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Pending</div>
                        <div class="stat-value" style="color: #ff6b6b;"><?php echo $pending_count; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Completed</div>
                        <div class="stat-value" style="color: #51cf66;"><?php echo $completed_count; ?></div>
                    </div>
                </div>

                <!-- Pending Forms Section -->
                <div class="card">
                    <h3>📌 Pending Forms</h3>
                    <?php if (empty($pending_forms)): ?>
                        <p style="color: #666;">No pending forms</p>
                    <?php else: ?>
                        <div class="forms-list">
                            <?php foreach ($pending_forms as $form): ?>
                                <div class="form-item">
                                    <div class="form-info">
                                        <h4><?php echo htmlspecialchars($form['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                        <p><?php echo htmlspecialchars($form['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                        <small>By: <?php echo htmlspecialchars($form['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></small>
                                    </div>
                                    <div class="form-deadline">
                                        <?php if ($form['deadline']): ?>
                                            <div class="deadline-label">Due: <?php echo formatDateTime($form['deadline']); ?></div>
                                            <div class="time-remaining"><?php echo formatTimeRemaining($form['deadline']); ?> remaining</div>
                                        <?php else: ?>
                                            <div class="deadline-label">No deadline</div>
                                        <?php endif; ?>
                                    </div>
                                    <a href="form-submit.php?uuid=<?php echo urlencode($form['uuid']); ?>" class="btn btn-primary">
                                        Start Form →
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Completed Forms Section -->
                <?php if (!empty($completed_forms)): ?>
                    <div class="card">
                        <h3>✅ Completed Forms</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Teacher</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed_forms as $form): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($form['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($form['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo formatDate($form['updated_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <style>
        .forms-list {
            display: grid;
            gap: 1rem;
        }
        .form-item {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            align-items: center;
        }
        .form-info h4 {
            margin: 0 0 0.5rem 0;
        }
        .form-info p {
            margin: 0.25rem 0;
            font-size: 0.95rem;
            color: #666;
        }
        .form-info small {
            color: #999;
        }
        .form-deadline {
            text-align: right;
        }
        .deadline-label {
            font-size: 0.9rem;
            color: #666;
        }
        .time-remaining {
            font-weight: 600;
            color: #ff6b6b;
            font-size: 0.95rem;
        }
        @media (max-width: 768px) {
            .form-item {
                grid-template-columns: 1fr;
            }
            .form-deadline {
                text-align: left;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
</body>
</html>
