<?php
/**
 * Teacher View Form Responses
 */
require '../config.php';
require '../includes/auth.php';
require '../includes/functions.php';

// Require teacher role
requireRole(ROLE_TEACHER);

$user = getCurrentUser();
$db = getDB();
$form_id = getParam('form_id', null, 'GET');

// Get form (verify ownership)
if ($form_id) {
    $form = $db->fetchOne('SELECT * FROM forms WHERE id = ? AND teacher_id = ?', [$form_id, $user['id']]);
    if (!$form) {
        errorPage(403, 'You do not have access to this form');
    }
}

// Get responses for this form
$responses = [];
if ($form_id) {
    $responses = $db->fetchAll('
        SELECT r.*, u.name as student_name, u.email as student_email
        FROM responses r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.form_id = ?
        ORDER BY r.submitted_at DESC
    ', [$form_id]);
}

// Get all teacher's forms (for dropdown)
$forms = $db->fetchAll('
    SELECT id, title FROM forms WHERE teacher_id = ? ORDER BY created_at DESC',
    [$user['id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Responses - <?php echo APP_NAME; ?></title>
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
                <a href="forms.php" class="nav-item">
                    <span class="icon">📋</span> My Forms
                </a>
                <a href="responses.php" class="nav-item active">
                    <span class="icon">📊</span> Responses
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-title">
                    <h1>Form Responses</h1>
                </div>
                <div class="header-user">
                    <a href="/logout.php" class="btn-logout">Logout</a>
                </div>
            </header>

            <!-- Page Content -->
            <div class="content">
                <!-- Form Selection -->
                <div class="card">
                    <h2>Select Form</h2>
                    <form method="GET" class="form" style="max-width: 400px;">
                        <div class="form-group">
                            <label for="form_id">Choose Form</label>
                            <select id="form_id" name="form_id" onChange="this.form.submit()">
                                <option value="">-- Select a form --</option>
                                <?php foreach ($forms as $f): ?>
                                    <option value="<?php echo $f['id']; ?>" <?php echo $form_id == $f['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($f['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>

                <?php if ($form_id && $form): ?>
                    <!-- Responses List -->
                    <div class="card">
                        <h2>Responses for: <?php echo htmlspecialchars($form['title'], ENT_QUOTES, 'UTF-8'); ?></h2>

                        <?php if (empty($responses)): ?>
                            <p style="color: #666; text-align: center; padding: 2rem;">
                                No responses yet.
                            </p>
                        <?php else: ?>
                            <p style="color: #666; margin-bottom: 1rem;">
                                Total Responses: <strong><?php echo count($responses); ?></strong>
                            </p>

                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($responses as $response): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($response['student_name'] ?? 'Anonymous', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($response['student_email'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <span class="badge <?php echo $response['is_complete'] ? 'badge-success' : 'badge-warning'; ?>">
                                                    <?php echo $response['is_complete'] ? 'Complete' : 'Incomplete'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo $response['submitted_at'] ? formatDateTime($response['submitted_at']) : 'In Progress'; ?>
                                            </td>
                                            <td>
                                                <a href="response-view.php?id=<?php echo $response['id']; ?>" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
