<?php
/**
 * College Data Hub - Configuration File
 * Central configuration for database, paths, and app constants
 */

// Environment
define('ENV', getenv('APP_ENV') ?: 'development');
define('DEBUG', ENV === 'development');

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'college_data_hub');
define('DB_PORT', getenv('DB_PORT') ?: 3306);

// Application Paths
define('BASE_PATH', dirname(__FILE__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ADMIN_PATH', BASE_PATH . '/admin');
define('TEACHER_PATH', BASE_PATH . '/teacher');
define('STUDENT_PATH', BASE_PATH . '/student');
define('API_PATH', BASE_PATH . '/api');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('UPLOADS_PATH', ASSETS_PATH . '/uploads');

// URLs
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost');
define('UPLOAD_URL', BASE_URL . '/assets/uploads');

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('REMEMBER_TOKEN_LIFETIME', 30 * 24 * 3600); // 30 days
define('LOGIN_ATTEMPTS_MAX', 5);
define('LOGIN_LOCKOUT_TIME', 15 * 60); // 15 minutes

// File Upload Configuration
define('MAX_FILE_SIZE_BYTES', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png', 'docx']);
define('ALLOWED_MIME_TYPES', [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
]);

// Application Constants
define('APP_NAME', 'College Data Hub');
define('APP_VERSION', '1.0.0');

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_TEACHER', 'teacher');
define('ROLE_STUDENT', 'student');

// Form Status
define('FORM_STATUS_DRAFT', 'draft');
define('FORM_STATUS_ACTIVE', 'active');
define('FORM_STATUS_CLOSED', 'closed');

// Question Types
define('QUESTION_TYPES', [
    'short' => 'Short Text',
    'paragraph' => 'Paragraph',
    'multiple_choice' => 'Multiple Choice',
    'checkbox' => 'Checkboxes',
    'dropdown' => 'Dropdown',
    'date' => 'Date',
    'rating' => 'Rating (1-5)',
    'file' => 'File Upload'
]);

// Notification Types
define('NOTIFICATION_NEW_FORM', 'new_form');
define('NOTIFICATION_DEADLINE_REMINDER', 'deadline_reminder');
define('NOTIFICATION_RESPONSE_RECEIVED', 'response_received');

// Password Requirements
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REGEX', '/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])?.{' . PASSWORD_MIN_LENGTH . ',}$/');

// Email Configuration
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@collegdatahub.local');
define('MAIL_FROM_NAME', APP_NAME);

// Session Configuration (PHP settings)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_secure', ENV !== 'development' ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Ensure uploads directory is writable
if (!is_dir(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0755, true);
}
if (!is_writable(UPLOADS_PATH)) {
    // Try to make it writable
    @chmod(UPLOADS_PATH, 0755);
}

/**
 * Error handling
 */
if (!DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

/**
 * Helper function to get CSRF token
 */
if (!function_exists('getCsrfToken')) {
    function getCsrfToken() {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}
