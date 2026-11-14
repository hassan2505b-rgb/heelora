<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$categories  = get_categories();
$styleCount  = count_products();

$page_title = 'Our story — ' . SITE_NAME;
$page_desc  = 'Heelora designs and makes considered ladies footwear in Lahore — small-batch, hand-finished and built to last.';
$body_class = 'page-about';
$active     = 'about';

require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="container">
        <span class="eyebrow">Since 2019</span>
        <h1 class="page-head__title">Footwear, made slowly.</h1>
        <p class="page-head__sub">Designed in Lahore. Finished by hand. Worn everywhere.</p>
    </div>
</section>

<div class="container">
    <div class="figures">
        <div class="figure"><strong><?= (int) $styleCount ?></strong><span>Signature styles</span></div>
        <div class="figure"><strong><?= count($categories) ?></strong><span>Silhouettes</span></div>
        <div class="figure"><strong>7</strong><span>Day exchanges</span></div>
        <div class="figure"><strong>100%</strong><span>Genuine leather</span></div>
    </div>

    <section class="section">
        <div class="split">
            <div class="split__media split__media--arch">
                <img src="<?= e(asset('img/editorial/atelier.jpg')) ?>" alt="Inside the Heelora atelier" loading="lazy">
                <div class="split__caption">
                    <strong>Made by hand</strong>
                    Small batches, finished in our Lahore atelier.
                </div>
            </div>
            <div class="split__body">
                <span class="eyebrow">The beginning</span>
                <h2 class="display-3 section__title">It started with one pair.</h2>
                <div class="lead-rule"></div>
                <p class="lede">
                    Heelora began in a two-room workshop with a single question: why is it so hard to find a
                    beautiful heel that you can actually walk in?
                </p>
                <p class="text-muted">
                    We started sketching our own lasts, testing heel heights on real pavements and choosing
                    leathers for how they age, not just how they photograph. The result is a small wardrobe of
                    styles designed to be worn on repeat.
                </p>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="section__head">
            <div>
                <span class="eyebrow">What we stand for</span>
                <h2 class="display-3 section__title">Considered in every detail</h2>
            </div>
        </div>

        <div class="values">
            <div class="value-card" data-reveal>
                <?= icon('sparkle') ?>
                <h3>Designed to last</h3>
                <p>Real leathers, cushioned footbeds and soles chosen to survive more than one season.</p>
            </div>
            <div class="value-card" data-reveal>
                <?= icon('shield') ?>
                <h3>Small batch</h3>
                <p>Produced in limited runs by family-run workshops, checked pair by pair before dispatch.</p>
            </div>
            <div class="value-card" data-reveal>
                <?= icon('users') ?>
                <h3>Fit first</h3>
                <p>Every style is tested across sizes on real feet, and our stylists are always a message away.</p>
            </div>
            <div class="value-card" data-reveal>
                <?= icon('truck') ?>
                <h3>Delivered with care</h3>
                <p>Complimentary delivery over <?= e(money(FREE_SHIPPING_MIN)) ?> and 7-day exchanges, always.</p>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="quote">
            <span class="quote__mark">&ldquo;</span>
            <blockquote>Buy less, choose well, make it last — and always, always walk comfortably.</blockquote>
            <span class="quote__by">The Heelora studio</span>
        </div>
    </section>

    <section class="section section--tight">
        <div class="newsletter-band">
            <div>
                <span class="eyebrow" style="color: var(--blush)">Come see us</span>
                <h2 class="display-3">Visit the atelier</h2>
                <p><?= e(SITE_ADDRESS) ?> — open Monday to Saturday, 11am to 8pm.</p>
            </div>
            <div>
                <a class="btn btn--blush" href="<?= e(url('contact.php')) ?>">Get in touch <?= icon('arrow') ?></a>
            </div>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
