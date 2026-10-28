<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$revenue  = (float) db_value("SELECT IFNULL(SUM(total),0) FROM orders WHERE status <> 'cancelled'");
$orders   = (int) db_value('SELECT COUNT(*) FROM orders');
$users    = (int) db_value('SELECT COUNT(*) FROM users');
$products = (int) db_value('SELECT COUNT(*) FROM products');

$statusCounts = array_fill_keys(['pending', 'processing', 'shipped', 'delivered', 'cancelled'], 0);
foreach (db_fetch_all('SELECT status, COUNT(*) AS c FROM orders GROUP BY status') as $r) {
    $statusCounts[$r['status']] = (int) $r['c'];
}

$recentOrders = db_fetch_all(
    'SELECT o.*, u.name AS user_name FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC, o.id DESC LIMIT 10'
);
$lowStock = db_fetch_all(
    'SELECT p.id, p.name, p.stock, p.image_url, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.stock < 5 ORDER BY p.stock ASC'
);
$topProducts = db_fetch_all(
    "SELECT oi.product_name, SUM(oi.quantity) AS sold, SUM(oi.quantity * oi.price) AS revenue
     FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status <> 'cancelled'
     GROUP BY oi.product_name ORDER BY sold DESC LIMIT 5"
);

$page_title = 'Dashboard';
$admin_page = 'dashboard';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-revenue">
            <div><span class="stat-label">Total Revenue</span><h3><?= format_price($revenue) ?></h3><small>Excluding cancelled orders</small></div>
            <i class="fa-solid fa-indian-rupee-sign stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-orders">
            <div><span class="stat-label">Total Orders</span><h3><?= $orders ?></h3><small><?= $statusCounts['pending'] ?> pending</small></div>
            <i class="fa-solid fa-receipt stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-users">
            <div><span class="stat-label">Total Users</span><h3><?= $users ?></h3><small>Registered customers</small></div>
            <i class="fa-solid fa-users stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-products">
            <div><span class="stat-label">Total Products</span><h3><?= $products ?></h3><small><?= count($lowStock) ?> low on stock</small></div>
            <i class="fa-solid fa-box-open stat-icon"></i>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <?php foreach ($statusCounts as $status => $count): ?>
        <div class="col">
            <a href="<?= url('admin/orders.php?status=' . $status) ?>" class="status-pill-card">
                <?= status_badge($status) ?><strong><?= $count ?></strong>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5><i class="fa-solid fa-clock-rotate-left me-2 text-accent"></i>Recent Orders</h5>
                <a href="<?= url('admin/orders.php') ?>" class="btn btn-sm btn-outline-dark">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td class="fw-semibold">#<?= (int) $o['id'] ?></td>
                            <td><?= e($o['user_name']) ?></td>
                            <td><?= format_price($o['total']) ?></td>
                            <td><?= status_badge($o['status']) ?></td>
                            <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                            <td class="text-end"><a href="<?= url('admin/order_detail.php?id=' . (int) $o['id']) ?>" class="btn btn-sm btn-dark"><i class="fa-solid fa-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentOrders): ?><tr><td colspan="6" class="text-center text-muted py-4">No orders yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h5><i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i>Low Stock (&lt; 5)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Product</th><th class="text-center">Stock</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($lowStock as $p): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= e($p['image_url']) ?>" class="admin-thumb" alt="" onerror="this.onerror=null;this.src='<?= url('assets/img/no-image.svg') ?>'">
                                    <div><div class="small fw-semibold"><?= e($p['name']) ?></div><div class="small text-muted"><?= e($p['category_name']) ?></div></div>
                                </div>
                            </td>
                            <td class="text-center"><span class="badge <?= (int) $p['stock'] === 0 ? 'bg-danger' : 'bg-warning text-dark' ?>"><?= (int) $p['stock'] ?></span></td>
                            <td class="text-end"><a href="<?= url('admin/edit_product.php?id=' . (int) $p['id']) ?>" class="btn btn-sm btn-outline-dark"><i class="fa-solid fa-pen"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$lowStock): ?><tr><td colspan="3" class="text-center text-muted py-4">All products are well stocked.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="admin-card">
            <div class="admin-card-header"><h5><i class="fa-solid fa-trophy me-2 text-accent"></i>Top Selling</h5></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($topProducts as $i => $t): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><span class="badge bg-dark me-2"><?= $i + 1 ?></span><?= e($t['product_name']) ?></span>
                        <span class="small text-muted"><?= (int) $t['sold'] ?> sold</span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$topProducts): ?><li class="list-group-item text-muted text-center">No sales yet.</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
