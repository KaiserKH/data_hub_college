<?php
/**
 * Admin Classes Management
 */
require '../config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require '../includes/validation.php';
require '../includes/csrf.php';

// Require admin role
requireRole(ROLE_ADMIN);

$db = getDB();
$user = getCurrentUser();
$message = '';
$errors = [];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrfToken()) {
    $action = getParam('_action');

    if ($action === 'create') {
        $name = trim(getParam('name', ''));
        $description = trim(getParam('description', ''));

        if (empty($name)) {
            $errors[] = 'Class name is required';
        } elseif (strlen($name) > 100) {
            $errors[] = 'Class name is too long';
        } else {
            try {
                $db->query(
                    'INSERT INTO classes (name, description) VALUES (?, ?)',
                    [$name, $description]
                );
                $message = 'Class created successfully!';
                auditLog('create_class', 'class', $db->lastInsertId(), "Created class: $name");
            } catch (Exception $e) {
                $errors[] = 'Failed to create class. It may already exist.';
            }
        }
    }
}

// Get all classes
$classes = getAllClasses();
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes - <?php echo APP_NAME; ?></title>
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
                <a href="classes.php" class="nav-item active">
                    <span class="icon">🏫</span> Classes
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
                    <h1>Classes Management</h1>
                </div>
                <div class="header-user">
                    <a href="/logout.php" class="btn-logout">Logout</a>
                </div>
            </header>

            <!-- Page Content -->
            <div class="content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>

                <!-- Add Class Form -->
                <div class="card">
                    <h2>Add New Class</h2>
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="name">Class Name *</label>
                            <input type="text" id="name" name="name" placeholder="e.g., Class 10A" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" placeholder="Add notes about this class"></textarea>
                        </div>

                        <input type="hidden" name="_action" value="create">
                        <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                        <button type="submit" class="btn btn-primary">Create Class</button>
                    </form>
                </div>

                <!-- Classes List -->
                <div class="card">
                    <h2>All Classes</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Class Name</th>
                                <th>Description</th>
                                <th>Students</th>
                                <th>Forms</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($classes)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No classes found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($classes as $class): ?>
                                    <?php
                                    $student_count = $db->fetchOne('SELECT COUNT(*) as count FROM users WHERE class_id = ? AND role = ?', [$class['id'], ROLE_STUDENT])['count'];
                                    $form_count = $db->fetchOne('SELECT COUNT(*) as count FROM forms WHERE class_id = ?', [$class['id']])['count'];
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($class['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo $student_count; ?></td>
                                        <td><?php echo $form_count; ?></td>
                                        <td><?php echo formatDate($class['created_at']); ?></td>
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
