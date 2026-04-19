<?php
/**
 * Global Utility Functions
 */

/**
 * Redirect to URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Get the web path prefix where this app is running (e.g. /subdir/app).
 */
function getAppUrlPrefix() {
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = str_replace('\\', '/', dirname($script_name));

    if ($dir === '/' || $dir === '.' || $dir === '\\') {
        return '';
    }

    return rtrim($dir, '/');
}

/**
 * Build a safe local redirect target and fall back if target is invalid.
 */
function getSafeRedirectPath($target, $default = '/index.php') {
    if (!is_string($target) || trim($target) === '') {
        return $default;
    }

    $parsed = @parse_url($target);
    if ($parsed === false || isset($parsed['scheme']) || isset($parsed['host'])) {
        return $default;
    }

    $path = $parsed['path'] ?? '';
    if ($path === '') {
        $path = $default;
    }
    if ($path[0] !== '/') {
        $path = '/' . $path;
    }

    $path = preg_replace('#/+#', '/', $path);
    if ($path === null || strpos($path, "\0") !== false || strpos($path, '..') !== false) {
        return $default;
    }

    $prefix = getAppUrlPrefix();
    $check_path = $path;
    if ($prefix !== '') {
        if ($check_path === $prefix) {
            $check_path = '/';
        } elseif (strpos($check_path, $prefix . '/') === 0) {
            $check_path = substr($check_path, strlen($prefix));
        }
    }

    $base_real = realpath(BASE_PATH);
    if ($base_real === false) {
        return $default;
    }

    $fs_path = $check_path === '/'
        ? $base_real . '/index.php'
        : $base_real . '/' . ltrim($check_path, '/');

    $fs_real = realpath($fs_path);
    if ($fs_real === false) {
        return $default;
    }

    if (strpos($fs_real, $base_real) !== 0) {
        return $default;
    }

    if (is_dir($fs_real)) {
        $dir_index = realpath($fs_real . '/index.php');
        if ($dir_index === false || !is_file($dir_index)) {
            return $default;
        }
        $fs_real = $dir_index;
    } elseif (!is_file($fs_real)) {
        return $default;
    }

    $safe_path = '/' . ltrim(str_replace('\\', '/', substr($fs_real, strlen($base_real))), '/');
    $query = isset($parsed['query']) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';

    return $safe_path . $query;
}

/**
 * Return JSON response
 */
function jsonResponse($success, $data = null, $message = null, $code = 200) {
    header('Content-Type: application/json');
    http_response_code($code);

    $response = [
        'success' => (bool)$success,
        'data' => $data,
        'message' => $message
    ];

    // Remove null values to keep JSON clean
    if ($data === null) {
        unset($response['data']);
    }
    if ($message === null) {
        unset($response['message']);
    }

    echo json_encode($response);
    exit;
}

/**
 * Get user's class information
 */
function getUserClass($user_id) {
    $db = getDB();
    $user = $db->fetchOne(
        'SELECT c.* FROM classes c 
         JOIN users u ON u.class_id = c.id 
         WHERE u.id = ?',
        [$user_id]
    );
    return $user;
}

/**
 * Get class by ID
 */
function getClassById($class_id) {
    $db = getDB();
    return $db->fetchOne('SELECT * FROM classes WHERE id = ?', [$class_id]);
}

/**
 * Get all classes
 */
function getAllClasses() {
    $db = getDB();
    return $db->fetchAll('SELECT * FROM classes ORDER BY name ASC');
}

/**
 * Get form by ID
 */
function getFormById($form_id) {
    $db = getDB();
    return $db->fetchOne('SELECT * FROM forms WHERE id = ?', [$form_id]);
}

/**
 * Get form by UUID
 */
function getFormByUuid($uuid) {
    $db = getDB();
    return $db->fetchOne('SELECT * FROM forms WHERE uuid = ?', [$uuid]);
}

/**
 * Get form questions
 */
function getFormQuestions($form_id) {
    $db = getDB();
    return $db->fetchAll(
        'SELECT * FROM form_questions WHERE form_id = ? ORDER BY sort_order ASC',
        [$form_id]
    );
}

/**
 * Decode question options from JSON
 */
function decodeQuestionOptions($json_options) {
    if (!$json_options) {
        return [];
    }
    $options = json_decode($json_options, true);
    return is_array($options) ? $options : [];
}

/**
 * Encode question options to JSON
 */
