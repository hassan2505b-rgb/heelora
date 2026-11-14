<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/schema.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (BASE_PATH ?: '') . '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$pdo = db();
ensure_schema($pdo);

/* Flash data should survive one render only */
if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
    $_SESSION['flash'] = [];
}
