<?php
/**
 * ElectroMart - One-click installer.
 * Creates the `electromart` database, all tables and sample data.
 * Safe to run multiple times: every table is dropped and recreated.
 */
define('SETUP_MODE', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$steps = [];
$error = null;

try {
    $conn->query('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $conn->select_db(DB_NAME);
    $steps[] = 'Database <strong>' . DB_NAME . '</strong> ready';

    $conn->query('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['cart', 'wishlist', 'reviews', 'order_items', 'orders', 'admins', 'users', 'products', 'categories'] as $t) {
        $conn->query("DROP TABLE IF EXISTS `$t`");
    }
    $conn->query('SET FOREIGN_KEY_CHECKS = 1');
    $steps[] = 'Old tables dropped (if any)';

    $schema = [
        "CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",

        "CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            original_price DECIMAL(10,2),
            stock INT DEFAULT 0,
            category_id INT,
            image_url VARCHAR(500),
            specs TEXT,
            is_featured TINYINT(1) DEFAULT 0,
            rating DECIMAL(3,1) DEFAULT 0,
            review_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",

        "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            city VARCHAR(100),
            state VARCHAR(100),
            zip VARCHAR(20),
            country VARCHAR(100) DEFAULT 'India',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",

        "CREATE TABLE admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(150),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",

        "CREATE TABLE orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
            shipping_name VARCHAR(100),
            shipping_address TEXT,
            shipping_city VARCHAR(100),
            shipping_state VARCHAR(100),
            shipping_zip VARCHAR(20),
            shipping_phone VARCHAR(20),
            payment_method VARCHAR(50) DEFAULT 'COD',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NULL,
            product_name VARCHAR(255),
            price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",

        "CREATE TABLE reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NOT NULL,
            rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_review (product_id, user_id),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE wishlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_wish (user_id, product_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT DEFAULT 1,
            UNIQUE KEY unique_cart (user_id, product_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
    ];
    foreach ($schema as $sql) {
        $conn->query($sql);
    }
    $steps[] = '9 tables created (categories, products, users, admins, orders, order_items, reviews, wishlist, cart)';

    /* ---------------- Categories ---------------- */
    $categories = [
        ['Smartphones & Tablets', 'fa-mobile-screen-button'],
        ['Laptops & Computers', 'fa-laptop'],
        ['Audio & Headphones', 'fa-headphones'],
        ['Smart Home', 'fa-house-signal'],
        ['Gaming', 'fa-gamepad'],
        ['Cameras', 'fa-camera'],
        ['Accessories', 'fa-plug'],
    ];
    foreach ($categories as $c) {
        db_execute('INSERT INTO categories (name, icon) VALUES (?, ?)', 'ss', $c);
    }
    $steps[] = count($categories) . ' categories inserted';

    /* ---------------- Products ---------------- */
    $img = function ($id) {
        return 'https://images.unsplash.com/photo-' . $id . '?auto=format&fit=crop&w=800&q=80';
    };
    // name, description, price, original_price, stock, category_id, image, specs, featured
    $products = [
        ['iPhone 15 Pro', 'The iPhone 15 Pro features a strong and light aerospace-grade titanium design, the A17 Pro chip for console-level gaming, a customisable Action button and a pro camera system with a 48MP main camera and 3x telephoto.', 129900, 134900, 25, 1, $img('1695048133142-1a20484d2569'),
            ['Brand' => 'Apple', 'Model' => 'iPhone 15 Pro', 'Display' => '6.1" Super Retina XDR OLED, 120Hz', 'Processor' => 'A17 Pro', 'Storage' => '128 GB', 'Camera' => '48MP + 12MP + 12MP', 'Battery' => 'Up to 23 hours video playback', 'Color' => 'Natural Titanium', 'Warranty' => '1 Year Apple Warranty'], 1],
        ['Samsung Galaxy S24', 'Galaxy S24 brings Galaxy AI to your fingertips: live translate calls, circle to search and a ProVisual engine for stunning photos. Compact 6.2" Dynamic AMOLED 2X display with a buttery 120Hz refresh rate.', 74999, 79999, 30, 1, $img('1610945265064-0e34e5519bbf'),
            ['Brand' => 'Samsung', 'Model' => 'Galaxy S24 (SM-S921B)', 'Display' => '6.2" Dynamic AMOLED 2X, 120Hz', 'Processor' => 'Exynos 2400', 'RAM' => '8 GB', 'Storage' => '256 GB', 'Camera' => '50MP + 12MP + 10MP', 'Color' => 'Onyx Black', 'Warranty' => '1 Year Manufacturer Warranty'], 1],
        ['MacBook Air M3 13"', 'Supercharged by the M3 chip, the strikingly thin MacBook Air delivers up to 18 hours of battery life, a brilliant Liquid Retina display and a fanless design that stays silent under any workload.', 114900, 119900, 15, 2, $img('1517336714731-489689fd1ca8'),
            ['Brand' => 'Apple', 'Model' => 'MacBook Air 13 (M3, 2024)', 'Processor' => 'Apple M3 8-core CPU, 10-core GPU', 'RAM' => '8 GB Unified Memory', 'Storage' => '256 GB SSD', 'Display' => '13.6" Liquid Retina', 'Weight' => '1.24 kg', 'Color' => 'Midnight', 'Warranty' => '1 Year Apple Warranty'], 1],
        ['Dell XPS 15', 'A creator-class laptop with a stunning 15.6" 3.5K OLED InfinityEdge display, 13th Gen Intel Core i7 and NVIDIA GeForce RTX 4050 graphics, all in a premium CNC-machined aluminium chassis.', 189990, 214990, 8, 2, $img('1593642632823-8f785ba67e45'),
            ['Brand' => 'Dell', 'Model' => 'XPS 15 9530', 'Processor' => 'Intel Core i7-13700H', 'RAM' => '16 GB DDR5', 'Storage' => '1 TB NVMe SSD', 'Graphics' => 'NVIDIA RTX 4050 6GB', 'Display' => '15.6" 3.5K OLED Touch', 'Color' => 'Platinum Silver', 'Warranty' => '1 Year Premium Support'], 0],
        ['Sony WH-1000XM5', 'Industry-leading noise cancellation powered by two processors and eight microphones. Exceptional sound quality, crystal clear hands-free calling and up to 30 hours of battery life with quick charging.', 29990, 34990, 40, 3, $img('1618366712010-f4ae9c647dcb'),
            ['Brand' => 'Sony', 'Model' => 'WH-1000XM5', 'Type' => 'Over-ear, Wireless', 'Noise Cancelling' => 'Yes, Adaptive ANC', 'Battery' => 'Up to 30 hours', 'Connectivity' => 'Bluetooth 5.2, LDAC', 'Color' => 'Black', 'Warranty' => '1 Year Sony India Warranty'], 1],
        ['JBL Flip 6', 'Bold JBL Original Pro Sound in a compact, portable speaker. IP67 waterproof and dustproof, 12 hours of playtime and PartyBoost to pair multiple JBL speakers for even bigger sound.', 9999, 14999, 50, 3, $img('1608043152269-423dbba4e7e1'),
            ['Brand' => 'JBL', 'Model' => 'Flip 6', 'Output' => '20W + 10W', 'Battery' => 'Up to 12 hours', 'Water Resistance' => 'IP67', 'Connectivity' => 'Bluetooth 5.1', 'Color' => 'Blue', 'Warranty' => '1 Year Warranty'], 0],
        ['Sony PlayStation 5', 'Experience lightning-fast loading with an ultra-high speed SSD, deeper immersion with haptic feedback, adaptive triggers and 3D Audio, and an all-new generation of incredible PlayStation games.', 54990, 59990, 12, 5, $img('1606813907291-d86efa9b94db'),
            ['Brand' => 'Sony', 'Model' => 'PlayStation 5 (Disc Edition)', 'Storage' => '1 TB SSD', 'Resolution' => 'Up to 4K 120Hz, 8K output', 'Controller' => 'DualSense Wireless', 'Color' => 'White', 'Warranty' => '1 Year Sony India Warranty'], 1],
        ['Xbox Series X', 'The fastest, most powerful Xbox ever. Play thousands of titles from four generations of consoles with true 4K gaming at up to 120 FPS, Quick Resume and a 1TB custom NVMe SSD.', 49990, 52990, 10, 5, $img('1621259182978-fbf93132d53d'),
            ['Brand' => 'Microsoft', 'Model' => 'Xbox Series X', 'Processor' => 'Custom AMD Zen 2, 12 TFLOPS', 'Storage' => '1 TB NVMe SSD', 'Resolution' => '4K at 120 FPS', 'Color' => 'Carbon Black', 'Warranty' => '1 Year Warranty'], 0],
        ['DJI Mini 4 Pro', 'A mini camera drone under 249 g with 4K/60fps HDR video, omnidirectional obstacle sensing, ActiveTrack 360 and up to 34 minutes of flight time. Perfect for travel and content creators.', 87990, 94990, 6, 6, $img('1473968512647-3e447244af8f'),
            ['Brand' => 'DJI', 'Model' => 'Mini 4 Pro', 'Camera' => '48MP 1/1.3" CMOS', 'Video' => '4K/60fps HDR', 'Flight Time' => 'Up to 34 minutes', 'Weight' => '< 249 g', 'Color' => 'Grey', 'Warranty' => '1 Year DJI Warranty'], 1],
        ['Canon EOS R50 Mirrorless', 'Compact and lightweight mirrorless camera with a 24.2MP APS-C sensor, Dual Pixel CMOS AF II with subject detection and uncropped 4K video. Includes RF-S 18-45mm kit lens.', 68990, 75995, 9, 6, $img('1516035069371-29a1b244cc32'),
            ['Brand' => 'Canon', 'Model' => 'EOS R50 + RF-S 18-45mm', 'Sensor' => '24.2MP APS-C CMOS', 'Video' => '4K 30p uncropped', 'Autofocus' => 'Dual Pixel CMOS AF II', 'Color' => 'Black', 'Warranty' => '2 Years Canon India Warranty'], 0],
        ['Amazon Echo Dot (5th Gen)', 'Our best sounding Echo Dot yet with clearer vocals and deeper bass. Control smart home devices with your voice, set timers, play music and ask Alexa anything.', 4499, 5499, 60, 4, $img('1543512214-318c7553f230'),
            ['Brand' => 'Amazon', 'Model' => 'Echo Dot 5th Gen', 'Voice Assistant' => 'Alexa', 'Connectivity' => 'Wi-Fi, Bluetooth', 'Sensors' => 'Temperature sensor', 'Color' => 'Charcoal', 'Warranty' => '1 Year Warranty'], 0],
        ['Google Nest Hub (2nd Gen)', 'A smart display with Google Assistant for your home. Control compatible lights and cameras, watch YouTube, view your photo frame and track your sleep with Sleep Sensing.', 7999, 9999, 3, 4, $img('1558089687-f282ffcbc126'),
            ['Brand' => 'Google', 'Model' => 'Nest Hub 2nd Gen', 'Display' => '7" Touchscreen', 'Voice Assistant' => 'Google Assistant', 'Connectivity' => 'Wi-Fi, Bluetooth, Thread', 'Color' => 'Chalk', 'Warranty' => '1 Year Warranty'], 0],
        ['iPad Pro 11" (M4)', 'Impossibly thin with the outrageous performance of the M4 chip and a breakthrough Ultra Retina XDR tandem OLED display. Works with Apple Pencil Pro and Magic Keyboard.', 99900, 104900, 14, 1, $img('1544244015-0df4b3ffc6b0'),
            ['Brand' => 'Apple', 'Model' => 'iPad Pro 11 (M4)', 'Display' => '11" Ultra Retina XDR OLED', 'Processor' => 'Apple M4', 'Storage' => '256 GB', 'Connectivity' => 'Wi-Fi 6E', 'Color' => 'Space Black', 'Warranty' => '1 Year Apple Warranty'], 1],
        ['Apple Watch Series 9', 'A more powerful S9 chip, a brighter display and a magical new double tap gesture. Advanced health features including ECG, blood oxygen and temperature sensing, plus crash detection.', 41900, 44900, 20, 7, $img('1434493789847-2f02dc6ca35d'),
            ['Brand' => 'Apple', 'Model' => 'Watch Series 9 GPS 45mm', 'Display' => 'Always-On Retina, 2000 nits', 'Processor' => 'S9 SiP', 'Water Resistance' => 'WR50', 'Battery' => 'Up to 18 hours', 'Color' => 'Midnight Aluminium', 'Warranty' => '1 Year Apple Warranty'], 0],
        ['NVIDIA GeForce RTX 4070 GPU', 'Built on the NVIDIA Ada Lovelace architecture with DLSS 3 and full ray tracing. 12GB GDDR6X memory delivers smooth 1440p gaming and accelerated creative workloads.', 58999, 64999, 4, 2, $img('1591488320449-011701bb6704'),
            ['Brand' => 'NVIDIA', 'Model' => 'GeForce RTX 4070 Founders Edition', 'Memory' => '12 GB GDDR6X', 'CUDA Cores' => '5888', 'Boost Clock' => '2.48 GHz', 'Power' => '200W TGP', 'Color' => 'Silver/Black', 'Warranty' => '3 Years Warranty'], 0],
        ['Logitech MX Master 3S Mouse', 'An iconic mouse remastered with 8K DPI tracking on any surface (even glass), quiet clicks and MagSpeed electromagnetic scrolling. Connect up to 3 devices across Windows and macOS.', 9495, 10995, 35, 7, $img('1527864550417-7fd91fc51a46'),
            ['Brand' => 'Logitech', 'Model' => 'MX Master 3S', 'DPI' => '200 - 8000', 'Connectivity' => 'Bluetooth, Logi Bolt USB', 'Battery' => 'Up to 70 days, USB-C', 'Color' => 'Graphite', 'Warranty' => '1 Year Warranty'], 0],
        ['Samsung 55" 4K OLED Smart TV', 'Experience pure blacks and brilliant colour with Samsung OLED powered by the NQ4 AI Gen2 processor. Dolby Atmos, 144Hz gaming support and Tizen smart platform with all your favourite apps.', 139990, 189900, 5, 4, $img('1593359677879-a4bb92f829d1'),
            ['Brand' => 'Samsung', 'Model' => 'QA55S90DAE', 'Screen Size' => '55 inches', 'Resolution' => '4K Ultra HD (3840x2160)', 'Refresh Rate' => '144Hz', 'Audio' => '40W, Dolby Atmos', 'Color' => 'Titan Black', 'Warranty' => '2 Years Comprehensive Warranty'], 1],
        ['Bose QuietComfort 45', 'Iconic quiet, comfort and sound. World-class noise cancellation with Quiet and Aware modes, balanced high-fidelity audio and up to 24 hours of battery life on a single charge.', 24900, 32900, 2, 3, $img('1546435770-a3e426bf472b'),
            ['Brand' => 'Bose', 'Model' => 'QuietComfort 45', 'Type' => 'Over-ear, Wireless', 'Noise Cancelling' => 'Yes (Quiet / Aware)', 'Battery' => 'Up to 24 hours', 'Connectivity' => 'Bluetooth 5.1', 'Color' => 'Triple Black', 'Warranty' => '1 Year Warranty'], 0],
        ['OnePlus 12', 'Smooth beyond belief with the Snapdragon 8 Gen 3, a 2K 120Hz ProXDR display, 4th Gen Hasselblad Camera for Mobile and 80W SUPERVOOC charging that powers a full day in minutes.', 64999, 69999, 22, 1, $img('1511707171634-5f897ff02aa9'),
            ['Brand' => 'OnePlus', 'Model' => 'OnePlus 12 (CPH2573)', 'Display' => '6.82" 2K LTPO AMOLED, 120Hz', 'Processor' => 'Snapdragon 8 Gen 3', 'RAM' => '12 GB', 'Storage' => '256 GB', 'Battery' => '5400 mAh, 80W', 'Color' => 'Flowy Emerald', 'Warranty' => '1 Year Warranty'], 0],
        ['Anker 7-in-1 USB-C Hub', 'Expand your laptop with 4K HDMI, 100W Power Delivery pass-through, two USB-A 5Gbps ports, a USB-C data port and SD/microSD card readers in one compact aluminium hub.', 3499, 4999, 0, 7, $img('1625842268584-8f3296236761'),
            ['Brand' => 'Anker', 'Model' => 'PowerExpand 7-in-1 (A8346)', 'Ports' => 'HDMI 4K, 2x USB-A, USB-C, SD, microSD, PD', 'Power Delivery' => '100W pass-through', 'Material' => 'Aluminium', 'Color' => 'Space Grey', 'Warranty' => '18 Months Warranty'], 0],
    ];
    foreach ($products as $p) {
        db_execute(
            'INSERT INTO products (name, description, price, original_price, stock, category_id, image_url, specs, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'ssddiissi',
            [$p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], json_encode($p[7]), $p[8]]
        );
    }
    $steps[] = count($products) . ' electronics products inserted';

    /* ---------------- Admin ---------------- */
    db_execute('INSERT INTO admins (username, password, email) VALUES (?, ?, ?)', 'sss',
        ['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin@electromart.com']);
    $steps[] = 'Admin account created (admin / admin123)';

    /* ---------------- Sample customers ---------------- */
    $users = [
        ['John Doe', 'john@example.com', '9876543210', '221B MG Road', 'Bengaluru', 'Karnataka', '560001'],
        ['Priya Sharma', 'priya@example.com', '9812345678', '14 Marine Drive', 'Mumbai', 'Maharashtra', '400002'],
        ['Rahul Verma', 'rahul@example.com', '9898989898', '7 Connaught Place', 'New Delhi', 'Delhi', '110001'],
    ];
    $userIds = [];
    $pw = password_hash('password123', PASSWORD_DEFAULT);
    foreach ($users as $u) {
        $userIds[] = db_execute(
            'INSERT INTO users (name, email, password, phone, address, city, state, zip, country) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "India")',
            'ssssssss',
            [$u[0], $u[1], $pw, $u[2], $u[3], $u[4], $u[5], $u[6]]
        );
    }
    $steps[] = count($users) . ' sample customers created (password: password123)';

    /* ---------------- Sample reviews ---------------- */
    $comments = [
        5 => ['Absolutely love it! Worth every rupee.', 'Excellent build quality and super fast delivery from ElectroMart.', 'Best purchase this year. Highly recommended!'],
        4 => ['Very good product, minor issues but overall great value.', 'Performs really well. Packaging could be better.', 'Great features, battery life is impressive.'],
        3 => ['Decent product for the price. Does the job.', 'Average experience, expected a little more.'],
    ];
    $reviewCount = 0;
    foreach (range(1, count($products)) as $pid) {
        foreach ($userIds as $i => $uid) {
            if (($pid + $i) % 3 === 0) {
                continue; // not every user reviews every product
            }
            $rating = [5, 4, 5, 3, 4][($pid + $i) % 5];
            $list = $comments[$rating];
            $comment = $list[($pid + $i) % count($list)];
            db_execute('INSERT INTO reviews (product_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW() - INTERVAL ? DAY)',
                'iiisi', [$pid, $uid, $rating, $comment, ($pid * 3 + $i) % 60]);
            $reviewCount++;
        }
    }
    $conn->query('UPDATE products p SET
        rating = (SELECT IFNULL(ROUND(AVG(r.rating),1),0) FROM reviews r WHERE r.product_id = p.id),
        review_count = (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id)');
    $steps[] = $reviewCount . ' sample reviews inserted and product ratings calculated';

    /* ---------------- Sample orders ---------------- */
    $sampleOrders = [
        [$userIds[0], 'delivered', 'COD', 20, [[5, 1], [16, 1]]],
        [$userIds[0], 'shipped', 'UPI', 4, [[11, 2]]],
        [$userIds[1], 'processing', 'UPI', 2, [[3, 1]]],
        [$userIds[2], 'pending', 'COD', 0, [[7, 1], [6, 1]]],
        [$userIds[1], 'cancelled', 'COD', 10, [[14, 1]]],
    ];
    foreach ($sampleOrders as $o) {
        $u = db_fetch_one('SELECT * FROM users WHERE id = ?', 'i', [$o[0]]);
        $total = 0;
        $lines = [];
        foreach ($o[4] as [$pid, $qty]) {
            $prod = db_fetch_one('SELECT name, price FROM products WHERE id = ?', 'i', [$pid]);
            $total += $prod['price'] * $qty;
            $lines[] = [$pid, $prod['name'], $prod['price'], $qty];
        }
        if ($total < FREE_SHIPPING_THRESHOLD) {
            $total += SHIPPING_FEE;
        }
        $orderId = db_execute(
            'INSERT INTO orders (user_id, total, status, shipping_name, shipping_address, shipping_city, shipping_state, shipping_zip, shipping_phone, payment_method, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY)',
            'idssssssssi',
            [$u['id'], $total, $o[1], $u['name'], $u['address'], $u['city'], $u['state'], $u['zip'], $u['phone'], $o[2], $o[3]]
        );
        foreach ($lines as $l) {
            db_execute('INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)',
                'iisdi', [$orderId, $l[0], $l[1], $l[2], $l[3]]);
        }
    }
    $steps[] = count($sampleOrders) . ' sample orders inserted';

    // Fresh install: reset any old session data
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['cart']);
} catch (Throwable $ex) {
    $error = $ex->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ElectroMart Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="auth-bg">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card auth-card shadow-lg">
                <div class="card-body p-4 p-md-5">
                    <h2 class="brand-logo text-dark mb-1"><span class="brand-icon"><i class="fa-solid fa-bolt"></i></span> <span>Electro<span class="text-accent">Mart</span></span> Setup</h2>
                    <p class="text-muted">Database installer</p>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><strong>Setup failed:</strong> <?= e($error) ?></div>
                        <?php if ($steps): ?><p class="small text-muted">Completed before the error:</p><?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><strong>Success!</strong> ElectroMart has been installed.</div>
                    <?php endif; ?>
                    <ul class="list-group mb-4">
                        <?php foreach ($steps as $s): ?>
                            <li class="list-group-item"><i class="fa-solid fa-check text-success me-2"></i><?= $s ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (!$error): ?>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="p-3 border rounded h-100">
                                    <h6><i class="fa-solid fa-user-shield text-accent me-2"></i>Admin Login</h6>
                                    <div class="small">Username: <code>admin</code><br>Password: <code>admin123</code></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded h-100">
                                    <h6><i class="fa-solid fa-user text-accent me-2"></i>Customer Login</h6>
                                    <div class="small">Email: <code>john@example.com</code><br>Password: <code>password123</code></div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="<?= url('index.php') ?>" class="btn btn-accent"><i class="fa-solid fa-store me-1"></i>Visit Store</a>
                            <a href="<?= url('admin/login.php') ?>" class="btn btn-dark"><i class="fa-solid fa-gauge me-1"></i>Admin Panel</a>
                        </div>
                        <p class="small text-muted mt-4 mb-0"><i class="fa-solid fa-triangle-exclamation me-1"></i>Running this page again resets all data to the sample data.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
