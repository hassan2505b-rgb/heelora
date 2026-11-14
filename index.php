<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$categories = get_categories();
$featured   = get_products(['featured' => 1, 'limit' => 8, 'sort' => 'popular']);
$newIn      = get_products(['limit' => 8, 'sort' => 'newest']);

/* Everything below is drawn from the database / configuration */
$styleCount    = count_products();
$silhouetteCount = count($categories);
$heroProduct   = $featured[0] ?? ($newIn[0] ?? null);
$heroImage     = asset('img/editorial/hero.jpg');

$page_title = SITE_NAME . ' — Handcrafted Ladies Footwear in Lahore';
$page_desc  = 'Heelora crafts elegant ladies footwear — stilettos, block heels, flats, sandals and bridal heels, designed in Lahore and made in small batches.';
$body_class = 'page-home';
$active     = 'home';

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container hero__inner">
        <div class="hero__content">
            <span class="eyebrow">Autumn / Winter Edit</span>
            <h1 class="display-1 hero__title">Footwear made for the way <em>you</em> move.</h1>
            <p class="lede hero__text">
                Considered silhouettes, hand-finished leathers and a fit that lasts past the
                first wear. Every pair is designed in Lahore and made in small batches.
            </p>

            <div class="hero__actions">
                <a class="btn btn--dark btn--lg" href="<?= e(url('shop.php')) ?>">Shop the collection <?= icon('arrow') ?></a>
                <a class="btn btn--light btn--lg" href="<?= e(url('about.php')) ?>">Our atelier</a>
            </div>

            <div class="hero__meta">
                <div class="hero__stat">
                    <strong><?= (int) $styleCount ?></strong>
                    <span>Signature styles</span>
                </div>
                <div class="hero__stat">
                    <strong><?= (int) $silhouetteCount ?></strong>
                    <span>Silhouettes</span>
                </div>
                <div class="hero__stat">
                    <strong><?= e(money(FREE_SHIPPING_MIN)) ?>+</strong>
                    <span>Free delivery</span>
                </div>
            </div>
        </div>

        <div class="hero__media">
            <img src="<?= e($heroImage) ?>" alt="Model wearing Heelora heels" fetchpriority="high">
            <?php if ($heroProduct): ?>
                <a class="hero__badge" href="<?= e(url('product.php?slug=' . urlencode((string) $heroProduct['slug']))) ?>">
                    <strong><?= e($heroProduct['name']) ?></strong>
                    <span>
                        <?= e(money(current_price($heroProduct))) ?>
                        <?php if (current_price($heroProduct) < (float) $heroProduct['price']): ?>
                            · was <?= e(money($heroProduct['price'])) ?>
                        <?php endif; ?>
                    </span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <div class="perks">
            <div class="perk">
                <?= icon('truck') ?>
                <h3>Free delivery</h3>
                <p>On all orders above <?= e(money(FREE_SHIPPING_MIN)) ?> across Pakistan.</p>
            </div>
            <div class="perk">
                <?= icon('refresh') ?>
                <h3>7-day exchanges</h3>
                <p>Wrong size? Swap it within a week, no questions asked.</p>
            </div>
            <div class="perk">
                <?= icon('shield') ?>
                <h3>Genuine leather</h3>
                <p>Hand-finished uppers and cushioned soles, made to last.</p>
            </div>
            <div class="perk">
                <?= icon('phone') ?>
                <h3>Fit support</h3>
                <p>Not sure of your size? Our stylists will help you choose.</p>
            </div>
        </div>
    </div>
</section>

<?php if ($categories): ?>
<section class="section">
    <div class="container">
        <div class="section__head">
            <div>
                <span class="eyebrow">Shop by silhouette</span>
                <h2 class="display-3 section__title">Find your heel height</h2>
            </div>
            <a class="link-arrow" href="<?= e(url('shop.php')) ?>">View all footwear <?= icon('arrow') ?></a>
        </div>

        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a class="category-card" href="<?= e(url('shop.php?category=' . $cat['slug'])) ?>" data-reveal>
                    <img src="<?= e(category_image($cat['image'])) ?>" alt="<?= e($cat['name']) ?>" loading="lazy">
                    <div class="category-card__body">
                        <h3 class="category-card__name"><?= e($cat['name']) ?></h3>
                        <p class="category-card__desc"><?= e($cat['description']) ?></p>
                        <span class="category-card__cta">Shop now <?= icon('arrow') ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($featured): ?>
<section class="section section--tint">
    <div class="container">
        <div class="section__head">
            <div>
                <span class="eyebrow">The edit</span>
                <h2 class="display-3 section__title">Most loved this season</h2>
                <p class="section__sub">The pairs our customers keep coming back for — tried, tested and quietly iconic.</p>
            </div>
            <a class="link-arrow" href="<?= e(url('shop.php?sort=popular')) ?>">See bestsellers <?= icon('arrow') ?></a>
        </div>

        <div class="product-grid">
            <?php foreach ($featured as $product): ?>
                <?php require __DIR__ . '/includes/card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container">
        <div class="split">
            <div class="split__media split__media--arch">
                <img src="<?= e(asset('img/editorial/atelier.jpg')) ?>" alt="Inside the Heelora atelier" loading="lazy">
                <div class="split__caption">
                    <strong>Made by hand</strong>
                    Small batches, finished in our Lahore atelier.
                </div>
            </div>
            <div class="split__body">
                <span class="eyebrow">The Heelora atelier</span>
                <h2 class="display-3 section__title">Slowly made, quietly luxurious.</h2>
                <div class="lead-rule"></div>
                <p class="lede">
                    We work with a handful of family-run workshops to produce footwear the way it
                    used to be made — cut by hand, stitched in small runs, checked pair by pair.
                </p>
                <p class="text-muted">
                    No mass production, no shortcuts. Just leathers that soften with wear and
                    shapes that flatter for years, not seasons.
                </p>
                <div class="mt-3">
                    <a class="btn btn--ghost" href="<?= e(url('about.php')) ?>">Read our story <?= icon('arrow') ?></a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($newIn): ?>
<section class="section section--tint">
    <div class="container">
        <div class="section__head">
            <div>
                <span class="eyebrow">Just arrived</span>
                <h2 class="display-3 section__title">New in</h2>
            </div>
            <a class="link-arrow" href="<?= e(url('shop.php?sort=newest')) ?>">Shop new in <?= icon('arrow') ?></a>
        </div>

        <div class="product-grid">
            <?php foreach ($newIn as $product): ?>
                <?php require __DIR__ . '/includes/card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section section--tight">
    <div class="container">
        <div class="newsletter-band" data-reveal>
            <div>
                <span class="eyebrow" style="color: var(--blush)">The Heelora Letter</span>
                <h2 class="display-3">First looks, styling notes, quiet offers.</h2>
                <p>Hear about new arrivals before anyone else. No noise, ever.</p>
            </div>
            <form action="<?= e(url('newsletter.php')) ?>" method="post" class="newsletter-form">
                <?= csrf_field() ?>
                <input type="email" name="email" placeholder="Your email address" required aria-label="Email address">
                <button type="submit" class="btn btn--blush">Join</button>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
