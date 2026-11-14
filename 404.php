<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

http_response_code(404);

$page_title = 'Page not found — ' . SITE_NAME;
$body_class = 'page-404';
$active     = '';

require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="empty">
        <div class="empty__icon"><?= icon('alert') ?></div>
        <span class="eyebrow">Error 404</span>
        <h2>This page has walked off</h2>
        <p>The link may be broken or the page may have been moved. Let's get you back on solid ground.</p>
        <div class="buy-row" style="justify-content:center">
            <a class="btn btn--dark" href="<?= e(url('index.php')) ?>">Back to home</a>
            <a class="btn btn--light" href="<?= e(url('shop.php')) ?>">Shop all footwear</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
