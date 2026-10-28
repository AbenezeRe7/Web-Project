<?php
/**
 * Wishlist toggle endpoint.
 * POST product_id  -> JSON {status: 'added'|'removed'|'login_required'|'error', message, count}
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'error', 'message' => 'Invalid request method.'], 405);
}
if (!is_logged_in()) {
    json_response(['status' => 'login_required', 'message' => 'Please log in to use your wishlist.', 'login_url' => url('login.php')], 401);
}
if (!verify_csrf()) {
    json_response(['status' => 'error', 'message' => 'Session expired. Please refresh the page.'], 419);
}

$uid = (int) $_SESSION['user_id'];
$productId = (int) ($_POST['product_id'] ?? 0);
if (!$productId || !db_value('SELECT COUNT(*) FROM products WHERE id = ?', 'i', [$productId])) {
    json_response(['status' => 'error', 'message' => 'Product not found.'], 404);
}

$exists = db_value('SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?', 'ii', [$uid, $productId]);
if ($exists) {
    db_execute('DELETE FROM wishlist WHERE id = ?', 'i', [$exists]);
    $status = 'removed';
    $message = 'Removed from your wishlist.';
} else {
    db_execute('INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)', 'ii', [$uid, $productId]);
    $status = 'added';
    $message = 'Added to your wishlist.';
}

json_response([
    'status'  => $status,
    'message' => $message,
    'count'   => (int) db_value('SELECT COUNT(*) FROM wishlist WHERE user_id = ?', 'i', [$uid]),
]);
