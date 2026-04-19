<?php
/**
 * Student Form Submission
 */
require '../config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require '../includes/csrf.php';

// Require student role
requireRole(ROLE_STUDENT);

$user = getCurrentUser();
$db = getDB();
$uuid = getParam('uuid', null, 'GET');

// Get form by UUID
if (!$uuid) {
    errorPage(400, 'Form ID is required');
}

$form = getFormByUuid($uuid);
if (!$form) {
    errorPage(404, 'Form not found');
}

// Check if form is active
if ($form['status'] !== FORM_STATUS_ACTIVE) {
    errorPage(403, 'This form is not currently available');
}

// Check deadline
if (isDeadlinePassed($form['deadline'])) {
    errorPage(403, 'This form submission deadline has passed');
}

// Check if user already responded (unless editable after submit)
if (!$form['allows_edit_after_submit'] && hasUserResponded($form['id'], $user['id'])) {
    errorPage(403, 'You have already submitted this form');
}

// Get form questions
$questions = getFormQuestions($form['id']);

// Handle form submission
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrfToken()) {
    try {
        // Check again before saving
        if (!$form['allows_edit_after_submit'] && hasUserResponded($form['id'], $user['id'])) {
            errorPage(403, 'You have already submitted this form');
        }

        $db->beginTransaction();

        // Create or get response
        $response = $db->fetchOne(
            'SELECT id FROM responses WHERE form_id = ? AND user_id = ?',
            [$form['id'], $user['id']]
        );

        if (!$response) {
            $db->query(
                'INSERT INTO responses (form_id, user_id, is_complete, created_at, updated_at) VALUES (?, ?, 0, NOW(), NOW())',
                [$form['id'], $user['id']]
            );
            $response_id = $db->lastInsertId();
        } else {
            $response_id = $response['id'];
        }

        // Save answers
        foreach ($questions as $question) {
            $answer_key = 'answer_' . $question['id'];
            $answer_value = $_POST[$answer_key] ?? null;

            if (!empty($answer_value) || isset($_POST[$answer_key])) {
                // Delete existing answer first
                $db->query('DELETE FROM answers WHERE response_id = ? AND question_id = ?', [$response_id, $question['id']]);

                // Insert new answer
                $db->query(
                    'INSERT INTO answers (response_id, question_id, answer_text, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())',
                    [$response_id, $question['id'], $answer_value]
                );
            }
        }

        // Mark complete and set submitted_at
        $db->query(
            'UPDATE responses SET is_complete = 1, submitted_at = NOW(), updated_at = NOW() WHERE id = ?',
            [$response_id]
        );

        // Log submission
        auditLog('submit_form', 'response', $response_id, "Submitted form: " . $form['title']);

        $db->commit();

        $message = 'Form submitted successfully!';
    } catch (Exception $e) {
        $db->rollback();
        $errors[] = 'Failed to submit form: ' . $e->getMessage();
    }
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($form['title'], ENT_QUOTES, 'UTF-8'); ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .form-header {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .form-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
        }
        .form-header p {
            color: #666;
            margin: 0.25rem 0;
        }
        .form-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .question-group {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #ddd;
        }
        .question-group:last-child {
            border-bottom: none;
        }
        .question-label {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-color);
        }
        .question-label.required::after {
            content: ' *';
            color: var(--danger-color);
        }
        .question-group input[type="text"],
        .question-group input[type="date"],
        .question-group input[type="email"],
        .question-group textarea,
        .question-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
            font-family: inherit;
        }
        .question-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        .question-group input[type="radio"],
        .question-group input[type="checkbox"] {
            margin-right: 0.5rem;
        }
        .option {
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
        }
        .form-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }
        .back-link {
            color: var(--primary-color);
            text-decoration: none;
            margin-top: 1rem;
        }
    </style>
</head>
<body style="background: #f8f9fa; padding: 2rem 1rem;">
    <div class="form-container">
        <?php if ($message): ?>
            <div class="alert alert-success" style="margin-bottom: 1rem;"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <div style="background: white; padding: 2rem; border-radius: 8px; text-align: center;">
                <h2 style="color: var(--success-color);">✅ Form Submitted Successfully!</h2>
                <p style="color: #666; margin: 1rem 0;">Thank you for your submission.</p>
                <a href="/student/dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
        <?php else: ?>
            <!-- Form Header -->
            <div class="form-header">
                <h1><?php echo htmlspecialchars($form['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p><?php echo htmlspecialchars($form['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if ($form['deadline']): ?>
                    <p style="color: var(--danger-color); font-weight: 600;">
                        Due: <?php echo formatDateTime($form['deadline']); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Errors -->
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error" style="margin-bottom: 1rem;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>

            <!-- Form Content -->
            <form method="POST" class="form-content">
                <?php if (empty($questions)): ?>
                    <p style="text-align: center; color: #666;">This form has no questions.</p>
                <?php else: ?>
                    <?php foreach ($questions as $question): ?>
                        <div class="question-group">
                            <label class="question-label <?php echo $question['is_required'] ? 'required' : ''; ?>">
                                <?php echo htmlspecialchars($question['question_text'], ENT_QUOTES, 'UTF-8'); ?>
                            </label>

                            <?php
                            $question_type = $question['question_type'];
                            $options = decodeQuestionOptions($question['question_options']);
                            $name = 'answer_' . $question['id'];
                            $req = $question['is_required'] ? 'required' : '';
                            ?>

                            <?php if ($question_type === 'short'): ?>
                                <input type="text" name="<?php echo $name; ?>" <?php echo $req; ?>>

                            <?php elseif ($question_type === 'paragraph'): ?>
                                <textarea name="<?php echo $name; ?>" <?php echo $req; ?>></textarea>

                            <?php elseif ($question_type === 'date'): ?>
                                <input type="date" name="<?php echo $name; ?>" <?php echo $req; ?>>

                            <?php elseif ($question_type === 'multiple_choice'): ?>
                                <?php foreach ($options as $option): ?>
                                    <div class="option">
                                        <input type="radio" id="<?php echo $name . '_' . sanitizeString($option); ?>" name="<?php echo $name; ?>" value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $req; ?>>
                                        <label for="<?php echo $name . '_' . sanitizeString($option); ?>"><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></label>
                                    </div>
                                <?php endforeach; ?>

                            <?php elseif ($question_type === 'checkbox'): ?>
                                <?php foreach ($options as $option): ?>
                                    <div class="option">
                                        <input type="checkbox" id="<?php echo $name . '_' . sanitizeString($option); ?>" name="<?php echo $name; ?>[]" value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>">
                                        <label for="<?php echo $name . '_' . sanitizeString($option); ?>"><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></label>
                                    </div>
                                <?php endforeach; ?>

                            <?php elseif ($question_type === 'dropdown'): ?>
                                <select name="<?php echo $name; ?>" <?php echo $req; ?>>
                                    <option value="">-- Select --</option>
                                    <?php foreach ($options as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif ($question_type === 'rating'): ?>
                                <div class="option">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <input type="radio" id="<?php echo $name . '_' . $i; ?>" name="<?php echo $name; ?>" value="<?php echo $i; ?>" <?php echo $req; ?>>
                                        <label for="<?php echo $name . '_' . $i; ?>" style="margin-right: 1rem;"><?php echo $i; ?> ⭐</label>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-primary">Submit Form</button>
                        <a href="/student/dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
