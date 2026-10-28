<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$redirectTo = safe_redirect_target($_GET['redirect'] ?? ($_POST['redirect'] ?? ''), 'account.php');
if (is_logged_in()) {
    redirect($redirectTo);
}

$email = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!verify_csrf()) {
        $error = 'Session expired. Please try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $user = db_fetch_one('SELECT id, name, password FROM users WHERE email = ?', 's', [$email]);
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            cart_merge_on_login();
            flash('auth', 'Welcome back, ' . $user['name'] . '!', 'success');
            redirect($redirectTo);
        }
        $error = 'Invalid email or password.';
    }
}

$page_title = 'Login';
require __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card auth-card shadow">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="auth-icon"><i class="fa-solid fa-right-to-bracket"></i></div>
                        <h3 class="mt-3 mb-1">Welcome Back</h3>
                        <p class="text-muted">Log in to your ElectroMart account</p>
                    </div>
                    <?php if ($error): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($error) ?></div><?php endif; ?>
                    <form method="post" action="<?= url('login.php') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="redirect" value="<?= e($redirectTo) ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required autofocus>
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
                        <button type="submit" class="btn btn-accent btn-lg w-100">Login</button>
                    </form>
                    <p class="text-center mt-4 mb-0">Don't have an account? <a href="<?= url('register.php') ?>" class="fw-semibold">Register here</a></p>
                    <div class="demo-credentials mt-3 small text-muted text-center">Demo: john@example.com / password123</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
