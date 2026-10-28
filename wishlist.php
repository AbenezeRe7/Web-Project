<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$uid = (int) $_SESSION['user_id'];

// Non-JS fallback: remove via GET
if (isset($_GET['remove'])) {
    db_execute('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?', 'ii', [$uid, (int) $_GET['remove']]);
    flash('wishlist', 'Item removed from your wishlist.', 'info');
    redirect('wishlist.php');
}

$items = db_fetch_all(
    'SELECT p.*, c.name AS category_name FROM wishlist w
     JOIN products p ON p.id = w.product_id
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE w.user_id = ? ORDER BY w.created_at DESC',
    'i',
    [$uid]
);

$page_title = 'My Wishlist';
$active_page = 'wishlist';
require __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1><i class="fa-solid fa-heart me-2"></i>My Wishlist</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Wishlist</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-4">
    <div id="wishlist-empty" class="empty-state" style="<?= $items ? 'display:none' : '' ?>">
        <i class="fa-regular fa-heart"></i>
        <h4>Your wishlist is empty</h4>
        <p class="text-muted">Tap the heart icon on any product to save it here.</p>
        <a href="<?= url('products.php') ?>" class="btn btn-accent">Explore Products</a>
    </div>
    <?php if ($items): ?>
        <p class="text-muted"><span id="wishlist-count"><?= count($items) ?></span> saved item(s)</p>
        <div class="row g-4" id="wishlist-grid">
            <?php foreach ($items as $p): ?>
                <div class="col-sm-6 col-lg-4 col-xl-3 wishlist-item" data-id="<?= (int) $p['id'] ?>"><?= render_product_card($p, [], true) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
