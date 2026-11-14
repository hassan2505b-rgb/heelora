<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$admin = require_admin();

$pdo = db();
$id  = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    $status = (string) ($_POST['status'] ?? '');
    if ($id > 0 && in_array($status, order_statuses(), true)) {
        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);
        flash_set('success', 'Order status updated to ' . $status . '.');
    }
    redirect('admin/order-view.php?id=' . $id);
}

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    flash_set('error', 'That order could not be found.');
    redirect('admin/orders.php');
}

$itemStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemStmt->execute([$id]);
$items = $itemStmt->fetchAll();

$customerStmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
$customerStmt->execute([(int) ($order['customer_id'] ?? 0)]);
$customer = $customerStmt->fetch();

$admin_title  = 'Order ' . $order['order_number'];
$admin_active = 'orders';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-toolbar">
    <a class="btn btn--light btn--sm" href="<?= e(admin_url('orders.php')) ?>"><?= icon('arrow-left') ?> All orders</a>
    <form method="post" action="<?= e(admin_url('order-view.php?id=' . (int) $order['id'])) ?>" class="admin-toolbar__filters">
        <?= csrf_field() ?>
        <label class="field__label" for="ov-status" style="margin:0">Status</label>
        <select class="select" id="ov-status" name="status" data-auto-submit>
            <?php foreach (order_statuses() as $state): ?>
                <option value="<?= e($state) ?>" <?= $order['status'] === $state ? 'selected' : '' ?>><?= e(ucfirst($state)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn--dark btn--sm">Update</button>
    </form>
</div>

<div class="cols">
    <div class="admin-card">
        <div class="admin-card__head">
            <h2 class="admin-card__title">Items</h2>
            <span class="status <?= e(status_class((string) $order['status'])) ?>"><?= e(ucfirst((string) $order['status'])) ?></span>
        </div>

        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Size</th>
                        <th>Qty</th>
                        <th class="ta-right">Price</th>
                        <th class="ta-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:.8rem">
                                    <img class="thumb" style="width:40px;height:50px" src="<?= e(product_image($item['image'])) ?>" alt="<?= e($item['product_name']) ?>">
                                    <span class="cell-strong"><?= e($item['product_name']) ?></span>
                                </div>
                            </td>
                            <td><?= $item['size'] !== '' ? e($item['size']) : '—' ?></td>
                            <td><?= (int) $item['qty'] ?></td>
                            <td class="ta-right"><?= e(money($item['price'])) ?></td>
                            <td class="ta-right cell-strong"><?= e(money((float) $item['price'] * (int) $item['qty'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="max-width:320px;margin-left:auto;margin-top:1.2rem">
            <div style="display:flex;justify-content:space-between;padding:.4rem 0"><span>Subtotal</span><span><?= e(money($order['subtotal'])) ?></span></div>
            <div style="display:flex;justify-content:space-between;padding:.4rem 0"><span>Delivery</span><span><?= (float) $order['shipping'] > 0 ? e(money($order['shipping'])) : 'Free' ?></span></div>
            <div style="display:flex;justify-content:space-between;padding:.6rem 0;border-top:1px solid var(--line);font-size:1.1rem">
                <span>Total</span><strong style="font-family:var(--serif)"><?= e(money($order['total'])) ?></strong>
            </div>
        </div>
    </div>

    <div>
        <div class="admin-card">
            <div class="admin-card__head"><h2 class="admin-card__title">Customer</h2></div>
            <p class="mb-0">
                <span class="cell-strong"><?= e($order['name']) ?></span><br>
                <span class="cell-sub"><?= e($order['email']) ?></span><br>
                <span class="cell-sub"><?= e($order['phone']) ?></span>
            </p>
            <?php if ($customer): ?>
                <p class="field__hint mt-2">Registered account · <?= e($customer['email']) ?></p>
            <?php else: ?>
                <p class="field__hint mt-2">Guest checkout</p>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <div class="admin-card__head"><h2 class="admin-card__title">Delivery</h2></div>
            <p class="mb-0">
                <?= e($order['address']) ?><br>
                <?= e($order['city']) ?><?= $order['postal_code'] !== '' ? ', ' . e($order['postal_code']) : '' ?>
            </p>
            <?php if (trim((string) $order['notes']) !== ''): ?>
                <p class="field__hint mt-2">Notes: “<?= e($order['notes']) ?>”</p>
            <?php endif; ?>
        </div>

        <div class="admin-card mb-0">
            <div class="admin-card__head"><h2 class="admin-card__title">Payment</h2></div>
            <p class="mb-0"><?= $order['payment_method'] === 'cod' ? 'Cash on delivery' : 'Bank transfer' ?></p>
            <p class="field__hint">Placed <?= e(format_date((string) $order['created_at'], 'j M Y, g:ia')) ?></p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
