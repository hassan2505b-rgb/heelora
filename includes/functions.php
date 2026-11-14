<?php
declare(strict_types=1);

/* ------------------------------------------------------------------ *
 *  Output helpers
 * ------------------------------------------------------------------ */

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

function money(float|int|string|null $amount): string
{
    return CURRENCY . number_format((float) $amount, 0);
}

function discount_percent(float $price, ?float $sale): ?int
{
    if ($sale === null || $sale <= 0 || $sale >= $price) {
        return null;
    }
    return (int) round((($price - $sale) / $price) * 100);
}

function product_image(?string $image): string
{
    $image = trim((string) $image);
    if ($image === '') {
        return asset('img/products/aurelia-pump.jpg');
    }
    if (preg_match('#^https?://#', $image)) {
        return $image;
    }
    if (str_starts_with($image, '/uploads/')) {
        return url(ltrim($image, '/'));
    }
    if (str_starts_with($image, 'uploads/')) {
        return url($image);
    }
    if (str_starts_with($image, 'assets/')) {
        return url($image);
    }
    return asset('img/products/' . $image);
}

function category_image(?string $image): string
{
    $image = trim((string) $image);
    if ($image === '') {
        return asset('img/categories/heels.jpg');
    }
    if (preg_match('#^https?://#', $image) || str_starts_with($image, '/')) {
        return product_image($image);
    }
    return asset('img/' . ltrim($image, '/'));
}

function excerpt(string $text, int $length = 120): string
{
    $text = trim(strip_tags($text));
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $length)) . '…';
}

function format_date(?string $date, string $format = 'j M Y'): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '—';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $out .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $out !== '' ? $out : 'H';
}

/* ------------------------------------------------------------------ *
 *  Flash messages & session
 * ------------------------------------------------------------------ */

function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }
    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $message;
}

function flash_render(): string
{
    $types = ['success' => 'success', 'error' => 'error', 'info' => 'info'];
    $html = '';
    foreach ($types as $key => $class) {
        $message = flash_get($key);
        if ($message !== null) {
            $html .= '<div class="alert alert--' . $class . '"><span>' . e($message) . '</span>'
                . '<button type="button" class="alert__close" aria-label="Dismiss">&times;</button></div>';
        }
    }
    return $html;
}

/* ------------------------------------------------------------------ *
 *  CSRF
 * ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && $token !== '' && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        redirect('index.php');
    }
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
    }
}

/* ------------------------------------------------------------------ *
 *  Catalogue
 * ------------------------------------------------------------------ */

function get_categories(): array
{
    return db()->query('SELECT * FROM categories ORDER BY sort_order, name')->fetchAll();
}

function get_category_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE slug = ?');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_product_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT p.*, c.name AS category_name, c.slug AS category_slug
                           FROM products p LEFT JOIN categories c ON c.id = p.category_id
                           WHERE p.slug = ? AND p.is_active = 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function current_price(array $product): float
{
    return $product['sale_price'] !== null && (float) $product['sale_price'] > 0
        ? (float) $product['sale_price']
        : (float) $product['price'];
}

function product_sizes(array $product): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string) $product['sizes']))));
}

function product_colors(array $product): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string) $product['colors']))));
}

/**
 * Turn a title into a URL-safe slug.
 */
function slugify(string $text): string
{
    $text = trim($text);
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        if ($converted !== false) {
            $text = $converted;
        }
    }
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');

    return $text !== '' ? $text : 'item';
}

/**
 * Guarantee a unique slug within a table.
 */
