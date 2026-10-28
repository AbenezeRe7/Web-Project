<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Remove item (GET)
if (isset($_GET['remove'])) {
    cart_remove((int) $_GET['remove']);
    flash('cart', 'Item removed from your cart.', 'info');
    redirect('cart.php');
}

// Clear cart (GET)
if (isset($_GET['clear'])) {
    cart_clear();
    flash('cart', 'Your cart has been cleared.', 'info');
    redirect('cart.php');
}

// Update quantities (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash('cart', 'Session expired. Please try again.', 'danger');
        redirect('cart.php');
    }
    $messages = [];
    foreach ((array) ($_POST['qty'] ?? []) as $pid => $qty) {
        [$ok, $msg] = cart_update((int) $pid, (int) $qty);
        if (strpos($msg, 'Only') === 0) {
            $messages[] = $msg;
        }
    }
    flash('cart', $messages ? implode(' ', array_unique($messages)) : 'Cart updated successfully.', $messages ? 'warning' : 'success');
    redirect('cart.php');
}

$cart = get_cart_details();
$page_title = 'Shopping Cart';
require __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1><i class="fa-solid fa-cart-shopping me-2"></i>Shopping Cart</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cart</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-4">
    <?php if (!$cart['items']): ?>
        <div class="empty-state">
            <i class="fa-solid fa-cart-arrow-down"></i>
            <h4>Your cart is empty</h4>
            <p class="text-muted">Looks like you haven't added anything yet. Explore our latest gadgets!</p>
            <a href="<?= url('products.php') ?>" class="btn btn-accent btn-lg"><i class="fa-solid fa-bag-shopping me-2"></i>Shop Now</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <form method="post" action="<?= url('cart.php') ?>" id="cart-form" class="content-card p-0 overflow-hidden">
                    <?= csrf_field() ?>
                    <div class="table-responsive">
                        <table class="table cart-table align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th class="text-center">Quantity</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($cart['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?= e($item['image_url']) ?>" alt="<?= e($item['name']) ?>" class="cart-thumb" onerror="this.onerror=null;this.src='<?= url('assets/img/no-image.svg') ?>'">
                                            <div>
                                                <a href="<?= url('product.php?id=' . (int) $item['id']) ?>" class="cart-product-name"><?= e($item['name']) ?></a>
                                                <div class="small text-muted"><?= (int) $item['stock'] ?> in stock</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-nowrap"><?= format_price($item['price']) ?></td>
                                    <td>
                                        <div class="qty-control qty-sm mx-auto">
                                            <button type="button" class="btn qty-btn qty-minus" data-autosubmit="1" aria-label="Decrease"><i class="fa-solid fa-minus"></i></button>
                                            <input type="number" name="qty[<?= (int) $item['id'] ?>]" class="form-control qty-input" value="<?= (int) $item['quantity'] ?>" min="0" max="<?= (int) $item['stock'] ?>">
                                            <button type="button" class="btn qty-btn qty-plus" data-autosubmit="1" aria-label="Increase"><i class="fa-solid fa-plus"></i></button>
                                        </div>
                                    </td>
                                    <td class="fw-semibold text-nowrap"><?= format_price($item['line_total']) ?></td>
                                    <td>
                                        <a href="<?= url('cart.php?remove=' . (int) $item['id']) ?>" class="btn btn-sm btn-outline-danger confirm-delete" data-confirm="Remove this item from your cart?" title="Remove"><i class="fa-solid fa-trash-can"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between flex-wrap gap-2 p-3 border-top">
                        <a href="<?= url('products.php') ?>" class="btn btn-outline-dark"><i class="fa-solid fa-arrow-left me-1"></i>Continue Shopping</a>
                        <div class="d-flex gap-2">
                            <a href="<?= url('cart.php?clear=1') ?>" class="btn btn-outline-danger confirm-delete" data-confirm="Remove all items from your cart?"><i class="fa-solid fa-trash me-1"></i>Clear Cart</a>
                            <button type="submit" class="btn btn-dark"><i class="fa-solid fa-rotate me-1"></i>Update Cart</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="content-card summary-card">
                    <h5 class="mb-3">Order Summary</h5>
                    <div class="d-flex justify-content-between mb-2"><span>Subtotal (<?= (int) $cart['count'] ?> items)</span><span><?= format_price($cart['subtotal']) ?></span></div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping</span>
                        <span><?= $cart['shipping'] == 0 ? '<span class="text-success fw-semibold">FREE</span>' : format_price($cart['shipping']) ?></span>
                    </div>
                    <?php if ($cart['shipping'] > 0): ?>
                        <div class="alert alert-warning small py-2">
                            <i class="fa-solid fa-truck me-1"></i>Add <?= format_price(FREE_SHIPPING_THRESHOLD - $cart['subtotal']) ?> more for FREE shipping.
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between fs-5 fw-bold mb-3"><span>Total</span><span class="text-accent-dark"><?= format_price($cart['total']) ?></span></div>
                    <a href="<?= url('checkout.php') ?>" class="btn btn-accent btn-lg w-100"><i class="fa-solid fa-lock me-2"></i>Proceed to Checkout</a>
                    <div class="text-center small text-muted mt-3"><i class="fa-solid fa-shield-halved me-1"></i>Safe and secure checkout</div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
