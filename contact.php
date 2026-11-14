<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$form = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();

    foreach (array_keys($form) as $field) {
        $form[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    if ($form['name'] === '') {
        $errors['name'] = 'Please tell us your name.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (mb_strlen($form['message']) < 10) {
        $errors['message'] = 'Please tell us a little more.';
    }

    if (!$errors) {
        db()->prepare('INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)')
            ->execute([$form['name'], $form['email'], $form['subject'], $form['message']]);

        flash_set('success', 'Thank you — our team will reply within one working day.');
        redirect('contact.php');
    }

    flash_set('error', 'Please check the highlighted fields.');
}

$faqs = [
    ['How do I choose the right size?', 'Our footwear runs true to size. If you are between sizes or have a wider foot, we suggest sizing up half a size. Still unsure? Send us your usual size and we will advise.'],
    ['What is your delivery time?', 'Orders are dispatched within 24 hours from Lahore. Delivery takes 2–4 working days nationwide and is free on orders above ' . money(FREE_SHIPPING_MIN) . '.'],
    ['Can I exchange or return?', 'Unworn pairs in their original packaging can be exchanged within 7 days of delivery. Simply message us with your order number and the size you need.'],
    ['Which payment methods do you accept?', 'We accept cash on delivery across Pakistan and bank transfer. After placing a bank-transfer order we will share our account details by email.'],
];

$page_title = 'Contact — ' . SITE_NAME;
$page_desc  = 'Questions about sizing, delivery or an order? The Heelora team in Lahore is here to help.';
$body_class = 'page-contact';
$active     = 'contact';

require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="container">
        <span class="eyebrow">We'd love to hear from you</span>
        <h1 class="page-head__title">Get in touch</h1>
        <p class="page-head__sub">Sizing advice, order help or just to say hello — we reply within one working day.</p>
    </div>
</section>

<div class="container">
    <div class="contact-layout">
        <div>
            <div class="info-item">
                <?= icon('pin') ?>
                <div>
                    <strong>Atelier</strong>
                    <?= e(SITE_ADDRESS) ?>
                </div>
            </div>
            <div class="info-item">
                <?= icon('phone') ?>
                <div>
                    <strong>Call or WhatsApp</strong>
                    <a href="https://wa.me/9242111433567" target="_blank" rel="noopener"><?= e(SITE_PHONE) ?></a>
                </div>
            </div>
            <div class="info-item">
                <?= icon('mail') ?>
                <div>
                    <strong>Email</strong>
                    <a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a>
                </div>
            </div>
            <div class="info-item">
                <?= icon('clock') ?>
                <div>
                    <strong>Opening hours</strong>
                    Monday to Saturday, 11am – 8pm
                </div>
            </div>
        </div>

        <div class="card mb-0">
            <div class="card__head"><h2 class="card__title">Send a message</h2></div>

            <form method="post" action="<?= e(url('contact.php')) ?>">
                <?= csrf_field() ?>

                <div class="field-row">
                    <div class="field">
                        <label class="field__label" for="ct-name">Name</label>
                        <input class="input" type="text" id="ct-name" name="name" value="<?= e($form['name']) ?>" required>
                        <?php if (isset($errors['name'])): ?><p class="field__error"><?= e($errors['name']) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="field__label" for="ct-email">Email</label>
                        <input class="input" type="email" id="ct-email" name="email" value="<?= e($form['email']) ?>" required>
                        <?php if (isset($errors['email'])): ?><p class="field__error"><?= e($errors['email']) ?></p><?php endif; ?>
                    </div>
                </div>

                <div class="field">
                    <label class="field__label" for="ct-subject">Subject</label>
                    <input class="input" type="text" id="ct-subject" name="subject" value="<?= e($form['subject']) ?>" placeholder="Order query, sizing, wholesale…">
                </div>

                <div class="field">
                    <label class="field__label" for="ct-message">Message</label>
                    <textarea class="textarea" id="ct-message" name="message" rows="5" required><?= e($form['message']) ?></textarea>
                    <?php if (isset($errors['message'])): ?><p class="field__error"><?= e($errors['message']) ?></p><?php endif; ?>
                </div>

                <button type="submit" class="btn btn--dark">Send message <?= icon('arrow') ?></button>
            </form>
        </div>
    </div>

    <section class="section" id="faq">
        <div class="section__head">
            <div>
                <span class="eyebrow">Good to know</span>
                <h2 class="display-3 section__title">Frequently asked</h2>
            </div>
        </div>

        <div class="accordion">
            <?php foreach ($faqs as $index => $faq): ?>
                <div class="accordion__item <?= $index === 0 ? 'is-open' : '' ?>">
                    <button type="button" class="accordion__trigger"><?= e($faq[0]) ?> <?= icon('chevron') ?></button>
                    <div class="accordion__panel"><p class="mb-0"><?= e($faq[1]) ?></p></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
