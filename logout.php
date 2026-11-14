<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

unset($_SESSION['customer_id']);
session_regenerate_id(true);

flash_set('info', 'You have been signed out.');
redirect('index.php');
