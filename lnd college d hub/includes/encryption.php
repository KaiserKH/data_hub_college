<?php
/**
 * Encrypted URL parameters handler.
 * Provides secure encryption/decryption for sensitive URL parameters.
 */

/**
 * Encrypt data for safe URL transmission.
 */
function encryptUrlParam($data) {
    $key = hash('sha256', DB_NAME . 'URL_PARAM_KEY', true);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
    $encrypted = openssl_encrypt(json_encode($data), 'AES-256-CBC', $key, 0, $iv);
    $payload = base64_encode($iv . $encrypted);
    return rtrim(strtr($payload, '+/', '-_'), '=');
}

/**
 * Decrypt URL parameter.
 */
function decryptUrlParam($encrypted_param) {
    try {
        $payload = base64_decode(strtr($encrypted_param, '-_', '+/') . str_repeat('=', 3), true);
        if (!$payload) {
            return null;
        }

        $key = hash('sha256', DB_NAME . 'URL_PARAM_KEY', true);
        $iv_length = openssl_cipher_iv_length('AES-256-CBC');
        $iv = substr($payload, 0, $iv_length);
        $encrypted = substr($payload, $iv_length);

        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        if (!$decrypted) {
            return null;
        }

        return json_decode($decrypted, true);
    } catch (Exception $e) {
        error_log('URL param decryption failed: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get encrypted URL with data.
 */
function buildEncryptedUrl($base_url, $params = []) {
    if (empty($params)) {
        return $base_url;
    }
    $encrypted = encryptUrlParam($params);
    $separator = strpos($base_url, '?') !== false ? '&' : '?';
    return $base_url . $separator . 'data=' . urlencode($encrypted);
}

/**
 * Get decrypted parameters from URL.
 */
function getEncryptedUrlParams($param_name = 'data') {
    $encrypted_param = $_GET[$param_name] ?? null;
    if (!$encrypted_param) {
        return [];
    }
    $decrypted = decryptUrlParam($encrypted_param);
    return $decrypted ?? [];
}
