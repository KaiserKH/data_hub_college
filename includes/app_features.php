<?php
/**
 * Optional security, notices, and attendance feature helpers.
 */

/**
 * Get setting value.
 */
function getAppSetting($key, $default = null) {
    try {
        $db = getDB();
        $row = $db->fetchOne('SELECT setting_value FROM app_settings WHERE setting_key = ?', [$key]);
        if (!$row) {
            return $default;
        }
        return $row['setting_value'];
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Get a boolean setting.
 */
function getAppSettingBool($key, $default = false) {
    $value = getAppSetting($key, $default ? '1' : '0');
    return in_array((string)$value, ['1', 'true', 'on', 'yes'], true);
}

/**
 * Save setting value.
 */
function setAppSetting($key, $value, $updatedBy = null) {
    try {
        $db = getDB();
        $db->query(
            'INSERT INTO app_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?)\n             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP',
            [$key, (string)$value, $updatedBy]
        );
        return true;
    } catch (Exception $e) {
        error_log('Failed to save app setting: ' . $e->getMessage());
        return false;
    }
}

/**
 * Determine if security controls are globally enabled.
 */
function isSecurityControlsEnabled() {
    return getAppSettingBool('security_controls_enabled', true);
}

/**
 * Determine if 2FA should be enforced.
 */
function isTwoFactorEnabled() {
    return isSecurityControlsEnabled() && getAppSettingBool('two_factor_enabled', false);
}

/**
 * Determine if attendance feature is enabled.
 */
function isAttendanceEnabled() {
    return getAppSettingBool('attendance_enabled', false);
}

/**
 * Basic email sender wrapper.
 */
function sendAppEmail($to, $subject, $message) {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/plain; charset=UTF-8';
    $headers[] = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>';

    $sent = @mail($to, $subject, $message, implode("\r\n", $headers));
    if (!$sent) {
        error_log('Mail send failed to: ' . $to . ' | Subject: ' . $subject);
    }

    return $sent;
}

/**
 * Create and send OTP for 2FA verification.
 */
function createTwoFactorChallenge($user) {
    $db = getDB();
    $code = (string)random_int(100000, 999999);
    $hash = password_hash($code, PASSWORD_BCRYPT);
    $expires_at = date('Y-m-d H:i:s', time() + 10 * 60);

    $db->query('DELETE FROM user_2fa_codes WHERE user_id = ? AND (is_used = 1 OR expires_at < NOW())', [$user['id']]);
    $db->query(
        'INSERT INTO user_2fa_codes (user_id, code_hash, expires_at) VALUES (?, ?, ?)',
        [$user['id'], $hash, $expires_at]
    );

    $subject = APP_NAME . ' login verification code';
    $body = "Hello {$user['name']},\n\n"
        . "Your verification code is: {$code}\n"
        . "This code will expire in 10 minutes.\n\n"
        . "If this was not you, please contact the administrator.\n";

    sendAppEmail($user['email'], $subject, $body);
    return true;
}

/**
 * Verify OTP code for a user.
 */
function verifyTwoFactorChallenge($user_id, $code) {
    $db = getDB();
    $rows = $db->fetchAll(
        'SELECT * FROM user_2fa_codes WHERE user_id = ? AND is_used = 0 AND expires_at >= NOW() ORDER BY id DESC LIMIT 3',
        [$user_id]
    );

    foreach ($rows as $row) {
        if (password_verify($code, $row['code_hash'])) {
            $db->query('UPDATE user_2fa_codes SET is_used = 1 WHERE id = ?', [$row['id']]);
            return true;
        }
    }

    return false;
}

/**
 * Create admin notice/post.
 */
function createNoticePost($title, $content, $audience, $publishedBy) {
    try {
        $db = getDB();
        $db->query(
            'INSERT INTO notices (title, content, audience, published_by, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())',
            [$title, $content, $audience, $publishedBy]
        );

        return (int)$db->lastInsertId();
    } catch (Exception $e) {
        error_log('Failed to create notice: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Fetch notices visible to a role.
 */
function getVisibleNotices($role, $limit = 10) {
    try {
        $db = getDB();
        return $db->fetchAll(
            'SELECT n.*, u.name AS published_by_name
             FROM notices n
             LEFT JOIN users u ON u.id = n.published_by
             WHERE n.is_active = 1 AND (n.audience = ? OR n.audience = "all")
             ORDER BY n.created_at DESC
             LIMIT ?',
            [$role, (int)$limit]
        );
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Start an attendance session and prefill all students as missed.
 */
function startAttendanceSession($teacher_id, $class_id, $title, $duration_minutes = 15) {
    $db = getDB();

    if (!isAttendanceEnabled()) {
        return ['success' => false, 'message' => 'Attendance is disabled by admin.'];
    }

    $duration_minutes = max(1, (int)$duration_minutes);
    $session_code = strtoupper(substr(bin2hex(random_bytes(6)), 0, 8));
    $starts_at = date('Y-m-d H:i:s');
    $ends_at = date('Y-m-d H:i:s', time() + ($duration_minutes * 60));

    try {
        $students = $db->fetchAll(
            'SELECT id FROM users WHERE role = ? AND class_id = ? AND is_active = 1',
            [ROLE_STUDENT, $class_id]
        );
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Attendance tables are not ready. Please run database update.'];
    }

    if (empty($students)) {
        return ['success' => false, 'message' => 'No active students found in selected class.'];
    }

    $db->beginTransaction();
    try {
        $db->query(
            'INSERT INTO attendance_sessions (session_code, teacher_id, class_id, title, starts_at, ends_at, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())',
            [$session_code, $teacher_id, $class_id, $title, $starts_at, $ends_at]
        );
        $session_id = (int)$db->lastInsertId();

        foreach ($students as $student) {
            $db->query(
                'INSERT INTO attendance_records (session_id, student_id, status, created_at) VALUES (?, ?, "missed", NOW())',
                [$session_id, $student['id']]
            );
        }

        $db->commit();

        return [
            'success' => true,
            'session_id' => $session_id,
            'session_code' => $session_code,
            'ends_at' => $ends_at
        ];
    } catch (Exception $e) {
        $db->rollback();
        error_log('Attendance start failed: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to start attendance session.'];
    }
}

/**
 * Mark present from student QR scan.
 */
function markAttendanceFromScan($session_code, $student_id) {
    $db = getDB();

    if (!isAttendanceEnabled()) {
        return ['success' => false, 'message' => 'Attendance is currently disabled.'];
    }

    $session = $db->fetchOne(
        'SELECT * FROM attendance_sessions WHERE session_code = ? AND is_active = 1 AND ends_at >= NOW()',
        [$session_code]
    );

    if (!$session) {
        return ['success' => false, 'message' => 'Attendance session not active or already closed.'];
    }

    $student = getUserById($student_id);
    if (!$student || (int)$student['class_id'] !== (int)$session['class_id']) {
        return ['success' => false, 'message' => 'You are not assigned to this attendance session class.'];
    }

    $record = $db->fetchOne(
        'SELECT * FROM attendance_records WHERE session_id = ? AND student_id = ?',
        [$session['id'], $student_id]
    );

    if (!$record) {
        return ['success' => false, 'message' => 'Attendance record not found for this student.'];
    }

    if ($record['status'] === 'present') {
        return ['success' => true, 'message' => 'Attendance already marked as present.'];
    }

    $db->query(
        'UPDATE attendance_records SET status = "present", scanned_at = NOW(), updated_at = CURRENT_TIMESTAMP WHERE id = ?',
        [$record['id']]
    );

    sendNotification($student_id, 'attendance', 'Attendance marked successfully for: ' . $session['title'], $session['id']);
    sendAppEmail(
        $student['email'],
        'Attendance marked successfully',
        "Hello {$student['name']},\n\nYour attendance is marked as PRESENT for: {$session['title']}.\nTime: " . date('Y-m-d H:i:s') . "\n"
    );

    return ['success' => true, 'message' => 'Attendance marked successfully.'];
}

/**
 * Finalize a session and notify absentees.
 */
function finalizeAttendanceSession($session_id, $teacher_id = null) {
    $db = getDB();

    $params = [$session_id];
    $sql = 'SELECT * FROM attendance_sessions WHERE id = ? AND is_active = 1';
    if ($teacher_id !== null) {
        $sql .= ' AND teacher_id = ?';
        $params[] = $teacher_id;
    }

    $session = $db->fetchOne($sql, $params);
    if (!$session) {
        return false;
    }

    $db->query('UPDATE attendance_sessions SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$session_id]);

    $missed = $db->fetchAll(
        'SELECT u.id, u.name, u.email FROM attendance_records ar JOIN users u ON u.id = ar.student_id WHERE ar.session_id = ? AND ar.status = "missed"',
        [$session_id]
    );

    foreach ($missed as $student) {
        sendNotification($student['id'], 'attendance_missed', 'You missed attendance for: ' . $session['title'], $session_id);
        sendAppEmail(
            $student['email'],
            'Attendance missed notification',
            "Hello {$student['name']},\n\nYou missed attendance for: {$session['title']}.\nPlease contact your teacher if this is incorrect.\n"
        );
    }

    return true;
}

/**
 * Close all expired active sessions.
 */
function processExpiredAttendanceSessions() {
    try {
        $db = getDB();
        $sessions = $db->fetchAll('SELECT id FROM attendance_sessions WHERE is_active = 1 AND ends_at < NOW()');
        foreach ($sessions as $session) {
            finalizeAttendanceSession((int)$session['id']);
        }
    } catch (Exception $e) {
        // Ignore when feature tables are missing.
    }
}

/**
 * Teacher attendance history.
 */
function getTeacherAttendanceSessions($teacher_id, $limit = 20) {
    try {
        $db = getDB();
        return $db->fetchAll(
            'SELECT s.*, c.name AS class_name,
               (SELECT COUNT(*) FROM attendance_records WHERE session_id = s.id AND status = "present") AS present_count,
               (SELECT COUNT(*) FROM attendance_records WHERE session_id = s.id) AS total_count
             FROM attendance_sessions s
             LEFT JOIN classes c ON c.id = s.class_id
             WHERE s.teacher_id = ?
             ORDER BY s.created_at DESC
             LIMIT ?',
            [$teacher_id, (int)$limit]
        );
    } catch (Exception $e) {
        return [];
    }
}
