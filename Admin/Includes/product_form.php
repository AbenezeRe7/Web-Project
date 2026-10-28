<?php
/**
 * Shared product form logic + markup for add_product.php and edit_product.php.
 *
 * Functions:
 *   product_form_defaults()          -> empty form array
 *   product_form_from_row($row)      -> form array from a DB product row
 *   product_form_process(&$form)     -> validates $_POST, returns [errors, data]
 *   product_form_render($form, $errors, $categories, $submitLabel)
 */

function specs_to_text($json)
{
    $lines = [];
    foreach (parse_specs($json) as $k => $v) {
        $lines[] = $k . ': ' . (is_array($v) ? implode(', ', $v) : $v);
    }
    return implode("\n", $lines);
}

/**
 * Accept either a JSON object or "Key: Value" lines. Returns [array|null, error|null].
 */
function text_to_specs($text)
{
    $text = trim((string) $text);
    if ($text === '') {
        return [[], null];
    }
    if ($text[0] === '{') {
        $decoded = json_decode($text, true);
        if (!is_array($decoded)) {
            return [null, 'Specs JSON is invalid.'];
        }
        $specs = [];
        foreach ($decoded as $k => $v) {
            $specs[sanitize($k)] = sanitize(is_array($v) ? implode(', ', $v) : $v);
        }
        return [$specs, null];
    }
    $specs = [];
    foreach (preg_split('/\r\n|\r|\n/', $text) as $i => $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $pos = strpos($line, ':');
        if ($pos === false) {
            return [null, 'Specs line ' . ($i + 1) . ' must be in "Key: Value" format.'];
        }
        $key = sanitize(substr($line, 0, $pos));
        $val = sanitize(substr($line, $pos + 1));
        if ($key !== '') {
            $specs[$key] = $val;
        }
    }
    return [$specs, null];
}

function product_form_defaults()
{
    return ['name' => '', 'description' => '', 'price' => '', 'original_price' => '', 'stock' => '0',
        'category_id' => '', 'image_url' => '', 'specs' => "Brand: \nModel: \nColor: \nWarranty: 1 Year", 'is_featured' => 0];
}

function product_form_from_row($row)
{
    return ['name' => $row['name'], 'description' => $row['description'], 'price' => $row['price'],
        'original_price' => $row['original_price'], 'stock' => $row['stock'], 'category_id' => $row['category_id'],
        'image_url' => $row['image_url'], 'specs' => specs_to_text($row['specs']), 'is_featured' => (int) $row['is_featured']];
}

function product_form_process(&$form)
{
    $errors = [];
    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please submit again.';
    }
    $form['name'] = sanitize($_POST['name'] ?? '');
    $form['description'] = sanitize($_POST['description'] ?? '');
    $form['price'] = trim($_POST['price'] ?? '');
    $form['original_price'] = trim($_POST['original_price'] ?? '');
    $form['stock'] = trim($_POST['stock'] ?? '0');
    $form['category_id'] = (int) ($_POST['category_id'] ?? 0);
    $form['image_url'] = trim($_POST['image_url'] ?? '');
    $form['specs'] = (string) ($_POST['specs'] ?? '');
    $form['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;

    if ($form['name'] === '') {
        $errors[] = 'Product name is required.';
    }
    if (!is_numeric($form['price']) || (float) $form['price'] <= 0) {
        $errors[] = 'Price must be a positive number.';
    }
    if ($form['original_price'] !== '' && (!is_numeric($form['original_price']) || (float) $form['original_price'] < 0)) {
        $errors[] = 'Original price must be a valid number.';
    }
    if (!ctype_digit((string) $form['stock'])) {
        $errors[] = 'Stock must be a whole number (0 or more).';
    }
    if (!$form['category_id'] || !db_value('SELECT COUNT(*) FROM categories WHERE id = ?', 'i', [$form['category_id']])) {
        $errors[] = 'Please choose a valid category.';
    }
    if ($form['image_url'] !== '' && !filter_var($form['image_url'], FILTER_VALIDATE_URL) && !preg_match('#^[\w\-/\.]+$#', $form['image_url'])) {
        $errors[] = 'Image URL is not valid.';
    }
    [$specs, $specErr] = text_to_specs($form['specs']);
    if ($specErr) {
        $errors[] = $specErr;
    }

    $data = [
        'name' => $form['name'],
        'description' => $form['description'],
        'price' => (float) $form['price'],
        'original_price' => $form['original_price'] === '' ? null : (float) $form['original_price'],
        'stock' => (int) $form['stock'],
        'category_id' => $form['category_id'],
        'image_url' => $form['image_url'],
        'specs' => json_encode($specs ?: new stdClass()),
        'is_featured' => $form['is_featured'],
    ];
    return [$errors, $data];
}

function product_form_render($form, $errors, $categories, $submitLabel)
{
    ?>
    <?php if ($errors): ?>
        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><strong>Please fix the following:</strong>
            <ul class="mb-0 mt-2"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="admin-card p-4">
                    <h5 class="mb-3">Product Information</h5>
                    <div class="mb-3">
                        <label class="form-label" for="name">Product Name *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= e($form['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5"><?= e($form['description']) ?></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="price">Price (&#8377;) *</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?= e($form['price']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="original_price">Original Price (&#8377;)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="original_price" name="original_price" value="<?= e($form['original_price']) ?>">
                            <div class="form-text">Shown with strikethrough if higher than price.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="stock">Stock *</label>
                            <input type="number" min="0" step="1" class="form-control" id="stock" name="stock" value="<?= e($form['stock']) ?>" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label" for="specs">Specifications</label>
                        <textarea class="form-control font-monospace" id="specs" name="specs" rows="8" placeholder="Brand: Sony&#10;Model: WH-1000XM5&#10;Color: Black&#10;Warranty: 1 Year"><?= e($form['specs']) ?></textarea>
                        <div class="form-text">One spec per line as <code>Key: Value</code>, or paste a JSON object like <code>{"Brand":"Sony"}</code>.</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="admin-card p-4 mb-4">
                    <h5 class="mb-3">Organization</h5>
                    <div class="mb-3">
                        <label class="form-label" for="category_id">Category *</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= (int) $form['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_featured" name="is_featured" value="1" <?= $form['is_featured'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_featured">Featured on homepage</label>
                    </div>
                </div>
                <div class="admin-card p-4 mb-4">
                    <h5 class="mb-3">Product Image</h5>
                    <label class="form-label" for="image_url">Image URL</label>
                    <input type="text" class="form-control mb-3" id="image_url" name="image_url" value="<?= e($form['image_url']) ?>" placeholder="https://images.unsplash.com/...">
                    <img id="image-preview" src="<?= e($form['image_url'] ?: url('assets/img/no-image.svg')) ?>" alt="Preview" class="img-fluid rounded border image-preview" onerror="this.onerror=null;this.src='<?= url('assets/img/no-image.svg') ?>'">
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-accent btn-lg"><i class="fa-solid fa-floppy-disk me-2"></i><?= e($submitLabel) ?></button>
                    <a href="<?= url('admin/products.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
    <?php
}
