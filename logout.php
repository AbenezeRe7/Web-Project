<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// The cart is already persisted in the DB for this user, so it is restored on next login.
$keepAdmin = $_SESSION['admin_id'] ?? null;
$keepAdminName = $_SESSION['admin_name'] ?? null;

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

session_start();
if ($keepAdmin) {
    // Logging out as a customer should not log the administrator out
    $_SESSION['admin_id'] = $keepAdmin;
    $_SESSION['admin_name'] = $keepAdminName;
}
flash('auth', 'You have been logged out successfully.', 'info');
redirect('login.php');
