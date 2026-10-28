<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

/* ---------- Read & validate filters ---------- */
$selectedCats = $_GET['category'] ?? [];
if (!is_array($selectedCats)) {
    $selectedCats = [$selectedCats];
}
$selectedCats = array_values(array_filter(array_map('intval', $selectedCats)));
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? max(0, (float) $_GET['min_price']) : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? max(0, (float) $_GET['max_price']) : null;
$search   = trim((string) ($_GET['search'] ?? ''));
$sort     = $_GET['sort'] ?? 'newest';
$page     = max(1, (int) ($_GET['page'] ?? 1));

$sortOptions = [
    'newest'     => ['Newest First', 'p.created_at DESC, p.id DESC'],
    'price_asc'  => ['Price: Low to High', 'p.price ASC'],
    'price_desc' => ['Price: High to Low', 'p.price DESC'],
    'rating'     => ['Top Rated', 'p.rating DESC, p.review_count DESC'],
];
if (!isset($sortOptions[$sort])) {
    $sort = 'newest';
}

/* ---------- Build WHERE clause ---------- */
$where = [];
$types = '';
$params = [];
if ($selectedCats) {
    $where[] = 'p.category_id IN (' . implode(',', array_fill(0, count($selectedCats), '?')) . ')';
    $types .= str_repeat('i', count($selectedCats));
    $params = array_merge($params, $selectedCats);
}
if ($minPrice !== null) {
    $where[] = 'p.price >= ?';
    $types .= 'd';
    $params[] = $minPrice;
}
if ($maxPrice !== null) {
    $where[] = 'p.price <= ?';
    $types .= 'd';
    $params[] = $maxPrice;
}
if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $types .= 'ss';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int) db_value("SELECT COUNT(*) FROM products p $whereSql", $types, $params);
$totalPages = max(1, (int) ceil($total / PRODUCTS_PER_PAGE));
$page = min($page, $totalPages);
$offset = ($page - 1) * PRODUCTS_PER_PAGE;

$products = db_fetch_all(
    "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id
     $whereSql ORDER BY {$sortOptions[$sort][1]} LIMIT ? OFFSET ?",
    $types . 'ii',
    array_merge($params, [PRODUCTS_PER_PAGE, $offset])
);

$categories = db_fetch_all(
    'SELECT c.id, c.name, c.icon, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id ORDER BY c.id'
);
$priceBounds = db_fetch_one('SELECT IFNULL(MIN(price),0) AS min_p, IFNULL(MAX(price),0) AS max_p FROM products');
$wishlistIds = get_wishlist_ids();

$heading = 'All Products';
if (count($selectedCats) === 1) {
    foreach ($categories as $c) {
        if ((int) $c['id'] === $selectedCats[0]) {
            $heading = $c['name'];
        }
    }
}

