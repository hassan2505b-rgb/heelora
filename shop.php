<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$categories = get_categories();
$allSizes   = available_sizes();

$q        = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$size     = trim((string) ($_GET['size'] ?? ''));
$minPrice = trim((string) ($_GET['min_price'] ?? ''));
$maxPrice = trim((string) ($_GET['max_price'] ?? ''));
$sort     = trim((string) ($_GET['sort'] ?? 'newest'));
$page     = max(1, (int) ($_GET['page'] ?? 1));

$sortOptions = [
    'newest'     => 'Newest arrivals',
    'popular'    => 'Most loved',
    'price-low'  => 'Price: low to high',
    'price-high' => 'Price: high to low',
    'name'       => 'Alphabetical',
];
if (!isset($sortOptions[$sort])) {
    $sort = 'newest';
}

$activeCategory = $category !== '' ? get_category_by_slug($category) : null;
if ($category !== '' && !$activeCategory) {
    $category = '';
}
if ($size !== '' && !in_array($size, $allSizes, true)) {
    $size = '';
}

$opts = [
    'q'         => $q,
    'category'  => $category,
    'size'      => $size,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'sort'      => $sort,
];

$perPage  = 9;
$total    = count_products($opts);
$pages    = max(1, (int) ceil($total / $perPage));
$page     = min($page, $pages);
$products = get_products($opts + ['limit' => $perPage, 'offset' => ($page - 1) * $perPage]);

$baseParams = array_filter([
    'q'         => $q,
    'category'  => $category,
    'size'      => $size,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'sort'      => $sort !== 'newest' ? $sort : '',
], static fn ($v) => $v !== '' && $v !== null);

$linkTo = static function (array $overrides = []) use ($baseParams): string {
    $params = array_filter(
        array_merge($baseParams, $overrides),
        static fn ($v) => $v !== '' && $v !== null
    );
    return url('shop.php' . ($params ? '?' . http_build_query($params) : ''));
};

$activeChips = [];
if ($q !== '')        { $activeChips[] = ['label' => '“' . $q . '”',           'url' => $linkTo(['q' => null, 'page' => null])]; }
if ($activeCategory)  { $activeChips[] = ['label' => $activeCategory['name'],  'url' => $linkTo(['category' => null, 'page' => null])]; }
if ($size !== '')     { $activeChips[] = ['label' => 'Size ' . $size,          'url' => $linkTo(['size' => null, 'page' => null])]; }
if ($minPrice !== '') { $activeChips[] = ['label' => 'From ' . money($minPrice), 'url' => $linkTo(['min_price' => null, 'page' => null])]; }
if ($maxPrice !== '') { $activeChips[] = ['label' => 'Up to ' . money($maxPrice), 'url' => $linkTo(['max_price' => null, 'page' => null])]; }

$hasFilters = (bool) $activeChips;

$page_title = ($activeCategory ? $activeCategory['name'] : 'Shop All') . ' — ' . SITE_NAME;
$page_desc  = $activeCategory['description'] ?? 'Shop Heelora ladies footwear — heels, sandals, flats, block heels and bridal. Free delivery over ' . money(FREE_SHIPPING_MIN) . '.';
$body_class = 'page-shop';
$active     = $category === 'bridal' ? 'bridal' : ((!$hasFilters && $sort === 'newest') ? 'new' : 'shop');

require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="container">
        <nav class="breadcrumb" style="justify-content:center">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <span class="breadcrumb__sep">/</span>
            <a href="<?= e(url('shop.php')) ?>">Shop</a>
            <?php if ($activeCategory): ?>
                <span class="breadcrumb__sep">/</span>
                <span><?= e($activeCategory['name']) ?></span>
            <?php endif; ?>
        </nav>
        <h1 class="page-head__title"><?= e($activeCategory['name'] ?? 'The full collection') ?></h1>
        <p class="page-head__sub">
            <?= e($activeCategory['description'] ?? 'Every silhouette we make — heels, sandals, flats, block heels and bridal.') ?>
        </p>
    </div>
