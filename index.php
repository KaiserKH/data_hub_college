<?php
/**
 * Main Index/Dashboard Page
 */
require 'config.php';
require 'includes/auth.php';
require 'includes/functions.php';

// Require login
requireLogin();

$user = getCurrentUser();
$user_role = getCurrentUserRole();

// Role-based redirects
if ($user_role === ROLE_ADMIN) {
    header('Location: /admin/dashboard.php');
    exit;
} elseif ($user_role === ROLE_TEACHER) {
    header('Location: /teacher/dashboard.php');
    exit;
} elseif ($user_role === ROLE_STUDENT) {
    header('Location: /student/dashboard.php');
    exit;
}

// Fallback if role is unexpected
header('Location: /login.php');
exit;
?>
