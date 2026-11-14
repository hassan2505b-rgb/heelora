<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$admin = require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    $id     = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    if ($id > 0) {
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            flash_set('success', 'Product deleted.');
        } elseif ($action === 'toggle') {
            $pdo->prepare('UPDATE products SET is_active = CASE is_active WHEN 1 THEN 0 ELSE 1 END WHERE id = ?')->execute([$id]);
            flash_set('success', 'Product availability updated.');
        } elseif ($action === 'feature') {
            $pdo->prepare('UPDATE products SET is_featured = CASE is_featured WHEN 1 THEN 0 ELSE 1 END WHERE id = ?')->execute([$id]);
            flash_set('success', 'Featured flag updated.');
        }
    }

    $back = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    if (preg_match('#^https?://#', $back)) {
        header('Location: ' . $back);
        exit;
    }
    redirect('admin/products.php');
}

$q        = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 12;

$where  = ['1 = 1'];
$params = [];
if ($q !== '') {
    $where[]  = '(p.name LIKE ? OR p.tagline LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($category !== '') {
    $where[]  = 'c.slug = ?';
    $params[] = $category;
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page  = min($page, $pages);

$listStmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name
     FROM products p LEFT JOIN categories c ON c.id = p.category_id
     WHERE $whereSql
     ORDER BY p.id DESC
     LIMIT $perPage OFFSET " . (($page - 1) * $perPage)
);
$listStmt->execute($params);
$products = $listStmt->fetchAll();

$categories = get_categories();

$linkTo = static function (array $overrides = []) use ($q, $category): string {
    $params = array_filter(array_merge([
        'q'        => $q,
        'category' => $category,
    ], $overrides), static fn ($v) => $v !== '' && $v !== null);

    return admin_url('products.php' . ($params ? '?' . http_build_query($params) : ''));
};

$admin_title  = 'Products';
$admin_active = 'products';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-toolbar">
    <form method="get" action="<?= e(admin_url('products.php')) ?>" class="admin-toolbar__filters">
        <div class="admin-search">
            <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search products…" aria-label="Search products">
            <button type="submit" class="btn btn--light btn--sm">Search</button>
        </div>
        <select class="select" name="category" data-auto-submit aria-label="Filter by category">
            <option value="">All categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat['slug']) ?>" <?= $category === $cat['slug'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($q !== '' || $category !== ''): ?>
            <a class="btn btn--light btn--sm" href="<?= e(admin_url('products.php')) ?>">Reset</a>
        <?php endif; ?>
    </form>

    <a class="btn btn--dark btn--sm" href="<?= e(admin_url('product-edit.php')) ?>"><?= icon('plus') ?> New product</a>
</div>

<p class="text-muted"><?= $total ?> <?= $total === 1 ? 'product' : 'products' ?></p>

<?php if (!$products): ?>
    <div class="admin-card">
        <div class="empty">
            <div class="empty__icon"><?= icon('box') ?></div>
            <p>No products found.</p>
            <a class="btn btn--dark" href="<?= e(admin_url('product-edit.php')) ?>">Add your first product</a>
        </div>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th class="ta-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <?php
                    $price = (float) $product['price'];
                    $sale  = ($product['sale_price'] !== null && (float) $product['sale_price'] > 0) ? (float) $product['sale_price'] : null;
                    $stock = (int) $product['stock'];
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:.8rem">
                                <img class="thumb" src="<?= e(product_image($product['image'])) ?>" alt="<?= e($product['name']) ?>">
                                <div>
                                    <a class="cell-strong" href="<?= e(admin_url('product-edit.php?id=' . (int) $product['id'])) ?>"><?= e($product['name']) ?></a>
                                    <span class="cell-sub" style="display:block"><?= e($product['slug']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td><?= e($product['category_name'] ?? '—') ?></td>
                        <td>
                            <?= e(money($sale ?? $price)) ?>
                            <?php if ($sale !== null): ?>
                                <span class="cell-sub" style="display:block;text-decoration:line-through"><?= e(money($price)) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $stock ?>
                            <?php if ($stock <= 0): ?>
                                <span class="badge badge--off" style="display:block">Out</span>
                            <?php elseif ($stock <= LOW_STOCK_LIMIT): ?>
                                <span class="badge badge--low" style="display:block">Low</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) $product['is_active']): ?>
                                <span class="badge badge--on">Live</span>
                            <?php else: ?>
                                <span class="badge badge--off">Hidden</span>
                            <?php endif; ?>
                            <?php if ((int) $product['is_featured']): ?>
                                <span class="badge" style="display:block;margin-top:.3rem">★ Featured</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="admin-table__actions">
                                <a class="btn btn--light btn--sm" href="<?= e(admin_url('product-edit.php?id=' . (int) $product['id'])) ?>"><?= icon('edit') ?> Edit</a>

                                <form method="post" action="<?= e(admin_url('products.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                    <input type="hidden" name="action" value="feature">
                                    <button type="submit" class="btn btn--light btn--sm" title="Toggle featured"><?= icon('star') ?></button>
                                </form>

                                <form method="post" action="<?= e(admin_url('products.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <button type="submit" class="btn btn--light btn--sm" title="Toggle availability"><?= icon('eye') ?></button>
                                </form>

                                <form method="post" action="<?= e(admin_url('products.php')) ?>" data-confirm="Delete “<?= e($product['name']) ?>”? This cannot be undone.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn--danger btn--sm" title="Delete"><?= icon('trash') ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="is-current"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= e($linkTo(['page' => $i])) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
