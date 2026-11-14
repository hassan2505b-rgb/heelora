<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (current_customer()) {
    redirect('account.php');
}

$next = trim((string) ($_GET['next'] ?? $_POST['next'] ?? 'account.php'));
if (preg_match('#^(https?:)?//#', $next) || str_contains($next, '..')) {
    $next = 'account.php';
}

$email  = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors['password'] = 'Please enter your password.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT * FROM customers WHERE email = ?');
        $stmt->execute([$email]);
        $customer = $stmt->fetch();

        if ($customer && password_verify($password, (string) $customer['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['customer_id'] = (int) $customer['id'];
            flash_set('success', 'Welcome back, ' . explode(' ', trim((string) $customer['name']))[0] . '.');
            redirect($next);
        }

        $errors['form'] = 'Those details do not match our records.';
    }
}

$page_title = 'Sign in — ' . SITE_NAME;
$body_class = 'page-auth';
$active     = '';

require __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
    <div class="auth-card">
        <h1 class="auth-card__title">Welcome back</h1>
        <p class="auth-card__lead">Sign in to track orders and save your favourite styles.</p>

        <?php if (isset($errors['form'])): ?>
            <div class="alert alert--error" style="margin:0 0 1.2rem"><span><?= e($errors['form']) ?></span></div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('login.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">

            <div class="field">
                <label class="field__label" for="login-email">Email</label>
                <input class="input" type="email" id="login-email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
                <?php if (isset($errors['email'])): ?><p class="field__error"><?= e($errors['email']) ?></p><?php endif; ?>
            </div>

            <div class="field">
                <label class="field__label" for="login-password">Password</label>
                <input class="input" type="password" id="login-password" name="password" autocomplete="current-password" required>
                <?php if (isset($errors['password'])): ?><p class="field__error"><?= e($errors['password']) ?></p><?php endif; ?>
            </div>

            <button type="submit" class="btn btn--dark btn--full">Sign in <?= icon('arrow') ?></button>
        </form>

        <p class="auth-alt">New to Heelora? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
        <p class="auth-alt" style="margin-top:.6rem;font-size:.78rem">Demo: ayesha@example.com · demo1234</p>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
