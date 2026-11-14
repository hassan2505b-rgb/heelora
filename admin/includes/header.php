<?php
declare(strict_types=1);

$admin_title  = $admin_title ?? 'Dashboard';
$admin_active = $admin_active ?? 'dashboard';
$admin        = $admin ?? admin_user();

$pendingCount = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$unreadCount  = (int) db()->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();

$adminNav = [
    ['label' => 'Dashboard',   'href' => 'index.php',       'key' => 'dashboard',  'icon' => 'grid'],
    ['label' => 'Products',    'href' => 'products.php',    'key' => 'products',   'icon' => 'box'],
    ['label' => 'Categories',  'href' => 'categories.php',  'key' => 'categories', 'icon' => 'layers'],
    ['label' => 'Orders',      'href' => 'orders.php',      'key' => 'orders',     'icon' => 'receipt', 'count' => $pendingCount],
    ['label' => 'Customers',   'href' => 'customers.php',   'key' => 'customers',  'icon' => 'users'],
    ['label' => 'Messages',    'href' => 'messages.php',    'key' => 'messages',   'icon' => 'mail', 'count' => $unreadCount],
    ['label' => 'Subscribers', 'href' => 'subscribers.php', 'key' => 'subscribers','icon' => 'sparkle'],
    ['label' => 'Settings',    'href' => 'settings.php',    'key' => 'settings',   'icon' => 'shield'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($admin_title) ?> · Heelora Admin</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
<div class="admin-body">
    <aside class="admin-sidebar" data-admin-sidebar>
        <div class="admin-brand">
            <span><?= heel_glyph() ?></span>
            <span>
                <span class="admin-brand__name">HEELORA</span>
                <span class="admin-brand__sub">Admin panel</span>
            </span>
        </div>

        <nav class="admin-nav" aria-label="Admin">
            <?php foreach ($adminNav as $item): ?>
                <a href="<?= e(admin_url($item['href'])) ?>" class="<?= $admin_active === $item['key'] ? 'is-active' : '' ?>">
                    <?= icon($item['icon']) ?>
                    <span><?= e($item['label']) ?></span>
                    <?php if (!empty($item['count'])): ?>
                        <span class="admin-nav__count"><?= (int) $item['count'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="admin-sidebar__foot">
            <div class="admin-sidebar__user">
                <strong><?= e($admin['name'] ?? 'Administrator') ?></strong>
                <?= e($admin['email'] ?? '') ?>
            </div>
            <a class="btn btn--light btn--sm" href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener"><?= icon('external') ?> View store</a>
            <a class="btn btn--danger btn--sm" href="<?= e(admin_url('logout.php')) ?>"><?= icon('logout') ?> Sign out</a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <div style="display:flex;align-items:center;gap:.7rem">
                <button class="icon-btn admin-menu-btn" type="button" data-admin-menu aria-label="Open menu"><?= icon('menu') ?></button>
                <div>
                    <h1 class="admin-topbar__title"><?= e($admin_title) ?></h1>
                    <p class="admin-topbar__sub mb-0"><?= e(date('l, j F Y')) ?></p>
                </div>
            </div>
            <div class="admin-topbar__actions">
                <a class="btn btn--light btn--sm" href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">Storefront <?= icon('external') ?></a>
                <a class="icon-btn" href="<?= e(admin_url('logout.php')) ?>" aria-label="Sign out"><?= icon('logout') ?></a>
            </div>
        </header>

        <div class="admin-content">
            <?= flash_render() ?>
