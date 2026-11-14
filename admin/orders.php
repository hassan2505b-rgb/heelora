<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$admin = require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    $id     = (int) ($_POST['id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');

    if ($id > 0 && in_array($status, order_statuses(), true)) {
        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);
        flash_set('success', 'Order status updated to ' . $status . '.');
    }

    $back = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    if (preg_match('#^https?://#', $back)) {
        header('Location: ' . $back);
        exit;
    }
    redirect('admin/orders.php');
}

$status = trim((string) ($_GET['status'] ?? ''));
$q      = trim((string) ($_GET['q'] ?? ''));
$page   = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;

$where  = ['1 = 1'];
$params = [];
if ($status !== '' && in_array($status, order_statuses(), true)) {
    $where[]  = 'o.status = ?';
    $params[] = $status;
} else {
    $status = '';
}
if ($q !== '') {
    $where[]  = '(o.order_number LIKE ? OR o.name LIKE ? OR o.email LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page  = min($page, $pages);

$listStmt = $pdo->prepare(
    "SELECT o.*, (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.id) AS item_count
     FROM orders o WHERE $whereSql
     ORDER BY o.id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage)
);
$listStmt->execute($params);
$orders = $listStmt->fetchAll();

$linkTo = static function (array $overrides = []) use ($status, $q): string {
    $params = array_filter(array_merge(['status' => $status, 'q' => $q], $overrides), static fn ($v) => $v !== '' && $v !== null);
    return admin_url('orders.php' . ($params ? '?' . http_build_query($params) : ''));
};

$admin_title  = 'Orders';
$admin_active = 'orders';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-toolbar">
    <form method="get" action="<?= e(admin_url('orders.php')) ?>" class="admin-toolbar__filters">
        <div class="admin-search">
            <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Order no, name or email…" aria-label="Search orders">
            <button type="submit" class="btn btn--light btn--sm">Search</button>
        </div>
        <select class="select" name="status" data-auto-submit aria-label="Filter by status">
            <option value="">All statuses</option>
            <?php foreach (order_statuses() as $state): ?>
                <option value="<?= e($state) ?>" <?= $status === $state ? 'selected' : '' ?>><?= e(ucfirst($state)) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($status !== '' || $q !== ''): ?>
            <a class="btn btn--light btn--sm" href="<?= e(admin_url('orders.php')) ?>">Reset</a>
        <?php endif; ?>
    </form>
</div>

<p class="text-muted"><?= $total ?> <?= $total === 1 ? 'order' : 'orders' ?></p>

<?php if (!$orders): ?>
    <div class="admin-card"><div class="empty"><div class="empty__icon"><?= icon('receipt') ?></div><p>No orders match this view.</p></div></div>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th class="ta-right">Placed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><a class="cell-strong" href="<?= e(admin_url('order-view.php?id=' . (int) $order['id'])) ?>"><?= e($order['order_number']) ?></a></td>
                        <td>
                            <span class="cell-strong"><?= e($order['name']) ?></span>
                            <span class="cell-sub" style="display:block"><?= e($order['city']) ?></span>
                        </td>
                        <td><?= (int) $order['item_count'] ?></td>
                        <td class="cell-strong"><?= e(money($order['total'])) ?></td>
                        <td class="cell-sub"><?= $order['payment_method'] === 'cod' ? 'Cash on delivery' : 'Bank transfer' ?></td>
                        <td>
                            <form method="post" action="<?= e(admin_url('orders.php')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                                <select class="select" name="status" data-auto-submit aria-label="Update status">
                                    <?php foreach (order_statuses() as $state): ?>
                                        <option value="<?= e($state) ?>" <?= $order['status'] === $state ? 'selected' : '' ?>><?= e(ucfirst($state)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td class="ta-right cell-sub"><?= e(format_date((string) $order['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="is-current"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= e($linkTo(['page' => $i])) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
