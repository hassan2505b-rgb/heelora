<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

/**
 * Redirect back to wherever the action came from (works with a full path,
 * an external URL or a plain file name).
 */
function cart_redirect(string $target): never
{
    if ($target === '') {
        $target = 'cart.php';
    }
    if (preg_match('#^https?://#', $target) || str_starts_with($target, '/')) {
        header('Location: ' . $target);
        exit;
    }
    redirect($target);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    $action   = (string) ($_POST['action'] ?? '');
    $redirect = trim((string) ($_POST['redirect'] ?? ''));

    if ($action === 'add') {
        $id   = (int) ($_POST['product_id'] ?? 0);
        $qty  = max(1, (int) ($_POST['qty'] ?? 1));
        $size = trim((string) ($_POST['size'] ?? ''));

        $stmt = db()->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1');
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            flash_set('error', 'That style is no longer available.');
        } elseif ((int) $product['stock'] <= 0) {
            flash_set('error', $product['name'] . ' is sold out.');
        } else {
            cart_add($id, $qty, $size);
            flash_set('success', $product['name'] . ' added to your bag.');
        }

        cart_redirect($redirect);
    }

    if ($action === 'update') {
        cart_update((string) ($_POST['key'] ?? ''), (int) ($_POST['qty'] ?? 0));
        flash_set('success', 'Your bag has been updated.');
        cart_redirect($redirect);
    }

    if ($action === 'remove') {
        cart_remove((string) ($_POST['key'] ?? ''));
        flash_set('info', 'Item removed from your bag.');
        cart_redirect($redirect);
    }

    if ($action === 'clear') {
        cart_clear();
        flash_set('info', 'Your bag is now empty.');
        cart_redirect('cart.php');
    }

    cart_redirect($redirect);
}

$lines  = cart_lines();
$totals = cart_totals();

$page_title = 'Your bag — ' . SITE_NAME;
$body_class = 'page-cart';
$active     = '';

require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb">
        <a href="<?= e(url('index.php')) ?>">Home</a>
        <span class="breadcrumb__sep">/</span>
        <span>Your bag</span>
    </nav>

    <div class="section__head">
        <div>
            <h1 class="display-3 section__title">Your bag</h1>
            <p class="section__sub">
                <?= count($lines) ?> <?= count($lines) === 1 ? 'style' : 'styles' ?> selected
            </p>
        </div>
        <?php if ($lines): ?>
            <form method="post" action="<?= e(url('cart.php')) ?>" data-confirm="Empty your bag?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="icon-link"><?= icon('trash') ?> Empty bag</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!$lines): ?>
        <div class="empty">
            <div class="empty__icon"><?= icon('bag') ?></div>
            <h2>Your bag is empty</h2>
            <p>Nothing here yet — let's find you something beautiful.</p>
            <a class="btn btn--dark" href="<?= e(url('shop.php')) ?>">Start shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <div class="cart-list">
                <?php foreach ($lines as $line): ?>
                    <?php $p = $line['product']; ?>
                    <form method="post" action="<?= e(url('cart.php')) ?>" class="cart-row">
                        <?= csrf_field() ?>
                        <input type="hidden" name="key" value="<?= e($line['key']) ?>">
                        <input type="hidden" name="redirect" value="cart.php">

                        <div class="cart-row__media">
                            <a href="<?= e(url('product.php?slug=' . urlencode((string) $p['slug']))) ?>">
                                <img src="<?= e(product_image($p['image'])) ?>" alt="<?= e($p['name']) ?>">
                            </a>
                        </div>

                        <div class="cart-row__body">
                            <h2 class="cart-row__name">
                                <a href="<?= e(url('product.php?slug=' . urlencode((string) $p['slug']))) ?>"><?= e($p['name']) ?></a>
                            </h2>
                            <p class="cart-row__meta">
                                <?php if ($line['size'] !== ''): ?>Size <?= e($line['size']) ?> · <?php endif; ?>
                                <?= e(money($line['price'])) ?> each
                            </p>

                            <div class="cart-row__actions">
                                <div class="qty" data-qty>
                                    <button type="button" data-qty-step="-1" aria-label="Decrease quantity"><?= icon('minus') ?></button>
                                    <input type="number" name="qty" value="<?= (int) $line['qty'] ?>" min="1" max="10" aria-label="Quantity">
                                    <button type="button" data-qty-step="1" aria-label="Increase quantity"><?= icon('plus') ?></button>
                                </div>

                                <div class="cart-row__price"><?= e(money($line['total'])) ?></div>

                                <button type="submit" name="action" value="update" class="btn btn--light btn--sm">Update</button>
                                <button type="submit" name="action" value="remove" class="icon-link"><?= icon('trash') ?> Remove</button>
                            </div>
                        </div>
                    </form>
                <?php endforeach; ?>

                <a class="link-arrow" href="<?= e(url('shop.php')) ?>"><?= icon('arrow-left') ?> Continue shopping</a>
            </div>

            <aside class="summary">
                <h2 class="summary__title">Order summary</h2>

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

                <?php if ($totals['free_left'] > 0): ?>
                    <div class="freeship mt-2">
                        <p>Add <strong><?= e(money($totals['free_left'])) ?></strong> more for free delivery.</p>
                        <div class="freeship__bar">
                            <span style="width: <?= (int) min(100, round(($totals['subtotal'] / FREE_SHIPPING_MIN) * 100)) ?>%"></span>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="summary__note mt-2"><?= icon('check') ?> You've unlocked complimentary delivery.</p>
                <?php endif; ?>

                <div class="summary__row"><span>Subtotal</span><span><?= e(money($totals['subtotal'])) ?></span></div>
                <div class="summary__row">
                    <span>Delivery</span>
                    <span><?= $totals['shipping'] > 0 ? e(money($totals['shipping'])) : 'Free' ?></span>
                </div>
                <div class="summary__row summary__row--total">
                    <span>Total</span>
                    <strong><?= e(money($totals['total'])) ?></strong>
                </div>

                <a class="btn btn--dark btn--full" href="<?= e(url('checkout.php')) ?>">Proceed to checkout <?= icon('arrow') ?></a>
                <p class="summary__note"><?= icon('shield') ?> Secure checkout · Cash on delivery available</p>
            </aside>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
