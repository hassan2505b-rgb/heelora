<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$admin = require_admin();

$pdo     = db();
$id      = (int) ($_GET['id'] ?? 0);
$isEdit  = $id > 0;

$product = [
    'name'        => '',
    'slug'        => '',
    'category_id' => '',
    'tagline'     => '',
    'description' => '',
    'price'       => '',
    'sale_price'  => '',
    'image'       => '',
    'sizes'       => '36,37,38,39,40,41',
    'colors'      => '',
    'stock'       => '0',
    'badge'       => '',
    'is_featured' => 0,
    'is_active'   => 1,
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_set('error', 'That product could not be found.');
        redirect('admin/products.php');
    }
    $product = $row;
}

$categories = get_categories();
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    $product = array_merge($product, [
        'name'        => trim((string) ($_POST['name'] ?? '')),
        'slug'        => trim((string) ($_POST['slug'] ?? '')),
        'category_id' => trim((string) ($_POST['category_id'] ?? '')),
        'tagline'     => trim((string) ($_POST['tagline'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'price'       => trim((string) ($_POST['price'] ?? '')),
        'sale_price'  => trim((string) ($_POST['sale_price'] ?? '')),
        'image'       => trim((string) ($_POST['image'] ?? '')),
        'sizes'       => trim((string) ($_POST['sizes'] ?? '')),
        'colors'      => trim((string) ($_POST['colors'] ?? '')),
        'stock'       => trim((string) ($_POST['stock'] ?? '0')),
        'badge'       => trim((string) ($_POST['badge'] ?? '')),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_active'   => isset($_POST['is_active']) ? 1 : 0,
    ]);

    if ($product['name'] === '') {
        $errors['name'] = 'Please enter a product name.';
    }
    if (!is_numeric($product['price']) || (float) $product['price'] < 0) {
        $errors['price'] = 'Enter a valid price.';
    }
    if ($product['sale_price'] !== '' && (!is_numeric($product['sale_price']) || (float) $product['sale_price'] < 0)) {
        $errors['sale_price'] = 'Enter a valid sale price, or leave it blank.';
    }
    if (!is_numeric($product['stock']) || (int) $product['stock'] < 0) {
        $errors['stock'] = 'Enter a valid stock quantity.';
    }

    if (!$errors) {
        $slug  = slugify($product['slug'] !== '' ? $product['slug'] : $product['name']);
        $slug  = unique_slug($slug, 'products', $isEdit ? $id : 0);
        $catId = $product['category_id'] !== '' ? (int) $product['category_id'] : null;
        $sale  = $product['sale_price'] !== '' ? (float) $product['sale_price'] : null;

        $values = [
            $product['name'], $slug, $catId, $product['tagline'], $product['description'],
            (float) $product['price'], $sale, $product['image'], $product['sizes'], $product['colors'],
            (int) $product['stock'], $product['badge'], (int) $product['is_featured'], (int) $product['is_active'],
        ];

        if ($isEdit) {
            $values[] = $id;
            $pdo->prepare(
                'UPDATE products SET name = ?, slug = ?, category_id = ?, tagline = ?, description = ?, price = ?,
                 sale_price = ?, image = ?, sizes = ?, colors = ?, stock = ?, badge = ?, is_featured = ?, is_active = ?
                 WHERE id = ?'
            )->execute($values);
            flash_set('success', '“' . $product['name'] . '” has been updated.');
        } else {
            $pdo->prepare(
                'INSERT INTO products (name, slug, category_id, tagline, description, price, sale_price, image, sizes, colors, stock, badge, is_featured, is_active, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime("now"))'
            )->execute($values);
            flash_set('success', '“' . $product['name'] . '” has been added.');
        }

        redirect('admin/products.php');
    }

    flash_set('error', 'Please check the highlighted fields.');
}

$admin_title  = $isEdit ? 'Edit product' : 'New product';
$admin_active = 'products';

require __DIR__ . '/includes/header.php';
?>

<form method="post" action="<?= e(admin_url('product-edit.php' . ($isEdit ? '?id=' . $id : ''))) ?>">
    <?= csrf_field() ?>

    <div class="cols">
        <div>
            <div class="admin-card">
                <div class="admin-card__head"><h2 class="admin-card__title">Details</h2></div>

                <div class="field">
                    <label class="field__label" for="p-name">Product name</label>
                    <input class="input" type="text" id="p-name" name="name" value="<?= e($product['name']) ?>" data-slug-source="#p-slug" required>
                    <?php if (isset($errors['name'])): ?><p class="field__error"><?= e($errors['name']) ?></p><?php endif; ?>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label class="field__label" for="p-slug">Slug <span class="text-muted">(auto)</span></label>
                        <input class="input" type="text" id="p-slug" name="slug" value="<?= e($product['slug']) ?>" placeholder="aurelia-pointed-pump">
                    </div>
                    <div class="field">
                        <label class="field__label" for="p-cat">Category</label>
                        <select class="input" id="p-cat" name="category_id">
                            <option value="">Uncategorised</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>" <?= (string) $product['category_id'] === (string) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label class="field__label" for="p-tagline">Tagline</label>
                    <input class="input" type="text" id="p-tagline" name="tagline" value="<?= e($product['tagline']) ?>" placeholder="Soft nude leather · 85mm stiletto">
                </div>

                <div class="field mb-0">
                    <label class="field__label" for="p-desc">Description</label>
                    <textarea class="textarea" id="p-desc" name="description" rows="6"><?= e($product['description']) ?></textarea>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card__head"><h2 class="admin-card__title">Pricing &amp; stock</h2></div>

                <div class="field-row field-row--3">
                    <div class="field">
                        <label class="field__label" for="p-price">Price (<?= e(trim(CURRENCY)) ?>)</label>
                        <input class="input" type="number" step="1" min="0" id="p-price" name="price" value="<?= e((string) $product['price']) ?>" required>
                        <?php if (isset($errors['price'])): ?><p class="field__error"><?= e($errors['price']) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="field__label" for="p-sale">Sale price</label>
                        <input class="input" type="number" step="1" min="0" id="p-sale" name="sale_price" value="<?= e((string) ($product['sale_price'] ?? '')) ?>" placeholder="Optional">
                        <?php if (isset($errors['sale_price'])): ?><p class="field__error"><?= e($errors['sale_price']) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="field__label" for="p-stock">Stock</label>
                        <input class="input" type="number" step="1" min="0" id="p-stock" name="stock" value="<?= e((string) $product['stock']) ?>">
                        <?php if (isset($errors['stock'])): ?><p class="field__error"><?= e($errors['stock']) ?></p><?php endif; ?>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label class="field__label" for="p-sizes">Sizes <span class="text-muted">(comma separated)</span></label>
                        <input class="input" type="text" id="p-sizes" name="sizes" value="<?= e($product['sizes']) ?>" placeholder="36,37,38,39,40,41">
                    </div>
                    <div class="field">
                        <label class="field__label" for="p-colors">Colours <span class="text-muted">(comma separated)</span></label>
                        <input class="input" type="text" id="p-colors" name="colors" value="<?= e($product['colors']) ?>" placeholder="Nude,Black,Burgundy">
                    </div>
                </div>

                <div class="field mb-0">
                    <label class="field__label" for="p-badge">Badge <span class="text-muted">(optional label)</span></label>
                    <input class="input" type="text" id="p-badge" name="badge" value="<?= e($product['badge']) ?>" placeholder="Bestseller, New, Bridal…">
                </div>
            </div>
        </div>

        <div>
            <div class="admin-card">
                <div class="admin-card__head"><h2 class="admin-card__title">Image</h2></div>

                <img class="image-preview" id="p-preview" src="<?= e(product_image($product['image'])) ?>" alt="Preview">

                <div class="field mt-2">
                    <label class="field__label" for="p-image">Image file name or URL</label>
                    <input class="input" type="text" id="p-image" name="image" value="<?= e($product['image']) ?>" placeholder="aurelia-pump.jpg" data-image-input="#p-preview">
                    <p class="field__hint">Files live in <code>assets/img/products/</code>. Paste a full URL to use an external image.</p>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card__head"><h2 class="admin-card__title">Visibility</h2></div>
                <label class="checkbox" style="margin-bottom:.7rem">
                    <input type="checkbox" name="is_active" value="1" <?= (int) $product['is_active'] ? 'checked' : '' ?>>
                    Visible in the store
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="is_featured" value="1" <?= (int) $product['is_featured'] ? 'checked' : '' ?>>
                    Feature on the homepage
                </label>
            </div>

            <div class="admin-card mb-0">
                <div class="form-actions">
                    <button type="submit" class="btn btn--dark"><?= $isEdit ? 'Save changes' : 'Create product' ?></button>
                    <a class="btn btn--light" href="<?= e(admin_url('products.php')) ?>">Cancel</a>
                </div>
                <?php if ($isEdit): ?>
                    <p class="field__hint mt-2">View on the store:
                        <a href="<?= e(url('product.php?slug=' . urlencode((string) $product['slug']))) ?>" target="_blank" rel="noopener"><?= e($product['slug']) ?></a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
