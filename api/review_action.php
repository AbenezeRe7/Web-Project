<?php
/**
 * Review submission endpoint.
 * POST product_id, rating (1-5), comment
 * Responds with JSON for AJAX requests, otherwise redirects back to the product page.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$productId = (int) ($_POST['product_id'] ?? 0);
$back = 'product.php?id=' . $productId . '#reviews';

function review_result($ok, $message, $back, $code = 200)
{
    if (is_ajax()) {
        json_response(['success' => $ok, 'message' => $message], $code);
    }
    flash('review', $message, $ok ? 'success' : 'danger');
    redirect($back);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    review_result(false, 'Invalid request method.', 'products.php', 405);
}
if (!is_logged_in()) {
    if (is_ajax()) {
        json_response(['success' => false, 'message' => 'Please log in to write a review.'], 401);
    }
    flash('auth', 'Please log in to write a review.', 'warning');
    redirect('login.php?redirect=' . urlencode($back));
}
if (!verify_csrf()) {
    review_result(false, 'Session expired. Please try again.', $back, 419);
}

$uid = (int) $_SESSION['user_id'];
$rating = (int) ($_POST['rating'] ?? 0);
$comment = sanitize($_POST['comment'] ?? '');

if (!$productId || !db_value('SELECT COUNT(*) FROM products WHERE id = ?', 'i', [$productId])) {
    review_result(false, 'Product not found.', 'products.php', 404);
}
if ($rating < 1 || $rating > 5) {
    review_result(false, 'Please select a rating between 1 and 5 stars.', $back, 422);
}
if ($comment === '' || str_length($comment) < 3) {
    review_result(false, 'Please write a short comment (at least 3 characters).', $back, 422);
}
if (str_length(trim((string) $_POST['comment'])) > 1000) {
    review_result(false, 'Your review is too long (maximum 1000 characters).', $back, 422);
}
if (db_value('SELECT COUNT(*) FROM reviews WHERE product_id = ? AND user_id = ?', 'ii', [$productId, $uid])) {
    review_result(false, 'You have already reviewed this product.', $back, 409);
}

db_execute('INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)', 'iiis', [$productId, $uid, $rating, $comment]);
refresh_product_rating($productId);

review_result(true, 'Thank you! Your review has been posted.', $back);
