<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$id = (int) ($_GET['id'] ?? 0);
$order = db_fetch_one('SELECT * FROM orders WHERE id = ? AND user_id = ?', 'ii', [$id, $_SESSION['user_id']]);
if (!$order) {
    flash('order', 'Order not found.', 'danger');
    redirect('orders.php');
}
$items = db_fetch_all('SELECT * FROM order_items WHERE order_id = ?', 'i', [$id]);

$page_title = 'Order Confirmed';
require __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="content-card text-center success-card">
                <div class="success-icon"><i class="fa-solid fa-circle-check"></i></div>
                <h2 class="mt-3">Thank you for your order!</h2>
                <p class="text-muted">Your order <strong>#<?= (int) $order['id'] ?></strong> has been placed successfully on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>.</p>
                <div class="row text-start g-3 my-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6><i class="fa-solid fa-location-dot text-accent me-2"></i>Shipping To</h6>
                            <div class="small">
                                <strong><?= e($order['shipping_name']) ?></strong><br>
                                <?= e($order['shipping_address']) ?><br>
                                <?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?> - <?= e($order['shipping_zip']) ?><br>
                                <i class="fa-solid fa-phone me-1"></i><?= e($order['shipping_phone']) ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6><i class="fa-solid fa-receipt text-accent me-2"></i>Order Info</h6>
                            <div class="small">
                                Status: <?= status_badge($order['status']) ?><br>
                                Payment: <?= $order['payment_method'] === 'UPI' ? 'UPI' : 'Cash on Delivery' ?><br>
                                Total: <strong><?= format_price($order['total']) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
                <table class="table table-sm text-start">
                    <thead><tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Price</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr><td><?= e($it['product_name']) ?></td><td class="text-center"><?= (int) $it['quantity'] ?></td><td class="text-end"><?= format_price($it['price'] * $it['quantity']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <a href="<?= url('orders.php') ?>" class="btn btn-dark"><i class="fa-solid fa-box me-1"></i>View My Orders</a>
                    <a href="<?= url('products.php') ?>" class="btn btn-accent"><i class="fa-solid fa-bag-shopping me-1"></i>Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
