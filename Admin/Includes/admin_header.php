<?php
/**
 * Admin layout header + sidebar.
 * Pages must call require_admin() before including this file.
 * Optional: $page_title, $admin_page (dashboard|products|orders|users|categories)
 */
$page_title = $page_title ?? 'Dashboard';
$admin_page = $admin_page ?? '';
$pendingCount = (int) db_value("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$navItems = [
    'dashboard'  => ['index.php', 'fa-gauge-high', 'Dashboard'],
    'products'   => ['products.php', 'fa-box-open', 'Products'],
    'orders'     => ['orders.php', 'fa-receipt', 'Orders'],
    'users'      => ['users.php', 'fa-users', 'Users'],
    'categories' => ['categories.php', 'fa-layer-group', 'Categories'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($page_title) ?> | ElectroMart Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
    <script>window.EM = { baseUrl: <?= json_encode(BASE_URL) ?>, loggedIn: false };</script>
</head>
<body class="admin-body">
<div class="admin-wrapper">
    <aside class="admin-sidebar" id="adminSidebar">
        <a href="<?= url('admin/index.php') ?>" class="admin-brand">
            <span class="brand-icon"><i class="fa-solid fa-bolt"></i></span> <span>Electro<span class="text-accent">Mart</span></span>
            <small class="d-block">Admin Panel</small>
        </a>
        <nav class="admin-nav">
            <?php foreach ($navItems as $key => [$file, $icon, $label]): ?>
                <a href="<?= url('admin/' . $file) ?>" class="<?= $admin_page === $key ? 'active' : '' ?>">
                    <i class="fa-solid <?= $icon ?>"></i><span><?= $label ?></span>
                    <?php if ($key === 'orders' && $pendingCount): ?><span class="badge bg-warning text-dark ms-auto"><?= $pendingCount ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
            <hr>
            <a href="<?= url('index.php') ?>" target="_blank"><i class="fa-solid fa-store"></i><span>View Store</span></a>
            <a href="<?= url('admin/logout.php') ?>"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
        </nav>
    </aside>
    <div class="admin-sidebar-backdrop" id="adminBackdrop"></div>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" id="sidebarToggle" type="button" aria-label="Toggle sidebar"><i class="fa-solid fa-bars"></i></button>
                <h1 class="admin-page-title mb-0"><?= e($page_title) ?></h1>
            </div>
            <div class="dropdown">
                <a href="#" class="admin-user dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span>
                    <span class="d-none d-sm-inline"><?= e($_SESSION['admin_name'] ?? 'Admin') ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= url('index.php') ?>" target="_blank"><i class="fa-solid fa-store me-2"></i>View Store</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= url('admin/logout.php') ?>"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                </ul>
            </div>
        </header>
        <div class="admin-content">
            <?php display_all_flash(); ?>