function unique_slug(string $base, string $table, int $ignoreId = 0): string
{
    $slug      = slugify($base);
    $candidate = $slug;
    $suffix    = 2;
    $pdo       = db();

    while (true) {
        if ($ignoreId > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE slug = ? AND id != ?");
            $stmt->execute([$candidate, $ignoreId]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE slug = ?");
            $stmt->execute([$candidate]);
        }
        if ((int) $stmt->fetchColumn() === 0) {
            return $candidate;
        }
        $candidate = $slug . '-' . $suffix++;
    }
}

/**
 * Map a colour name to a hex value for swatches.
 */
function color_hex(string $name): string
{
    $map = [
        'nude' => '#D8B79A', 'black' => '#1C1A19', 'burgundy' => '#6E2A3C',
        'silver' => '#C7C9CC', 'ivory' => '#F3EDE2', 'blush' => '#E9C3C6',
        'tan' => '#B07A4B', 'cream' => '#EFE6D5', 'violet' => '#7A4E9B',
        'gold' => '#C9A227', 'white' => '#FFFFFF', 'red' => '#B3243A',
        'pink' => '#E8A0B4', 'grey' => '#9AA0A6', 'gray' => '#9AA0A6',
        'brown' => '#7A5230', 'blue' => '#3B5C8A', 'green' => '#4F7A55',
    ];

    return $map[strtolower(trim($name))] ?? '#C6B7A8';
}

/**
 * Badges for a product card / row — sold out, discount %, manual badge, low stock.
 */
function product_badges(array $product): array
{
    $stock = (int) ($product['stock'] ?? 0);
    $badges = [];

    if ($stock <= 0) {
        return [['label' => 'Sold out', 'class' => 'badge--soldout']];
    }

    $price = (float) ($product['price'] ?? 0);
    $sale  = (isset($product['sale_price']) && (float) $product['sale_price'] > 0)
        ? (float) $product['sale_price']
        : null;

    $percent = discount_percent($price, $sale);
    if ($percent !== null) {
        $badges[] = ['label' => '-' . $percent . '%', 'class' => 'badge--sale'];
    }

    if (trim((string) ($product['badge'] ?? '')) !== '') {
        $badges[] = ['label' => (string) $product['badge'], 'class' => 'badge--tag'];
    }

    if ($stock <= LOW_STOCK_LIMIT) {
        $badges[] = ['label' => 'Low stock', 'class' => 'badge--low'];
    }

    return array_slice($badges, 0, 2);
}

/**
 * Fetch products with optional filters.
 * Supports: category, q, size, min_price, max_price, featured, limit, offset, sort, exclude.
 */
function get_products(array $opts = []): array
{
    $where  = ['p.is_active = 1'];
    $params = [];

    if (!empty($opts['category'])) {
        $where[] = 'c.slug = ?';
        $params[] = $opts['category'];
    }
    if (!empty($opts['q'])) {
        $where[] = '(p.name LIKE ? OR p.tagline LIKE ? OR p.description LIKE ? OR c.name LIKE ?)';
        $term = '%' . $opts['q'] . '%';
        array_push($params, $term, $term, $term, $term);
    }
    if (!empty($opts['size'])) {
        $where[] = '("," || p.sizes || ",") LIKE ?';
        $params[] = '%,' . $opts['size'] . ',%';
    }
    if (isset($opts['min_price']) && $opts['min_price'] !== '' && $opts['min_price'] !== null) {
        $where[] = 'COALESCE(NULLIF(p.sale_price, 0), p.price) >= ?';
        $params[] = (float) $opts['min_price'];
    }
    if (isset($opts['max_price']) && $opts['max_price'] !== '' && $opts['max_price'] !== null) {
        $where[] = 'COALESCE(NULLIF(p.sale_price, 0), p.price) <= ?';
        $params[] = (float) $opts['max_price'];
    }
    if (!empty($opts['featured'])) {
        $where[] = 'p.is_featured = 1';
    }
    if (!empty($opts['exclude'])) {
        $where[] = 'p.id != ?';
        $params[] = (int) $opts['exclude'];
    }

    $sort = match ($opts['sort'] ?? 'newest') {
        'price-low'  => 'COALESCE(NULLIF(p.sale_price, 0), p.price) ASC',
        'price-high' => 'COALESCE(NULLIF(p.sale_price, 0), p.price) DESC',
        'name'       => 'p.name ASC',
        'popular'    => 'p.is_featured DESC, p.stock ASC',
        default      => 'p.created_at DESC, p.id DESC',
    };

    $limit  = isset($opts['limit']) ? max(1, (int) $opts['limit']) : 24;
    $offset = isset($opts['offset']) ? max(0, (int) $opts['offset']) : 0;

    $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p LEFT JOIN categories c ON c.id = p.category_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ' . $sort . '
            LIMIT ' . $limit . ' OFFSET ' . $offset;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function count_products(array $opts = []): int
{
    $opts['limit']  = 1;
    $opts['offset'] = 0;

    $where  = ['p.is_active = 1'];
    $params = [];
    if (!empty($opts['category'])) {
        $where[] = 'c.slug = ?';
        $params[] = $opts['category'];
    }
    if (!empty($opts['q'])) {
        $where[] = '(p.name LIKE ? OR p.tagline LIKE ? OR p.description LIKE ? OR c.name LIKE ?)';
        $term = '%' . $opts['q'] . '%';
        array_push($params, $term, $term, $term, $term);
    }
    if (!empty($opts['size'])) {
        $where[] = '("," || p.sizes || ",") LIKE ?';
        $params[] = '%,' . $opts['size'] . ',%';
    }
    if (isset($opts['min_price']) && $opts['min_price'] !== '' && $opts['min_price'] !== null) {
        $where[] = 'COALESCE(NULLIF(p.sale_price, 0), p.price) >= ?';
        $params[] = (float) $opts['min_price'];
    }
    if (isset($opts['max_price']) && $opts['max_price'] !== '' && $opts['max_price'] !== null) {
        $where[] = 'COALESCE(NULLIF(p.sale_price, 0), p.price) <= ?';
        $params[] = (float) $opts['max_price'];
    }

    $sql = 'SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id
            WHERE ' . implode(' AND ', $where);
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function related_products(array $product, int $limit = 4): array
{
    return get_products([
        'category' => $product['category_slug'] ?? '',
        'exclude'  => $product['id'],
        'limit'    => $limit,
        'sort'     => 'popular',
    ]);
}

function available_sizes(): array
{
    return ['36', '37', '38', '39', '40', '41'];
}

/* ------------------------------------------------------------------ *
 *  Cart (session based)
 * ------------------------------------------------------------------ */

function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_add(int $productId, int $qty, string $size): void
{
    $qty  = max(1, $qty);
    $key  = $productId . ':' . $size;
    $cart = cart();
    $cart[$key] = [
        'id'   => $productId,
        'qty'  => min(10, ($cart[$key]['qty'] ?? 0) + $qty),
        'size' => $size,
    ];
    $_SESSION['cart'] = $cart;
}

function cart_update(string $key, int $qty): void
{
    $cart = cart();
    if (!isset($cart[$key])) {
        return;
    }
    if ($qty <= 0) {
        unset($cart[$key]);
    } else {
        $cart[$key]['qty'] = min(10, $qty);
    }
    $_SESSION['cart'] = $cart;
}

function cart_remove(string $key): void
{
    $cart = cart();
    unset($cart[$key]);
    $_SESSION['cart'] = $cart;
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

function cart_count(): int
{
    return array_sum(array_map(static fn ($line) => (int) $line['qty'], cart()));
}

/**
 * Join cart lines with product data. Drops lines whose product disappeared.
 */
function cart_lines(): array
{
    $cart = cart();
    if (!$cart) {
        return [];
    }

    $ids = array_unique(array_map(static fn ($line) => (int) $line['id'], $cart));
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT * FROM products WHERE id IN ($in) AND is_active = 1");
    $stmt->execute(array_values($ids));
    $products = [];
    foreach ($stmt->fetchAll() as $row) {
        $products[(int) $row['id']] = $row;
    }

    $lines = [];
    foreach ($cart as $key => $line) {
        $product = $products[(int) $line['id']] ?? null;
        if (!$product) {
            continue;
        }
        $qty = max(1, min((int) $line['qty'], max(1, (int) $product['stock'])));
        $lines[] = [
            'key'     => $key,
            'product' => $product,
            'qty'     => $qty,
            'size'    => $line['size'],
            'price'   => current_price($product),
            'total'   => current_price($product) * $qty,
        ];
    }
    return $lines;
}

function cart_totals(): array
{
    $subtotal = 0.0;
    foreach (cart_lines() as $line) {
        $subtotal += $line['total'];
    }
    $shipping = ($subtotal > 0 && $subtotal < FREE_SHIPPING_MIN) ? (float) SHIPPING_FEE : 0.0;
    return [
        'subtotal'  => $subtotal,
        'shipping'  => $shipping,
        'total'     => $subtotal + $shipping,
        'free_left' => max(0, FREE_SHIPPING_MIN - $subtotal),
    ];
}

/* ------------------------------------------------------------------ *
 *  Customer auth
 * ------------------------------------------------------------------ */

function current_customer(): ?array
{
    if (empty($_SESSION['customer_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([(int) $_SESSION['customer_id']]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function require_customer(): array
{
    $customer = current_customer();
    if (!$customer) {
        flash_set('error', 'Please sign in to continue.');
        redirect('login.php?next=account.php');
    }
    return $customer;
}

/* ------------------------------------------------------------------ *
 *  Inline icons
 * ------------------------------------------------------------------ */

function icon(string $name, string $class = ''): string
{
    $paths = [
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/>',
        'bag'       => '<path d="M6 8h12l1 12H5L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
        'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close'     => '<path d="m6 6 12 12M18 6 6 18"/>',
        'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left'=> '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'minus'     => '<path d="M5 12h14"/>',
        'trash'     => '<path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13M10 11v6M14 11v6"/>',
        'check'     => '<path d="m5 13 4 4L19 7"/>',
        'chevron'   => '<path d="m6 9 6 6 6-6"/>',
        'star'      => '<path d="m12 3 2.7 5.7 6.3.8-4.6 4.3 1.2 6.2-5.6-3-5.6 3 1.2-6.2L3 9.5l6.3-.8L12 3Z"/>',
        'truck'     => '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17.5" cy="18" r="1.6"/>',
        'refresh'   => '<path d="M20 11a8 8 0 1 0-2.6 6.1"/><path d="M20 5v6h-6"/>',
        'shield'    => '<path d="M12 3 5 6v6c0 4 3 7 7 9 4-2 7-5 7-9V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'sparkle'   => '<path d="M12 3v6M12 15v6M3 12h6M15 12h6M6.5 6.5 9 9M15 15l2.5 2.5M17.5 6.5 15 9M9 15l-2.5 2.5"/>',
        'pin'       => '<path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
        'phone'     => '<path d="M6 3h4l2 5-2.5 1.5a12 12 0 0 0 5 5L16 12l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 4 5a2 2 0 0 1 2-2Z"/>',
        'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"/>',
        'facebook'  => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8Z"/>',
        'whatsapp'  => '<path d="M20 12a8 8 0 0 1-11.9 7L4 20l1.1-4A8 8 0 1 1 20 12Z"/><path d="M9 9c0 3 3 6 6 6l1.5-2-2-1-1 1a5 5 0 0 1-2.5-2.5l1-1-1-2L9 9Z"/>',
        'grid'      => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'box'       => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
        'layers'    => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
        'receipt'   => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>',
        'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M16 5.5A3.5 3.5 0 0 1 16 12M18 20c0-2.5-1-4-3-5"/>',
        'logout'    => '<path d="M14 4h5v16h-5M10 8l-4 4 4 4M6 12h10"/>',
        'external'  => '<path d="M14 4h6v6M20 4l-8 8"/><path d="M18 14v6H4V6h6"/>',
        'trend'     => '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
        'alert'     => '<path d="M12 4 2.5 20h19L12 4Z"/><path d="M12 10v4M12 17h.01"/>',
        'edit'      => '<path d="M4 20h4l11-11-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>',
        'eye'       => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'heart'     => '<path d="M12 20s-8-4.7-8-10a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 5.3-8 10-8 10Z"/>',
    ];

    $path = $paths[$name] ?? '';
    if ($path === '') {
        return '';
    }

    return '<svg class="icon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $path . '</svg>';
}

/* ------------------------------------------------------------------ *
 *  Brand mark
 * ------------------------------------------------------------------ */

function heel_glyph(string $class = ''): string
{
    return '<svg class="heel-glyph ' . e($class) . '" viewBox="0 0 200 170" fill="none" aria-hidden="true">'
        . '<path d="M187 106c-19-18-42-29-64-33-12-2-21 0-26 7-9 11-11 26-7 41l-12 42h8l13-31c15 6 42 6 63 0 15-5 25-13 25-26Z" fill="currentColor"/>'
        . '<path d="M122 82c8 6 18 13 26 22 4 5 2 10-3 10h-14c-9 0-15-5-16-13-1-7 1-15 7-19Z" fill="#E9C3C6"/>'
        . '<path d="M97 78c-5 11-6 25-3 38" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>'
        . '</svg>';
}

function brand_logo(string $variant = 'header', string $href = 'index.php'): string
{
    if ($variant === 'footer') {
        return '<a class="brand brand--stacked" href="' . e(url($href)) . '" aria-label="Heelora — Ladies Footwear">'
            . '<span class="brand__glyph">' . heel_glyph() . '</span>'
            . '<span class="brand__name">HEELORA</span>'
            . '<span class="brand__rule"><span></span><em>Ladies Footwear</em><span></span></span>'
            . '</a>';
    }

    return '<a class="brand" href="' . e(url($href)) . '" aria-label="Heelora — Ladies Footwear">'
        . '<span class="brand__glyph">' . heel_glyph() . '</span>'
        . '<span class="brand__text"><span class="brand__name">HEELORA</span>'
        . '<span class="brand__sub">Ladies Footwear</span></span>'
        . '</a>';
}

/* ------------------------------------------------------------------ *
 *  Misc
 * ------------------------------------------------------------------ */

function order_statuses(): array
{
    return ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
}

function status_class(string $status): string
{
    return match ($status) {
        'delivered' => 'is-delivered',
        'shipped'   => 'is-shipped',
        'confirmed' => 'is-confirmed',
        'cancelled' => 'is-cancelled',
        default     => 'is-pending',
    };
}

function wishlist_count(): int
{
    return count($_SESSION['wishlist'] ?? []);
}

function newsletter_store(string $email): bool
{
    $stmt = db()->prepare('INSERT OR IGNORE INTO subscribers (email) VALUES (?)');
    return $stmt->execute([$email]);
}
