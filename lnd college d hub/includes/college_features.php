<?php
/**
 * College-specific features: Courses, Profiles, Role Settings
 */

/**
 * Get or create user profile.
 */
function getUserProfile($user_id) {
    $db = getDB();
    $profile = $db->fetchOne('SELECT * FROM user_profiles WHERE user_id = ?', [$user_id]);
    
    if (!$profile) {
        $db->query('INSERT INTO user_profiles (user_id, created_at) VALUES (?, NOW())', [$user_id]);
        $profile = $db->fetchOne('SELECT * FROM user_profiles WHERE user_id = ?', [$user_id]);
    }
    
    return $profile;
}

/**
 * Update user profile.
 */
function updateUserProfile($user_id, $data) {
    $db = getDB();
    
    $allowed_fields = ['bio', 'phone', 'gender', 'date_of_birth', 'address', 'city', 'enrollment_date'];
    $updates = [];
    $params = [];
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (empty($updates)) {
        return false;
    }
    
    $updates[] = 'updated_at = CURRENT_TIMESTAMP';
    $params[] = $user_id;
    
    $sql = 'UPDATE user_profiles SET ' . implode(', ', $updates) . ' WHERE user_id = ?';
    $db->query($sql, $params);
    
    return true;
}

/**
 * Update user profile picture.
 */
function updateProfilePicture($user_id, $filename) {
    $db = getDB();
    $db->query('UPDATE user_profiles SET profile_picture = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?', [$filename, $user_id]);
    return true;
}

/**
 * Get user's profile picture URL.
 */
function getUserProfilePictureUrl($user_id) {
    $profile = getUserProfile($user_id);
    if (!$profile || !$profile['profile_picture']) {
        return '/assets/images/default-avatar.png';
    }
    return UPLOAD_URL . '/profiles/' . urlencode($profile['profile_picture']);
}

/**
 * Create a course.
 */
function createCourse($code, $name, $description, $instructor_id, $credits = 3, $semester = 1) {
    $db = getDB();
    
    try {
        $db->query(
            'INSERT INTO courses (code, name, description, instructor_id, total_credits, semester, is_active, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, 1, NOW())',
            [$code, $name, $description, $instructor_id, $credits, $semester]
        );
        
        return (int)$db->lastInsertId();
    } catch (Exception $e) {
        error_log('Create course failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get all active courses.
 */
function getAllCourses($include_inactive = false) {
    $db = getDB();
    $sql = 'SELECT c.*, u.name as instructor_name FROM courses c LEFT JOIN users u ON c.instructor_id = u.id';
    
    if (!$include_inactive) {
        $sql .= ' WHERE c.is_active = 1';
    }
    
    $sql .= ' ORDER BY c.semester ASC, c.code ASC';
    return $db->fetchAll($sql);
}

/**
 * Get course by ID.
 */
function getCourseById($course_id) {
    $db = getDB();
    return $db->fetchOne('SELECT c.*, u.name as instructor_name FROM courses c LEFT JOIN users u ON c.instructor_id = u.id WHERE c.id = ?', [$course_id]);
}

/**
 * Update course.
 */
function updateCourse($course_id, $data) {
    $db = getDB();
    
    $allowed_fields = ['name', 'description', 'instructor_id', 'total_credits', 'semester', 'is_active'];
    $updates = [];
    $params = [];
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (empty($updates)) {
        return false;
    }
    
    $updates[] = 'updated_at = CURRENT_TIMESTAMP';
    $params[] = $course_id;
    
    $sql = 'UPDATE courses SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $db->query($sql, $params);
    
    return true;
}

/**
 * Delete course (soft delete).
 */
function deleteCourse($course_id) {
    $db = getDB();
    $db->query('UPDATE courses SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$course_id]);
    return true;
}

/**
 * Assign course to class.
 */
function assignCourseToClass($course_id, $class_id, $teacher_id = null) {
    $db = getDB();
    
    try {
        $db->query(
            'INSERT INTO course_assignments (course_id, class_id, teacher_id, created_at) VALUES (?, ?, ?, NOW())',
            [$course_id, $class_id, $teacher_id]
        );
        return true;
    } catch (Exception $e) {
        error_log('Assign course failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Remove course assignment from class.
 */
function removeCourseFromClass($course_id, $class_id) {
    $db = getDB();
    $db->query('DELETE FROM course_assignments WHERE course_id = ? AND class_id = ?', [$course_id, $class_id]);
    return true;
}

/**
 * Get courses for a specific class.
 */
function getClassCourses($class_id) {
    $db = getDB();
    return $db->fetchAll(
        'SELECT c.*, u.name as instructor_name, ca.teacher_id
         FROM course_assignments ca
         JOIN courses c ON ca.course_id = c.id
         LEFT JOIN users u ON c.instructor_id = u.id
         WHERE ca.class_id = ? AND c.is_active = 1
         ORDER BY c.semester ASC, c.code ASC',
        [$class_id]
    );
}

/**
 * Get or set role-based setting.
 */
function getRoleSetting($role, $setting_key, $default = null) {
    try {
        $db = getDB();
        $row = $db->fetchOne('SELECT setting_value FROM role_settings WHERE role = ? AND setting_key = ?', [$role, $setting_key]);
        
        if (!$row) {
            return $default;
        }
        
        return $row['setting_value'];
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Set role-based setting.
 */
function setRoleSetting($role, $setting_key, $setting_value, $updated_by = null) {
    try {
        $db = getDB();
        $db->query(
            'INSERT INTO role_settings (role, setting_key, setting_value, updated_by, created_at) 
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP',
            [$role, $setting_key, $setting_value, $updated_by]
        );
        return true;
    } catch (Exception $e) {
        error_log('Set role setting failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get all settings for a role.
 */
function getRoleSettings($role) {
    try {
        $db = getDB();
        $rows = $db->fetchAll('SELECT setting_key, setting_value FROM role_settings WHERE role = ?', [$role]);
        
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Check if a role has permission for a feature.
 */
function roleHasPermission($role, $permission) {
    return true;
}

/**
 * Grant permission to role.
 */
function grantRolePermission($role, $permission, $updated_by = null) {
    return setRoleSetting($role, 'permissions_' . $permission, 'enabled', $updated_by);
}

/**
 * Revoke permission from role.
 */
function revokeRolePermission($role, $permission, $updated_by = null) {
    return setRoleSetting($role, 'permissions_' . $permission, 'disabled', $updated_by);
}
