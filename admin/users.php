<?php
/**
 * Admin Users Management
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
        $email = trim(getParam('email', ''));
        $password = getParam('password', '');
        $role = getParam('role', ROLE_STUDENT);
        $class_id = getParam('class_id', null);

        $validators = [
            'name' => 'required|min_length:2|max_length:150',
            'email' => 'required|email',
            'password' => 'required|min_length:8',
            'role' => 'required'
        ];

        $validation_errors = validateFormInput(['name' => $name, 'email' => $email, 'password' => $password, 'role' => $role], $validators);

        if ($validation_errors) {
            $errors = array_values($validation_errors);
        } else {
            $result = createUser($name, $email, $password, $role, $class_id, null);
            if ($result['success']) {
                $message = 'User created successfully!';
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

// Get all users
$users = $db->fetchAll('SELECT u.*, c.name as class_name FROM users u LEFT JOIN classes c ON u.class_id = c.id ORDER BY u.created_at DESC');
$classes = getAllClasses();

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - <?php echo APP_NAME; ?></title>
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
                <a href="users.php" class="nav-item active">
                    <span class="icon">👥</span> Users
                </a>
                <a href="classes.php" class="nav-item">
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
                    <h1>Users Management</h1>
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

                <!-- Add User Form -->
                <div class="card">
                    <h2>Add New User</h2>
                    <form method="POST" class="form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <label for="role">Role *</label>
                                <select id="role" name="role" required>
                                    <option value="<?php echo ROLE_STUDENT; ?>">Student</option>
                                    <option value="<?php echo ROLE_TEACHER; ?>">Teacher</option>
                                    <option value="<?php echo ROLE_ADMIN; ?>">Admin</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="class_id">Class (for students)</label>
                            <select id="class_id" name="class_id">
                                <option value="">-- Select Class --</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>">
                                        <?php echo htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <input type="hidden" name="_action" value="create">
                        <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                        <button type="submit" class="btn btn-primary">Create User</button>
                    </form>
                </div>

                <!-- Users List -->
                <div class="card">
                    <h2>All Users</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Class</th>
                                <th>Status</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No users found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $u['role']; ?>">
                                                <?php echo ucfirst($u['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $u['class_name'] ? htmlspecialchars($u['class_name'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                        <td>
                                            <span class="badge <?php echo $u['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($u['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <style>
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
