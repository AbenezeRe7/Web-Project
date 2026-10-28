<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$cart = get_cart_details();
if (!$cart['items']) {
    flash('cart', 'Your cart is empty. Add some products before checking out.', 'warning');
    redirect('cart.php');
}

$user = current_user();
$form = [
    'name'    => $user['name'] ?? '',
    'email'   => $user['email'] ?? '',
    'phone'   => $user['phone'] ?? '',
    'address' => $user['address'] ?? '',
    'city'    => $user['city'] ?? '',
    'state'   => $user['state'] ?? '',
    'zip'     => $user['zip'] ?? '',
    'country' => $user['country'] ?: 'India',
    'payment' => 'COD',
    'notes'   => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please submit the form again.';
    }
    foreach (['name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'country', 'notes'] as $f) {
        $form[$f] = sanitize($_POST[$f] ?? '');
    }
    $form['payment'] = in_array($_POST['payment'] ?? '', ['COD', 'UPI'], true) ? $_POST['payment'] : 'COD';

    foreach (['name' => 'Full name', 'email' => 'Email', 'phone' => 'Phone', 'address' => 'Address', 'city' => 'City', 'state' => 'State', 'zip' => 'ZIP code', 'country' => 'Country'] as $f => $label) {
        if ($form[$f] === '') {
            $errors[] = "$label is required.";
        }
    }
    if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($form['phone'] !== '' && !preg_match('/^[0-9+\-\s]{7,15}$/', $form['phone'])) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if ($form['zip'] !== '' && !preg_match('/^[A-Za-z0-9\s\-]{3,10}$/', $form['zip'])) {
        $errors[] = 'Please enter a valid ZIP / PIN code.';
    }
    if ($form['payment'] === 'UPI' && trim($_POST['upi_id'] ?? '') !== '' && !preg_match('/^[\w.\-]{2,}@[a-zA-Z]{2,}$/', trim($_POST['upi_id']))) {
        $errors[] = 'Please enter a valid UPI ID (e.g. name@upi).';
    }

    if (!$errors) {
        $conn->begin_transaction();
        try {
            // Lock product rows and re-check stock
            $total = 0;
            $lines = [];
            foreach (get_cart() as $pid => $qty) {
                $p = db_fetch_one('SELECT id, name, price, stock FROM products WHERE id = ? FOR UPDATE', 'i', [(int) $pid]);
                if (!$p) {
                    continue;
                }
                if ((int) $p['stock'] < (int) $qty) {
                    throw new RuntimeException('Sorry, only ' . (int) $p['stock'] . ' unit(s) of "' . $p['name'] . '" are available. Please update your cart.');
                }
                $lines[] = [$p, (int) $qty];
                $total += (float) $p['price'] * (int) $qty;
            }
            if (!$lines) {
                throw new RuntimeException('Your cart is empty.');
            }
            $shipping = $total >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_FEE;
            $grandTotal = $total + $shipping;
            $fullAddress = $form['address'] . ($form['country'] !== '' ? ', ' . $form['country'] : '');
            $notes = $form['notes'];
            if ($form['payment'] === 'UPI' && trim($_POST['upi_id'] ?? '') !== '') {
                $notes = trim('UPI ID: ' . sanitize($_POST['upi_id']) . "\n" . $notes);
            }

            $orderId = db_execute(
                'INSERT INTO orders (user_id, total, status, shipping_name, shipping_address, shipping_city, shipping_state, shipping_zip, shipping_phone, payment_method, notes)
                 VALUES (?, ?, "pending", ?, ?, ?, ?, ?, ?, ?, ?)',
                'idssssssss',
                [$_SESSION['user_id'], $grandTotal, $form['name'], $fullAddress, $form['city'], $form['state'], $form['zip'], $form['phone'], $form['payment'], $notes]
            );
            foreach ($lines as [$p, $qty]) {
                db_execute('INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)',
                    'iisdi', [$orderId, $p['id'], $p['name'], $p['price'], $qty]);
                db_execute('UPDATE products SET stock = stock - ? WHERE id = ?', 'ii', [$qty, $p['id']]);
            }

            // Save shipping details to profile if empty
            if (empty($user['address'])) {
                db_execute('UPDATE users SET phone = ?, address = ?, city = ?, state = ?, zip = ?, country = ? WHERE id = ?',
                    'ssssssi', [$form['phone'], $form['address'], $form['city'], $form['state'], $form['zip'], $form['country'], $_SESSION['user_id']]);
            }

            $conn->commit();
            cart_clear();
            $_SESSION['last_order_id'] = $orderId;
            redirect('order_success.php?id=' . $orderId);
        } catch (RuntimeException $ex) {
            $conn->rollback();
            $errors[] = $ex->getMessage();
        } catch (mysqli_sql_exception $ex) {
            $conn->rollback();
            $errors[] = 'Could not place your order. Please try again.';
        }
    }
}

