<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (current_customer()) {
    redirect('account.php');
}

$form = [
    'name'     => '',
    'email'    => '',
    'phone'    => '',
    'city'     => '',
    'address'  => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    foreach (array_keys($form) as $field) {
        $form[$field] = trim((string) ($_POST[$field] ?? ''));
    }
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['password_confirm'] ?? '');

    if ($form['name'] === '') {
        $errors['name'] = 'Please tell us your name.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (mb_strlen($password) < 6) {
        $errors['password'] = 'Use at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors['password_confirm'] = 'The passwords do not match.';
    }

    if (!isset($errors['email'])) {
        $check = db()->prepare('SELECT COUNT(*) FROM customers WHERE email = ?');
        $check->execute([$form['email']]);
        if ((int) $check->fetchColumn() > 0) {
            $errors['email'] = 'That email is already registered.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO customers (name, email, phone, password_hash, city, address) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $form['name'],
            $form['email'],
            $form['phone'],
            password_hash($password, PASSWORD_DEFAULT),
            $form['city'],
            $form['address'],
        ]);

        session_regenerate_id(true);
        $_SESSION['customer_id'] = (int) db()->lastInsertId();
        flash_set('success', 'Welcome to Heelora, ' . explode(' ', $form['name'])[0] . '.');
        redirect('account.php');
    }

    flash_set('error', 'Please check the highlighted fields.');
}

$page_title = 'Create account — ' . SITE_NAME;
$body_class = 'page-auth';
$active     = '';

require __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
    <div class="auth-card">
        <h1 class="auth-card__title">Create your account</h1>
        <p class="auth-card__lead">Faster checkout, order tracking and a saved wishlist.</p>

        <form method="post" action="<?= e(url('register.php')) ?>">
            <?= csrf_field() ?>

            <div class="field">
                <label class="field__label" for="reg-name">Full name</label>
                <input class="input" type="text" id="reg-name" name="name" value="<?= e($form['name']) ?>" autocomplete="name" required>
                <?php if (isset($errors['name'])): ?><p class="field__error"><?= e($errors['name']) ?></p><?php endif; ?>
            </div>

            <div class="field-row">
                <div class="field">
                    <label class="field__label" for="reg-email">Email</label>
                    <input class="input" type="email" id="reg-email" name="email" value="<?= e($form['email']) ?>" autocomplete="email" required>
                    <?php if (isset($errors['email'])): ?><p class="field__error"><?= e($errors['email']) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label class="field__label" for="reg-phone">Phone</label>
                    <input class="input" type="tel" id="reg-phone" name="phone" value="<?= e($form['phone']) ?>" autocomplete="tel">
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label class="field__label" for="reg-city">City</label>
                    <input class="input" type="text" id="reg-city" name="city" value="<?= e($form['city']) ?>">
                </div>
                <div class="field">
                    <label class="field__label" for="reg-address">Address</label>
                    <input class="input" type="text" id="reg-address" name="address" value="<?= e($form['address']) ?>">
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label class="field__label" for="reg-password">Password</label>
                    <input class="input" type="password" id="reg-password" name="password" autocomplete="new-password" required>
                    <?php if (isset($errors['password'])): ?><p class="field__error"><?= e($errors['password']) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label class="field__label" for="reg-confirm">Confirm password</label>
                    <input class="input" type="password" id="reg-confirm" name="password_confirm" autocomplete="new-password" required>
                    <?php if (isset($errors['password_confirm'])): ?><p class="field__error"><?= e($errors['password_confirm']) ?></p><?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn--dark btn--full">Create account <?= icon('arrow') ?></button>
        </form>

        <p class="auth-alt">Already have an account? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