</section>

<div class="container">
    <div class="shop-layout">
        <aside class="filters" data-filters>
            <form method="get" action="<?= e(url('shop.php')) ?>" id="shopFilters">
                <?php if ($q !== ''): ?>
                    <input type="hidden" name="q" value="<?= e($q) ?>">
                <?php endif; ?>

                <div class="filter-group">
                    <h3 class="filter-group__title">Category</h3>
                    <div class="filter-list">
                        <label>
                            <input type="radio" name="category" value="" data-auto-submit <?= $category === '' ? 'checked' : '' ?>>
                            All footwear
                        </label>
                        <?php foreach ($categories as $cat): ?>
                            <label>
                                <input type="radio" name="category" value="<?= e($cat['slug']) ?>" data-auto-submit <?= $category === $cat['slug'] ? 'checked' : '' ?>>
                                <?= e($cat['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-group">
                    <h3 class="filter-group__title">Size (EU)</h3>
                    <div class="size-grid">
                        <?php foreach ($allSizes as $s): ?>
                            <label class="size-chip">
                                <input type="radio" name="size" value="<?= e($s) ?>" data-auto-submit <?= $size === $s ? 'checked' : '' ?>>
                                <span><?= e($s) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-group">
                    <h3 class="filter-group__title">Price</h3>
                    <div class="price-inputs">
                        <input type="number" name="min_price" min="0" step="500" placeholder="Min" value="<?= e($minPrice) ?>">
                        <span>—</span>
                        <input type="number" name="max_price" min="0" step="500" placeholder="Max" value="<?= e($maxPrice) ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn--dark btn--full">Apply filters</button>
                <?php if ($hasFilters): ?>
                    <a class="btn btn--light btn--full mt-2" href="<?= e(url('shop.php')) ?>">Clear all</a>
                <?php endif; ?>
            </form>
        </aside>

        <div class="shop-main">
            <div class="shop-toolbar">
                <p class="shop-toolbar__count">
                    <?= (int) $total ?> <?= $total === 1 ? 'style' : 'styles' ?>
                    <?php if ($activeCategory): ?>in <?= e($activeCategory['name']) ?><?php endif; ?>
                </p>
                <div class="shop-toolbar__right">
                    <button class="btn btn--light btn--sm filters-toggle" type="button" data-filters-toggle>Filters</button>
                    <select class="select" name="sort" form="shopFilters" data-auto-submit aria-label="Sort products">
                        <?php foreach ($sortOptions as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $sort === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($activeChips): ?>
                <div class="active-chips">
                    <?php foreach ($activeChips as $chip): ?>
                        <span class="chip"><?= e($chip['label']) ?> <a href="<?= e($chip['url']) ?>" aria-label="Remove filter">&times;</a></span>
                    <?php endforeach; ?>
                    <a class="chip" href="<?= e(url('shop.php')) ?>">Clear all</a>
                </div>
            <?php endif; ?>

            <?php if (!$products): ?>
                <div class="empty">
                    <div class="empty__icon"><?= icon('search') ?></div>
                    <h2>Nothing matches — yet</h2>
                    <p>Try a different silhouette, size or price range.</p>
                    <a class="btn btn--dark" href="<?= e(url('shop.php')) ?>">Browse the full collection</a>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <?php require __DIR__ . '/includes/card.php'; ?>
                    <?php endforeach; ?>
                </div>

                <?php if ($pages > 1): ?>
                    <nav class="pagination" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <a href="<?= e($linkTo(['page' => $page - 1])) ?>" aria-label="Previous page"><?= icon('arrow-left') ?></a>
                        <?php else: ?>
                            <span class="is-disabled"><?= icon('arrow-left') ?></span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="is-current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="<?= e($linkTo(['page' => $i])) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $pages): ?>
                            <a href="<?= e($linkTo(['page' => $page + 1])) ?>" aria-label="Next page"><?= icon('arrow') ?></a>
                        <?php else: ?>
                            <span class="is-disabled"><?= icon('arrow') ?></span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