$page_title = $search !== '' ? 'Search: ' . $search : $heading;
$active_page = 'shop';
require __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1><?= e($heading) ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Shop</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-4">
    <div class="row g-4">
        <!-- Sidebar filters -->
        <aside class="col-lg-3">
            <button class="btn btn-dark w-100 d-lg-none mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel">
                <i class="fa-solid fa-filter me-2"></i>Show Filters
            </button>
            <div class="collapse d-lg-block" id="filterPanel">
                <form method="get" action="<?= url('products.php') ?>" class="filter-card">
                    <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?= e($search) ?>"><?php endif; ?>
                    <input type="hidden" name="sort" value="<?= e($sort) ?>">

                    <h6 class="filter-title"><i class="fa-solid fa-layer-group me-2"></i>Categories</h6>
                    <?php foreach ($categories as $c): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="category[]" value="<?= (int) $c['id'] ?>" id="cat<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $selectedCats, true) ? 'checked' : '' ?>>
                            <label class="form-check-label d-flex justify-content-between" for="cat<?= (int) $c['id'] ?>">
                                <span><i class="fa-solid <?= e($c['icon'] ?: 'fa-microchip') ?> me-2 text-muted"></i><?= e($c['name']) ?></span>
                                <span class="badge bg-light text-dark"><?= (int) $c['product_count'] ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>

                    <hr>
                    <h6 class="filter-title"><i class="fa-solid fa-indian-rupee-sign me-2"></i>Price Range</h6>
                    <input type="range" class="form-range price-slider" id="priceSlider" min="0" max="<?= (int) ceil($priceBounds['max_p']) ?>" step="500"
                           value="<?= (int) ($maxPrice ?? ceil($priceBounds['max_p'])) ?>" data-target="#max_price">
                    <div class="small text-muted mb-2">Up to <strong id="priceSliderValue">&#8377;<?= number_format($maxPrice ?? ceil($priceBounds['max_p'])) ?></strong></div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small mb-1" for="min_price">Min (&#8377;)</label>
                            <input type="number" class="form-control form-control-sm" name="min_price" id="min_price" min="0" value="<?= $minPrice !== null ? e($minPrice) : '' ?>" placeholder="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1" for="max_price">Max (&#8377;)</label>
                            <input type="number" class="form-control form-control-sm" name="max_price" id="max_price" min="0" value="<?= $maxPrice !== null ? e($maxPrice) : '' ?>" placeholder="<?= (int) ceil($priceBounds['max_p']) ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-accent w-100 mt-3"><i class="fa-solid fa-check me-1"></i>Apply Filters</button>
                    <a href="<?= url('products.php') ?>" class="btn btn-outline-secondary w-100 mt-2">Clear All</a>
                </form>
            </div>
        </aside>

        <!-- Product grid -->
        <div class="col-lg-9">
            <div class="shop-toolbar">
                <form method="get" action="<?= url('products.php') ?>" class="flex-grow-1 shop-search">
                    <?php foreach ($selectedCats as $cid): ?><input type="hidden" name="category[]" value="<?= $cid ?>"><?php endforeach; ?>
                    <?php if ($minPrice !== null): ?><input type="hidden" name="min_price" value="<?= e($minPrice) ?>"><?php endif; ?>
                    <?php if ($maxPrice !== null): ?><input type="hidden" name="max_price" value="<?= e($maxPrice) ?>"><?php endif; ?>
                    <input type="hidden" name="sort" value="<?= e($sort) ?>">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" name="search" class="form-control" placeholder="Search products..." value="<?= e($search) ?>">
                        <button class="btn btn-dark" type="submit">Search</button>
                    </div>
                </form>
                <form method="get" action="<?= url('products.php') ?>" class="d-flex align-items-center gap-2">
                    <?php foreach ($selectedCats as $cid): ?><input type="hidden" name="category[]" value="<?= $cid ?>"><?php endforeach; ?>
                    <?php if ($minPrice !== null): ?><input type="hidden" name="min_price" value="<?= e($minPrice) ?>"><?php endif; ?>
                    <?php if ($maxPrice !== null): ?><input type="hidden" name="max_price" value="<?= e($maxPrice) ?>"><?php endif; ?>
                    <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?= e($search) ?>"><?php endif; ?>
                    <label for="sort" class="small text-nowrap text-muted">Sort by</label>
                    <select name="sort" id="sort" class="form-select auto-submit">
                        <?php foreach ($sortOptions as $key => $opt): ?>
                            <option value="<?= $key ?>" <?= $sort === $key ? 'selected' : '' ?>><?= $opt[0] ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <p class="text-muted small my-3">
                Showing <?= $total ? ($offset + 1) . '&ndash;' . min($offset + PRODUCTS_PER_PAGE, $total) : 0 ?> of <?= $total ?> products
                <?php if ($search !== ''): ?> for &ldquo;<strong><?= e($search) ?></strong>&rdquo;<?php endif; ?>
            </p>

            <?php if ($products): ?>
                <div class="row g-4">
                    <?php foreach ($products as $p): ?>
                        <div class="col-sm-6 col-xl-4"><?= render_product_card($p, $wishlistIds) ?></div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-5" aria-label="Product pages">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= build_query(['page' => $page - 1]) ?>">&laquo; Prev</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= build_query(['page' => $i]) ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= build_query(['page' => $page + 1]) ?>">Next &raquo;</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <h4>No products found</h4>
                    <p class="text-muted">Try adjusting your search or filters.</p>
                    <a href="<?= url('products.php') ?>" class="btn btn-accent">View All Products</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
