<?php
/**
 * Admin Role-Based Settings and Permissions
 */
require '../config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require '../includes/csrf.php';
require '../includes/college_features.php';

requireRole(ROLE_ADMIN);

$user = getCurrentUser();
$message = '';
$errors = [];

$roles = [ROLE_ADMIN, ROLE_TEACHER, ROLE_STUDENT];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token.';
    } else {
        $action = $_POST['_action'] ?? '';
        $role = $_POST['role'] ?? '';

        if (in_array($role, $roles) && $action === 'update_permissions') {
            $permissions = [
                'can_create_forms' => isset($_POST['can_create_forms']),
                'can_view_forms' => isset($_POST['can_view_forms']),
                'can_manage_courses' => isset($_POST['can_manage_courses']),
                'can_view_attendance' => isset($_POST['can_view_attendance']),
                'can_upload_picture' => isset($_POST['can_upload_picture']),
                'can_manage_users' => isset($_POST['can_manage_users']),
            ];

            foreach ($permissions as $perm => $enabled) {
                if ($enabled) {
                    grantRolePermission($role, $perm, $user['id']);
                } else {
                    revokeRolePermission($role, $perm, $user['id']);
                }
            }

            $message = 'Permissions updated for ' . ucfirst($role) . ' role.';
            auditLog('role_permissions_updated', 'role', null, $role);
        }
    }
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Settings - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
    <div class="wrapper">
        <aside class="sidebar">
            <div class="sidebar-header"><h2><?php echo APP_NAME; ?></h2></div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><span class="icon">📊</span> Dashboard</a>
                <a href="users.php" class="nav-item"><span class="icon">👥</span> Users</a>
                <a href="courses.php" class="nav-item"><span class="icon">📚</span> Courses</a>
                <a href="role-settings.php" class="nav-item active"><span class="icon">👻</span> Role Settings</a>
                <a href="settings.php" class="nav-item"><span class="icon">🔐</span> Security Settings</a>
                <a href="/index.php" class="nav-item"><span class="icon">🏠</span> Home</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-title"><h1>Role-Based Settings & Permissions</h1></div>
                <div class="header-user"><a href="/logout.php" class="btn-logout">Logout</a></div>
            </header>

            <div class="content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>

                <?php foreach ($roles as $role): ?>
                    <?php
                    $current_perms = getRoleSettings($role);
                    $can_create_forms = isset($current_perms['permissions_can_create_forms']) && $current_perms['permissions_can_create_forms'] === 'enabled';
                    $can_view_forms = isset($current_perms['permissions_can_view_forms']) && $current_perms['permissions_can_view_forms'] === 'enabled';
                    $can_manage_courses = isset($current_perms['permissions_can_manage_courses']) && $current_perms['permissions_can_manage_courses'] === 'enabled';
                    $can_view_attendance = isset($current_perms['permissions_can_view_attendance']) && $current_perms['permissions_can_view_attendance'] === 'enabled';
                    $can_upload_picture = isset($current_perms['permissions_can_upload_picture']) && $current_perms['permissions_can_upload_picture'] === 'enabled';
                    $can_manage_users = isset($current_perms['permissions_can_manage_users']) && $current_perms['permissions_can_manage_users'] === 'enabled';

                    if ($role === ROLE_ADMIN) {
                        $can_create_forms = true;
                        $can_view_forms = true;
                        $can_manage_courses = true;
                        $can_view_attendance = true;
                        $can_upload_picture = true;
                        $can_manage_users = true;
                    }
                    ?>
                    <div class="card">
                        <h2><?php echo ucfirst($role); ?> Role Permissions</h2>
                        <form method="POST" class="form">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                                <label><input type="checkbox" name="can_create_forms" <?php echo $can_create_forms || $role === ROLE_ADMIN ? 'checked' : ''; ?> <?php echo $role === ROLE_ADMIN ? 'disabled' : ''; ?>> Can Create Forms</label>
                                <label><input type="checkbox" name="can_view_forms" <?php echo $can_view_forms || $role === ROLE_ADMIN ? 'checked' : ''; ?> <?php echo $role === ROLE_ADMIN ? 'disabled' : ''; ?>> Can View Forms</label>
                                <label><input type="checkbox" name="can_manage_courses" <?php echo $can_manage_courses || $role === ROLE_ADMIN ? 'checked' : ''; ?> <?php echo $role === ROLE_ADMIN ? 'disabled' : ''; ?>> Can Manage Courses</label>
                                <label><input type="checkbox" name="can_view_attendance" <?php echo $can_view_attendance || $role === ROLE_ADMIN ? 'checked' : ''; ?> <?php echo $role === ROLE_ADMIN ? 'disabled' : ''; ?>> Can View Attendance</label>
                                <label><input type="checkbox" name="can_upload_picture" <?php echo $can_upload_picture || $role === ROLE_ADMIN ? 'checked' : ''; ?> <?php echo $role === ROLE_ADMIN ? 'disabled' : ''; ?>> Can Upload Profile Picture</label>
                                <label><input type="checkbox" name="can_manage_users" <?php echo $can_manage_users || $role === ROLE_ADMIN ? 'checked' : ''; ?> <?php echo $role === ROLE_ADMIN ? 'disabled' : ''; ?>> Can Manage Users</label>
                            </div>

                            <?php if ($role !== ROLE_ADMIN): ?>
                                <input type="hidden" name="_action" value="update_permissions">
                                <input type="hidden" name="role" value="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Save Permissions</button>
                            <?php else: ?>
                                <p style="font-style:italic;color:#666;margin-top:1rem;">Admin role has all permissions by default.</p>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <style>
        label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            margin-bottom: 0.5rem;
        }
        label input[type="checkbox"] {
            width: auto;
        }
    </style>
</body>
</html>
