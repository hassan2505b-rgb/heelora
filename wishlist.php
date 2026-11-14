<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    $action    = (string) ($_POST['action'] ?? '');
    $productId = (int) ($_POST['product_id'] ?? 0);
    $redirect  = trim((string) ($_POST['redirect'] ?? ''));

    $wishlist = array_values(array_unique(array_map('intval', $_SESSION['wishlist'] ?? [])));

    if ($action === 'add' && $productId > 0) {
        $stmt = db()->prepare('SELECT name FROM products WHERE id = ? AND is_active = 1');
        $stmt->execute([$productId]);
        $name = $stmt->fetchColumn();

        if ($name !== false && !in_array($productId, $wishlist, true)) {
            $wishlist[] = $productId;
            flash_set('success', $name . ' saved to your wishlist.');
        }
    } elseif ($action === 'remove') {
        $wishlist = array_values(array_diff($wishlist, [$productId]));
        flash_set('info', 'Removed from your wishlist.');
    }

    $_SESSION['wishlist'] = $wishlist;

    if ($redirect === '') {
        $redirect = 'wishlist.php';
    }
    if (preg_match('#^https?://#', $redirect) || str_starts_with($redirect, '/')) {
        header('Location: ' . $redirect);
        exit;
    }
    redirect($redirect);
}

$wishlist = array_values(array_unique(array_map('intval', $_SESSION['wishlist'] ?? [])));

$products = [];
if ($wishlist) {
    $in   = implode(',', array_fill(0, count($wishlist), '?'));
    $stmt = db()->prepare(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM products p LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.id IN ($in) AND p.is_active = 1"
    );
    $stmt->execute($wishlist);
    $products = $stmt->fetchAll();
}

$page_title = 'Wishlist — ' . SITE_NAME;
$body_class = 'page-wishlist';
$active     = '';

require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb">
        <a href="<?= e(url('index.php')) ?>">Home</a>
        <span class="breadcrumb__sep">/</span>
        <span>Wishlist</span>
    </nav>

    <div class="section__head">
        <div>
            <h1 class="display-3 section__title">Your wishlist</h1>
            <p class="section__sub"><?= count($products) ?> saved <?= count($products) === 1 ? 'style' : 'styles' ?></p>
        </div>
        <a class="link-arrow" href="<?= e(url('shop.php')) ?>">Keep shopping <?= icon('arrow') ?></a>
    </div>

    <?php if (!$products): ?>
        <div class="empty">
            <div class="empty__icon"><?= icon('heart') ?></div>
            <h2>Nothing saved yet</h2>
            <p>Tap the heart on any style to keep it here for later.</p>
            <a class="btn btn--dark" href="<?= e(url('shop.php')) ?>">Browse footwear</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <?php require __DIR__ . '/includes/card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
