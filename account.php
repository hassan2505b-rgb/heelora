<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$customer = require_customer();

$tab    = ($_GET['tab'] ?? 'orders') === 'profile' ? 'profile' : 'orders';
$errors = [];

$form = [
    'name'    => (string) $customer['name'],
    'phone'   => (string) $customer['phone'],
    'city'    => (string) $customer['city'],
    'address' => (string) $customer['address'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    if (($_POST['action'] ?? '') === 'profile') {
        $tab = 'profile';
        foreach (array_keys($form) as $field) {
            $form[$field] = trim((string) ($_POST[$field] ?? ''));
        }
        if ($form['name'] === '') {
            $errors['name'] = 'Please enter your name.';
        }

        if (!$errors) {
            db()->prepare('UPDATE customers SET name = ?, phone = ?, city = ?, address = ? WHERE id = ?')
                ->execute([$form['name'], $form['phone'], $form['city'], $form['address'], (int) $customer['id']]);
            flash_set('success', 'Your details have been saved.');
            redirect('account.php?tab=profile');
        }

        flash_set('error', 'Please check the highlighted fields.');
    }
}

$orderStmt = db()->prepare('SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC');
$orderStmt->execute([(int) $customer['id']]);
$orders = $orderStmt->fetchAll();

$itemStmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
$spent    = 0.0;
foreach ($orders as &$order) {
    $itemStmt->execute([(int) $order['id']]);
    $order['items'] = $itemStmt->fetchAll();
    if ($order['status'] !== 'cancelled') {
        $spent += (float) $order['total'];
    }
}
unset($order);

$page_title = 'My account — ' . SITE_NAME;
$body_class = 'page-account';
$active     = '';

require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb">
        <a href="<?= e(url('index.php')) ?>">Home</a>
        <span class="breadcrumb__sep">/</span>
        <span>My account</span>
    </nav>

    <div class="section__head">
        <div>
            <span class="eyebrow">Hello, <?= e(explode(' ', trim((string) $customer['name']))[0]) ?></span>
            <h1 class="display-3 section__title">My account</h1>
        </div>
        <a class="btn btn--light" href="<?= e(url('logout.php')) ?>"><?= icon('logout') ?> Sign out</a>
    </div>

    <div class="tabs">
        <a class="tab <?= $tab === 'orders' ? 'is-active' : '' ?>" href="<?= e(url('account.php?tab=orders')) ?>">Orders</a>
        <a class="tab <?= $tab === 'profile' ? 'is-active' : '' ?>" href="<?= e(url('account.php?tab=profile')) ?>">Profile</a>
        <a class="tab" href="<?= e(url('wishlist.php')) ?>">Wishlist (<?= (int) wishlist_count() ?>)</a>
    </div>

    <?php if ($tab === 'profile'): ?>
        <div class="card" style="max-width:640px">
            <div class="card__head"><h2 class="card__title">Your details</h2></div>

            <form method="post" action="<?= e(url('account.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="profile">

                <div class="field">
                    <label class="field__label" for="acc-name">Full name</label>
                    <input class="input" type="text" id="acc-name" name="name" value="<?= e($form['name']) ?>" required>
                    <?php if (isset($errors['name'])): ?><p class="field__error"><?= e($errors['name']) ?></p><?php endif; ?>
                </div>

                <div class="field">
                    <label class="field__label">Email</label>
                    <input class="input" type="email" value="<?= e($customer['email']) ?>" disabled>
                    <p class="field__hint">Your email is used to sign in and cannot be changed here.</p>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label class="field__label" for="acc-phone">Phone</label>
                        <input class="input" type="tel" id="acc-phone" name="phone" value="<?= e($form['phone']) ?>">
                    </div>
                    <div class="field">
                        <label class="field__label" for="acc-city">City</label>
                        <input class="input" type="text" id="acc-city" name="city" value="<?= e($form['city']) ?>">
                    </div>
                </div>

                <div class="field">
                    <label class="field__label" for="acc-address">Address</label>
                    <input class="input" type="text" id="acc-address" name="address" value="<?= e($form['address']) ?>">
                </div>

                <button type="submit" class="btn btn--dark">Save changes</button>
            </form>
        </div>
    <?php else: ?>
        <div class="figures" style="padding-top:0">
            <div class="figure"><strong><?= count($orders) ?></strong><span>Orders placed</span></div>
            <div class="figure"><strong><?= e(money($spent)) ?></strong><span>Lifetime spend</span></div>
            <div class="figure"><strong><?= (int) wishlist_count() ?></strong><span>Saved styles</span></div>
        </div>

        <?php if (!$orders): ?>
            <div class="empty">
                <div class="empty__icon"><?= icon('receipt') ?></div>
                <h2>No orders yet</h2>
                <p>When you place your first order it will appear here.</p>
                <a class="btn btn--dark" href="<?= e(url('shop.php')) ?>">Start shopping</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="card">
                    <div class="card__head" style="flex-wrap:wrap">
                        <h2 class="order-row__num mb-0"><?= e($order['order_number']) ?></h2>
                        <span class="status <?= e(status_class((string) $order['status'])) ?>"><?= e(ucfirst((string) $order['status'])) ?></span>
                        <span class="order-row__meta" style="margin-left:auto">
                            <?= e(format_date((string) $order['created_at'])) ?> ·
                            <?= count($order['items']) ?> <?= count($order['items']) === 1 ? 'item' : 'items' ?> ·
                            <strong><?= e(money($order['total'])) ?></strong>
                        </span>
                    </div>

                    <?php foreach ($order['items'] as $item): ?>
                        <div class="mini-line">
                            <div class="mini-line__media">
                                <img src="<?= e(product_image($item['image'])) ?>" alt="<?= e($item['product_name']) ?>">
                                <span class="mini-line__qty"><?= (int) $item['qty'] ?></span>
                            </div>
                            <div>
                                <div class="mini-line__name"><?= e($item['product_name']) ?></div>
                                <div class="mini-line__meta"><?= $item['size'] !== '' ? 'Size ' . e($item['size']) : 'One size' ?></div>
                            </div>
                            <div class="mini-line__price"><?= e(money((float) $item['price'] * (int) $item['qty'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
