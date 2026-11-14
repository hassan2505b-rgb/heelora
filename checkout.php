<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$lines  = cart_lines();
$totals = cart_totals();

if (!$lines) {
    flash_set('info', 'Your bag is empty — add something you love first.');
    redirect('shop.php');
}

$customer = current_customer();

$payments = [
    'cod'  => ['label' => 'Cash on delivery', 'note' => 'Pay in cash when your order arrives at your door.'],
    'bank' => ['label' => 'Bank transfer',    'note' => 'We will share our account details once your order is placed.'],
];

$form = [
    'name'           => (string) ($customer['name'] ?? ''),
    'email'          => (string) ($customer['email'] ?? ''),
    'phone'          => (string) ($customer['phone'] ?? ''),
    'address'        => (string) ($customer['address'] ?? ''),
    'city'           => (string) ($customer['city'] ?? ''),
    'postal_code'    => '',
    'notes'          => '',
    'payment_method' => 'cod',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    foreach (array_keys($form) as $field) {
        $form[$field] = trim((string) ($_POST[$field] ?? ''));
    }
    if (!isset($payments[$form['payment_method']])) {
        $form['payment_method'] = 'cod';
    }

    if ($form['name'] === '') {
        $errors['name'] = 'Please enter your full name.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($form['phone'] === '') {
        $errors['phone'] = 'Please enter a contact number.';
    }
    if ($form['address'] === '') {
        $errors['address'] = 'Please enter your delivery address.';
    }
    if ($form['city'] === '') {
        $errors['city'] = 'Please enter your city.';
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO orders (order_number, customer_id, name, email, phone, address, city, postal_code, notes, subtotal, shipping, total, payment_method, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime("now"))'
            )->execute([
                'TMP-' . bin2hex(random_bytes(6)),
                $customer['id'] ?? null,
                $form['name'],
                $form['email'],
                $form['phone'],
                $form['address'],
                $form['city'],
                $form['postal_code'],
                $form['notes'],
                $totals['subtotal'],
                $totals['shipping'],
                $totals['total'],
                $form['payment_method'],
                'pending',
            ]);

            $orderId     = (int) $pdo->lastInsertId();
            $orderNumber = 'HW-' . (1000 + $orderId);

            $pdo->prepare('UPDATE orders SET order_number = ? WHERE id = ?')->execute([$orderNumber, $orderId]);

            $itemStmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, image, price, qty, size) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stockStmt = $pdo->prepare('UPDATE products SET stock = MAX(0, stock - ?) WHERE id = ?');

            foreach ($lines as $line) {
                $itemStmt->execute([
                    $orderId,
                    (int) $line['product']['id'],
                    $line['product']['name'],
                    $line['product']['image'],
                    $line['price'],
                    (int) $line['qty'],
                    $line['size'],
                ]);
                $stockStmt->execute([(int) $line['qty'], (int) $line['product']['id']]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        cart_clear();
        $_SESSION['last_order_id'] = $orderId;
        flash_set('success', 'Thank you! Your order ' . $orderNumber . ' is confirmed.');
        redirect('order-success.php');
    }

    flash_set('error', 'Please check the highlighted fields and try again.');
}

$page_title = 'Checkout — ' . SITE_NAME;
$body_class = 'page-checkout';
$active     = '';

