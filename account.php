<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$uid = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash('account', 'Session expired. Please try again.', 'danger');
        redirect('account.php');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $f = [];
        foreach (['name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'country'] as $k) {
            $f[$k] = sanitize($_POST[$k] ?? '');
        }
        if ($f['name'] === '') {
            flash('account', 'Name is required.', 'danger');
        } elseif (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
            flash('account', 'Please enter a valid email address.', 'danger');
        } elseif (db_value('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?', 'si', [$f['email'], $uid])) {
            flash('account', 'That email is already used by another account.', 'danger');
        } elseif ($f['phone'] !== '' && !preg_match('/^[0-9+\-\s]{7,15}$/', $f['phone'])) {
            flash('account', 'Please enter a valid phone number.', 'danger');
        } else {
            db_execute(
                'UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zip = ?, country = ? WHERE id = ?',
                'ssssssssi',
                [$f['name'], $f['email'], $f['phone'], $f['address'], $f['city'], $f['state'], $f['zip'], $f['country'] ?: 'India', $uid]
            );
            $_SESSION['user_name'] = $f['name'];
            flash('account', 'Profile updated successfully.', 'success');
        }
    } elseif ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $hash = db_value('SELECT password FROM users WHERE id = ?', 'i', [$uid]);
        if (!password_verify($current, $hash)) {
            flash('account', 'Current password is incorrect.', 'danger');
        } elseif (strlen($new) < 6) {
            flash('account', 'New password must be at least 6 characters.', 'danger');
        } elseif ($new !== $confirm) {
            flash('account', 'New passwords do not match.', 'danger');
        } else {
            db_execute('UPDATE users SET password = ? WHERE id = ?', 'si', [password_hash($new, PASSWORD_DEFAULT), $uid]);
            flash('account', 'Password changed successfully.', 'success');
        }
    }
    redirect('account.php');
}

$user = current_user();
$stats = db_fetch_one('SELECT COUNT(*) AS orders, IFNULL(SUM(CASE WHEN status <> "cancelled" THEN total END),0) AS spent FROM orders WHERE user_id = ?', 'i', [$uid]);
$wishCount = (int) db_value('SELECT COUNT(*) FROM wishlist WHERE user_id = ?', 'i', [$uid]);

$page_title = 'My Account';
$active_page = 'account';
require __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1><i class="fa-solid fa-user-gear me-2"></i>My Account</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">My Account</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="content-card text-center">
                <div class="account-avatar"><?= e(strtoupper(substr($user['name'], 0, 1))) ?></div>
                <h5 class="mt-3 mb-0"><?= e($user['name']) ?></h5>
                <div class="small text-muted mb-3"><?= e($user['email']) ?></div>
                <div class="small text-muted">Member since <?= date('M Y', strtotime($user['created_at'])) ?></div>
            </div>
            <div class="list-group account-nav mt-3">
                <a href="<?= url('account.php') ?>" class="list-group-item list-group-item-action active"><i class="fa-solid fa-user me-2"></i>Profile</a>
                <a href="<?= url('orders.php') ?>" class="list-group-item list-group-item-action"><i class="fa-solid fa-box me-2"></i>My Orders</a>
                <a href="<?= url('wishlist.php') ?>" class="list-group-item list-group-item-action"><i class="fa-solid fa-heart me-2"></i>Wishlist</a>
                <a href="<?= url('logout.php') ?>" class="list-group-item list-group-item-action text-danger"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="mini-stat"><i class="fa-solid fa-box"></i><div><strong><?= (int) $stats['orders'] ?></strong><span>Orders</span></div></div></div>
                <div class="col-md-4"><div class="mini-stat"><i class="fa-solid fa-indian-rupee-sign"></i><div><strong><?= format_price($stats['spent']) ?></strong><span>Total Spent</span></div></div></div>
                <div class="col-md-4"><div class="mini-stat"><i class="fa-solid fa-heart"></i><div><strong><?= $wishCount ?></strong><span>Wishlist Items</span></div></div></div>
            </div>

            <div class="content-card mb-4">
                <h5 class="mb-3"><i class="fa-solid fa-id-card text-accent me-2"></i>Profile Information</h5>
                <form method="post" action="<?= url('account.php') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="profile">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="name">Full Name</label><input type="text" class="form-control" id="name" name="name" value="<?= e($user['name']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label" for="email">Email</label><input type="email" class="form-control" id="email" name="email" value="<?= e($user['email']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label" for="phone">Phone</label><input type="tel" class="form-control" id="phone" name="phone" value="<?= e($user['phone']) ?>"></div>
                        <div class="col-md-6"><label class="form-label" for="country">Country</label><input type="text" class="form-control" id="country" name="country" value="<?= e($user['country']) ?>"></div>
                        <div class="col-12"><label class="form-label" for="address">Address</label><textarea class="form-control" id="address" name="address" rows="2"><?= e($user['address']) ?></textarea></div>
                        <div class="col-md-4"><label class="form-label" for="city">City</label><input type="text" class="form-control" id="city" name="city" value="<?= e($user['city']) ?>"></div>
                        <div class="col-md-4"><label class="form-label" for="state">State</label><input type="text" class="form-control" id="state" name="state" value="<?= e($user['state']) ?>"></div>
                        <div class="col-md-4"><label class="form-label" for="zip">ZIP / PIN</label><input type="text" class="form-control" id="zip" name="zip" value="<?= e($user['zip']) ?>"></div>
                    </div>
                    <button type="submit" class="btn btn-accent mt-3"><i class="fa-solid fa-floppy-disk me-1"></i>Update Profile</button>
                </form>
            </div>

            <div class="content-card">
                <h5 class="mb-3"><i class="fa-solid fa-key text-accent me-2"></i>Change Password</h5>
                <form method="post" action="<?= url('account.php') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="password">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label" for="current_password">Current Password</label><input type="password" class="form-control" id="current_password" name="current_password" required></div>
                        <div class="col-md-4"><label class="form-label" for="new_password">New Password</label><input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required></div>
                        <div class="col-md-4"><label class="form-label" for="confirm_password">Confirm New Password</label><input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required></div>
                    </div>
                    <button type="submit" class="btn btn-dark mt-3"><i class="fa-solid fa-lock me-1"></i>Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
