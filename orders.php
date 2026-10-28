<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$uid = (int) $_SESSION['user_id'];

// Customers may cancel their own pending orders (stock is restored)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    if (verify_csrf()) {
        $oid = (int) ($_POST['order_id'] ?? 0);
        $order = db_fetch_one('SELECT id, status FROM orders WHERE id = ? AND user_id = ?', 'ii', [$oid, $uid]);
        if ($order && $order['status'] === 'pending') {
            $conn->begin_transaction();
            db_execute('UPDATE orders SET status = "cancelled" WHERE id = ?', 'i', [$oid]);
            db_execute('UPDATE products p JOIN order_items oi ON oi.product_id = p.id SET p.stock = p.stock + oi.quantity WHERE oi.order_id = ?', 'i', [$oid]);
            $conn->commit();
            flash('orders', 'Order #' . $oid . ' has been cancelled.', 'info');
        } else {
            flash('orders', 'Only pending orders can be cancelled.', 'warning');
        }
    }
    redirect('orders.php');
}

$orders = db_fetch_all('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC', 'i', [$uid]);
$itemsByOrder = [];
if ($orders) {
    $rows = db_fetch_all(
        'SELECT oi.*, p.image_url FROM order_items oi JOIN orders o ON o.id = oi.order_id LEFT JOIN products p ON p.id = oi.product_id WHERE o.user_id = ?',
        'i',
        [$uid]
    );
    foreach ($rows as $r) {
        $itemsByOrder[$r['order_id']][] = $r;
    }
}

$page_title = 'My Orders';
$active_page = 'orders';
require __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1><i class="fa-solid fa-box me-2"></i>My Orders</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= url('account.php') ?>">My Account</a></li>
                <li class="breadcrumb-item active" aria-current="page">Orders</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-4">
    <?php if (!$orders): ?>
        <div class="empty-state">
            <i class="fa-solid fa-box-open"></i>
            <h4>No orders yet</h4>
            <p class="text-muted">When you place an order, it will appear here.</p>
            <a href="<?= url('products.php') ?>" class="btn btn-accent">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="content-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 orders-table">
                    <thead>
                    <tr><th>Order #</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th class="text-end">Details</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o):
                        $items = $itemsByOrder[$o['id']] ?? []; ?>
                        <tr>
                            <td class="fw-semibold">#<?= (int) $o['id'] ?></td>
                            <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                            <td><?= array_sum(array_column($items, 'quantity')) ?></td>
                            <td class="fw-semibold"><?= format_price($o['total']) ?></td>
                            <td><?= e($o['payment_method']) ?></td>
                            <td><?= status_badge($o['status']) ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#order<?= (int) $o['id'] ?>" aria-expanded="false">
                                    <i class="fa-solid fa-eye me-1"></i>View Details
                                </button>
                            </td>
                        </tr>
                        <tr class="collapse order-detail-row" id="order<?= (int) $o['id'] ?>">
                            <td colspan="7" class="bg-light">
                                <div class="row g-3 p-2">
                                    <div class="col-lg-8">
                                        <table class="table table-sm bg-white mb-0">
                                            <thead><tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>
                                            <tbody>
                                            <?php foreach ($items as $it): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($it['image_url']): ?><img src="<?= e($it['image_url']) ?>" alt="" class="order-thumb me-2"><?php endif; ?>
                                                        <?php if ($it['product_id']): ?>
                                                            <a href="<?= url('product.php?id=' . (int) $it['product_id']) ?>"><?= e($it['product_name']) ?></a>
                                                        <?php else: ?><?= e($it['product_name']) ?><?php endif; ?>
                                                    </td>
                                                    <td class="text-center"><?= (int) $it['quantity'] ?></td>
                                                    <td class="text-end"><?= format_price($it['price']) ?></td>
                                                    <td class="text-end"><?= format_price($it['price'] * $it['quantity']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-lg-4 small">
                                        <h6>Shipping Address</h6>
                                        <strong><?= e($o['shipping_name']) ?></strong><br>
                                        <?= e($o['shipping_address']) ?><br>
                                        <?= e($o['shipping_city']) ?>, <?= e($o['shipping_state']) ?> - <?= e($o['shipping_zip']) ?><br>
                                        <i class="fa-solid fa-phone me-1"></i><?= e($o['shipping_phone']) ?>
                                        <?php if ($o['status'] === 'pending'): ?>
                                            <form method="post" action="<?= url('orders.php') ?>" class="mt-3">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger confirm-delete" data-confirm="Cancel order #<?= (int) $o['id'] ?>?"><i class="fa-solid fa-xmark me-1"></i>Cancel Order</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
