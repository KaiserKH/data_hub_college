<?php
/**
 * Authentication Helper Functions
 */

/**
 * Start session if not already started
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    startSession();
    if (!isLoggedIn()) {
        return null;
    }

    $db = getDB();
    $user = $db->fetchOne(
        'SELECT * FROM users WHERE id = ?',
        [$_SESSION['user_id']]
    );

    return $user;
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    startSession();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    startSession();
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    startSession();
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Check if user has one of multiple roles
 */
function hasAnyRole($roles) {
    startSession();
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], (array)$roles);
}

/**
 * Require specific role, redirect if not authorized
 */
function requireRole($role) {
    startSession();
    requireLogin();

    // Role-based access can be toggled by admin security controls.
    if (!isRoleProtectionEnabled()) {
        return;
    }

    if (!hasRole($role)) {
        http_response_code(403);
        die('Access Denied');
    }
}

/**
 * Require one of multiple roles
 */
function requireAnyRole($roles) {
    startSession();
    requireLogin();

    if (!isRoleProtectionEnabled()) {
        return;
    }

    if (!hasAnyRole((array)$roles)) {
        http_response_code(403);
        die('Access Denied');
    }
}

/**
 * Role checks are enforced only when security controls are enabled.
 */
function isRoleProtectionEnabled() {
    if (!function_exists('isSecurityControlsEnabled')) {
        $app_features_file = __DIR__ . '/app_features.php';
        if (is_file($app_features_file)) {
            require_once $app_features_file;
        }
    }

    if (function_exists('isSecurityControlsEnabled')) {
        return isSecurityControlsEnabled();
    }

    return false;
}

/**
 * Require login, redirect to login if not authenticated
 */
function requireLogin() {
    startSession();
    if (!isLoggedIn()) {
        redirect('/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate secure random token
 */
function generateToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Validate password strength
 */
function isStrongPassword($password) {
    $hasUppercase = preg_match('/[A-Z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    $isLongEnough = strlen($password) >= PASSWORD_MIN_LENGTH;

    return $hasUppercase && $hasNumber && $isLongEnough;
}

/**
 * Login user
 */
function loginUser($user_id, $remember_me = false) {
    startSession();
    $db = getDB();

    // Get user data
    $user = $db->fetchOne('SELECT * FROM users WHERE id = ?', [$user_id]);
    if (!$user) {
        return false;
    }

    // Check if user is active
    if (!$user['is_active']) {
        return false;
    }

    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    // Set session data
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];

    // Update last login time
    $db->query(
        'UPDATE users SET last_login = NOW() WHERE id = ?',
        [$user_id]
    );

    // Handle remember me
    if ($remember_me) {
        $token = generateToken();
        $expiry = time() + REMEMBER_TOKEN_LIFETIME;

        // Store token in database
        $db->query(
            'UPDATE users SET remember_token = ? WHERE id = ?',
            [$token, $user_id]
        );

        // Set secure cookie
        setcookie(
            'remember_token',
            $token,
            $expiry,
            '/',
            $_SERVER['HTTP_HOST'],
            true, // secure (only HTTPS)
            true  // httpOnly
        );
    }

    return true;
}

/**
 * Logout user
 */
function logoutUser() {
    startSession();

    // Clear remember token cookie
    if (isset($_COOKIE['remember_token'])) {
        $db = getDB();
        if (isLoggedIn()) {
            $db->query('UPDATE users SET remember_token = NULL WHERE id = ?', [getCurrentUserId()]);
        }
        setcookie('remember_token', '', time() - 3600, '/', $_SERVER['HTTP_HOST'], true, true);
    }

    // Destroy session
    $_SESSION = [];
    session_destroy();
}

/**
 * Check remember token
 */
function checkRememberToken() {
    if (!isset($_COOKIE['remember_token'])) {
        return false;
    }

    $db = getDB();
    $token = $_COOKIE['remember_token'];

    $user = $db->fetchOne(
        'SELECT id FROM users WHERE remember_token = ? AND is_active = 1',
        [$token]
    );

    if ($user) {
        loginUser($user['id']);
        return true;
    }

    return false;
}

/**
 * Check login attempt rate limiting
 */
function checkLoginAttempts($email) {
    $db = getDB();
    $cache_key = 'login_attempts_' . md5($email);

    // Use database for persistent storage
    $attempts = $db->fetchOne(
        'SELECT failed_attempts, lockout_until FROM login_attempts WHERE email = ?',
        [$email]
    );

    if ($attempts) {
        if ($attempts['lockout_until'] && strtotime($attempts['lockout_until']) > time()) {
            return false; // Still locked out
        }
    }

    return true;
}

/**
 * Record login attempt failure
 */
function recordFailedLoginAttempt($email) {
    $db = getDB();

    $attempts = $db->fetchOne(
        'SELECT failed_attempts FROM login_attempts WHERE email = ?',
        [$email]
    );

    if (!$attempts) {
        $db->query(
            'INSERT INTO login_attempts (email, failed_attempts, last_attempt) VALUES (?, 1, NOW())',
            [$email]
        );
    } else {
        $new_attempts = $attempts['failed_attempts'] + 1;
        $lockout_until = null;

        if ($new_attempts >= LOGIN_ATTEMPTS_MAX) {
            $lockout_until = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
        }

        $db->query(
            'UPDATE login_attempts SET failed_attempts = ?, lockout_until = ?, last_attempt = NOW() WHERE email = ?',
            [$new_attempts, $lockout_until, $email]
        );
    }
}

/**
 * Clear login attempts
 */
function clearLoginAttempts($email) {
    $db = getDB();
    $db->query('DELETE FROM login_attempts WHERE email = ?', [$email]);
}

/**
 * Get user by email
 */
function getUserByEmail($email) {
    $db = getDB();
    return $db->fetchOne('SELECT * FROM users WHERE email = ? AND is_active = 1', [$email]);
}

/**
 * Get user by ID
 */
function getUserById($id) {
    $db = getDB();
    return $db->fetchOne('SELECT * FROM users WHERE id = ?', [$id]);
}

/**
 * Create new user (admin only)
 */
function createUser($name, $email, $password, $role, $class_id = null, $roll_number = null) {
    if (!isStrongPassword($password)) {
        return ['success' => false, 'message' => 'Password too weak'];
    }

    $db = getDB();

    // Check if email exists
    if ($db->fetchOne('SELECT id FROM users WHERE email = ?', [$email])) {
        return ['success' => false, 'message' => 'Email already exists'];
    }

    try {
        $hash = hashPassword($password);
        $db->query(
            'INSERT INTO users (name, email, password, role, class_id, roll_number, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())',
            [$name, $email, $hash, $role, $class_id, $roll_number]
        );

        auditLog('create_user', 'user', $db->lastInsertId(), "Created user: $name ($email) with role: $role");

        return ['success' => true, 'message' => 'User created successfully', 'user_id' => $db->lastInsertId()];
    } catch (Exception $e) {
        error_log("Error creating user: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create user'];
    }
}
