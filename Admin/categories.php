<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash('admin', 'Session expired. Please try again.', 'danger');
        redirect('admin/categories.php');
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = sanitize($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        if ($icon !== '' && !preg_match('/^fa-[a-z0-9\-]+$/', $icon)) {
            $icon = 'fa-' . preg_replace('/[^a-z0-9\-]/', '', strtolower(preg_replace('/^(fa-solid|fa-regular|fas|far)\s+/', '', $icon)));
            $icon = preg_replace('/^fa-fa-/', 'fa-', $icon);
        }
        if ($name === '') {
            flash('admin', 'Category name is required.', 'danger');
        } elseif (db_value('SELECT COUNT(*) FROM categories WHERE name = ?', 's', [$name])) {
            flash('admin', 'A category with this name already exists.', 'warning');
        } else {
            db_execute('INSERT INTO categories (name, icon) VALUES (?, ?)', 'ss', [$name, $icon ?: 'fa-microchip']);
            flash('admin', 'Category "' . $name . '" added.', 'success');
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $cat = db_fetch_one('SELECT name FROM categories WHERE id = ?', 'i', [$id]);
        if ($cat) {
            // products.category_id is ON DELETE SET NULL -> products become "Uncategorized"
            db_execute('DELETE FROM categories WHERE id = ?', 'i', [$id]);
            flash('admin', 'Category "' . $cat['name'] . '" deleted. Its products are now uncategorized.', 'success');
        }
    }
    redirect('admin/categories.php');
}

$categories = db_fetch_all(
    'SELECT c.*, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id ORDER BY c.id'
);
$uncategorized = (int) db_value('SELECT COUNT(*) FROM products WHERE category_id IS NULL');
$iconSuggestions = ['fa-mobile-screen-button', 'fa-laptop', 'fa-headphones', 'fa-house-signal', 'fa-gamepad', 'fa-camera', 'fa-plug', 'fa-tv', 'fa-clock', 'fa-print', 'fa-microchip', 'fa-keyboard'];

$page_title = 'Categories';
$admin_page = 'categories';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="admin-card">
            <div class="admin-card-header"><h5 class="mb-0">All Categories</h5></div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead><tr><th>ID</th><th>Icon</th><th>Name</th><th>Products</th><th>Created</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td><?= (int) $c['id'] ?></td>
                            <td><span class="cat-icon-sm"><i class="fa-solid <?= e($c['icon'] ?: 'fa-microchip') ?>"></i></span></td>
                            <td class="fw-semibold"><?= e($c['name']) ?></td>
                            <td><a href="<?= url('admin/products.php?category=' . (int) $c['id']) ?>" class="badge bg-secondary text-decoration-none"><?= (int) $c['product_count'] ?></a></td>
                            <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                            <td class="text-end">
                                <form method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger confirm-delete"
                                            data-confirm="Delete category &quot;<?= e($c['name']) ?>&quot;?<?= $c['product_count'] ? ' Its ' . (int) $c['product_count'] . ' product(s) will become uncategorized.' : '' ?>">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$categories): ?><tr><td colspan="6" class="text-center text-muted py-5">No categories yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($uncategorized): ?>
                <div class="p-3 border-top small text-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i><?= $uncategorized ?> product(s) have no category.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="admin-card p-4">
            <h5 class="mb-3"><i class="fa-solid fa-plus me-2 text-accent"></i>Add Category</h5>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label class="form-label" for="name">Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="icon">Font Awesome Icon</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-microchip" id="icon-preview"></i></span>
                        <input type="text" class="form-control" id="icon" name="icon" placeholder="fa-tv">
                    </div>
                    <div class="form-text">Click a suggestion or enter any <a href="https://fontawesome.com/icons" target="_blank" rel="noopener">Font Awesome</a> solid icon name.</div>
                    <div class="icon-suggestions mt-2">
                        <?php foreach ($iconSuggestions as $ic): ?>
                            <button type="button" class="btn btn-sm btn-light icon-pick" data-icon="<?= $ic ?>" title="<?= $ic ?>"><i class="fa-solid <?= $ic ?>"></i></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-accent w-100"><i class="fa-solid fa-floppy-disk me-1"></i>Add Category</button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
