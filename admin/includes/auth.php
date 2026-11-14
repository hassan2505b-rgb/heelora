<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

/**
 * Currently signed-in administrator, or null.
 */
function admin_user(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([(int) $_SESSION['admin_id']]);
    $row = $stmt->fetch();

    return $row ?: null;
}

/**
 * Guard the panel — sends guests to the sign-in screen.
 */
function require_admin(): array
{
    $admin = admin_user();
    if (!$admin) {
        flash_set('error', 'Please sign in to open the admin panel.');
        redirect('admin/login.php');
    }

    return $admin;
}

/**
 * Absolute URL for a file inside /admin.
 */
function admin_url(string $path = ''): string
{
    return url('admin/' . ltrim($path, '/'));
}