$page_title = 'Checkout';
require __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1><i class="fa-solid fa-lock me-2"></i>Checkout</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= url('cart.php') ?>">Cart</a></li>
                <li class="breadcrumb-item active" aria-current="page">Checkout</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-4">
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation me-2"></i><strong>Please fix the following:</strong>
            <ul class="mb-0 mt-2"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url('checkout.php') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="content-card mb-4">
                    <h5 class="mb-3"><span class="step-num">1</span>Shipping Details</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= e($form['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($form['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($form['phone']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="country" class="form-label">Country *</label>
                            <input type="text" class="form-control" id="country" name="country" value="<?= e($form['country']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Address *</label>
                            <textarea class="form-control" id="address" name="address" rows="2" required><?= e($form['address']) ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="city" class="form-label">City *</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?= e($form['city']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="state" class="form-label">State *</label>
                            <input type="text" class="form-control" id="state" name="state" value="<?= e($form['state']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="zip" class="form-label">ZIP / PIN *</label>
                            <input type="text" class="form-control" id="zip" name="zip" value="<?= e($form['zip']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="notes" class="form-label">Order Notes (optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Delivery instructions, landmark..."><?= e($form['notes']) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <h5 class="mb-3"><span class="step-num">2</span>Payment Method</h5>
                    <label class="payment-option">
                        <input class="form-check-input" type="radio" name="payment" value="COD" <?= $form['payment'] === 'COD' ? 'checked' : '' ?>>
                        <i class="fa-solid fa-money-bill-wave"></i>
                        <div><strong>Cash on Delivery</strong><div class="small text-muted">Pay when your order arrives</div></div>
                    </label>
                    <label class="payment-option">
                        <input class="form-check-input" type="radio" name="payment" value="UPI" <?= $form['payment'] === 'UPI' ? 'checked' : '' ?>>
                        <i class="fa-brands fa-google-pay"></i>
                        <div><strong>UPI</strong><div class="small text-muted">Google Pay, PhonePe, Paytm, BHIM</div></div>
                    </label>
                    <div id="upi-field" class="mt-3" style="<?= $form['payment'] === 'UPI' ? '' : 'display:none' ?>">
                        <label for="upi_id" class="form-label">UPI ID (optional)</label>
                        <input type="text" class="form-control" id="upi_id" name="upi_id" placeholder="yourname@upi" value="<?= e($_POST['upi_id'] ?? '') ?>">
                        <div class="form-text">Demo mode: no real payment is processed.</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="content-card summary-card">
                    <h5 class="mb-3"><span class="step-num">3</span>Order Summary</h5>
                    <?php foreach ($cart['items'] as $item): ?>
                        <div class="checkout-item">
                            <img src="<?= e($item['image_url']) ?>" alt="" class="checkout-thumb" onerror="this.onerror=null;this.src='<?= url('assets/img/no-image.svg') ?>'">
                            <div class="flex-grow-1">
                                <div class="fw-semibold small"><?= e($item['name']) ?></div>
                                <div class="small text-muted"><?= (int) $item['quantity'] ?> &times; <?= format_price($item['price']) ?></div>
                            </div>
                            <div class="fw-semibold small text-nowrap"><?= format_price($item['line_total']) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <hr>
                    <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span><?= format_price($cart['subtotal']) ?></span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Shipping</span><span><?= $cart['shipping'] == 0 ? '<span class="text-success fw-semibold">FREE</span>' : format_price($cart['shipping']) ?></span></div>
                    <hr>
                    <div class="d-flex justify-content-between fs-5 fw-bold mb-3"><span>Total</span><span class="text-accent-dark"><?= format_price($cart['total']) ?></span></div>
                    <button type="submit" class="btn btn-accent btn-lg w-100"><i class="fa-solid fa-circle-check me-2"></i>Place Order</button>
                    <a href="<?= url('cart.php') ?>" class="btn btn-link w-100 mt-2 text-muted">Back to cart</a>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
