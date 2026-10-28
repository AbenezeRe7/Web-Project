<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Only the admin part of the session is removed so a logged-in customer session survives.
unset($_SESSION['admin_id'], $_SESSION['admin_name']);
session_regenerate_id(true);
if (empty($_SESSION['user_id'])) {
    $_SESSION = [];
    session_destroy();
    session_start();
}
flash('admin', 'You have been logged out of the admin panel.', 'info');
redirect('admin/login.php');
