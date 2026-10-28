<?php
/**
 * ElectroMart - Helper functions
 * Requires config/db.php to be included first ($conn + session).
 */

/* ---------------------------------------------------------------
 * Input / output helpers
 * ------------------------------------------------------------- */

/** Trim and HTML-escape user input. */
function sanitize($input)
{
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim((string) $input), ENT_QUOTES, 'UTF-8');
}

/** Escape for output without double-encoding already sanitized values. */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8', false);
}

/** Multibyte-safe string length (works even if the mbstring extension is disabled). */
function str_length($value)
{
    $value = (string) $value;
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen(utf8_decode_safe($value));
}

function utf8_decode_safe($value)
{
    return preg_replace('/[\x{80}-\x{10FFFF}]/u', '?', $value) ?? $value;
}

/** Build an absolute URL inside the app. */
function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect($url)
{
    if (!preg_match('#^(https?:)?/#', $url)) {
        $url = url($url);
    }
    header('Location: ' . $url);
    exit;
}

function format_price($price)
{
    return '&#8377;' . number_format((float) $price, 2);
}

/* ---------------------------------------------------------------
 * Flash messages
 * ------------------------------------------------------------- */

/** Store a flash message (types: success, danger, warning, info). */
function flash($name, $msg, $type = 'success')
{
    $_SESSION['flash'][$name] = ['message' => $msg, 'type' => $type];
}

