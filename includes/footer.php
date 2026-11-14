<?php
declare(strict_types=1);

$footerCategories = get_categories();
?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-top">
            <div class="footer-brand">
                <?= brand_logo('footer', 'index.php') ?>
                <p class="footer-brand__text">
                    Considered footwear for the modern woman — designed in Lahore,
                    made in small batches, worn everywhere.
                </p>
                <div class="footer-social">
                    <a href="<?= e(SITE_INSTAGRAM) ?>" target="_blank" rel="noopener" aria-label="Instagram"><?= icon('instagram') ?></a>
                    <a href="<?= e(SITE_FACEBOOK) ?>" target="_blank" rel="noopener" aria-label="Facebook"><?= icon('facebook') ?></a>
                    <a href="https://wa.me/9242111433567" target="_blank" rel="noopener" aria-label="WhatsApp"><?= icon('whatsapp') ?></a>
                </div>
            </div>

            <div class="footer-col">
                <h3 class="footer-col__title">Shop</h3>
                <ul>
                    <li><a href="<?= e(url('shop.php')) ?>">All footwear</a></li>
                    <?php foreach ($footerCategories as $cat): ?>
                        <li><a href="<?= e(url('shop.php?category=' . $cat['slug'])) ?>"><?= e($cat['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="footer-col">
                <h3 class="footer-col__title">Help</h3>
                <ul>
                    <li><a href="<?= e(url('contact.php')) ?>">Contact us</a></li>
                    <li><a href="<?= e(url('contact.php#faq')) ?>">Size &amp; fit guide</a></li>
                    <li><a href="<?= e(url('contact.php#faq')) ?>">Shipping &amp; returns</a></li>
                    <li><a href="<?= e(url('account.php')) ?>">Track your order</a></li>
                </ul>
            </div>

            <div class="footer-col footer-news">
                <h3 class="footer-col__title">The Heelora Letter</h3>
                <p>New arrivals, styling notes and quiet little offers. No noise.</p>
                <form action="<?= e(url('newsletter.php')) ?>" method="post" class="newsletter-form">
                    <?= csrf_field() ?>
                    <input type="email" name="email" placeholder="Your email address" required aria-label="Email address">
                    <button type="submit" class="btn btn--light">Join</button>
                </form>
                <p class="footer-news__note">By joining you agree to our privacy policy.</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Heelora. All rights reserved.</p>
            <p class="footer-bottom__made">Designed &amp; made in Lahore, Pakistan</p>
            <div class="footer-bottom__links">
                <a href="<?= e(url('contact.php')) ?>">Privacy</a>
                <a href="<?= e(url('contact.php')) ?>">Terms</a>
                <a href="<?= e(url('admin/login.php')) ?>">Admin</a>
            </div>
        </div>
    </div>
</footer>

<button class="to-top" type="button" data-to-top aria-label="Back to top"><?= icon('arrow') ?></button>

<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
