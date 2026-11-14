<?php
declare(strict_types=1);

/**
 * Reusable product card.
 * Expects $product (a row from the products table, joined with category columns).
 */

if (!isset($product) || !is_array($product)) {
    return;
}

$p_id     = (int) $product['id'];
$p_href   = url('product.php?slug=' . urlencode((string) $product['slug']));
$p_price  = (float) $product['price'];
$p_sale   = ($product['sale_price'] !== null && (float) $product['sale_price'] > 0) ? (float) $product['sale_price'] : null;
$p_now    = $p_sale ?? $p_price;
$p_badges = product_badges($product);
$p_sizes  = array_values(array_filter(product_sizes($product)));
$p_sizes  = array_slice($p_sizes, 0, 6);
$p_colors = array_slice(product_colors($product), 0, 4);
$p_stock  = (int) $product['stock'];
$p_soldout = $p_stock <= 0;
$p_inWish = in_array($p_id, array_map('intval', $_SESSION['wishlist'] ?? []), true);
?>
<article class="product-card" data-reveal>
    <div class="product-card__media">
        <a href="<?= e($p_href) ?>" class="product-card__link" aria-label="<?= e($product['name']) ?>">
            <img class="product-card__img" src="<?= e(product_image($product['image'])) ?>"
                 alt="<?= e($product['name']) ?>" loading="lazy">
        </a>

        <?php if ($p_badges): ?>
            <div class="product-card__badges">
                <?php foreach ($p_badges as $badge): ?>
                    <span class="badge <?= e($badge['class']) ?>"><?= e($badge['label']) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('wishlist.php')) ?>" class="product-card__wish" id="wishForm_<?= $p_id ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $p_inWish ? 'remove' : 'add' ?>">
            <input type="hidden" name="product_id" value="<?= $p_id ?>">
            <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI'] ?? url('shop.php')) ?>">
            <button type="submit" class="wish-btn <?= $p_inWish ? 'is-active' : '' ?>"
                    aria-label="<?= $p_inWish ? 'Remove from wishlist' : 'Add to wishlist' ?>">
                <?= icon('heart') ?>
            </button>
        </form>

        <?php if (!$p_soldout): ?>
            <form method="post" action="<?= e(url('cart.php')) ?>" class="product-card__quick">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $p_id ?>">
                <input type="hidden" name="qty" value="1">
                <input type="hidden" name="size" value="<?= e($p_sizes[0] ?? '38') ?>">
                <button type="submit" class="btn btn--dark btn--sm btn--full"><?= icon('bag') ?> Quick add</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="product-card__body">
        <?php if (!empty($product['category_name'])): ?>
            <span class="product-card__cat"><?= e($product['category_name']) ?></span>
        <?php endif; ?>

        <h3 class="product-card__name"><a href="<?= e($p_href) ?>"><?= e($product['name']) ?></a></h3>

        <?php if (trim((string) $product['tagline']) !== ''): ?>
            <p class="product-card__tagline"><?= e($product['tagline']) ?></p>
        <?php endif; ?>

        <div class="product-card__price">
            <span class="price<?= $p_sale !== null ? ' price--sale' : '' ?>"><?= e(money($p_now)) ?></span>
            <?php if ($p_sale !== null): ?>
                <span class="price-was"><?= e(money($p_price)) ?></span>
            <?php endif; ?>
        </div>

        <?php if ($p_colors): ?>
            <div class="product-card__swatches">
                <?php foreach ($p_colors as $color): ?>
                    <span class="swatch" style="--swatch: <?= e(color_hex($color)) ?>" title="<?= e($color) ?>"></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($p_soldout): ?>
            <p class="product-card__oos">Sold out</p>
        <?php endif; ?>
    </div>
</article>
