<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$admin = require_admin();

$pdo = db();

$errors = [];
$tab    = (string) ($_GET['tab'] ?? 'profile');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    $form = (string) ($_POST['form'] ?? '');

    if ($form === 'profile') {
        $name  = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($name === '') {
            $errors['name'] = 'Please enter your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM admins WHERE email = ? AND id != ?');
            $stmt->execute([$email, (int) $admin['id']]);
            if ((int) $stmt->fetchColumn() > 0) {
                $errors['email'] = 'That email is already in use.';
            }
        }

        if (!$errors) {
            $pdo->prepare('UPDATE admins SET name = ?, email = ? WHERE id = ?')
                ->execute([$name, $email, (int) $admin['id']]);
            flash_set('success', 'Profile updated.');
            redirect('admin/settings.php');
        }
        $tab = 'profile';
    } elseif ($form === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (!password_verify($current, (string) $admin['password_hash'])) {
            $errors['current_password'] = 'Your current password is incorrect.';
        }
        if (strlen($new) < 6) {
            $errors['new_password'] = 'Use at least 6 characters.';
        }
        if ($new !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (!$errors) {
            $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), (int) $admin['id']]);
            flash_set('success', 'Password changed.');
            redirect('admin/settings.php?tab=password');
        }
        $tab = 'password';
    }
}

$admin_title  = 'Settings';
$admin_active = 'settings';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-tabs">
    <a href="<?= e(admin_url('settings.php?tab=profile')) ?>" class="<?= $tab !== 'password' ? 'is-active' : '' ?>">Profile</a>
    <a href="<?= e(admin_url('settings.php?tab=password')) ?>" class="<?= $tab === 'password' ? 'is-active' : '' ?>">Password</a>
</div>

<div class="admin-grid-2">
    <?php if ($tab !== 'password'): ?>
        <form method="post" action="<?= e(admin_url('settings.php')) ?>" class="admin-card">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="profile">

            <h2 class="admin-card__title"><?= icon('user') ?> Profile</h2>

            <div class="field">
                <label for="s-name">Name</label>
                <input class="input" type="text" id="s-name" name="name" value="<?= e((string) ($_POST['name'] ?? $admin['name'])) ?>" required>
                <?php if (isset($errors['name'])): ?><span class="field__error"><?= e($errors['name']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label for="s-email">Email</label>
                <input class="input" type="email" id="s-email" name="email" value="<?= e((string) ($_POST['email'] ?? $admin['email'])) ?>" required>
                <?php if (isset($errors['email'])): ?><span class="field__error"><?= e($errors['email']) ?></span><?php endif; ?>
            </div>

            <button type="submit" class="btn btn--dark"><?= icon('check') ?> Save profile</button>
        </form>
    <?php else: ?>
        <form method="post" action="<?= e(admin_url('settings.php')) ?>" class="admin-card">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="password">

            <h2 class="admin-card__title"><?= icon('shield') ?> Change password</h2>

            <div class="field">
                <label for="s-current">Current password</label>
                <input class="input" type="password" id="s-current" name="current_password" autocomplete="current-password" required>
                <?php if (isset($errors['current_password'])): ?><span class="field__error"><?= e($errors['current_password']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label for="s-new">New password</label>
                <input class="input" type="password" id="s-new" name="new_password" autocomplete="new-password" required>
                <?php if (isset($errors['new_password'])): ?><span class="field__error"><?= e($errors['new_password']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label for="s-confirm">Confirm new password</label>
                <input class="input" type="password" id="s-confirm" name="confirm_password" autocomplete="new-password" required>
                <?php if (isset($errors['confirm_password'])): ?><span class="field__error"><?= e($errors['confirm_password']) ?></span><?php endif; ?>
            </div>

            <button type="submit" class="btn btn--dark"><?= icon('shield') ?> Update password</button>
        </form>
    <?php endif; ?>

    <aside class="admin-card">
        <h2 class="admin-card__title"><?= icon('grid') ?> Store</h2>
        <ul class="admin-list">
            <li><span>Store name</span><strong><?= e(SITE_NAME) ?></strong></li>
            <li><span>Currency</span><strong><?= e(trim(CURRENCY)) ?></strong></li>
            <li><span>Free shipping over</span><strong><?= e(money(FREE_SHIPPING_MIN)) ?></strong></li>
            <li><span>Shipping fee</span><strong><?= e(money(SHIPPING_FEE)) ?></strong></li>
            <li><span>Low-stock threshold</span><strong><?= (int) LOW_STOCK_LIMIT ?> units</strong></li>
        </ul>
        <p class="cell-sub mt-2">Store settings are defined in <code>includes/config.php</code>.</p>
    </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
