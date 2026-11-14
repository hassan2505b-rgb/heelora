<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$admin = require_admin();

$pdo = db();

/* ---------- CSV export (before any output) ---------- */
if (($_GET['export'] ?? '') === 'csv') {
    $rows = $pdo->query('SELECT email, created_at FROM subscribers ORDER BY id DESC')->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="heelora-subscribers-' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Subscribed at']);
    foreach ($rows as $row) {
        fputcsv($out, [$row['email'], $row['created_at']]);
    }
    fclose($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    $id     = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete' && $id > 0) {
        $pdo->prepare('DELETE FROM subscribers WHERE id = ?')->execute([$id]);
        flash_set('info', 'Subscriber removed.');
    }

    redirect('admin/subscribers.php');
}

$q       = trim((string) ($_GET['q'] ?? ''));
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where  = ['1 = 1'];
$params = [];
if ($q !== '') {
    $where[] = 'email LIKE ?';
    $params[] = '%' . $q . '%';
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM subscribers WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page  = min($page, $pages);

$listStmt = $pdo->prepare(
    "SELECT * FROM subscribers WHERE $whereSql ORDER BY id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage)
);
$listStmt->execute($params);
$subscribers = $listStmt->fetchAll();

$admin_title  = 'Subscribers';
$admin_active = 'subscribers';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-toolbar">
    <form method="get" action="<?= e(admin_url('subscribers.php')) ?>" class="admin-toolbar__filters">
        <div class="admin-search">
            <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search email…" aria-label="Search subscribers">
            <button type="submit" class="btn btn--light btn--sm">Search</button>
        </div>
        <?php if ($q !== ''): ?>
            <a class="btn btn--light btn--sm" href="<?= e(admin_url('subscribers.php')) ?>">Reset</a>
        <?php endif; ?>
    </form>

    <a class="btn btn--dark btn--sm" href="<?= e(admin_url('subscribers.php?export=csv')) ?>"><?= icon('arrow') ?> Export CSV</a>
</div>

<p class="text-muted"><?= $total ?> <?= $total === 1 ? 'subscriber' : 'subscribers' ?></p>

<?php if (!$subscribers): ?>
    <div class="admin-card"><div class="empty"><div class="empty__icon"><?= icon('sparkle') ?></div><p>No subscribers yet.</p></div></div>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Subscribed</th>
                    <th class="ta-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscribers as $subscriber): ?>
                    <tr>
                        <td class="cell-strong"><?= e($subscriber['email']) ?></td>
                        <td class="cell-sub"><?= e(format_date((string) $subscriber['created_at'], 'j M Y')) ?></td>
                        <td>
                            <div class="admin-table__actions">
                                <form method="post" action="<?= e(admin_url('subscribers.php')) ?>" data-confirm="Remove <?= e($subscriber['email']) ?>?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $subscriber['id'] ?>">
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
                    <a href="<?= e(admin_url('subscribers.php?' . http_build_query(['q' => $q, 'page' => $i]))) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