require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb">
        <a href="<?= e(url('index.php')) ?>">Home</a>
        <span class="breadcrumb__sep">/</span>
        <a href="<?= e(url('cart.php')) ?>">Your bag</a>
        <span class="breadcrumb__sep">/</span>
        <span>Checkout</span>
    </nav>

    <div class="steps">
        <span class="step is-done"><span class="step__dot"><?= icon('check') ?></span> Bag</span>
        <span class="step__sep"></span>
        <span class="step is-active"><span class="step__dot">2</span> Details</span>
        <span class="step__sep"></span>
        <span class="step"><span class="step__dot">3</span> Confirmation</span>
    </div>

    <form method="post" action="<?= e(url('checkout.php')) ?>">
        <?= csrf_field() ?>

        <div class="checkout-layout">
            <div>
                <div class="card">
                    <div class="card__head">
                        <span class="card__num">1</span>
                        <h2 class="card__title">Contact details</h2>
                    </div>

                    <div class="field">
                        <label class="field__label" for="co-name">Full name</label>
                        <input class="input" type="text" id="co-name" name="name" value="<?= e($form['name']) ?>" required>
                        <?php if (isset($errors['name'])): ?><p class="field__error"><?= e($errors['name']) ?></p><?php endif; ?>
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label class="field__label" for="co-email">Email</label>
                            <input class="input" type="email" id="co-email" name="email" value="<?= e($form['email']) ?>" required>
                            <?php if (isset($errors['email'])): ?><p class="field__error"><?= e($errors['email']) ?></p><?php endif; ?>
                        </div>
                        <div class="field">
                            <label class="field__label" for="co-phone">Phone</label>
                            <input class="input" type="tel" id="co-phone" name="phone" value="<?= e($form['phone']) ?>" required>
                            <?php if (isset($errors['phone'])): ?><p class="field__error"><?= e($errors['phone']) ?></p><?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card__head">
                        <span class="card__num">2</span>
                        <h2 class="card__title">Delivery address</h2>
                    </div>

                    <div class="field">
                        <label class="field__label" for="co-address">Street address</label>
                        <input class="input" type="text" id="co-address" name="address" value="<?= e($form['address']) ?>" required>
                        <?php if (isset($errors['address'])): ?><p class="field__error"><?= e($errors['address']) ?></p><?php endif; ?>
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label class="field__label" for="co-city">City</label>
                            <input class="input" type="text" id="co-city" name="city" value="<?= e($form['city']) ?>" required>
                            <?php if (isset($errors['city'])): ?><p class="field__error"><?= e($errors['city']) ?></p><?php endif; ?>
                        </div>
                        <div class="field">
                            <label class="field__label" for="co-postal">Postal code</label>
                            <input class="input" type="text" id="co-postal" name="postal_code" value="<?= e($form['postal_code']) ?>">
                        </div>
                    </div>

                    <div class="field mb-0">
                        <label class="field__label" for="co-notes">Order notes <span class="text-muted">(optional)</span></label>
                        <textarea class="textarea" id="co-notes" name="notes" rows="3" placeholder="Delivery instructions, gift note…"><?= e($form['notes']) ?></textarea>
                    </div>
                </div>

                <div class="card">
                    <div class="card__head">
                        <span class="card__num">3</span>
                        <h2 class="card__title">Payment</h2>
                    </div>

                    <div class="pay-options">
                        <?php foreach ($payments as $value => $pay): ?>
                            <label class="pay-option">
                                <input type="radio" name="payment_method" value="<?= e($value) ?>" data-pay <?= $form['payment_method'] === $value ? 'checked' : '' ?>>
                                <span>
                                    <strong><?= e($pay['label']) ?></strong>
                                    <small><?= e($pay['note']) ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <?php foreach ($payments as $value => $pay): ?>
                        <p class="field__hint mt-2" data-pay-note="<?= e($value) ?>" hidden>
                            <?= icon('shield') ?> <?= e($pay['note']) ?>
                        </p>
                    <?php endforeach; ?>
                </div>
            </div>

            <aside class="summary">
                <h2 class="summary__title">Your order</h2>

                <?php foreach ($lines as $line): ?>
                    <div class="mini-line">
                        <div class="mini-line__media">
                            <img src="<?= e(product_image($line['product']['image'])) ?>" alt="<?= e($line['product']['name']) ?>">
                            <span class="mini-line__qty"><?= (int) $line['qty'] ?></span>
                        </div>
                        <div>
                            <div class="mini-line__name"><?= e($line['product']['name']) ?></div>
                            <div class="mini-line__meta"><?= $line['size'] !== '' ? 'Size ' . e($line['size']) : 'One size' ?></div>
                        </div>
                        <div class="mini-line__price"><?= e(money($line['total'])) ?></div>
                    </div>
                <?php endforeach; ?>

                <div class="summary__row"><span>Subtotal</span><span><?= e(money($totals['subtotal'])) ?></span></div>
                <div class="summary__row">
                    <span>Delivery</span>
                    <span><?= $totals['shipping'] > 0 ? e(money($totals['shipping'])) : 'Free' ?></span>
                </div>
                <div class="summary__row summary__row--total">
                    <span>Total</span>
                    <strong><?= e(money($totals['total'])) ?></strong>
                </div>

                <button type="submit" class="btn btn--dark btn--full">Place order <?= icon('arrow') ?></button>
                <p class="summary__note"><?= icon('refresh') ?> <?= e(money(FREE_SHIPPING_MIN)) ?>+ orders ship free · 7-day exchanges</p>
            </aside>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
