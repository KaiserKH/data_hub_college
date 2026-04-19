<?php
/**
 * CSRF Token Helper Functions
 */

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    startSession();
    if (!isset($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Get CSRF token for use in forms
 */
function getCsrfTokenField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate CSRF token from POST/PUT request
 */
function validateCsrfToken($token = null) {
    startSession();

    if ($token === null) {
        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }

    if (!isset($_SESSION['_csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['_csrf_token'], $token ?? '');
}

/**
 * Check CSRF token and die if invalid
 */
function requireValidCsrfToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
        if (!validateCsrfToken()) {
            header('Content-Type: application/json');
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => 'CSRF token invalid']));
        }
    }
}

/**
 * Refresh CSRF token (call after sensitive operations)
 */
function refreshCsrfToken() {
    startSession();
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf_token'];
}