/** Print and remove a flash message. */
function display_flash($name)
{
    if (empty($_SESSION['flash'][$name])) {
        return;
    }
    $f = $_SESSION['flash'][$name];
    unset($_SESSION['flash'][$name]);
    $icons = ['success' => 'circle-check', 'danger' => 'circle-exclamation', 'warning' => 'triangle-exclamation', 'info' => 'circle-info'];
    $icon = $icons[$f['type']] ?? 'circle-info';
    echo '<div class="alert alert-' . e($f['type']) . ' alert-dismissible fade show flash-alert" role="alert">'
        . '<i class="fa-solid fa-' . $icon . ' me-2"></i>' . e($f['message'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}

/** Display every pending flash message. */
function display_all_flash()
{
    if (!empty($_SESSION['flash'])) {
        foreach (array_keys($_SESSION['flash']) as $name) {
            display_flash($name);
        }
    }
}

/* ---------------------------------------------------------------
 * CSRF protection
 * ------------------------------------------------------------- */

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf()
{
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

/* ---------------------------------------------------------------
 * Database helpers (prepared statements)
 * ------------------------------------------------------------- */

function db_query($sql, $types = '', $params = [])
{
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($types !== '' && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

function db_fetch_all($sql, $types = '', $params = [])
{
    $stmt = db_query($sql, $types, $params);
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

function db_fetch_one($sql, $types = '', $params = [])
{
    $stmt = db_query($sql, $types, $params);
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function db_value($sql, $types = '', $params = [])
{
    $row = db_fetch_one($sql, $types, $params);
    return $row ? reset($row) : null;
}

/** Execute INSERT/UPDATE/DELETE. Returns insert id (if any) or affected rows. */
function db_execute($sql, $types = '', $params = [])
{
    $stmt = db_query($sql, $types, $params);
    $result = $stmt->insert_id ?: $stmt->affected_rows;
    $stmt->close();
    return $result;
}

/* ---------------------------------------------------------------
 * Authentication
 * ------------------------------------------------------------- */

function is_logged_in()
{
    return !empty($_SESSION['user_id']);
}

function is_admin()
{
    return !empty($_SESSION['admin_id']);
}

/** Return a safe, relative redirect target (prevents open redirects). */
function safe_redirect_target($target, $default = 'account.php')
{
    $target = (string) $target;
    if ($target === '' || preg_match('#^[a-z][a-z0-9+.-]*:#i', $target) || strpos($target, '//') === 0 || strpos($target, '\\') !== false) {
        return $default;
    }
    return $target;
}

/** Customer-only pages: redirect to login (remembering the page). */
function require_login()
{
    if (!is_logged_in()) {
        $current = basename($_SERVER['SCRIPT_NAME']);
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current .= '?' . $_SERVER['QUERY_STRING'];
        }
        flash('auth', 'Please log in to continue.', 'warning');
        redirect('login.php?redirect=' . urlencode($current));
    }
}

/** Admin-only pages. */
function require_admin()
{
    if (!is_admin()) {
        flash('admin', 'Please log in as administrator.', 'warning');
        redirect('admin/login.php');
    }
}

function current_user()
{
    if (!is_logged_in()) {
        return null;
    }
    return db_fetch_one('SELECT * FROM users WHERE id = ?', 'i', [$_SESSION['user_id']]);
}

/* ---------------------------------------------------------------
 * Cart (session based, mirrored to DB for logged-in users)
 * ------------------------------------------------------------- */

function get_cart()
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function get_cart_count()
{
    return array_sum(get_cart());
}

/** Persist the session cart to the DB cart table (logged-in users only). */
function cart_save_db()
{
    if (!is_logged_in()) {
        return;
    }
    $uid = (int) $_SESSION['user_id'];
    db_execute('DELETE FROM cart WHERE user_id = ?', 'i', [$uid]);
    foreach (get_cart() as $pid => $qty) {
        db_execute('INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)', 'iii', [$uid, (int) $pid, (int) $qty]);
    }
}

/** On login: merge the DB cart with the guest session cart. */
function cart_merge_on_login()
{
    $cart = get_cart();
    $rows = db_fetch_all(
        'SELECT c.product_id, c.quantity, p.stock FROM cart c JOIN products p ON p.id = c.product_id WHERE c.user_id = ?',
        'i',
        [$_SESSION['user_id']]
    );
    foreach ($rows as $r) {
        $pid = (int) $r['product_id'];
        $qty = ($cart[$pid] ?? 0) + (int) $r['quantity'];
        $qty = min($qty, max(0, (int) $r['stock']));
        if ($qty > 0) {
            $cart[$pid] = $qty;
        }
    }
    $_SESSION['cart'] = $cart;
    cart_save_db();
}

/**
 * Add a product to the cart. Returns [bool success, string message].
 */
function cart_add($productId, $qty = 1)
{
    $productId = (int) $productId;
    $qty = max(1, (int) $qty);
    $product = db_fetch_one('SELECT id, name, stock FROM products WHERE id = ?', 'i', [$productId]);
    if (!$product) {
        return [false, 'Product not found.'];
    }
    if ((int) $product['stock'] <= 0) {
        return [false, 'Sorry, this product is out of stock.'];
    }
    $cart = get_cart();
    $newQty = ($cart[$productId] ?? 0) + $qty;
    $msg = $product['name'] . ' added to cart.';
    if ($newQty > (int) $product['stock']) {
        $newQty = (int) $product['stock'];
        $msg = 'Only ' . $product['stock'] . ' units available. Cart updated to maximum stock.';
    }
    $cart[$productId] = $newQty;
    $_SESSION['cart'] = $cart;
    cart_save_db();
    return [true, $msg];
}

/** Set an exact quantity (0 removes). */
function cart_update($productId, $qty)
{
    $productId = (int) $productId;
    $qty = (int) $qty;
    $cart = get_cart();
    if (!isset($cart[$productId])) {
        return [false, 'Item not in cart.'];
    }
    if ($qty <= 0) {
        return cart_remove($productId);
    }
    $stock = (int) db_value('SELECT stock FROM products WHERE id = ?', 'i', [$productId]);
    $msg = 'Cart updated.';
    if ($qty > $stock) {
        $qty = $stock;
        $msg = 'Only ' . $stock . ' units available.';
    }
    if ($qty <= 0) {
        return cart_remove($productId);
    }
    $cart[$productId] = $qty;
    $_SESSION['cart'] = $cart;
    cart_save_db();
    return [true, $msg];
}

function cart_remove($productId)
{
    $cart = get_cart();
    unset($cart[(int) $productId]);
    $_SESSION['cart'] = $cart;
    cart_save_db();
    return [true, 'Item removed from cart.'];
}

function cart_clear()
{
    $_SESSION['cart'] = [];
    if (is_logged_in()) {
        db_execute('DELETE FROM cart WHERE user_id = ?', 'i', [$_SESSION['user_id']]);
    }
}

/**
 * Return detailed cart lines + totals:
 * ['items' => [...], 'subtotal' => x, 'shipping' => y, 'total' => z, 'count' => n]
 */
function get_cart_details()
{
    $cart = get_cart();
    $items = [];
    $subtotal = 0;
    if ($cart) {
        $ids = array_map('intval', array_keys($cart));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = db_fetch_all(
            "SELECT id, name, price, stock, image_url FROM products WHERE id IN ($placeholders)",
            str_repeat('i', count($ids)),
            $ids
        );
        $found = [];
        foreach ($rows as $p) {
            $qty = (int) $cart[$p['id']];
            $p['quantity'] = $qty;
            $p['line_total'] = $qty * (float) $p['price'];
            $subtotal += $p['line_total'];
            $items[] = $p;
            $found[] = (int) $p['id'];
        }
        // Remove products that no longer exist
        foreach ($ids as $id) {
            if (!in_array($id, $found, true)) {
                unset($_SESSION['cart'][$id]);
            }
        }
    }
    $shipping = ($subtotal == 0 || $subtotal >= FREE_SHIPPING_THRESHOLD) ? 0 : SHIPPING_FEE;
    return [
        'items'    => $items,
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total'    => $subtotal + $shipping,
        'count'    => get_cart_count(),
    ];
}

/* ---------------------------------------------------------------
 * Wishlist
 * ------------------------------------------------------------- */

function get_wishlist_ids()
{
    if (!is_logged_in()) {
        return [];
    }
    $rows = db_fetch_all('SELECT product_id FROM wishlist WHERE user_id = ?', 'i', [$_SESSION['user_id']]);
    return array_map('intval', array_column($rows, 'product_id'));
}

/* ---------------------------------------------------------------
 * Presentation helpers
 * ------------------------------------------------------------- */

/** Return HTML for a 5-star rating (supports half stars). */
function get_rating_stars($rating)
{
    $rating = max(0, min(5, (float) $rating));
    $full = (int) floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    $html = '<span class="rating-stars" title="' . number_format($rating, 1) . ' out of 5">';
    $html .= str_repeat('<i class="fa-solid fa-star"></i>', $full);
    $html .= $half ? '<i class="fa-solid fa-star-half-stroke"></i>' : '';
    $html .= str_repeat('<i class="fa-regular fa-star"></i>', $empty);
    return $html . '</span>';
}

function status_badge($status)
{
    $map = [
        'pending'    => 'warning text-dark',
        'processing' => 'primary',
        'shipped'    => 'indigo',
        'delivered'  => 'success',
        'cancelled'  => 'danger',
    ];
    $cls = $map[$status] ?? 'secondary';
    return '<span class="badge status-badge bg-' . $cls . '">' . e(ucfirst($status)) . '</span>';
}

function stock_badge($stock)
{
    $stock = (int) $stock;
    if ($stock <= 0) {
        return '<span class="badge stock-badge out-of-stock"><i class="fa-solid fa-circle-xmark me-1"></i>Out of Stock</span>';
    }
    if ($stock < 5) {
        return '<span class="badge stock-badge low-stock"><i class="fa-solid fa-triangle-exclamation me-1"></i>Only ' . $stock . ' left</span>';
    }
    return '<span class="badge stock-badge in-stock"><i class="fa-solid fa-circle-check me-1"></i>In Stock</span>';
}

function discount_percent($price, $original)
{
    if ($original && (float) $original > (float) $price) {
        return (int) round((1 - (float) $price / (float) $original) * 100);
    }
    return 0;
}

/** Decode the JSON specs column into an associative array. */
function parse_specs($json)
{
    $specs = json_decode((string) $json, true);
    return is_array($specs) ? $specs : [];
}

/** Render a product card (used on home, shop, wishlist, related). */
function render_product_card($p, $wishlistIds = [], $showRemove = false)
{
    $id = (int) $p['id'];
    $inWish = in_array($id, $wishlistIds, true);
    $discount = discount_percent($p['price'], $p['original_price'] ?? null);
    $link = url('product.php?id=' . $id);
    ob_start(); ?>
    <div class="card product-card h-100">
        <div class="product-img-wrap">
            <a href="<?= $link ?>"><img src="<?= e($p['image_url']) ?>" class="card-img-top product-img" alt="<?= e($p['name']) ?>" loading="lazy" onerror="this.onerror=null;this.src='<?= url('assets/img/no-image.svg') ?>'"></a>
            <?php if ($discount > 0): ?><span class="discount-tag">-<?= $discount ?>%</span><?php endif; ?>
            <?php if ($showRemove): ?>
                <button type="button" class="wishlist-btn active wishlist-remove" data-id="<?= $id ?>" title="Remove from wishlist"><i class="fa-solid fa-trash-can"></i></button>
            <?php else: ?>
                <button type="button" class="wishlist-btn <?= $inWish ? 'active' : '' ?>" data-id="<?= $id ?>" title="Wishlist"><i class="<?= $inWish ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i></button>
            <?php endif; ?>
        </div>
        <div class="card-body d-flex flex-column">
            <?php if (!empty($p['category_name'])): ?>
                <span class="badge category-badge mb-2 align-self-start"><?= e($p['category_name']) ?></span>
            <?php endif; ?>
            <h6 class="card-title product-title"><a href="<?= $link ?>"><?= e($p['name']) ?></a></h6>
            <div class="mb-2 small"><?= get_rating_stars($p['rating']) ?> <span class="text-muted">(<?= (int) $p['review_count'] ?>)</span></div>
            <div class="price-block mb-3">
                <span class="price"><?= format_price($p['price']) ?></span>
                <?php if ($discount > 0): ?><span class="original-price"><?= format_price($p['original_price']) ?></span><?php endif; ?>
            </div>
            <div class="mt-auto d-flex gap-2">
                <?php if ((int) $p['stock'] > 0): ?>
                    <button type="button" class="btn btn-accent btn-sm flex-fill add-to-cart" data-id="<?= $id ?>"><i class="fa-solid fa-cart-plus me-1"></i>Add to Cart</button>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary btn-sm flex-fill" disabled>Out of Stock</button>
                <?php endif; ?>
                <a href="<?= $link ?>" class="btn btn-outline-dark btn-sm" title="View Details"><i class="fa-solid fa-eye"></i></a>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/** Build a pagination query string keeping current GET filters. */
function build_query(array $overrides = [])
{
    $params = array_merge($_GET, $overrides);
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null || $v === []) {
            unset($params[$k]);
        }
    }
    return http_build_query($params);
}

/** Recalculate average rating / review count for a product. */
function refresh_product_rating($productId)
{
    db_execute(
        'UPDATE products p SET
            rating = (SELECT IFNULL(ROUND(AVG(r.rating),1),0) FROM reviews r WHERE r.product_id = p.id),
            review_count = (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id)
         WHERE p.id = ?',
        'i',
        [(int) $productId]
    );
}

/** Return true when the request expects JSON (AJAX). */
function is_ajax()
{
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

function json_response(array $data, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
