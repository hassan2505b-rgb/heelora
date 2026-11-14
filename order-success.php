<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$orderId = (int) ($_SESSION['last_order_id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    flash_set('info', 'We could not find that order.');
    redirect('shop.php');
}

$itemStmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemStmt->execute([(int) $order['id']]);
$items = $itemStmt->fetchAll();

$payments = [
    'cod'  => 'Cash on delivery',
    'bank' => 'Bank transfer',
];

$page_title = 'Order confirmed — ' . SITE_NAME;
$body_class = 'page-success';
$active     = '';

require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="steps">
        <span class="step is-done"><span class="step__dot"><?= icon('check') ?></span> Bag</span>
        <span class="step__sep"></span>
        <span class="step is-done"><span class="step__dot"><?= icon('check') ?></span> Details</span>
        <span class="step__sep"></span>
        <span class="step is-active"><span class="step__dot">3</span> Confirmation</span>
    </div>

    <div class="order-hero">
        <div class="order-hero__icon"><?= icon('check') ?></div>
        <span class="eyebrow">Order <?= e($order['order_number']) ?></span>
        <h1 class="display-2">Thank you, <?= e(explode(' ', trim((string) $order['name']))[0]) ?>.</h1>
        <p class="lede" style="margin-inline:auto">
            Your order is confirmed and will be dispatched from our Lahore atelier within 24 hours.
            A confirmation has been sent to <strong><?= e($order['email']) ?></strong>.
        </p>
    </div>

    <div class="order-detail">
        <div class="card">
            <div class="card__head">
                <h2 class="card__title">Items ordered</h2>
                <span class="status <?= e(status_class((string) $order['status'])) ?>" style="margin-left:auto"><?= e(ucfirst((string) $order['status'])) ?></span>
            </div>

            <?php foreach ($items as $item): ?>
                <div class="mini-line">
                    <div class="mini-line__media">
                        <img src="<?= e(product_image($item['image'])) ?>" alt="<?= e($item['product_name']) ?>">
                        <span class="mini-line__qty"><?= (int) $item['qty'] ?></span>
                    </div>
                    <div>
                        <div class="mini-line__name"><?= e($item['product_name']) ?></div>
                        <div class="mini-line__meta">
                            <?= $item['size'] !== '' ? 'Size ' . e($item['size']) : 'One size' ?> ·
                            <?= (int) $item['qty'] ?> × <?= e(money($item['price'])) ?>
                        </div>
                    </div>
                    <div class="mini-line__price"><?= e(money((float) $item['price'] * (int) $item['qty'])) ?></div>
                </div>
            <?php endforeach; ?>

            <div class="summary__row mt-3"><span>Subtotal</span><span><?= e(money($order['subtotal'])) ?></span></div>
            <div class="summary__row">
                <span>Delivery</span>
                <span><?= (float) $order['shipping'] > 0 ? e(money($order['shipping'])) : 'Free' ?></span>
            </div>
            <div class="summary__row summary__row--total">
                <span>Total</span>
                <strong><?= e(money($order['total'])) ?></strong>
            </div>
        </div>

        <aside class="summary">
            <h2 class="summary__title">Delivery details</h2>
            <div class="info-item">
                <?= icon('pin') ?>
                <div>
                    <strong>Ship to</strong>
                    <?= e($order['name']) ?><br>
                    <?= e($order['address']) ?><br>
                    <?= e($order['city']) ?><?= $order['postal_code'] !== '' ? ', ' . e($order['postal_code']) : '' ?>
                </div>
            </div>
            <div class="info-item">
                <?= icon('phone') ?>
                <div>
                    <strong>Contact</strong>
                    <?= e($order['phone']) ?><br><?= e($order['email']) ?>
                </div>
            </div>
            <div class="info-item" style="border-bottom:0">
                <?= icon('receipt') ?>
                <div>
                    <strong>Payment</strong>
                    <?= e($payments[$order['payment_method']] ?? ucfirst((string) $order['payment_method'])) ?><br>
                    Ordered <?= e(format_date((string) $order['created_at'])) ?>
                </div>
            </div>
            <?php if (trim((string) $order['notes']) !== ''): ?>
                <p class="summary__note"><?= icon('edit') ?> “<?= e($order['notes']) ?>”</p>
            <?php endif; ?>

            <a class="btn btn--dark btn--full" href="<?= e(url('shop.php')) ?>">Continue shopping <?= icon('arrow') ?></a>
            <a class="btn btn--light btn--full mt-2" href="<?= e(url('account.php')) ?>">View my orders</a>
        </aside>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
