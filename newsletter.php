<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

require_post();

$email = trim((string) ($_POST['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_set('error', 'Please enter a valid email address.');
} else {
    newsletter_store($email);
    flash_set('success', 'You are on the list — welcome to the Heelora Letter.');
}

$back = (string) ($_SERVER['HTTP_REFERER'] ?? '');
if (preg_match('#^https?://#', $back)) {
    header('Location: ' . $back);
    exit;
}
redirect('index.php');
