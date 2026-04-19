<?php
/**
 * Teacher Create/Edit Form
 */
require '../config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require '../includes/csrf.php';
require '../includes/validation.php';

// Require teacher role
requireRole(ROLE_TEACHER);

$user = getCurrentUser();
$db = getDB();
$form_id = getParam('id', null, 'GET');
$form = null;
$message = '';
$errors = [];

// If editing, get existing form
if ($form_id) {
    $form = $db->fetchOne('SELECT * FROM forms WHERE id = ? AND teacher_id = ?', [$form_id, $user['id']]);
    if (!$form) {
        errorPage(403, 'You do not have access to this form');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrfToken()) {
    $title = trim(getParam('title', ''));
    $description = trim(getParam('description', ''));
    $class_id = getParam('class_id', null);
    $deadline = getParam('deadline', null);
    $status = getParam('status', FORM_STATUS_DRAFT);

    $validators = [
        'title' => 'required|min_length:3|max_length:255',
        'class_id' => 'required',
    ];

    $validation_errors = validateFormInput(['title' => $title, 'class_id' => $class_id], $validators);

    if ($validation_errors) {
        $errors = array_values($validation_errors);
    } else {
        try {
            if ($form_id) {
                // Update existing form
                $db->query(
                    'UPDATE forms SET title = ?, description = ?, deadline = ?, status = ?, updated_at = NOW() WHERE id = ?',
                    [$title, $description, $deadline ?: null, $status, $form_id]
                );
                $message = 'Form updated successfully!';
                auditLog('update_form', 'form', $form_id);
            } else {
                // Create new form
                $uuid = generateUuid();
                $db->query(
                    'INSERT INTO forms (uuid, teacher_id, class_id, title, description, deadline, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                    [$uuid, $user['id'], $class_id, $title, $description, $deadline ?: null, $status]
                );
                $new_form_id = $db->lastInsertId();
                $message = 'Form created successfully! Now add questions.';
                auditLog('create_form', 'form', $new_form_id, "Created form: $title");
                $form_id = $new_form_id;
                $form = $db->fetchOne('SELECT * FROM forms WHERE id = ?', [$form_id]);
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to save form: ' . $e->getMessage();
        }
    }
}

$classes = getAllClasses();
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $form ? 'Edit' : 'Create'; ?> Form - <?php echo APP_NAME; ?></title>
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
                    <h1><?php echo $form ? 'Edit Form' : 'Create New Form'; ?></h1>
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

                <!-- Form Editor -->
                <div class="card">
                    <h2><?php echo $form ? 'Edit' : 'Create'; ?> Form</h2>
                    <form method="POST" class="form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="title">Form Title *</label>
                                <input type="text" id="title" name="title" required value="<?php echo $form ? htmlspecialchars($form['title'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="class_id">Class *</label>
                                <select id="class_id" name="class_id" required>
                                    <option value="">-- Select Class --</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" <?php echo $form && $form['class_id'] == $class['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4"><?php echo $form ? htmlspecialchars($form['description'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="deadline">Deadline (Optional)</label>
                                <input type="datetime-local" id="deadline" name="deadline" value="<?php echo $form && $form['deadline'] ? date('Y-m-d\TH:i', strtotime($form['deadline'])) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="<?php echo FORM_STATUS_DRAFT; ?>" <?php echo (!$form || $form['status'] === FORM_STATUS_DRAFT) ? 'selected' : ''; ?>>Draft</option>
                                    <option value="<?php echo FORM_STATUS_ACTIVE; ?>" <?php echo $form && $form['status'] === FORM_STATUS_ACTIVE ? 'selected' : ''; ?>>Active</option>
                                    <option value="<?php echo FORM_STATUS_CLOSED; ?>" <?php echo $form && $form['status'] === FORM_STATUS_CLOSED ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                        </div>

                        <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                        <button type="submit" class="btn btn-primary">
                            <?php echo $form ? 'Update Form' : 'Create Form'; ?>
                        </button>
                        <a href="forms.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>

                <?php if ($form): ?>
                    <!-- Questions Section -->
                    <div class="card">
                        <h2>Questions</h2>
                        <p style="color: #666; margin-bottom: 1rem;">
                            Add and manage questions for this form. 
                            <a href="form-questions.php?form_id=<?php echo $form['id']; ?>" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">Manage Questions →</a>
                        </p>
                        <?php
                        $questions = getFormQuestions($form['id']);
                        if (!empty($questions)):
                        ?>
                            <ol style="margin-top: 1rem;">
                                <?php foreach ($questions as $q): ?>
                                    <li style="margin-bottom: 0.5rem;">
                                        <?php echo htmlspecialchars($q['question_text'], ENT_QUOTES, 'UTF-8'); ?>
                                        <small style="color: #999;">
                                            (<?php echo ucfirst(str_replace('_', ' ', $q['question_type'])); ?>)
                                        </small>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php else: ?>
                            <p style="color: #999;">No questions added yet.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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
        .btn {
            display: inline-block;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
    </style>
</body>
</html>
