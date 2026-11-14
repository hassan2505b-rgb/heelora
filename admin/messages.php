<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$admin = require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    $id     = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    if ($id > 0) {
        if ($action === 'read') {
            $pdo->prepare('UPDATE messages SET is_read = 1 WHERE id = ?')->execute([$id]);
            flash_set('info', 'Marked as read.');
        } elseif ($action === 'unread') {
            $pdo->prepare('UPDATE messages SET is_read = 0 WHERE id = ?')->execute([$id]);
            flash_set('info', 'Marked as unread.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
            flash_set('info', 'Message deleted.');
        }
    }

    redirect('admin/messages.php');
}

$q       = trim((string) ($_GET['q'] ?? ''));
$filter  = (string) ($_GET['filter'] ?? '');
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;

$where  = ['1 = 1'];
$params = [];
if ($q !== '') {
    $where[]  = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)';
    $term     = '%' . $q . '%';
    array_push($params, $term, $term, $term, $term);
}
if ($filter === 'unread') {
    $where[] = 'is_read = 0';
} elseif ($filter === 'read') {
    $where[] = 'is_read = 1';
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page  = min($page, $pages);

$listStmt = $pdo->prepare(
    "SELECT * FROM messages WHERE $whereSql ORDER BY is_read ASC, id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage)
);
$listStmt->execute($params);
$messages = $listStmt->fetchAll();

$unreadTotal = (int) $pdo->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();

$admin_title  = 'Messages';
$admin_active = 'messages';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-toolbar">
    <form method="get" action="<?= e(admin_url('messages.php')) ?>" class="admin-toolbar__filters">
        <div class="admin-search">
            <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email or subject…" aria-label="Search messages">
            <button type="submit" class="btn btn--light btn--sm">Search</button>
        </div>
        <select class="input input--sm" name="filter" data-auto-submit aria-label="Filter messages">
            <option value="">All messages</option>
            <option value="unread" <?= $filter === 'unread' ? 'selected' : '' ?>>Unread</option>
            <option value="read" <?= $filter === 'read' ? 'selected' : '' ?>>Read</option>
        </select>
        <?php if ($q !== '' || $filter !== ''): ?>
            <a class="btn btn--light btn--sm" href="<?= e(admin_url('messages.php')) ?>">Reset</a>
        <?php endif; ?>
    </form>
</div>

<p class="text-muted"><?= $total ?> <?= $total === 1 ? 'message' : 'messages' ?><?php if ($unreadTotal > 0): ?> · <strong><?= $unreadTotal ?></strong> unread<?php endif; ?></p>

<?php if (!$messages): ?>
    <div class="admin-card"><div class="empty"><div class="empty__icon"><?= icon('mail') ?></div><p>No messages found.</p></div></div>
<?php else: ?>
    <div class="admin-card">
        <div class="message-list">
            <?php foreach ($messages as $message): ?>
                <details class="message-item <?= (int) $message['is_read'] === 0 ? 'is-unread' : '' ?>">
                    <summary class="message-item__head">
                        <span class="message-avatar"><?= e(initials((string) $message['name'])) ?></span>
                        <span class="message-item__meta">
                            <span class="message-item__name">
                                <?= e($message['name']) ?>
                                <?php if ((int) $message['is_read'] === 0): ?><span class="dot-new" title="Unread"></span><?php endif; ?>
                            </span>
                            <span class="cell-sub"><?= e($message['email']) ?></span>
                        </span>
                        <span class="message-item__subject"><?= e($message['subject'] !== '' ? $message['subject'] : '(no subject)') ?></span>
                        <span class="message-item__date"><?= e(format_date((string) $message['created_at'], 'j M Y')) ?></span>
                    </summary>

                    <div class="message-item__body">
                        <p class="message-item__text"><?= nl2br(e($message['message'])) ?></p>
                        <div class="message-item__foot">
                            <span class="cell-sub">Received <?= e(format_date((string) $message['created_at'], 'j M Y · H:i')) ?></span>
                            <div class="admin-table__actions">
                                <a class="btn btn--light btn--sm" href="mailto:<?= e($message['email']) ?>"><?= icon('mail') ?> Reply</a>
                                <form method="post" action="<?= e(admin_url('messages.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                                    <input type="hidden" name="action" value="<?= (int) $message['is_read'] === 0 ? 'read' : 'unread' ?>">
                                    <button type="submit" class="btn btn--light btn--sm"><?= (int) $message['is_read'] === 0 ? 'Mark read' : 'Mark unread' ?></button>
                                </form>
                                <form method="post" action="<?= e(admin_url('messages.php')) ?>" data-confirm="Delete this message?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn--danger btn--sm"><?= icon('trash') ?></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="is-current"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= e(admin_url('messages.php?' . http_build_query(['q' => $q, 'filter' => $filter, 'page' => $i]))) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
