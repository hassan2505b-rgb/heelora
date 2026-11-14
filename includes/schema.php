<?php
declare(strict_types=1);

/**
 * Creates the database structure on first run and seeds demo content once.
 * Safe to call on every request (uses IF NOT EXISTS + a version flag).
 */
function ensure_schema(PDO $pdo): void
{
    $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
    if ($version >= 1) {
        return;
    }

    $pdo->beginTransaction();

    try {
        $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS categories (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        name        TEXT NOT NULL,
        slug        TEXT NOT NULL UNIQUE,
        description TEXT DEFAULT '',
        image       TEXT DEFAULT '',
        sort_order  INTEGER DEFAULT 0,
        created_at  TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS products (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id  INTEGER,
        name         TEXT NOT NULL,
        slug         TEXT NOT NULL UNIQUE,
        tagline      TEXT DEFAULT '',
        description  TEXT DEFAULT '',
        price        REAL NOT NULL DEFAULT 0,
        sale_price   REAL,
        image        TEXT DEFAULT '',
        sizes        TEXT DEFAULT '36,37,38,39,40,41',
        colors       TEXT DEFAULT '',
        stock        INTEGER NOT NULL DEFAULT 0,
        badge        TEXT DEFAULT '',
        is_featured  INTEGER NOT NULL DEFAULT 0,
        is_active    INTEGER NOT NULL DEFAULT 1,
        created_at   TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS customers (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        name          TEXT NOT NULL,
        email         TEXT NOT NULL UNIQUE,
        phone         TEXT DEFAULT '',
        password_hash TEXT NOT NULL,
        city          TEXT DEFAULT '',
        address       TEXT DEFAULT '',
        created_at    TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS admins (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        name          TEXT NOT NULL,
        email         TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        created_at    TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS orders (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        order_number   TEXT NOT NULL UNIQUE,
        customer_id    INTEGER,
        name           TEXT NOT NULL,
        email          TEXT NOT NULL,
        phone          TEXT NOT NULL,
        address        TEXT NOT NULL,
        city           TEXT NOT NULL,
        postal_code    TEXT DEFAULT '',
        notes          TEXT DEFAULT '',
        subtotal       REAL NOT NULL DEFAULT 0,
        shipping       REAL NOT NULL DEFAULT 0,
        total          REAL NOT NULL DEFAULT 0,
        payment_method TEXT NOT NULL DEFAULT 'cod',
        status         TEXT NOT NULL DEFAULT 'pending',
        created_at     TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS order_items (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id     INTEGER NOT NULL,
        product_id   INTEGER,
        product_name TEXT NOT NULL,
        image        TEXT DEFAULT '',
        price        REAL NOT NULL DEFAULT 0,
        qty          INTEGER NOT NULL DEFAULT 1,
        size         TEXT DEFAULT '',
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS messages (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        name       TEXT NOT NULL,
        email      TEXT NOT NULL,
        subject    TEXT DEFAULT '',
        message    TEXT NOT NULL,
        is_read    INTEGER NOT NULL DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS subscribers (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        email      TEXT NOT NULL UNIQUE,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    SQL);

        seed_demo_data($pdo);

        $pdo->exec('PRAGMA user_version = 1');
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function seed_demo_data(PDO $pdo): void
{
    $productCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($productCount > 0) {
        return;
    }

    $categories = [
        ['Heels',       'heels',       'Sharp, sculpted and endlessly elegant.',        'categories/heels.jpg',       1],
        ['Sandals',     'sandals',     'Strappy details for golden-hour moments.',       'categories/sandals.jpg',     2],
        ['Flats',       'flats',       'Everyday ease, quietly refined.',                'categories/flats.jpg',       3],
        ['Block Heels', 'block-heels', 'Comfort you can wear from desk to dinner.',      'categories/block-heels.jpg', 4],
        ['Bridal',      'bridal',      'Something borrowed, something beautifully heeled.', 'categories/bridal.jpg',   5],
    ];

    $catStmt = $pdo->prepare('INSERT OR IGNORE INTO categories (name, slug, description, image, sort_order) VALUES (?, ?, ?, ?, ?)');
    foreach ($categories as $c) {
        $catStmt->execute($c);
    }

    $catIds = [];
    foreach ($pdo->query('SELECT id, slug FROM categories') as $row) {
        $catIds[(string) $row['slug']] = (int) $row['id'];
    }

    // name, cat, tagline, description, price, sale, image, sizes, colors, stock, badge, featured, created offset (days ago)
    $products = [
        [
            'Aurelia Pointed Pump', 'heels', 'Soft nude leather · 85mm stiletto',
            'A clean pointed toe, a sculpted 85mm heel and a padded leather footbed — the pump that quietly finishes every outfit. Hand-finished in supple calf leather with a breathable lining.',
            7900, 6500, 'aurelia-pump.jpg', '36,37,38,39,40,41', 'Nude,Black', 24, 'Bestseller', 1, 1,
        ],
        [
            'Vermeil Patent Pump', 'heels', 'Burgundy patent · 90mm heel',
            'A rich burgundy patent finish with a sharp almond toe. Vermeil is the pair you reach for when the evening deserves a little drama.',
            8400, null, 'vermeil-pump.jpg', '36,37,38,39,40', 'Burgundy', 14, 'New', 1, 2,
        ],
        [
            'Lumière Crystal Sandal', 'sandals', 'Crystal straps · 95mm heel',
            'Hand-set crystals on a barely-there strap, finished with a slim stiletto. Made for weddings, dinners and every photograph in between.',
            11500, null, 'lumiere-sandal.jpg', '36,37,38,39,40', 'Silver', 9, '', 1, 3,
        ],
        [
            'Aria Bridal Heel', 'bridal', 'Ivory satin · pearl detail',
            'Our bridal signature: ivory satin, an open toe and a cascade of pearls and crystals. Cushioned for the long walk down the aisle and the dancing after.',
            13900, null, 'aria-bridal.jpg', '36,37,38,39,40,41', 'Ivory', 7, 'Bridal', 1, 4,
        ],
        [
            'Coquette Bow Pump', 'heels', 'Blush patent · bow detail',
            'A softly squared bow, a glossy blush finish and a wearable 75mm heel. Romantic without trying too hard.',
            7200, 5900, 'coquette-bow.jpg', '36,37,38,39,40', 'Blush', 18, 'Sale', 1, 5,
        ],
        [
            'Noir Platform Sandal', 'heels', 'Black patent · 110mm platform',
            'A high-shine platform sandal with delicate ankle straps — the balance of height and stability that makes all-nighters possible.',
            9600, null, 'noir-platform.jpg', '37,38,39,40,41', 'Black', 11, '', 0, 6,
        ],
        [
            'Mirabel Nude Wedge', 'block-heels', 'Nude leather · 70mm wedge',
            'A contoured wedge in warm nude leather with an adjustable ankle strap. All-day height with none of the wobble.',
            6800, null, 'mirabel-wedge.jpg', '36,37,38,39,40,41', 'Nude', 21, '', 1, 7,
        ],
        [
            'Sahara Leather Sandal', 'flats', 'Tan leather · everyday flat',
            'Vegetable-tanned leather, a soft buckle strap and a flexible sole. The flat that goes everywhere your weekend does.',
            4500, null, 'sahara-sandal.jpg', '36,37,38,39,40', 'Tan', 30, '', 0, 8,
        ],
        [
            'Lily Block Heel', 'block-heels', 'Cream leather · 65mm block',
            'An easy block heel in cream leather with a woven vamp. Equally at home with linen trousers or a midi dress.',
            7400, null, 'lily-block-heel.jpg', '36,37,38,39,40,41', 'Cream', 3, 'New', 1, 9,
        ],
        [
            'Nova Mule Edit', 'block-heels', 'Leather mule · 60mm block',
            'The mule, edited: a square toe, a low block heel and a glove-soft leather upper. Available in four considered neutrals.',
            8200, null, 'nova-mule.jpg', '36,37,38,39,40,41', 'Tan,Blush,Black,Cream', 16, 'New', 1, 10,
        ],
        [
            'Vega Glitter Pump', 'heels', 'Violet glitter · 100mm heel',
            'A pointed glitter pump that catches the light from across the room. Party-season shorthand for done.',
            8900, null, 'vega-glitter.jpg', '36,37,38,39,40', 'Violet', 8, '', 0, 11,
        ],
        [
            'Harlow Buckle Sandal', 'flats', 'Black leather · buckle detail',
            'A minimal black leather sandal with a brushed buckle. Structured enough for the office, easy enough for everything else.',
            4900, null, 'harlow-sandal.jpg', '36,37,38,39,40,41', 'Black', 2, '', 0, 12,
        ],
    ];

    $pStmt = $pdo->prepare(
        'INSERT INTO products (name, slug, category_id, tagline, description, price, sale_price, image, sizes, colors, stock, badge, is_featured, is_active, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, datetime("now", ?))'
    );

    foreach ($products as $p) {
        $pStmt->execute([
            $p[0], slugify($p[0]), $catIds[$p[1]], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], $p[9], $p[10], $p[11],
            '-' . $p[12] . ' days',
        ]);
    }

    /* Default admin — always exists so the panel is reachable */
    $admin = $pdo->prepare('INSERT OR IGNORE INTO admins (name, email, password_hash) VALUES (?, ?, ?)');
    $admin->execute([
        'Heelora Admin',
        ADMIN_DEFAULT_EMAIL,
        password_hash(ADMIN_DEFAULT_PASSWORD, PASSWORD_DEFAULT),
    ]);

    /* A little demo activity so the admin dashboard has something to show */
    $customerStmt = $pdo->prepare('INSERT INTO customers (name, email, phone, password_hash, city, address, created_at) VALUES (?, ?, ?, ?, ?, ?, datetime("now", ?))');
    $customerStmt->execute(['Ayesha Khan', 'ayesha@example.com', '0300 1234567', password_hash('demo1234', PASSWORD_DEFAULT), 'Lahore', 'House 24, Phase 5, DHA', '-9 days']);
    $customerId = (int) $pdo->lastInsertId();

    $orders = [
        ['HW-1001', $customerId, 'Ayesha Khan', 'ayesha@example.com', '0300 1234567', 'House 24, Phase 5, DHA', 'Lahore', '54000', 'Please deliver after 5pm.', 6500, 0, 6500, 'cod', 'delivered', '-8 days',
            [['Aurelia Pointed Pump', 'aurelia-pump.jpg', 6500, 1, '38']]],
        ['HW-1002', null, 'Fatima Raza', 'fatima@example.com', '0321 9876543', 'Flat 7-B, Clifton Block 2', 'Karachi', '75600', '', 14300, 250, 14550, 'bank', 'shipped', '-4 days',
            [['Lumière Crystal Sandal', 'lumiere-sandal.jpg', 11500, 1, '37'], ['Sahara Leather Sandal', 'sahara-sandal.jpg', 4500, 1, '39']]],
        ['HW-1003', null, 'Zoya Ahmed', 'zoya@example.com', '0333 5551212', 'House 9, Street 14, F-7/2', 'Islamabad', '44000', 'Gift wrap please.', 7400, 250, 7650, 'cod', 'pending', '-1 days',
            [['Lily Block Heel', 'lily-block-heel.jpg', 7400, 1, '38']]],
    ];

    $orderStmt = $pdo->prepare(
        'INSERT INTO orders (order_number, customer_id, name, email, phone, address, city, postal_code, notes, subtotal, shipping, total, payment_method, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime("now", ?))'
    );
    $itemStmt = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_name, image, price, qty, size) VALUES (?, ?, ?, ?, ?, ?)'
    );

    foreach ($orders as $o) {
        $orderStmt->execute([
            $o[0], $o[1], $o[2], $o[3], $o[4], $o[5], $o[6], $o[7], $o[8], $o[9], $o[10], $o[11], $o[12], $o[13], $o[14],
        ]);
        $orderId = (int) $pdo->lastInsertId();
        foreach ($o[15] as $item) {
            $itemStmt->execute([$orderId, $item[0], $item[1], $item[2], $item[3], $item[4]]);
        }
    }

    $pdo->prepare('INSERT INTO messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, datetime("now", ?))')
        ->execute([
            'Mahnoor Iqbal',
            'mahnoor@example.com',
            'Size exchange for Aurelia',
            'Hi! I ordered the Aurelia pump in size 38 but I think I need a 37. How do I arrange an exchange?',
            '-2 days',
        ]);
}
