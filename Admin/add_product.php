<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/product_form.php';
require_admin();

$categories = db_fetch_all('SELECT id, name FROM categories ORDER BY name');
$form = product_form_defaults();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$errors, $d] = product_form_process($form);
    if (!$errors) {
        db_execute(
            'INSERT INTO products (name, description, price, original_price, stock, category_id, image_url, specs, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'ssddiissi',
            [$d['name'], $d['description'], $d['price'], $d['original_price'], $d['stock'], $d['category_id'], $d['image_url'], $d['specs'], $d['is_featured']]
        );
        flash('admin', 'Product "' . $d['name'] . '" added successfully.', 'success');
        redirect('admin/products.php');
    }
}

$page_title = 'Add New Product';
$admin_page = 'products';
require __DIR__ . '/includes/admin_header.php';
?>
<nav aria-label="breadcrumb"><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= url('admin/index.php') ?>">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?= url('admin/products.php') ?>">Products</a></li>
    <li class="breadcrumb-item active">Add</li>
</ol></nav>
<?php product_form_render($form, $errors, $categories, 'Save Product'); ?>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
