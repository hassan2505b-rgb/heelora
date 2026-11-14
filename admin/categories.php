<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$admin = require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $name        = trim((string) ($_POST['name'] ?? ''));
        $slug        = trim((string) ($_POST['slug'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $image       = trim((string) ($_POST['image'] ?? ''));
        $sortOrder   = (int) ($_POST['sort_order'] ?? 0);

        if ($name === '') {
            flash_set('error', 'Please give the category a name.');
            redirect('admin/categories.php');
        }

        $finalSlug = unique_slug($slug !== '' ? $slug : $name, 'categories', $id);

        if ($id > 0) {
            $pdo->prepare('UPDATE categories SET name = ?, slug = ?, description = ?, image = ?, sort_order = ? WHERE id = ?')
                ->execute([$name, $finalSlug, $description, $image, $sortOrder, $id]);
            flash_set('success', '“' . $name . '” has been updated.');
        } else {
            $pdo->prepare('INSERT INTO categories (name, slug, description, image, sort_order) VALUES (?, ?, ?, ?, ?)')
                ->execute([$name, $finalSlug, $description, $image, $sortOrder]);
            flash_set('success', '“' . $name . '” has been created.');
        }
    } elseif ($action === 'delete' && $id > 0) {
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        flash_set('info', 'Category deleted. Its products are now uncategorised.');
    }

    redirect('admin/categories.php');
}

$editing = null;
$editId  = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch() ?: null;
}

$categories = $pdo->query(
    'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
     FROM categories c ORDER BY c.sort_order, c.name'
)->fetchAll();

$admin_title  = 'Categories';
$admin_active = 'categories';

require __DIR__ . '/includes/header.php';
?>

<div class="cols">
    <div>
        <div class="admin-card__head" style="margin-bottom:.6rem">
            <h2 class="admin-card__title"><?= count($categories) ?> categories</h2>
        </div>

        <?php if (!$categories): ?>
            <div class="admin-card"><div class="empty"><p>No categories yet. Add one on the right.</p></div></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Slug</th>
                            <th>Products</th>
                            <th>Order</th>
                            <th class="ta-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:.8rem">
                                        <img class="thumb" style="width:40px;height:40px;border-radius:8px" src="<?= e(category_image($cat['image'])) ?>" alt="<?= e($cat['name']) ?>">
                                        <div>
                                            <span class="cell-strong"><?= e($cat['name']) ?></span>
                                            <span class="cell-sub" style="display:block"><?= e(excerpt((string) $cat['description'], 46)) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="cell-sub"><?= e($cat['slug']) ?></td>
                                <td><?= (int) $cat['product_count'] ?></td>
                                <td><?= (int) $cat['sort_order'] ?></td>
                                <td>
                                    <div class="admin-table__actions">
                                        <a class="btn btn--light btn--sm" href="<?= e(admin_url('categories.php?edit=' . (int) $cat['id'])) ?>"><?= icon('edit') ?> Edit</a>
                                        <form method="post" action="<?= e(admin_url('categories.php')) ?>" data-confirm="Delete “<?= e($cat['name']) ?>”?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn--danger btn--sm"><?= icon('trash') ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="admin-card">
        <div class="admin-card__head">
            <h2 class="admin-card__title"><?= $editing ? 'Edit category' : 'New category' ?></h2>
            <?php if ($editing): ?>
                <a class="btn btn--light btn--sm" href="<?= e(admin_url('categories.php')) ?>">New</a>
            <?php endif; ?>
        </div>

        <form method="post" action="<?= e(admin_url('categories.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">

            <div class="field">
                <label class="field__label" for="c-name">Name</label>
                <input class="input" type="text" id="c-name" name="name" value="<?= e($editing['name'] ?? '') ?>" data-slug-source="#c-slug" required>
            </div>

            <div class="field">
                <label class="field__label" for="c-slug">Slug <span class="text-muted">(auto)</span></label>
                <input class="input" type="text" id="c-slug" name="slug" value="<?= e($editing['slug'] ?? '') ?>" placeholder="block-heels">
            </div>

            <div class="field">
                <label class="field__label" for="c-desc">Description</label>
                <input class="input" type="text" id="c-desc" name="description" value="<?= e($editing['description'] ?? '') ?>">
            </div>

            <div class="field">
                <label class="field__label" for="c-image">Image</label>
                <input class="input" type="text" id="c-image" name="image" value="<?= e($editing['image'] ?? '') ?>" placeholder="categories/heels.jpg">
            </div>

            <div class="field">
                <label class="field__label" for="c-sort">Sort order</label>
                <input class="input" type="number" id="c-sort" name="sort_order" value="<?= e((string) ($editing['sort_order'] ?? 0)) ?>">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--dark"><?= $editing ? 'Save' : 'Create' ?></button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
