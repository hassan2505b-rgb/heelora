<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

unset($_SESSION['admin_id']);
session_regenerate_id(true);

flash_set('info', 'You have signed out of the admin panel.');
redirect('admin/login.php');
