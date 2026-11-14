<?php
declare(strict_types=1);

$page_title = $page_title ?? 'Heelora — Ladies Footwear';
$page_desc  = $page_desc ?? 'Heelora crafts elegant ladies footwear in Lahore — stilettos, block heels, flats, sandals and bridal heels made for the way you move.';
$body_class = $body_class ?? '';
$active     = $active ?? '';
$cartCount  = function_exists('cart_count') ? cart_count() : 0;
$customer   = function_exists('current_customer') ? current_customer() : null;
$navItems   = [
    ['label' => 'Shop All',    'href' => 'shop.php',                 'key' => 'shop'],
    ['label' => 'New In',      'href' => 'shop.php?sort=newest',     'key' => 'new'],
    ['label' => 'Bridal',      'href' => 'shop.php?category=bridal', 'key' => 'bridal'],
    ['label' => 'About',       'href' => 'about.php',                'key' => 'about'],
    ['label' => 'Contact',     'href' => 'contact.php',              'key' => 'contact'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?></title>
    <meta name="description" content="<?= e($page_desc) ?>">
    <meta property="og:title" content="<?= e($page_title) ?>">
    <meta property="og:description" content="<?= e($page_desc) ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= e(asset('img/editorial/hero.jpg')) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="<?= e($body_class) ?>">

<a class="skip-link" href="#main">Skip to content</a>

<div class="announce">
    <div class="container announce__inner">
        <span>Complimentary delivery on orders over <?= e(money(FREE_SHIPPING_MIN)) ?></span>
        <span class="announce__dot" aria-hidden="true"></span>
        <span>7-day easy exchanges</span>
        <span class="announce__dot" aria-hidden="true"></span>
        <span>Cash on delivery across Pakistan</span>
    </div>
</div>

<header class="site-header" id="siteHeader">
    <div class="container site-header__inner">
        <button class="nav-toggle" type="button" aria-label="Open menu" aria-expanded="false" data-nav-toggle>
            <?= icon('menu') ?>
        </button>

        <?= brand_logo('header', 'index.php') ?>

        <nav class="main-nav" aria-label="Main">
            <?php foreach ($navItems as $item): ?>
                <a href="<?= e(url($item['href'])) ?>" class="<?= $active === $item['key'] ? 'is-active' : '' ?>">
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="header-actions">
            <button class="icon-btn" type="button" aria-label="Search" data-search-open>
                <?= icon('search') ?>
            </button>
            <a class="icon-btn icon-btn--account" href="<?= e(url($customer ? 'account.php' : 'login.php')) ?>" aria-label="Your account">
                <?= icon('user') ?>
            </a>
            <a class="icon-btn icon-btn--bag" href="<?= e(url('cart.php')) ?>" aria-label="Shopping bag, <?= $cartCount ?> items">
                <?= icon('bag') ?>
                <span class="bag-count" data-cart-count <?= $cartCount === 0 ? 'hidden' : '' ?>><?= $cartCount ?></span>
            </a>
        </div>
    </div>
</header>

<div class="search-overlay" data-search-overlay aria-hidden="true">
    <div class="search-overlay__inner">
        <button class="search-overlay__close" type="button" aria-label="Close search" data-search-close><?= icon('close') ?></button>
        <span class="eyebrow">What are you looking for?</span>
        <form action="<?= e(url('shop.php')) ?>" method="get" class="search-form">
            <input type="search" name="q" placeholder="Search heels, sandals, bridal…" aria-label="Search products" data-search-input>
            <button type="submit" class="btn btn--dark">Search</button>
        </form>
        <div class="search-overlay__hints">
            <span>Popular:</span>
            <a href="<?= e(url('shop.php?category=heels')) ?>">Stilettos</a>
            <a href="<?= e(url('shop.php?category=bridal')) ?>">Bridal</a>
            <a href="<?= e(url('shop.php?category=flats')) ?>">Flats</a>
            <a href="<?= e(url('shop.php?sort=price-low')) ?>">Under <?= e(money(5000)) ?></a>
        </div>
    </div>
</div>

<div class="mobile-nav" data-mobile-nav aria-hidden="true">
    <div class="mobile-nav__head">
        <?= brand_logo('header', 'index.php') ?>
        <button class="icon-btn" type="button" aria-label="Close menu" data-nav-close><?= icon('close') ?></button>
    </div>
    <nav class="mobile-nav__links" aria-label="Mobile">
        <?php foreach ($navItems as $item): ?>
            <a href="<?= e(url($item['href'])) ?>"><?= e($item['label']) ?></a>
        <?php endforeach; ?>
        <a href="<?= e(url('shop.php?category=heels')) ?>">Heels</a>
        <a href="<?= e(url('shop.php?category=sandals')) ?>">Sandals</a>
        <a href="<?= e(url('shop.php?category=flats')) ?>">Flats</a>
        <a href="<?= e(url('shop.php?category=block-heels')) ?>">Block Heels</a>
    </nav>
    <div class="mobile-nav__foot">
        <a class="btn btn--dark btn--full" href="<?= e(url('cart.php')) ?>">Shopping bag (<?= $cartCount ?>)</a>
        <a class="btn btn--ghost btn--full" href="<?= e(url($customer ? 'account.php' : 'login.php')) ?>"><?= $customer ? 'My account' : 'Sign in' ?></a>
    </div>
</div>
<div class="overlay-scrim" data-scrim></div>

<main id="main">
<?= flash_render() ?>
