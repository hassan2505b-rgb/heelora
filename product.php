<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$slug    = trim((string) ($_GET['slug'] ?? ''));
$product = $slug !== '' ? get_product_by_slug($slug) : null;

if (!$product) {
    http_response_code(404);
    $page_title = 'Style not found — ' . SITE_NAME;
    $body_class = 'page-404';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="container">
        <div class="empty">
            <div class="empty__icon"><?= icon('alert') ?></div>
            <h2>We couldn't find that style</h2>
            <p>It may have sold out or been renamed. Explore the current collection instead.</p>
            <a class="btn btn--dark" href="<?= e(url('shop.php')) ?>">Shop all footwear</a>
        </div>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$badges   = product_badges($product);
$sizes    = product_sizes($product);
$colors   = product_colors($product);
$price    = (float) $product['price'];
$sale     = ($product['sale_price'] !== null && (float) $product['sale_price'] > 0) ? (float) $product['sale_price'] : null;
$now      = current_price($product);
$stock    = (int) $product['stock'];
$soldout  = $stock <= 0;
$inWish   = in_array((int) $product['id'], array_map('intval', $_SESSION['wishlist'] ?? []), true);
$related  = related_products($product, 4);

$page_title = $product['name'] . ' — ' . SITE_NAME;
$page_desc  = excerpt((string) $product['description'], 150);
$body_class = 'page-product';
$active     = 'shop';

require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb">
        <a href="<?= e(url('index.php')) ?>">Home</a>
        <span class="breadcrumb__sep">/</span>
        <a href="<?= e(url('shop.php')) ?>">Shop</a>
        <?php if (!empty($product['category_name'])): ?>
            <span class="breadcrumb__sep">/</span>
            <a href="<?= e(url('shop.php?category=' . urlencode((string) $product['category_slug']))) ?>"><?= e($product['category_name']) ?></a>
        <?php endif; ?>
        <span class="breadcrumb__sep">/</span>
        <span><?= e($product['name']) ?></span>
    </nav>

    <div class="product-detail">
        <div class="gallery">
            <div class="gallery__main">
                <img src="<?= e(product_image($product['image'])) ?>" alt="<?= e($product['name']) ?>" data-gallery-main fetchpriority="high">
            </div>
            <?php if ($badges): ?>
                <div class="product-meta">
                    <?php foreach ($badges as $badge): ?>
                        <span class="badge <?= e($badge['class']) ?>"><?= e($badge['label']) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="product-info">
            <?php if (!empty($product['category_name'])): ?>
                <span class="eyebrow"><?= e($product['category_name']) ?></span>
            <?php endif; ?>

            <h1 class="product-info__title"><?= e($product['name']) ?></h1>

            <?php if (trim((string) $product['tagline']) !== ''): ?>
                <p class="text-muted mb-0"><?= e($product['tagline']) ?></p>
            <?php endif; ?>

            <div class="product-info__price">
                <span class="price<?= $sale !== null ? ' price--sale' : '' ?>"><?= e(money($now)) ?></span>
                <?php if ($sale !== null): ?>
                    <span class="price-was"><?= e(money($price)) ?></span>
                    <span class="badge badge--sale">-<?= (int) discount_percent($price, $sale) ?>%</span>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= e(url('cart.php')) ?>" class="product-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="redirect" value="<?= e('product.php?slug=' . urlencode((string) $product['slug'])) ?>">

                <?php if ($sizes): ?>
                    <div class="option-group">
                        <div class="option-group__label">
                            <span>Size (EU)</span>
                            <a href="<?= e(url('contact.php#faq')) ?>">Size guide</a>
                        </div>
                        <div class="option-row">
                            <?php foreach ($sizes as $index => $s): ?>
                                <label class="option-chip">
                                    <input type="radio" name="size" value="<?= e($s) ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                    <span><?= e($s) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($colors): ?>
                    <div class="option-group">
                        <div class="option-group__label"><span>Colour</span></div>
                        <div class="option-row">
                            <?php foreach ($colors as $index => $color): ?>
                                <label class="option-chip option-chip--color">
                                    <input type="radio" name="color" value="<?= e($color) ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                    <span class="swatch" style="--swatch: <?= e(color_hex($color)) ?>"></span>
                                    <span><?= e($color) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="buy-row">
                    <div class="qty" data-qty>
                        <button type="button" data-qty-step="-1" aria-label="Decrease quantity"><?= icon('minus') ?></button>
                        <input type="number" name="qty" value="1" min="1" max="<?= max(1, $stock) ?>" aria-label="Quantity">
                        <button type="button" data-qty-step="1" aria-label="Increase quantity"><?= icon('plus') ?></button>
                    </div>
                    <?php if ($soldout): ?>
                        <button type="button" class="btn btn--dark btn--lg is-disabled" disabled>Sold out</button>
                    <?php else: ?>
                        <button type="submit" class="btn btn--dark btn--lg"><?= icon('bag') ?> Add to bag</button>
                    <?php endif; ?>
                </div>
            </form>

            <div class="buy-row">
                <form method="post" action="<?= e(url('wishlist.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $inWish ? 'remove' : 'add' ?>">
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <input type="hidden" name="redirect" value="<?= e('product.php?slug=' . urlencode((string) $product['slug'])) ?>">
                    <button type="submit" class="btn btn--light">
                        <?= icon('heart') ?> <?= $inWish ? 'Saved' : 'Save for later' ?>
                    </button>
                </form>
            </div>

            <div class="product-meta">
                <?php if ($soldout): ?>
                    <span><?= icon('alert') ?> Out of stock</span>
                <?php elseif ($stock <= LOW_STOCK_LIMIT): ?>
                    <span><?= icon('alert') ?> Only <?= $stock ?> left</span>
                <?php else: ?>
                    <span><?= icon('check') ?> In stock</span>
                <?php endif; ?>
                <span><?= icon('truck') ?> Free delivery over <?= e(money(FREE_SHIPPING_MIN)) ?></span>
                <span><?= icon('refresh') ?> 7-day exchanges</span>
            </div>

            <div class="accordion">
                <div class="accordion__item is-open">
                    <button type="button" class="accordion__trigger">Details <?= icon('chevron') ?></button>
                    <div class="accordion__panel">
                        <p><?= nl2br(e((string) $product['description'])) ?></p>
                        <?php if ($colors): ?>
                            <p class="mb-0"><strong>Colours:</strong> <?= e(implode(', ', $colors)) ?></p>
                        <?php endif; ?>
                        <?php if ($sizes): ?>
                            <p class="mb-0"><strong>Sizes:</strong> <?= e(implode(' · ', $sizes)) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="accordion__item">
                    <button type="button" class="accordion__trigger">Delivery &amp; returns <?= icon('chevron') ?></button>
                    <div class="accordion__panel">
                        <p class="mb-0">Dispatched within 24 hours from our Lahore atelier. Complimentary delivery on orders above
                            <?= e(money(FREE_SHIPPING_MIN)) ?>, otherwise a flat <?= e(money(SHIPPING_FEE)) ?> applies. Unworn pairs can be
                            exchanged within 7 days.</p>
                    </div>
                </div>
                <div class="accordion__item">
                    <button type="button" class="accordion__trigger">Fit notes <?= icon('chevron') ?></button>
                    <div class="accordion__panel">
                        <p class="mb-0">Runs true to size. If you are between sizes or have a wider foot, we recommend sizing up half a
                            size. Our stylists are happy to help — just <a href="<?= e(url('contact.php')) ?>">get in touch</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($related): ?>
        <section class="section">
            <div class="section__head">
                <div>
                    <span class="eyebrow">Complete the look</span>
                    <h2 class="display-3 section__title">You may also like</h2>
                </div>
                <a class="link-arrow" href="<?= e(url('shop.php?category=' . urlencode((string) $product['category_slug']))) ?>">More <?= e($product['category_name']) ?> <?= icon('arrow') ?></a>
            </div>
            <div class="product-grid">
                <?php foreach ($related as $product): ?>
                    <?php require __DIR__ . '/includes/card.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
