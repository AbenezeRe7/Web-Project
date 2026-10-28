<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
$product = db_fetch_one(
    'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ?',
    'i',
    [$id]
);
if (!$product) {
    flash('product', 'Product not found.', 'danger');
    redirect('products.php');
}

$specs = parse_specs($product['specs']);
$reviews = db_fetch_all(
    'SELECT r.*, u.name AS user_name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC',
    'i',
    [$id]
);
$ratingBreakdown = array_fill(1, 5, 0);
foreach ($reviews as $r) {
    $ratingBreakdown[(int) $r['rating']]++;
}
$userReviewed = false;
if (is_logged_in()) {
    foreach ($reviews as $r) {
        if ((int) $r['user_id'] === (int) $_SESSION['user_id']) {
            $userReviewed = true;
            break;
        }
    }
}
$related = db_fetch_all(
    'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.category_id = ? AND p.id <> ? ORDER BY p.rating DESC LIMIT 4',
    'ii',
    [(int) $product['category_id'], $id]
);
$wishlistIds = get_wishlist_ids();
$inWish = in_array($id, $wishlistIds, true);
$discount = discount_percent($product['price'], $product['original_price']);
$inCart = get_cart()[$id] ?? 0;

$page_title = $product['name'];
$active_page = 'shop';
require __DIR__ . '/includes/header.php';
?>

<div class="container pt-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('products.php') ?>">Shop</a></li>
            <?php if ($product['category_name']): ?>
                <li class="breadcrumb-item"><a href="<?= url('products.php?category=' . (int) $product['category_id']) ?>"><?= e($product['category_name']) ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?= e($product['name']) ?></li>
        </ol>
    </nav>
</div>

