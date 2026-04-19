<?php
/**
 * Input Validation and Sanitization Functions
 */

/**
 * Sanitize string for HTML display
 */
function sanitizeString($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize HTML (allows basic tags)
 */
function sanitizeHtml($str) {
    return strip_tags($str, '<p><br><strong><em><u><i><b><ul><ol><li>');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate URL
 */
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validate integer
 */
function validateInteger($value) {
    return filter_var($value, FILTER_VALIDATE_INT) !== false;
}

/**
 * Validate date format (YYYY-MM-DD)
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate datetime format (YYYY-MM-DD HH:MM:SS)
 */
function validateDateTime($datetime) {
    return validateDate($datetime, 'Y-m-d H:i:s');
}

/**
 * Validate UUID format
 */
function validateUuid($uuid) {
    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    return preg_match($pattern, $uuid) === 1;
}

/**
 * Validate JSON
 */
function validateJson($json) {
    json_decode($json);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Trim and validate non-empty string
 */
function validateRequired($value) {
    return !empty(trim($value ?? ''));
}

/**
 * Validate string length
 */
function validateLength($str, $min = 0, $max = null) {
    $len = strlen($str);
    if ($len < $min) {
        return false;
    }
    if ($max !== null && $len > $max) {
        return false;
    }
    return true;
}

/**
 * Validate regular expression match
 */
function validateRegex($str, $pattern) {
    return preg_match($pattern, $str) === 1;
}

/**
 * Validate in array
 */
function validateInArray($value, $array) {
    return in_array($value, $array, true);
}

/**
 * Get request parameter safely
 */
function getParam($name, $default = null, $method = null) {
    if ($method === 'GET') {
        return $_GET[$name] ?? $default;
    } elseif ($method === 'POST') {
        return $_POST[$name] ?? $default;
    } elseif ($method === 'REQUEST') {
        return $_REQUEST[$name] ?? $default;
    }

    // Auto-detect from global arrays (prefer POST)
    if (isset($_POST[$name])) {
        return $_POST[$name];
    }
    if (isset($_GET[$name])) {
        return $_GET[$name];
    }
    if (isset($_REQUEST[$name])) {
        return $_REQUEST[$name];
    }

    return $default;
}

/**
 * Get JSON input from request body
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

/**
 * Sanitize filename
 */
function sanitizeFilename($filename) {
    // Remove path components
    $filename = basename($filename);
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    return $filename;
}

/**
 * Validate file upload MIME type
 */
function validateFileMimeType($file_tmp, $allowed_mimes) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file_tmp);
    finfo_close($finfo);

    return in_array($mime, $allowed_mimes);
}

/**
 * Validate file upload extension
 */
function validateFileExtension($filename, $allowed_extensions) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $allowed_extensions);
}

/**
 * Validate file upload size
 */
function validateFileSize($size, $max_bytes) {
    return $size > 0 && $size <= $max_bytes;
}

/**
 * Escape for SQL (should use prepared statements, but this is fallback)
 */
function escapeSql($str) {
    // Do NOT use this - use prepared statements instead
    // This is only a last resort
    $db = getDB();
    return $db->getConnection()->quote($str);
}

/**
 * Build validation error message
 */
function getValidationError($field, $rule) {
    $messages = [
        'required' => "$field is required",
        'email' => "$field must be a valid email",
        'min_length' => "$field is too short",
        'max_length' => "$field is too long",
        'numeric' => "$field must be numeric",
        'date' => "$field must be a valid date",
        'url' => "$field must be a valid URL",
        'regex' => "$field format is invalid",
        'in_array' => "$field value is not allowed",
        'unique' => "$field already exists",
    ];

    return $messages[$rule] ?? "Validation failed for $field";
}

/**
 * Validate form input array
 */
function validateFormInput($data, $rules) {
    $errors = [];

    foreach ($rules as $field => $field_rules) {
        $value = $data[$field] ?? null;
        $field_rules = explode('|', $field_rules);

        foreach ($field_rules as $rule) {
            $rule = trim($rule);

            if ($rule === 'required' && !validateRequired($value)) {
                $errors[$field] = getValidationError($field, 'required');
                break;
            } elseif (strpos($rule, 'min_length:') === 0) {
                $min = (int)substr($rule, 11);
                if (validateRequired($value) && !validateLength($value, $min)) {
                    $errors[$field] = getValidationError($field, 'min_length');
                    break;
                }
            } elseif (strpos($rule, 'max_length:') === 0) {
                $max = (int)substr($rule, 11);
                if (validateRequired($value) && !validateLength($value, 0, $max)) {
                    $errors[$field] = getValidationError($field, 'max_length');
                    break;
                }
            } elseif ($rule === 'email' && validateRequired($value) && !validateEmail($value)) {
                $errors[$field] = getValidationError($field, 'email');
                break;
            } elseif ($rule === 'numeric' && validateRequired($value) && !validateInteger($value)) {
                $errors[$field] = getValidationError($field, 'numeric');
                break;
            } elseif ($rule === 'date' && validateRequired($value) && !validateDate($value)) {
                $errors[$field] = getValidationError($field, 'date');
                break;
            }
        }
    }

    return $errors;
}
