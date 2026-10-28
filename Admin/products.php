<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

// Delete product (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!verify_csrf()) {
        flash('admin', 'Session expired. Please try again.', 'danger');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $name = db_value('SELECT name FROM products WHERE id = ?', 'i', [$id]);
        if ($name) {
            // order_items keep product_name (FK is ON DELETE SET NULL); wishlist/cart/reviews cascade
            db_execute('DELETE FROM products WHERE id = ?', 'i', [$id]);
            flash('admin', 'Product "' . $name . '" deleted.', 'success');
        } else {
            flash('admin', 'Product not found.', 'danger');
        }
    }
    redirect('admin/products.php?' . build_query());
}

$search = trim((string) ($_GET['search'] ?? ''));
$catFilter = (int) ($_GET['category'] ?? 0);
$where = [];
$types = '';
$params = [];
if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $types .= 'ss';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($catFilter) {
    $where[] = 'p.category_id = ?';
    $types .= 'i';
    $params[] = $catFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$products = db_fetch_all(
    "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id $whereSql ORDER BY p.id DESC",
    $types,
    $params
);
$categories = db_fetch_all('SELECT id, name FROM categories ORDER BY name');

$page_title = 'Products';
$admin_page = 'products';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-card">
    <div class="admin-card-header flex-wrap gap-2">
        <form method="get" class="d-flex flex-wrap gap-2 flex-grow-1">
            <div class="input-group" style="max-width:320px">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="search" name="search" class="form-control" placeholder="Search products..." value="<?= e($search) ?>">
            </div>
            <select name="category" class="form-select auto-submit" style="max-width:220px">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $catFilter === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-dark" type="submit">Search</button>
            <?php if ($search !== '' || $catFilter): ?><a href="<?= url('admin/products.php') ?>" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
        </form>
        <a href="<?= url('admin/add_product.php') ?>" class="btn btn-accent"><i class="fa-solid fa-plus me-1"></i>Add New Product</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead>
            <tr><th>ID</th><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Rating</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= (int) $p['id'] ?></td>
                    <td><img src="<?= e($p['image_url']) ?>" class="admin-thumb" alt="" onerror="this.onerror=null;this.src='<?= url('assets/img/no-image.svg') ?>'"></td>
                    <td>
                        <div class="fw-semibold"><?= e($p['name']) ?></div>
                        <?php if ((int) $p['is_featured']): ?><span class="badge bg-warning text-dark"><i class="fa-solid fa-star me-1"></i>Featured</span><?php endif; ?>
                    </td>
                    <td><?= e($p['category_name'] ?? 'Uncategorized') ?></td>
                    <td class="text-nowrap">
                        <?= format_price($p['price']) ?>
                        <?php if ($p['original_price'] > $p['price']): ?><div class="small text-muted text-decoration-line-through"><?= format_price($p['original_price']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <?php $s = (int) $p['stock']; ?>
                        <span class="badge <?= $s === 0 ? 'bg-danger' : ($s < 5 ? 'bg-warning text-dark' : 'bg-success') ?>"><?= $s ?></span>
                    </td>
                    <td class="small"><i class="fa-solid fa-star text-warning"></i> <?= number_format((float) $p['rating'], 1) ?> (<?= (int) $p['review_count'] ?>)</td>
                    <td class="text-end text-nowrap">
                        <a href="<?= url('product.php?id=' . (int) $p['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View"><i class="fa-solid fa-eye"></i></a>
                        <a href="<?= url('admin/edit_product.php?id=' . (int) $p['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                        <form method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger confirm-delete" data-confirm="Delete &quot;<?= e($p['name']) ?>&quot;? This cannot be undone." title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$products): ?><tr><td colspan="8" class="text-center text-muted py-5">No products found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-3 small text-muted border-top"><?= count($products) ?> product(s)</div>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
