<?php
/**
 * File Upload Handler
 */

class FileUploadHandler {
    private $upload_dir;
    private $allowed_extensions;
    private $max_file_size;

    public function __construct($allowed_extensions = ALLOWED_FILE_TYPES, $max_file_size = MAX_FILE_SIZE_BYTES) {
        $this->allowed_extensions = $allowed_extensions;
        $this->max_file_size = $max_file_size;
    }

    /**
     * Validate uploaded file
     */
    public function validate($file) {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file uploaded'];
        }

        // Check file size
        if ($file['size'] > $this->max_file_size) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum of ' . formatBytes($this->max_file_size)
            ];
        }

        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowed_extensions)) {
            return [
                'valid' => false,
                'error' => 'File type not allowed. Allowed types: ' . implode(', ', $this->allowed_extensions)
            ];
        }

        // Check MIME type
        $allowed_mimes = array_flip(ALLOWED_MIME_TYPES);
        if (!validateFileMimeType($file['tmp_name'], array_keys($allowed_mimes))) {
            return [
                'valid' => false,
                'error' => 'File MIME type not allowed'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Process and save uploaded file
     */
    public function process($file, $destination_dir) {
        // Validate
        $validation = $this->validate($file);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }

        // Create destination directory if needed
        if (!is_dir($destination_dir)) {
            if (!mkdir($destination_dir, 0755, true)) {
                return ['success' => false, 'message' => 'Failed to create upload directory'];
            }
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $original_name = pathinfo($file['name'], PATHINFO_FILENAME);
        $new_filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $new_path = $destination_dir . '/' . $new_filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $new_path)) {
            return ['success' => false, 'message' => 'Failed to process uploaded file'];
        }

        // Set proper permissions
        chmod($new_path, 0644);

        return [
            'success' => true,
            'filename' => $new_filename,
            'original_name' => $original_name,
            'path' => $new_path,
            'relative_path' => str_replace(UPLOADS_PATH, '', $new_path)
        ];
    }

    /**
     * Delete uploaded file
     */
    public function delete($file_path) {
        $full_path = UPLOADS_PATH . $file_path;

        if (!file_exists($full_path)) {
            return ['success' => false, 'message' => 'File not found'];
        }

        // Prevent directory traversal
        $real_path = realpath($full_path);
        $uploads_real_path = realpath(UPLOADS_PATH);

        if (strpos($real_path, $uploads_real_path) !== 0) {
            return ['success' => false, 'message' => 'Invalid file path'];
        }

        if (!unlink($full_path)) {
            return ['success' => false, 'message' => 'Failed to delete file'];
        }

        return ['success' => true, 'message' => 'File deleted successfully'];
    }

    /**
     * Download file
     */
    public static function download($file_path) {
        $full_path = UPLOADS_PATH . $file_path;

        // Prevent directory traversal
        $real_path = realpath($full_path);
        $uploads_real_path = realpath(UPLOADS_PATH);

        if (!$real_path || strpos($real_path, $uploads_real_path) !== 0) {
            http_response_code(403);
            die('Access denied');
        }

        if (!file_exists($full_path)) {
            http_response_code(404);
            die('File not found');
        }

        $filename = basename($full_path);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Determine MIME type
        $mime_types = array_flip(ALLOWED_MIME_TYPES);
        $mime_type = isset($mime_types[$extension]) ? $mime_types[$extension] : 'application/octet-stream';

        // Set headers
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($full_path));
        header('Cache-Control: no-cache, no-store, must-revalidate');

        // Output file
        readfile($full_path);
        exit;
    }
}

/**
 * Get file upload handler instance
 */
function getUploadHandler($allowed_extensions = null, $max_file_size = null) {
    return new FileUploadHandler(
        $allowed_extensions ?? ALLOWED_FILE_TYPES,
        $max_file_size ?? MAX_FILE_SIZE_BYTES
    );
}
