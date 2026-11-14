<?php
declare(strict_types=1);

/* ------------------------------------------------------------------
 |  Heelora — Ladies Footwear
 |  Global configuration
 * ------------------------------------------------------------------ */

define('SITE_NAME', 'Heelora');
define('SITE_TAGLINE', 'Ladies Footwear');
define('SITE_EMAIL', 'hello@heelora.pk');
define('SITE_PHONE', '+92 42 111 433 567');
define('SITE_ADDRESS', '12-C Gulberg III, Lahore, Pakistan');
define('SITE_INSTAGRAM', 'https://instagram.com');
define('SITE_FACEBOOK', 'https://facebook.com');

define('CURRENCY', 'Rs ');
define('FREE_SHIPPING_MIN', 5000);
define('SHIPPING_FEE', 250);
define('LOW_STOCK_LIMIT', 5);

define('APP_ROOT', dirname(__DIR__));
define('DB_FILE', APP_ROOT . '/data/heelora.sqlite');
define('UPLOAD_DIR', APP_ROOT . '/uploads');
define('UPLOAD_URL', '/uploads');

/* Default administrator (seeded once, change the password after first login) */
define('ADMIN_DEFAULT_EMAIL', 'admin@heelora.com');
define('ADMIN_DEFAULT_PASSWORD', 'admin123');

/* Auto-detect the folder the site is served from (works in htdocs/subfolder too) */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

$rootFs  = str_replace('\\', '/', APP_ROOT);
$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));

$basePath = '';
if ($docRoot !== '' && str_starts_with($rootFs, $docRoot)) {
    $basePath = substr($rootFs, strlen($docRoot));
}
$basePath = '/' . trim(str_replace('\\', '/', $basePath), '/');
if ($basePath === '/') {
    $basePath = '';
}

define('BASE_PATH', $basePath);
define('SITE_ORIGIN', $scheme . '://' . $host);
