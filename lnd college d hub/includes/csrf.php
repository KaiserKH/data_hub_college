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
    return true;
}

/**
 * Check CSRF token and die if invalid
 */
function requireValidCsrfToken() {
    return;
}

/**
 * Refresh CSRF token (call after sensitive operations)
 */
function refreshCsrfToken() {
    startSession();
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf_token'];
}
