<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('admin/index.php');
}

$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT * FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, (string) $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        flash_set('success', 'Welcome back, ' . explode(' ', trim((string) $admin['name']))[0] . '.');
        redirect('admin/index.php');
    }

    $error = 'Those credentials do not match our records.';
}

$flash = flash_render();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in · Heelora Admin</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
<div class="login-screen">
    <div class="login-card">
        <span class="login-card__brand">
            <?= heel_glyph() ?>
            <strong>HEELORA</strong>
            <em>Admin panel</em>
        </span>

        <?= $flash ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert--error" style="text-align:left"><span><?= e($error) ?></span></div>
        <?php endif; ?>

        <form method="post" action="<?= e(admin_url('login.php')) ?>">
            <?= csrf_field() ?>

            <div class="field">
                <label class="field__label" for="admin-email">Email</label>
                <input class="input" type="email" id="admin-email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus>
            </div>

            <div class="field">
                <label class="field__label" for="admin-password">Password</label>
                <input class="input" type="password" id="admin-password" name="password" autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn--wine btn--full">Sign in</button>
        </form>

        <p class="text-muted" style="font-size:.78rem;margin:1.2rem 0 0">
            Default: <?= e(ADMIN_DEFAULT_EMAIL) ?> · <?= e(ADMIN_DEFAULT_PASSWORD) ?>
        </p>
        <p class="text-muted" style="font-size:.78rem;margin:.3rem 0 0">
            <a href="<?= e(url('index.php')) ?>" style="color:var(--wine)">← Back to the store</a>
        </p>
    </div>
</div>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
