<?php
/**
 * Admin Dashboard
 */
require '../config.php';
require '../includes/auth.php';
require '../includes/functions.php';

// Require admin role
requireRole(ROLE_ADMIN);

$user = getCurrentUser();
$db = getDB();

// Get statistics
$total_users = $db->fetchOne('SELECT COUNT(*) as count FROM users WHERE is_active = 1')['count'];
$total_teachers = $db->fetchOne('SELECT COUNT(*) as count FROM users WHERE role = ? AND is_active = 1', [ROLE_TEACHER])['count'];
$total_students = $db->fetchOne('SELECT COUNT(*) as count FROM users WHERE role = ? AND is_active = 1', [ROLE_STUDENT])['count'];
$total_classes = $db->fetchOne('SELECT COUNT(*) as count FROM classes')['count'];
$total_forms = $db->fetchOne('SELECT COUNT(*) as count FROM forms')['count'];
$active_forms = $db->fetchOne('SELECT COUNT(*) as count FROM forms WHERE status = ?', [FORM_STATUS_ACTIVE])['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
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
                <a href="users.php" class="nav-item">
                    <span class="icon">👥</span> Users
                </a>
                <a href="classes.php" class="nav-item">
                    <span class="icon">🏫</span> Classes
                </a>
                <a href="forms.php" class="nav-item">
                    <span class="icon">📋</span> Forms
                </a>
                <a href="audit-log.php" class="nav-item">
                    <span class="icon">📝</span> Audit Log
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
                        <div class="stat-label">Total Users</div>
                        <div class="stat-value"><?php echo $total_users; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Teachers</div>
                        <div class="stat-value"><?php echo $total_teachers; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Students</div>
                        <div class="stat-value"><?php echo $total_students; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Classes</div>
                        <div class="stat-value"><?php echo $total_classes; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Total Forms</div>
                        <div class="stat-value"><?php echo $total_forms; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Active Forms</div>
                        <div class="stat-value"><?php echo $active_forms; ?></div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons">
                        <a href="users.php?action=create" class="btn btn-primary">➕ Add User</a>
                        <a href="classes.php?action=create" class="btn btn-primary">➕ Add Class</a>
                        <a href="forms.php" class="btn btn-secondary">📋 View All Forms</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
