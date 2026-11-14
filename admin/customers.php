<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$admin = require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0 && ($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
        flash_set('info', 'Customer removed.');
    }

    redirect('admin/customers.php');
}

$q        = trim((string) ($_GET['q'] ?? ''));
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 15;

$where  = ['1 = 1'];
$params = [];
if ($q !== '') {
    $where[]  = '(c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.city LIKE ?)';
    $term     = '%' . $q . '%';
    array_push($params, $term, $term, $term, $term);
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM customers c WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page  = min($page, $pages);

$listStmt = $pdo->prepare(
    "SELECT c.*,
            (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) AS order_count,
            (SELECT COALESCE(SUM(o.total), 0) FROM orders o WHERE o.customer_id = c.id AND o.status != 'cancelled') AS spend
     FROM customers c WHERE $whereSql
     ORDER BY c.id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage)
);
$listStmt->execute($params);
$customers = $listStmt->fetchAll();

$admin_title  = 'Customers';
$admin_active = 'customers';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-toolbar">
    <form method="get" action="<?= e(admin_url('customers.php')) ?>" class="admin-toolbar__filters">
        <div class="admin-search">
            <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email, phone or city…" aria-label="Search customers">
            <button type="submit" class="btn btn--light btn--sm">Search</button>
        </div>
        <?php if ($q !== ''): ?>
            <a class="btn btn--light btn--sm" href="<?= e(admin_url('customers.php')) ?>">Reset</a>
        <?php endif; ?>
    </form>
</div>

<p class="text-muted"><?= $total ?> <?= $total === 1 ? 'customer' : 'customers' ?></p>

<?php if (!$customers): ?>
    <div class="admin-card"><div class="empty"><div class="empty__icon"><?= icon('users') ?></div><p>No customers yet.</p></div></div>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>City</th>
                    <th>Orders</th>
                    <th>Spend</th>
                    <th>Joined</th>
                    <th class="ta-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td class="cell-strong"><?= e($customer['name']) ?></td>
                        <td>
                            <span class="cell-sub" style="display:block"><?= e($customer['email']) ?></span>
                            <span class="cell-sub"><?= e($customer['phone'] !== '' ? $customer['phone'] : '—') ?></span>
                        </td>
                        <td><?= e($customer['city'] !== '' ? $customer['city'] : '—') ?></td>
                        <td><?= (int) $customer['order_count'] ?></td>
                        <td class="cell-strong"><?= e(money($customer['spend'])) ?></td>
                        <td class="cell-sub"><?= e(format_date((string) $customer['created_at'])) ?></td>
                        <td>
                            <div class="admin-table__actions">
                                <form method="post" action="<?= e(admin_url('customers.php')) ?>" data-confirm="Remove <?= e($customer['name']) ?>?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $customer['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn--danger btn--sm"><?= icon('trash') ?></button>
                                </form>
                            </div>
                        </td>
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
                    <a href="<?= e(admin_url('customers.php?' . http_build_query(['q' => $q, 'page' => $i]))) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
