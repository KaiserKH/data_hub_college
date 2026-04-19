<?php
/**
 * Logout Page
 */
require 'config.php';
require 'includes/auth.php';

// Log the logout
if (isLoggedIn()) {
    auditLog('logout', 'user', getCurrentUserId());
}

// Logout user
logoutUser();

// Redirect to login with message
redirect('/login.php?logged_out=1');
?>
