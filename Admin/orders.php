<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
$status = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$search = trim((string) ($_GET['search'] ?? ''));

$where = [];
$types = '';
$params = [];
if ($status !== '') {
    $where[] = 'o.status = ?';
    $types .= 's';
    $params[] = $status;
}
if ($search !== '') {
    $where[] = '(u.name LIKE ? OR u.email LIKE ? OR o.id = ?)';
    $types .= 'ssi';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = (int) ltrim($search, '#');
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$orders = db_fetch_all(
    "SELECT o.*, u.name AS user_name, u.email AS user_email,
            (SELECT SUM(quantity) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
     FROM orders o JOIN users u ON u.id = o.user_id $whereSql ORDER BY o.created_at DESC, o.id DESC",
    $types,
    $params
);

$page_title = 'Orders';
$admin_page = 'orders';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-card">
    <div class="admin-card-header">
        <form method="get" class="d-flex flex-wrap gap-2 w-100">
            <div class="input-group" style="max-width:320px">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="search" name="search" class="form-control" placeholder="Order # or customer..." value="<?= e($search) ?>">
            </div>
            <select name="status" class="form-select auto-submit" style="max-width:200px">
                <option value="">All statuses</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-dark">Filter</button>
            <?php if ($status !== '' || $search !== ''): ?><a href="<?= url('admin/orders.php') ?>" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead><tr><th>ID</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td class="fw-semibold">#<?= (int) $o['id'] ?></td>
                    <td><div><?= e($o['user_name']) ?></div><div class="small text-muted"><?= e($o['user_email']) ?></div></td>
                    <td><?= (int) $o['item_count'] ?></td>
                    <td class="fw-semibold text-nowrap"><?= format_price($o['total']) ?></td>
                    <td><?= e($o['payment_method']) ?></td>
                    <td><?= status_badge($o['status']) ?></td>
                    <td class="text-nowrap"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></td>
                    <td class="text-end"><a href="<?= url('admin/order_detail.php?id=' . (int) $o['id']) ?>" class="btn btn-sm btn-dark"><i class="fa-solid fa-eye me-1"></i>View</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?><tr><td colspan="8" class="text-center text-muted py-5">No orders found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-3 small text-muted border-top"><?= count($orders) ?> order(s)</div>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
