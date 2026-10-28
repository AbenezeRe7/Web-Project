<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$categories = db_fetch_all(
    'SELECT c.*, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id ORDER BY c.id'
);
$featured = db_fetch_all(
    'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.is_featured = 1 ORDER BY p.created_at DESC, p.id ASC LIMIT 8'
);
$deals = db_fetch_all(
    'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.original_price > p.price AND p.stock > 0
     ORDER BY (p.original_price - p.price) / p.original_price DESC LIMIT 4'
);
$wishlistIds = get_wishlist_ids();

$page_title = 'Next-Gen Electronics Store';
$active_page = 'home';
require __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 text-center text-lg-start">
                <span class="hero-tag"><i class="fa-solid fa-bolt me-1"></i> New arrivals every week</span>
                <h1 class="hero-title mt-3">Next-Gen <span class="text-accent">Electronics</span><br>at Unbeatable Prices</h1>
                <p class="hero-subtitle">Discover the latest smartphones, laptops, audio gear, gaming consoles and smart home devices &mdash; 100% genuine with official warranty and fast delivery.</p>
                <div class="d-flex gap-3 justify-content-center justify-content-lg-start flex-wrap">
                    <a href="<?= url('products.php') ?>" class="btn btn-accent btn-lg px-4"><i class="fa-solid fa-bag-shopping me-2"></i>Shop Now</a>
                    <a href="#deals" class="btn btn-outline-light btn-lg px-4"><i class="fa-solid fa-tags me-2"></i>View Deals</a>
                </div>
                <div class="hero-stats mt-4">
                    <div><strong>20+</strong><span>Top Brands</span></div>
                    <div><strong>24/7</strong><span>Support</span></div>
                    <div><strong>7 Day</strong><span>Easy Returns</span></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image-wrap">
                    <img src="https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=1000&q=80" alt="Latest electronics" class="img-fluid hero-image">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features strip -->
<section class="features-strip">
    <div class="container">
        <div class="row g-3 text-center text-md-start">
            <div class="col-6 col-md-3"><div class="feature-item"><i class="fa-solid fa-truck-fast"></i><div><h6>Free Shipping</h6><small>On orders over &#8377;<?= FREE_SHIPPING_THRESHOLD ?></small></div></div></div>
            <div class="col-6 col-md-3"><div class="feature-item"><i class="fa-solid fa-shield-halved"></i><div><h6>Genuine Products</h6><small>Official brand warranty</small></div></div></div>
            <div class="col-6 col-md-3"><div class="feature-item"><i class="fa-solid fa-rotate-left"></i><div><h6>Easy Returns</h6><small>7-day replacement</small></div></div></div>
            <div class="col-6 col-md-3"><div class="feature-item"><i class="fa-solid fa-lock"></i><div><h6>Secure Payment</h6><small>COD &amp; UPI available</small></div></div></div>
        </div>
    </div>
</section>

<!-- Categories -->
<section class="py-5">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Shop by <span class="text-accent">Category</span></h2>
            <a href="<?= url('products.php') ?>" class="section-link">All products <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-3 row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-7 category-row">
            <?php foreach ($categories as $c): ?>
                <div class="col">
                    <a href="<?= url('products.php?category=' . (int) $c['id']) ?>" class="category-card">
                        <div class="category-icon"><i class="fa-solid <?= e($c['icon'] ?: 'fa-microchip') ?>"></i></div>
                        <h6><?= e($c['name']) ?></h6>
                        <small><?= (int) $c['product_count'] ?> products</small>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured -->
<section class="py-5 bg-soft">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Featured <span class="text-accent">Products</span></h2>
            <a href="<?= url('products.php') ?>" class="section-link">View all <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
        <?php if ($featured): ?>
            <div class="row g-4">
                <?php foreach ($featured as $p): ?>
                    <div class="col-sm-6 col-lg-3"><?= render_product_card($p, $wishlistIds) ?></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">No featured products yet.</p>
        <?php endif; ?>
    </div>
</section>

<!-- Deals banner -->
<section class="py-5" id="deals">
    <div class="container">
        <div class="deals-banner">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="hero-tag"><i class="fa-solid fa-fire me-1"></i> Limited time offer</span>
                    <h2 class="mt-3 mb-2">Mega Electronics Sale &mdash; <span class="text-accent">Up to 30% OFF</span></h2>
                    <p class="mb-4">Grab the hottest deals on headphones, TVs, laptops and more. Free delivery and easy returns on every order.</p>
                    <div class="countdown mb-4" data-countdown="<?= date('Y-m-d', strtotime('+3 days')) ?>T23:59:59">
                        <div><span class="cd-days">00</span><small>Days</small></div>
                        <div><span class="cd-hours">00</span><small>Hours</small></div>
                        <div><span class="cd-mins">00</span><small>Mins</small></div>
                        <div><span class="cd-secs">00</span><small>Secs</small></div>
                    </div>
                    <a href="<?= url('products.php?sort=price_asc') ?>" class="btn btn-accent btn-lg"><i class="fa-solid fa-bolt me-2"></i>Grab the Deals</a>
                </div>
                <div class="col-lg-5 text-center">
                    <img src="https://images.unsplash.com/photo-1550009158-9ebf69173e03?auto=format&fit=crop&w=800&q=80" alt="Electronics deals" class="img-fluid deals-image">
                </div>
            </div>
        </div>

        <?php if ($deals): ?>
            <div class="section-header mt-5">
                <h2 class="section-title">Top <span class="text-accent">Deals</span> Today</h2>
            </div>
            <div class="row g-4">
                <?php foreach ($deals as $p): ?>
                    <div class="col-sm-6 col-lg-3"><?= render_product_card($p, $wishlistIds) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Newsletter -->
<section class="pb-5">
    <div class="container">
        <div class="newsletter-box text-center">
            <h3><i class="fa-solid fa-envelope-open-text text-accent me-2"></i>Stay ahead of the tech curve</h3>
            <p class="text-muted">Subscribe for exclusive offers, launches and reviews.</p>
            <form class="row g-2 justify-content-center newsletter-form" action="<?= url('contact.php') ?>" method="get">
                <div class="col-sm-7 col-md-5"><input type="email" name="subscribe" class="form-control form-control-lg" placeholder="Your email address" required></div>
                <div class="col-auto"><button class="btn btn-dark btn-lg" type="submit">Subscribe</button></div>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
