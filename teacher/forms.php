<?php
/**
 * Teacher Forms List
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Forms - <?php echo APP_NAME; ?></title>
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
                <a href="forms.php" class="nav-item active">
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
                    <h1>My Forms</h1>
                </div>
                <div class="header-user">
                    <a href="/logout.php" class="btn-logout">Logout</a>
                </div>
            </header>

            <!-- Page Content -->
            <div class="content">
                <div class="quick-actions">
                    <a href="form-create.php" class="btn btn-primary">➕ Create New Form</a>
                </div>

                <!-- Forms List -->
                <div class="card">
                    <h2>Your Forms</h2>
                    <?php if (empty($forms)): ?>
                        <p style="color: #666; text-align: center; padding: 2rem;">
                            No forms created yet. <a href="form-create.php">Create your first form →</a>
                        </p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                    <th>Questions</th>
                                    <th>Responses</th>
                                    <th>Deadline</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($forms as $form): ?>
                                    <?php
                                    $question_count = $db->fetchOne(
                                        'SELECT COUNT(*) as count FROM form_questions WHERE form_id = ?',
                                        [$form['id']]
                                    )['count'] ?? 0;
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($form['title'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($form['class_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $form['status']; ?>">
                                                <?php echo ucfirst($form['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $question_count; ?></td>
                                        <td><?php echo $form['response_count']; ?></td>
                                        <td>
                                            <?php if ($form['deadline']): ?>
                                                <?php echo formatDate($form['deadline']); ?>
                                            <?php else: ?>
                                                <span style="color: #999;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="form-edit.php?id=<?php echo $form['id']; ?>" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Edit</a>
                                            <a href="responses.php?form_id=<?php echo $form['id']; ?>" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Responses</a>
                                        </td>
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
