<?php
/**
 * Admin Course Management
 */
require '../config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require '../includes/csrf.php';
require '../includes/college_features.php';

requireRole(ROLE_ADMIN);

$user = getCurrentUser();
$db = getDB();
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token.';
    } else {
        $action = $_POST['_action'] ?? '';

        if ($action === 'create_course') {
            $code = trim($_POST['code'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $credits = (int)($_POST['credits'] ?? 3);
            $semester = (int)($_POST['semester'] ?? 1);
            $instructor_id = (int)($_POST['instructor_id'] ?? 0);

            if ($code === '' || $name === '') {
                $errors[] = 'Course code and name are required.';
            } else {
                $result = createCourse($code, $name, $description, $instructor_id ?: null, $credits, $semester);
                if ($result) {
                    $message = 'Course created successfully.';
                    auditLog('course_created', 'course', $result, $code);
                } else {
                    $errors[] = 'Failed to create course. Code may already exist.';
                }
            }
        }

        if ($action === 'update_course') {
            $course_id = (int)($_POST['course_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($course_id > 0) {
                updateCourse($course_id, [
                    'name' => $name,
                    'description' => $description,
                    'is_active' => $is_active
                ]);
                $message = 'Course updated successfully.';
            }
        }

        if ($action === 'deactivate_course') {
            $course_id = (int)($_POST['course_id'] ?? 0);
            if ($course_id > 0) {
                deleteCourse($course_id);
                $message = 'Course deactivated successfully.';
                auditLog('course_deleted', 'course', $course_id, '');
            }
        }

        if ($action === 'assign_course') {
            $course_id = (int)($_POST['course_id'] ?? 0);
            $class_id = (int)($_POST['class_id'] ?? 0);
            $teacher_id = (int)($_POST['teacher_id'] ?? 0);

            if ($course_id > 0 && $class_id > 0) {
                assignCourseToClass($course_id, $class_id, $teacher_id ?: null);
                $message = 'Course assigned to class successfully.';
            }
        }

        if ($action === 'remove_assignment') {
            $course_id = (int)($_POST['course_id'] ?? 0);
            $class_id = (int)($_POST['class_id'] ?? 0);

            if ($course_id > 0 && $class_id > 0) {
                removeCourseFromClass($course_id, $class_id);
                $message = 'Course removed from class.';
            }
        }
    }
}

$courses = getAllCourses(true);
$classes = getAllClasses();
$teachers = $db->fetchAll('SELECT id, name FROM users WHERE role = ? ORDER BY name ASC', [ROLE_TEACHER]);
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - <?php echo APP_NAME; ?></title>
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
                <a href="classes.php" class="nav-item"><span class="icon">🏫</span> Classes</a>
                <a href="courses.php" class="nav-item active"><span class="icon">📚</span> Courses</a>
                <a href="notices.php" class="nav-item"><span class="icon">📢</span> Posts & Notices</a>
                <a href="settings.php" class="nav-item"><span class="icon">🔐</span> Security Settings</a>
                <a href="/index.php" class="nav-item"><span class="icon">🏠</span> Home</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-title"><h1>Course Management</h1></div>
                <div class="header-user"><a href="/logout.php" class="btn-logout">Logout</a></div>
            </header>

            <div class="content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>

                <div class="card">
                    <h2>Create New Course</h2>
                    <form method="POST" class="form">
                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label for="code">Course Code *</label>
                                <input type="text" id="code" name="code" placeholder="e.g., BCA101" required>
                            </div>
                            <div class="form-group">
                                <label for="name">Course Name *</label>
                                <input type="text" id="name" name="name" placeholder="e.g., Programming Fundamentals" required>
                            </div>
                        </div>

                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label for="semester">Semester</label>
                                <input type="number" min="1" max="8" id="semester" name="semester" value="1">
                            </div>
                            <div class="form-group">
                                <label for="credits">Credits</label>
                                <input type="number" min="1" max="10" id="credits" name="credits" value="3">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="instructor_id">Instructor (Optional)</label>
                            <select id="instructor_id" name="instructor_id">
                                <option value="">-- Select Instructor --</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo (int)$teacher['id']; ?>"><?php echo htmlspecialchars($teacher['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" placeholder="Course description..."></textarea>
                        </div>

                        <input type="hidden" name="_action" value="create_course">
                        <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-primary">Create Course</button>
                    </form>
                </div>

                <div class="card">
                    <h2>All Courses</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Semester</th>
                                <th>Credits</th>
                                <th>Instructor</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($courses)): ?>
                                <tr><td colspan="7" class="text-center">No courses found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($course['code'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($course['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo (int)$course['semester']; ?></td>
                                        <td><?php echo (int)$course['total_credits']; ?></td>
                                        <td><?php echo htmlspecialchars($course['instructor_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <span class="badge <?php echo (int)$course['is_active'] === 1 ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo (int)$course['is_active'] === 1 ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="_action" value="deactivate_course">
                                                <input type="hidden" name="course_id" value="<?php echo (int)$course['id']; ?>">
                                                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <button class="btn btn-secondary" type="submit"><?php echo (int)$course['is_active'] === 1 ? 'Deactivate' : 'Activate'; ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card">
                    <h2>Assign Course to Class</h2>
                    <form method="POST" class="form">
                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label for="course_id">Course *</label>
                                <select id="course_id" name="course_id" required>
                                    <option value="">-- Select Course --</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo (int)$course['id']; ?>"><?php echo htmlspecialchars($course['code'] . ' - ' . $course['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="class_id">Class *</label>
                                <select id="class_id" name="class_id" required>
                                    <option value="">-- Select Class --</option>
                                    <?php foreach ($classes as $cls): ?>
                                        <option value="<?php echo (int)$cls['id']; ?>"><?php echo htmlspecialchars($cls['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="teacher_id">Assign Teacher (Optional)</label>
                            <select id="teacher_id" name="teacher_id">
                                <option value="">-- Select Teacher --</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo (int)$teacher['id']; ?>"><?php echo htmlspecialchars($teacher['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <input type="hidden" name="_action" value="assign_course">
                        <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-primary">Assign Course</button>
                    </form>
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
