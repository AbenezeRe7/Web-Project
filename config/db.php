<?php
/**
 * ElectroMart - Database configuration & bootstrap
 * Every page includes this file first: it starts the session,
 * defines constants and opens the mysqli connection ($conn).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'electromart');
define('SITE_NAME', 'ElectroMart');
define('FREE_SHIPPING_THRESHOLD', 999);
define('SHIPPING_FEE', 49);
define('PRODUCTS_PER_PAGE', 12);

/*
 * BASE_URL is detected automatically from the project folder location inside
 * the web root (e.g. http://localhost/electromart -> "/electromart").
 * If auto-detection does not work on your server, replace it with a fixed
 * value such as: define('BASE_URL', '/electromart');
 */
if (!defined('BASE_URL')) {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $appRoot = realpath(dirname(__DIR__));
    $base = '/electromart';
    if ($docRoot !== false && $appRoot !== false) {
        $docRoot = str_replace('\\', '/', $docRoot);
        $appRoot = str_replace('\\', '/', $appRoot);
        if (stripos($appRoot, $docRoot) === 0) {
            $base = substr($appRoot, strlen($docRoot));
        }
    }
    define('BASE_URL', rtrim($base, '/'));
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    if (defined('SETUP_MODE')) {
        // setup.php connects without selecting a database (it creates it)
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    $setupLink = BASE_URL . '/setup.php';
    echo '<div style="font-family:Arial,sans-serif;max-width:640px;margin:60px auto;padding:30px;border:1px solid #f59e0b;border-radius:12px;background:#fffbeb">';
    echo '<h2 style="color:#1a1f36;margin-top:0">&#9889; ElectroMart - Database connection failed</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Make sure MySQL is running in XAMPP and that you have run the installer: ';
    echo '<a href="' . htmlspecialchars($setupLink) . '">' . htmlspecialchars($setupLink) . '</a></p></div>';
    exit;
}

