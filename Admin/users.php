<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$viewId = (int) ($_GET['id'] ?? 0);
$viewUser = null;
if ($viewId) {
    $viewUser = db_fetch_one('SELECT * FROM users WHERE id = ?', 'i', [$viewId]);
    if (!$viewUser) {
        flash('admin', 'User not found.', 'danger');
        redirect('admin/users.php');
    }
    $userOrders = db_fetch_all('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC', 'i', [$viewId]);
    $userStats = db_fetch_one("SELECT COUNT(*) AS orders, IFNULL(SUM(CASE WHEN status <> 'cancelled' THEN total END),0) AS spent FROM orders WHERE user_id = ?", 'i', [$viewId]);
    $userReviews = (int) db_value('SELECT COUNT(*) FROM reviews WHERE user_id = ?', 'i', [$viewId]);
}

$search = trim((string) ($_GET['search'] ?? ''));
$params = [];
$types = '';
$whereSql = '';
if ($search !== '') {
    $whereSql = 'WHERE u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?';
    $types = 'sss';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}
$users = db_fetch_all(
    "SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count FROM users u $whereSql ORDER BY u.created_at DESC, u.id DESC",
    $types,
    $params
);

$page_title = $viewUser ? 'Customer: ' . $viewUser['name'] : 'Users';
$admin_page = 'users';
require __DIR__ . '/includes/admin_header.php';
?>

<?php if ($viewUser): ?>
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('admin/index.php') ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= url('admin/users.php') ?>">Users</a></li>
        <li class="breadcrumb-item active"><?= e($viewUser['name']) ?></li>
    </ol></nav>
    <div class="row g-4 mb-4">
        <div class="col-xl-4">
            <div class="admin-card p-4 text-center">
                <div class="account-avatar mx-auto"><?= e(strtoupper(substr($viewUser['name'], 0, 1))) ?></div>
                <h5 class="mt-3 mb-0"><?= e($viewUser['name']) ?></h5>
                <div class="text-muted small mb-3"><?= e($viewUser['email']) ?></div>
                <div class="row text-center g-2 mb-3">
                    <div class="col-4"><div class="fw-bold"><?= (int) $userStats['orders'] ?></div><small class="text-muted">Orders</small></div>
                    <div class="col-4"><div class="fw-bold"><?= format_price($userStats['spent']) ?></div><small class="text-muted">Spent</small></div>
                    <div class="col-4"><div class="fw-bold"><?= $userReviews ?></div><small class="text-muted">Reviews</small></div>
                </div>
                <ul class="list-group list-group-flush text-start small">
                    <li class="list-group-item"><i class="fa-solid fa-phone me-2 text-muted"></i><?= e($viewUser['phone'] ?: '—') ?></li>
                    <li class="list-group-item"><i class="fa-solid fa-location-dot me-2 text-muted"></i>
                        <?= $viewUser['address'] ? e($viewUser['address']) . ', ' . e($viewUser['city']) . ', ' . e($viewUser['state']) . ' ' . e($viewUser['zip']) . ', ' . e($viewUser['country']) : '—' ?></li>
                    <li class="list-group-item"><i class="fa-solid fa-calendar me-2 text-muted"></i>Joined <?= date('d M Y', strtotime($viewUser['created_at'])) ?></li>
                </ul>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="admin-card">
                <div class="admin-card-header"><h5 class="mb-0">Order History</h5></div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead><tr><th>Order</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($userOrders as $o): ?>
                            <tr>
                                <td class="fw-semibold">#<?= (int) $o['id'] ?></td>
                                <td><?= format_price($o['total']) ?></td>
                                <td><?= status_badge($o['status']) ?></td>
                                <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                                <td class="text-end"><a href="<?= url('admin/order_detail.php?id=' . (int) $o['id']) ?>" class="btn btn-sm btn-dark"><i class="fa-solid fa-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$userOrders): ?><tr><td colspan="5" class="text-center text-muted py-4">No orders yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <form method="get" class="d-flex gap-2 w-100">
            <div class="input-group" style="max-width:360px">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="search" name="search" class="form-control" placeholder="Search name, email or phone..." value="<?= e($search) ?>">
            </div>
            <button class="btn btn-dark" type="submit">Search</button>
            <?php if ($search !== ''): ?><a href="<?= url('admin/users.php') ?>" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Joined</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr class="<?= $viewId === (int) $u['id'] ? 'table-warning' : '' ?>">
                    <td><?= (int) $u['id'] ?></td>
                    <td class="fw-semibold"><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['phone'] ?: '—') ?></td>
                    <td><span class="badge bg-secondary"><?= (int) $u['order_count'] ?></span></td>
                    <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td class="text-end"><a href="<?= url('admin/users.php?id=' . (int) $u['id']) ?>" class="btn btn-sm btn-outline-dark"><i class="fa-solid fa-eye me-1"></i>View</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?><tr><td colspan="7" class="text-center text-muted py-5">No users found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-3 small text-muted border-top"><?= count($users) ?> user(s)</div>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