function encodeQuestionOptions($options) {
    return json_encode($options, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * Get form responses count
 */
function getFormResponseCount($form_id) {
    $db = getDB();
    $result = $db->fetchOne(
        'SELECT COUNT(*) as count FROM responses WHERE form_id = ?',
        [$form_id]
    );
    return $result['count'] ?? 0;
}

/**
 * Check if user has already responded to form
 */
function hasUserResponded($form_id, $user_id) {
    $db = getDB();
    $response = $db->fetchOne(
        'SELECT id FROM responses WHERE form_id = ? AND user_id = ?',
        [$form_id, $user_id]
    );
    return $response !== null;
}

/**
 * Get student's responses for a form
 */
function getUserResponses($form_id, $user_id) {
    $db = getDB();
    return $db->fetchAll(
        'SELECT * FROM responses WHERE form_id = ? AND user_id = ? ORDER BY submitted_at DESC',
        [$form_id, $user_id]
    );
}

/**
 * Get response data
 */
function getResponseData($response_id) {
    $db = getDB();
    $response = $db->fetchOne('SELECT * FROM responses WHERE id = ?', [$response_id]);

    if (!$response) {
        return null;
    }

    $answers = $db->fetchAll(
        'SELECT a.*, fq.question_text, fq.question_type FROM answers a 
         JOIN form_questions fq ON a.question_id = fq.id 
         WHERE a.response_id = ? 
         ORDER BY fq.sort_order ASC',
        [$response_id]
    );

    $response['answers'] = $answers;
    return $response;
}

/**
 * Generate UUID v4
 */
function generateUuid() {
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

/**
 * Format bytes as human readable
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M d, Y') {
    if (!$date) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = 'M d, Y h:i A') {
    if (!$datetime) {
        return '';
    }
    return date($format, strtotime($datetime));
}

/**
 * Format time remaining until deadline
 */
function formatTimeRemaining($deadline) {
    $now = time();
    $deadline_time = strtotime($deadline);

    if ($deadline_time <= $now) {
        return 'Closed';
    }

    $diff = $deadline_time - $now;

    if ($diff < 60) {
        return 'Less than 1 minute';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes !== 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours !== 1 ? 's' : '');
    } else {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days !== 1 ? 's' : '');
    }
}

/**
 * Check if deadline has passed
 */
function isDeadlinePassed($deadline) {
    return strtotime($deadline) <= time();
}

/**
 * Check if form should be closed (deadline passed)
 */
function shouldFormBeClosed($form) {
    if ($form['status'] === FORM_STATUS_CLOSED) {
        return false;
    }

    if ($form['deadline'] && isDeadlinePassed($form['deadline'])) {
        return true;
    }

    return false;
}

/**
 * Auto-close forms with passed deadlines
 */
function autoCloseExpiredForms() {
    $db = getDB();
    $db->query(
        "UPDATE forms SET status = ? WHERE status = ? AND deadline IS NOT NULL AND deadline < NOW()",
        [FORM_STATUS_CLOSED, FORM_STATUS_ACTIVE]
    );
}

/**
 * Log audit trail
 */
function auditLog($action, $entity_type, $entity_id, $details = null) {
    try {
        $db = getDB();
        $user_id = getCurrentUserId();
        $ip_address = getClientIp();

        $db->query(
            'INSERT INTO audit_log (user_id, action, entity_type, entity_id, ip_address, details, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$user_id, $action, $entity_type, $entity_id, $ip_address, $details]
        );
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

/**
 * Get client IP address
 */
function getClientIp() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        return $_SERVER['HTTP_X_FORWARDED'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
        return $_SERVER['HTTP_FORWARDED'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

/**
 * Send notification to user
 */
function sendNotification($user_id, $type, $message, $related_id = null) {
    try {
        $db = getDB();
        $db->query(
            'INSERT INTO notifications (user_id, type, message, related_id, created_at) 
             VALUES (?, ?, ?, ?, NOW())',
            [$user_id, $type, $message, $related_id]
        );
        return true;
    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notifications count
 */
function getUnreadNotificationsCount($user_id = null) {
    if ($user_id === null) {
        $user_id = getCurrentUserId();
    }

    if (!$user_id) {
        return 0;
    }

    $db = getDB();
    $result = $db->fetchOne(
        'SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0',
        [$user_id]
    );

    return $result['count'] ?? 0;
}

/**
 * Get notifications for user
 */
function getUserNotifications($user_id, $limit = 10) {
    $db = getDB();
    return $db->fetchAll(
        'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?',
        [$user_id, $limit]
    );
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notification_id, $user_id) {
    $db = getDB();
    $db->query(
        'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?',
        [$notification_id, $user_id]
    );
}

/**
 * Mark all notifications as read for user
 */
function markAllNotificationsAsRead($user_id) {
    $db = getDB();
    $db->query(
        'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0',
        [$user_id]
    );
}

/**
 * Error page
 */
function errorPage($code = 404, $message = null) {
    http_response_code($code);
    $default_messages = [
        400 => 'Bad Request',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error'
    ];

    $title = $default_messages[$code] ?? 'Error';
    $message = $message ?? $default_messages[$code];

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $code; ?> - <?php echo $title; ?></title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #333;
            }
            .container {
                text-align: center;
                background: white;
                padding: 2rem;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
                max-width: 500px;
            }
            h1 { 
                font-size: 3rem; 
                margin: 0; 
                color: #667eea;
            }
            p { 
                font-size: 1.1rem; 
                margin: 1rem 0;
            }
            a {
                display: inline-block;
                margin-top: 1rem;
                padding: 0.75rem 1.5rem;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                transition: background 0.3s;
            }
            a:hover {
                background: #764ba2;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1><?php echo $code; ?></h1>
            <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
            <a href="/">← Back to Home</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Get average time to complete form (in seconds)
 */
function getFormAverageCompletionTime($form_id) {
    $db = getDB();
    $result = $db->fetchOne(
        'SELECT AVG(UNIX_TIMESTAMP(updated_at) - UNIX_TIMESTAMP(created_at)) as avg_seconds 
         FROM responses 
         WHERE form_id = ? AND is_complete = 1',
        [$form_id]
    );

    return $result['avg_seconds'] ? (int)$result['avg_seconds'] : 0;
}

/**
 * Format seconds to readable time
 */
function formatSeconds($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;

    $parts = [];
    if ($hours > 0) {
        $parts[] = $hours . 'h';
    }
    if ($minutes > 0) {
        $parts[] = $minutes . 'm';
    }
    if ($secs > 0 || count($parts) === 0) {
        $parts[] = $secs . 's';
    }

    return implode(' ', $parts);
}
