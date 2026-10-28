<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('account.php');
}

$form = ['name' => '', 'email' => '', 'phone' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['name'] = sanitize($_POST['name'] ?? '');
    $form['email'] = sanitize($_POST['email'] ?? '');
    $form['phone'] = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try again.';
    }
    if ($form['name'] === '' || str_length($form['name']) < 2) {
        $errors[] = 'Please enter your full name.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (db_value('SELECT COUNT(*) FROM users WHERE email = ?', 's', [$form['email']])) {
        $errors[] = 'An account with this email already exists.';
    }
    if ($form['phone'] !== '' && !preg_match('/^[0-9+\-\s]{7,15}$/', $form['phone'])) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        db_execute(
            'INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)',
            'ssss',
            [$form['name'], $form['email'], password_hash($password, PASSWORD_DEFAULT), $form['phone']]
        );
        flash('auth', 'Registration successful! Please log in with your new account.', 'success');
        redirect('login.php');
    }
}

$page_title = 'Register';
require __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-6">
            <div class="card auth-card shadow">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="auth-icon"><i class="fa-solid fa-user-plus"></i></div>
                        <h3 class="mt-3 mb-1">Create Account</h3>
                        <p class="text-muted">Join ElectroMart for exclusive deals</p>
                    </div>
                    <?php if ($errors): ?>
                        <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
                    <?php endif; ?>
                    <form method="post" action="<?= url('register.php') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= e($form['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= e($form['email']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($form['phone']) ?>" placeholder="98765 43210">
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                                <div class="form-text">Minimum 6 characters</div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-accent btn-lg w-100 mt-4">Create Account</button>
                    </form>
                    <p class="text-center mt-4 mb-0">Already have an account? <a href="<?= url('login.php') ?>" class="fw-semibold">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
