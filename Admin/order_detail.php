<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
$id = (int) ($_GET['id'] ?? 0);
$order = db_fetch_one(
    'SELECT o.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone, u.created_at AS user_since
     FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?',
    'i',
    [$id]
);
if (!$order) {
    flash('admin', 'Order not found.', 'danger');
    redirect('admin/orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = $_POST['status'] ?? '';
    if (!verify_csrf()) {
        flash('admin', 'Session expired. Please try again.', 'danger');
    } elseif (!in_array($new, $statuses, true)) {
        flash('admin', 'Invalid status.', 'danger');
    } elseif ($new === $order['status']) {
        flash('admin', 'Status unchanged.', 'info');
    } else {
        $conn->begin_transaction();
        db_execute('UPDATE orders SET status = ? WHERE id = ?', 'si', [$new, $id]);
        // Keep inventory consistent when cancelling / un-cancelling
        if ($new === 'cancelled') {
            db_execute('UPDATE products p JOIN order_items oi ON oi.product_id = p.id SET p.stock = p.stock + oi.quantity WHERE oi.order_id = ?', 'i', [$id]);
        } elseif ($order['status'] === 'cancelled') {
            db_execute('UPDATE products p JOIN order_items oi ON oi.product_id = p.id SET p.stock = GREATEST(0, p.stock - oi.quantity) WHERE oi.order_id = ?', 'i', [$id]);
        }
        $conn->commit();
        flash('admin', 'Order #' . $id . ' status updated to ' . ucfirst($new) . '.', 'success');
    }
    redirect('admin/order_detail.php?id=' . $id);
}

$items = db_fetch_all(
    'SELECT oi.*, p.image_url FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?',
    'i',
    [$id]
);
$subtotal = 0;
foreach ($items as $it) {
    $subtotal += $it['price'] * $it['quantity'];
}
$shipping = $order['total'] - $subtotal;

$page_title = 'Order #' . $id;
$admin_page = 'orders';
require __DIR__ . '/includes/admin_header.php';
?>

<nav aria-label="breadcrumb"><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= url('admin/index.php') ?>">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?= url('admin/orders.php') ?>">Orders</a></li>
    <li class="breadcrumb-item active">#<?= $id ?></li>
</ol></nav>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h5 class="mb-0">Order #<?= $id ?> <?= status_badge($order['status']) ?></h5>
                <span class="small text-muted"><i class="fa-regular fa-calendar me-1"></i><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead><tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= e($it['image_url'] ?: url('assets/img/no-image.svg')) ?>" class="admin-thumb" alt="" onerror="this.onerror=null;this.src='<?= url('assets/img/no-image.svg') ?>'">
                                    <div>
                                        <div class="fw-semibold"><?= e($it['product_name']) ?></div>
                                        <div class="small text-muted"><?= $it['product_id'] ? 'Product ID: ' . (int) $it['product_id'] : 'Product deleted' ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center"><?= (int) $it['quantity'] ?></td>
                            <td class="text-end"><?= format_price($it['price']) ?></td>
                            <td class="text-end"><?= format_price($it['price'] * $it['quantity']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end"><?= format_price($subtotal) ?></td></tr>
                    <tr><td colspan="3" class="text-end">Shipping</td><td class="text-end"><?= $shipping > 0 ? format_price($shipping) : 'FREE' ?></td></tr>
                    <tr class="fw-bold fs-5"><td colspan="3" class="text-end">Order Total</td><td class="text-end"><?= format_price($order['total']) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php if ($order['notes']): ?>
            <div class="admin-card p-4"><h6><i class="fa-solid fa-note-sticky me-2 text-accent"></i>Order Notes</h6><p class="mb-0"><?= nl2br(e($order['notes'])) ?></p></div>
        <?php endif; ?>
    </div>

    <div class="col-xl-4">
        <div class="admin-card p-4 mb-4">
            <h6 class="mb-3"><i class="fa-solid fa-arrows-rotate me-2 text-accent"></i>Update Status</h6>
            <form method="post">
                <?= csrf_field() ?>
                <select name="status" class="form-select mb-3">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-accent w-100"><i class="fa-solid fa-floppy-disk me-1"></i>Save</button>
            </form>
            <div class="order-progress mt-4">
                <?php $flow = ['pending', 'processing', 'shipped', 'delivered'];
                $pos = array_search($order['status'], $flow, true);
                foreach ($flow as $i => $step): ?>
                    <div class="order-step <?= $pos !== false && $i <= $pos ? 'done' : '' ?>"><span><?= $i + 1 ?></span><?= ucfirst($step) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="admin-card p-4 mb-4">
            <h6 class="mb-3"><i class="fa-solid fa-user me-2 text-accent"></i>Customer</h6>
            <div class="small">
                <strong><?= e($order['user_name']) ?></strong><br>
                <i class="fa-solid fa-envelope me-1 text-muted"></i><?= e($order['user_email']) ?><br>
                <?php if ($order['user_phone']): ?><i class="fa-solid fa-phone me-1 text-muted"></i><?= e($order['user_phone']) ?><br><?php endif; ?>
                <span class="text-muted">Customer since <?= date('M Y', strtotime($order['user_since'])) ?></span><br>
                <a href="<?= url('admin/users.php?id=' . (int) $order['user_id']) ?>" class="btn btn-sm btn-outline-dark mt-2">View Customer</a>
            </div>
        </div>
        <div class="admin-card p-4">
            <h6 class="mb-3"><i class="fa-solid fa-truck me-2 text-accent"></i>Shipping &amp; Payment</h6>
            <div class="small">
                <strong><?= e($order['shipping_name']) ?></strong><br>
                <?= e($order['shipping_address']) ?><br>
                <?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?> - <?= e($order['shipping_zip']) ?><br>
                <i class="fa-solid fa-phone me-1 text-muted"></i><?= e($order['shipping_phone']) ?>
                <hr>
                Payment: <strong><?= $order['payment_method'] === 'UPI' ? 'UPI' : 'Cash on Delivery' ?></strong>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