<div class="container pb-4">
    <div class="row g-5">
        <div class="col-lg-6">
            <div class="product-detail-img zoom-container">
                <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" class="img-fluid zoom-img" onerror="this.onerror=null;this.src='<?= url('assets/img/no-image.svg') ?>'">
                <?php if ($discount > 0): ?><span class="discount-tag discount-lg">-<?= $discount ?>% OFF</span><?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <?php if ($product['category_name']): ?><span class="badge category-badge mb-2"><?= e($product['category_name']) ?></span><?php endif; ?>
            <h1 class="product-detail-title"><?= e($product['name']) ?></h1>
            <div class="d-flex align-items-center gap-2 mb-3">
                <?= get_rating_stars($product['rating']) ?>
                <strong><?= number_format((float) $product['rating'], 1) ?></strong>
                <a href="#reviews" class="text-muted small">(<?= (int) $product['review_count'] ?> reviews)</a>
            </div>
            <div class="price-block mb-3">
                <span class="price price-lg"><?= format_price($product['price']) ?></span>
                <?php if ($discount > 0): ?>
                    <span class="original-price fs-5"><?= format_price($product['original_price']) ?></span>
                    <span class="badge bg-success ms-2">You save <?= format_price($product['original_price'] - $product['price']) ?></span>
                <?php endif; ?>
                <div class="small text-muted mt-1">Inclusive of all taxes</div>
            </div>
            <div class="mb-3"><?= stock_badge($product['stock']) ?></div>

            <p class="product-description"><?= nl2br(e($product['description'])) ?></p>

            <?php if ((int) $product['stock'] > 0): ?>
                <div class="d-flex flex-wrap align-items-center gap-3 my-4">
                    <div class="qty-control">
                        <button type="button" class="btn qty-btn qty-minus" aria-label="Decrease"><i class="fa-solid fa-minus"></i></button>
                        <input type="number" id="product-qty" class="form-control qty-input" value="1" min="1" max="<?= (int) $product['stock'] ?>">
                        <button type="button" class="btn qty-btn qty-plus" aria-label="Increase"><i class="fa-solid fa-plus"></i></button>
                    </div>
                    <button type="button" class="btn btn-accent btn-lg add-to-cart" data-id="<?= $id ?>" data-qty-input="#product-qty">
                        <i class="fa-solid fa-cart-plus me-2"></i>Add to Cart
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-lg wishlist-btn-lg wishlist-btn <?= $inWish ? 'active' : '' ?>" data-id="<?= $id ?>" title="Add to Wishlist">
                        <i class="<?= $inWish ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                    </button>
                </div>
                <?php if ($inCart): ?>
                    <p class="small text-success"><i class="fa-solid fa-circle-check me-1"></i>You have <?= (int) $inCart ?> of this in your <a href="<?= url('cart.php') ?>">cart</a>.</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="d-flex gap-3 my-4">
                    <button class="btn btn-secondary btn-lg" disabled><i class="fa-solid fa-ban me-2"></i>Out of Stock</button>
                    <button type="button" class="btn btn-outline-danger btn-lg wishlist-btn-lg wishlist-btn <?= $inWish ? 'active' : '' ?>" data-id="<?= $id ?>" title="Add to Wishlist">
                        <i class="<?= $inWish ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                    </button>
                </div>
            <?php endif; ?>

            <div class="product-perks">
                <div><i class="fa-solid fa-truck-fast"></i> Free delivery on orders above &#8377;<?= FREE_SHIPPING_THRESHOLD ?></div>
                <div><i class="fa-solid fa-rotate-left"></i> 7-day replacement policy</div>
                <div><i class="fa-solid fa-shield-halved"></i> <?= e($specs['Warranty'] ?? 'Manufacturer warranty') ?></div>
            </div>
        </div>
    </div>

    <!-- Specs -->
    <div class="row mt-5 g-4">
        <div class="col-lg-7">
            <div class="content-card">
                <h4 class="mb-3"><i class="fa-solid fa-list-check text-accent me-2"></i>Specifications</h4>
                <?php if ($specs): ?>
                    <table class="table table-striped specs-table mb-0">
                        <tbody>
                        <?php foreach ($specs as $key => $value): ?>
                            <tr><th scope="row"><?= e($key) ?></th><td><?= e(is_array($value) ? implode(', ', $value) : $value) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted mb-0">No specifications available.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="content-card">
                <h4 class="mb-3"><i class="fa-solid fa-star text-accent me-2"></i>Customer Ratings</h4>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rating-big"><?= number_format((float) $product['rating'], 1) ?></div>
                    <div><?= get_rating_stars($product['rating']) ?><div class="small text-muted"><?= count($reviews) ?> reviews</div></div>
                </div>
                <?php for ($s = 5; $s >= 1; $s--):
                    $pct = $reviews ? round($ratingBreakdown[$s] / count($reviews) * 100) : 0; ?>
                    <div class="d-flex align-items-center gap-2 mb-1 small">
                        <span class="text-nowrap"><?= $s ?> <i class="fa-solid fa-star text-warning"></i></span>
                        <div class="progress flex-grow-1" style="height:8px"><div class="progress-bar bg-warning" style="width: <?= $pct ?>%"></div></div>
                        <span class="text-muted" style="width:32px"><?= $ratingBreakdown[$s] ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Reviews -->
    <div class="content-card mt-4" id="reviews">
        <h4 class="mb-4"><i class="fa-solid fa-comments text-accent me-2"></i>Reviews (<?= count($reviews) ?>)</h4>

        <?php if (!is_logged_in()): ?>
            <div class="alert alert-light border"><i class="fa-solid fa-circle-info me-2"></i>
                <a href="<?= url('login.php?redirect=' . urlencode('product.php?id=' . $id . '#reviews')) ?>">Log in</a> to write a review.</div>
        <?php elseif ($userReviewed): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i>You reviewed this product. Thank you for your feedback!</div>
        <?php else: ?>
            <form method="post" action="<?= url('api/review_action.php') ?>" class="review-form mb-4">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= $id ?>">
                <h6>Write a review</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="rating" class="form-label">Your Rating</label>
                        <select name="rating" id="rating" class="form-select" required>
                            <option value="">Select rating</option>
                            <option value="5">&#9733;&#9733;&#9733;&#9733;&#9733; Excellent</option>
                            <option value="4">&#9733;&#9733;&#9733;&#9733; Very Good</option>
                            <option value="3">&#9733;&#9733;&#9733; Good</option>
                            <option value="2">&#9733;&#9733; Fair</option>
                            <option value="1">&#9733; Poor</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="comment" class="form-label">Your Review</label>
                        <textarea name="comment" id="comment" rows="3" class="form-control" maxlength="1000" placeholder="Share your experience with this product..." required></textarea>
                    </div>
                    <div class="col-12"><button type="submit" class="btn btn-accent"><i class="fa-solid fa-paper-plane me-1"></i>Submit Review</button></div>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($reviews): ?>
            <?php foreach ($reviews as $r): ?>
                <div class="review-item">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="review-avatar"><?= e(strtoupper(substr($r['user_name'], 0, 1))) ?></div>
                            <div>
                                <strong><?= e($r['user_name']) ?></strong>
                                <div class="small"><?= get_rating_stars($r['rating']) ?></div>
                            </div>
                        </div>
                        <small class="text-muted"><i class="fa-regular fa-calendar me-1"></i><?= date('d M Y', strtotime($r['created_at'])) ?></small>
                    </div>
                    <p class="mb-0 mt-2"><?= nl2br(e($r['comment'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted mb-0">No reviews yet. Be the first to review this product!</p>
        <?php endif; ?>
    </div>

    <?php if ($related): ?>
        <div class="section-header mt-5">
            <h2 class="section-title">Related <span class="text-accent">Products</span></h2>
        </div>
        <div class="row g-4">
            <?php foreach ($related as $p): ?>
                <div class="col-sm-6 col-lg-3"><?= render_product_card($p, $wishlistIds) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
