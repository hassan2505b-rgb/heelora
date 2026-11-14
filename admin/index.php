<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$admin = require_admin();

$pdo = db();

$totalProducts  = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$activeProducts = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
$lowStockCount  = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE stock <= ' . (int) LOW_STOCK_LIMIT)->fetchColumn();
$totalCategories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();

$totalOrders   = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$pendingOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$revenue       = (float) $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();

$totalCustomers  = (int) $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
$unreadMessages  = (int) $pdo->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();
$totalSubscribers = (int) $pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn();

$recentOrders = $pdo->query(
    'SELECT o.*, (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.id) AS item_count
     FROM orders o ORDER BY o.id DESC LIMIT 6'
)->fetchAll();

$lowStock = $pdo->query(
    'SELECT * FROM products WHERE stock <= ' . (int) LOW_STOCK_LIMIT . ' ORDER BY stock ASC LIMIT 5'
)->fetchAll();

$recentMessages = $pdo->query('SELECT * FROM messages ORDER BY id DESC LIMIT 4')->fetchAll();

$admin_title  = 'Dashboard';
$admin_active = 'dashboard';

require __DIR__ . '/includes/header.php';
?>

<div class="stat-grid">
    <div class="stat-card stat-card--wine">
        <div class="stat-card__label">Revenue</div>
        <div class="stat-card__value"><?= e(money($revenue)) ?></div>
        <div class="stat-card__hint">Across <?= $totalOrders ?> <?= $totalOrders === 1 ? 'order' : 'orders' ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Orders</div>
        <div class="stat-card__value"><?= $totalOrders ?></div>
        <div class="stat-card__hint"><?= $pendingOrders ?> awaiting confirmation</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Products</div>
        <div class="stat-card__value"><?= $totalProducts ?></div>
        <div class="stat-card__hint"><?= $activeProducts ?> live · <?= $lowStockCount ?> low on stock</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Customers</div>
        <div class="stat-card__value"><?= $totalCustomers ?></div>
        <div class="stat-card__hint"><?= $totalSubscribers ?> newsletter subscribers</div>
    </div>
</div>

<div class="cols mt-3">
    <div class="admin-card">
        <div class="admin-card__head">
            <h2 class="admin-card__title">Recent orders</h2>
            <a class="btn btn--light btn--sm" href="<?= e(admin_url('orders.php')) ?>">View all</a>
        </div>

        <?php if (!$recentOrders): ?>
            <div class="empty"><p>No orders yet.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="ta-right">Placed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><a class="cell-strong" href="<?= e(admin_url('order-view.php?id=' . (int) $order['id'])) ?>"><?= e($order['order_number']) ?></a></td>
                                <td>
                                    <span class="cell-strong"><?= e($order['name']) ?></span>
                                    <span class="cell-sub" style="display:block"><?= e($order['city']) ?></span>
                                </td>
                                <td><?= (int) $order['item_count'] ?></td>
                                <td><?= e(money($order['total'])) ?></td>
                                <td><span class="status <?= e(status_class((string) $order['status'])) ?>"><?= e(ucfirst((string) $order['status'])) ?></span></td>
                                <td class="ta-right cell-sub"><?= e(format_date((string) $order['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="admin-card">
            <div class="admin-card__head">
                <h2 class="admin-card__title">Low stock</h2>
                <a class="btn btn--light btn--sm" href="<?= e(admin_url('products.php')) ?>">Manage</a>
            </div>
            <?php if (!$lowStock): ?>
                <p class="text-muted mb-0">Every style is comfortably in stock.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($lowStock as $product): ?>
                        <li style="display:flex;align-items:center;gap:.8rem;padding:.55rem 0;border-bottom:1px solid var(--line)">
                            <img class="thumb" style="width:38px;height:48px" src="<?= e(product_image($product['image'])) ?>" alt="<?= e($product['name']) ?>">
                            <span style="flex:1;min-width:0">
                                <a class="cell-strong" href="<?= e(admin_url('product-edit.php?id=' . (int) $product['id'])) ?>"><?= e($product['name']) ?></a>
                                <span class="cell-sub" style="display:block"><?= (int) $product['stock'] ?> left</span>
                            </span>
                            <span class="badge badge--low"><?= (int) $product['stock'] > 0 ? 'Low' : 'Out' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <div class="admin-card__head">
                <h2 class="admin-card__title">Latest messages</h2>
                <a class="btn btn--light btn--sm" href="<?= e(admin_url('messages.php')) ?>">Inbox</a>
            </div>
            <?php if (!$recentMessages): ?>
                <p class="text-muted mb-0">No messages yet.</p>
            <?php else: ?>
                <?php foreach ($recentMessages as $message): ?>
                    <div style="padding:.6rem 0;border-bottom:1px solid var(--line)">
                        <div style="display:flex;justify-content:space-between;gap:.6rem">
                            <span class="cell-strong"><?= e($message['name']) ?></span>
                            <?php if (!(int) $message['is_read']): ?><span class="badge badge--sale">New</span><?php endif; ?>
                        </div>
                        <span class="cell-sub"><?= e(excerpt((string) $message['message'], 70)) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="admin-card mb-0">
            <div class="admin-card__head"><h2 class="admin-card__title">Catalogue</h2></div>
            <p class="text-muted mb-0"><?= $totalCategories ?> categories · <?= $totalProducts ?> products · <?= $lowStockCount ?> low stock</p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
