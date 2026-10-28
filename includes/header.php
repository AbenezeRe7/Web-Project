<?php
/**
 * ElectroMart - Storefront header + navbar.
 * Optional variables before including: $page_title, $active_page
 */
$page_title  = $page_title ?? 'Next-Gen Electronics Store';
$active_page = $active_page ?? '';
$cart_count  = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($page_title) ?> | <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
    <script>
        window.EM = {
            baseUrl: <?= json_encode(BASE_URL) ?>,
            loggedIn: <?= is_logged_in() ? 'true' : 'false' ?>
        };
    </script>
</head>
<body>

<div class="top-strip d-none d-md-block">
    <div class="container d-flex justify-content-between">
        <span><i class="fa-solid fa-truck-fast me-1"></i> Free shipping on orders above &#8377;<?= FREE_SHIPPING_THRESHOLD ?></span>
        <span><i class="fa-solid fa-headset me-1"></i> Support: +91 98765 43210 &nbsp;|&nbsp; <i class="fa-solid fa-shield-halved me-1"></i> 100% Genuine Products</span>
    </div>
</div>

<nav class="navbar navbar-expand-lg navbar-dark main-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand brand-logo" href="<?= url('index.php') ?>">
            <span class="brand-icon"><i class="fa-solid fa-bolt"></i></span> <span>Electro<span class="text-accent">Mart</span></span>
        </a>

        <div class="d-flex align-items-center order-lg-3">
            <a href="<?= url('cart.php') ?>" class="nav-cart me-2 position-relative" title="Cart">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="cart-badge badge rounded-pill" id="cart-count" <?= $cart_count ? '' : 'style="display:none"' ?>><?= $cart_count ?></span>
            </a>
            <button class="navbar-toggler ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse order-lg-2" id="mainNav">
            <form class="nav-search mx-lg-auto my-3 my-lg-0" action="<?= url('products.php') ?>" method="get" role="search">
                <div class="input-group">
                    <input class="form-control" type="search" name="search" placeholder="Search phones, laptops, headphones..." value="<?= e($_GET['search'] ?? '') ?>" aria-label="Search">
                    <button class="btn btn-accent" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </form>
            <ul class="navbar-nav ms-lg-3 mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item"><a class="nav-link <?= $active_page === 'home' ? 'active' : '' ?>" href="<?= url('index.php') ?>"><i class="fa-solid fa-house me-1"></i>Home</a></li>
                <li class="nav-item"><a class="nav-link <?= $active_page === 'shop' ? 'active' : '' ?>" href="<?= url('products.php') ?>"><i class="fa-solid fa-store me-1"></i>Shop</a></li>
                <li class="nav-item"><a class="nav-link <?= $active_page === 'wishlist' ? 'active' : '' ?>" href="<?= url('wishlist.php') ?>"><i class="fa-solid fa-heart me-1"></i>Wishlist</a></li>
                <li class="nav-item dropdown">
                    <?php if (is_logged_in()): ?>
                        <a class="nav-link dropdown-toggle <?= in_array($active_page, ['account', 'orders'], true) ? 'active' : '' ?>" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-circle-user me-1"></i><?= e(explode(' ', $_SESSION['user_name'])[0]) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="<?= url('account.php') ?>"><i class="fa-solid fa-user-gear me-2"></i>My Account</a></li>
                            <li><a class="dropdown-item" href="<?= url('orders.php') ?>"><i class="fa-solid fa-box me-2"></i>Orders</a></li>
                            <li><a class="dropdown-item" href="<?= url('wishlist.php') ?>"><i class="fa-solid fa-heart me-2"></i>Wishlist</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= url('logout.php') ?>"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                        </ul>
                    <?php else: ?>
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-circle-user me-1"></i>Account
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="<?= url('login.php') ?>"><i class="fa-solid fa-right-to-bracket me-2"></i>Login</a></li>
                            <li><a class="dropdown-item" href="<?= url('register.php') ?>"><i class="fa-solid fa-user-plus me-2"></i>Register</a></li>
                        </ul>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3"></div>

<main class="site-main">
    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="container mt-3 flash-wrap">
            <?php display_all_flash(); ?>
        </div>
    <?php endif; ?>
