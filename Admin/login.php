<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_admin()) {
    redirect('admin/index.php');
}

$username = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!verify_csrf()) {
        $error = 'Session expired. Please try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } else {
        $admin = db_fetch_one('SELECT id, username, password FROM admins WHERE username = ? OR email = ?', 'ss', [$username, $username]);
        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_name'] = $admin['username'];
            flash('admin', 'Welcome back, ' . $admin['username'] . '!', 'success');
            redirect('admin/index.php');
        }
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | ElectroMart</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="auth-bg admin-login-bg">
<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-7 col-lg-4">
            <div class="card auth-card shadow-lg">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="auth-icon"><i class="fa-solid fa-user-shield"></i></div>
                        <h3 class="mt-3 mb-1">Admin Panel</h3>
                        <p class="text-muted mb-0">Electro<span class="text-accent fw-bold">Mart</span> management</p>
                    </div>
                    <?php display_all_flash(); ?>
                    <?php if ($error): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($error) ?></div><?php endif; ?>
                    <form method="post" action="<?= url('admin/login.php') ?>">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" value="<?= e($username) ?>" required autofocus>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-accent btn-lg w-100"><i class="fa-solid fa-right-to-bracket me-2"></i>Login</button>
                    </form>
                    <div class="text-center mt-4">
                        <a href="<?= url('index.php') ?>" class="small text-muted"><i class="fa-solid fa-arrow-left me-1"></i>Back to store</a>
                    </div>
                    <div class="demo-credentials mt-2 small text-muted text-center">Demo: admin / admin123</div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
