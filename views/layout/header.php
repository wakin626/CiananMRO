<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CIANAN MRO - Inventory Management</title>
    <link rel="stylesheet" href="/CIANANMRO/public/css/style.css">
</head>
<body>
<div class="page-loader" id="pageLoader" aria-hidden="true">
    <div class="page-loader-spinner"></div>
    <span class="page-loader-text">Loading&hellip;</span>
</div>
<?php
$currentPage = $_GET['page'] ?? 'items';
$collapsed = (isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === '1');
$freshLogin = isset($_COOKIE['fresh_login']);

$role = $_SESSION['role'] ?? '';
$miAllowed = in_array($role, ['admin', 'user', 'maintenance_technician'], true);

$miPages = [
    'items'       => in_array($currentPage, ['items']),
    'stock_in'    => in_array($currentPage, ['stock_in', 'submit_request', 'receive_request', 'cancel_request']),
    'stock_out'   => in_array($currentPage, ['stock_out', 'submit_withdrawal']),
    'consumables' => in_array($currentPage, ['consumables', 'submit_consumable']),
    'reports'     => in_array($currentPage, ['reports', 'export_report_csv']),
];
$miActive = in_array(true, $miPages, true);
?>
<div class="app-container">
    <aside class="sidebar<?= $collapsed ? ' collapsed' : '' ?>">
        <div class="sidebar-top">
            <span class="sidebar-brand">CIANAN MRO</span>
            <button type="button" class="sidebar-toggle" id="sidebarToggle"
                    title="Toggle sidebar" aria-label="Toggle sidebar">
                <span class="toggle-icon">&#9664;</span>
            </button>
        </div>
        <nav class="sidebar-nav">
            <?php if ($miAllowed): ?>
            <div class="nav-group<?= (!$freshLogin && ($miActive || (isset($_COOKIE['mi_group_open']) && $_COOKIE['mi_group_open'] === '1'))) ? ' open' : '' ?><?= $miActive ? ' group-active' : '' ?>">
                <button type="button" class="nav-group-toggle" aria-expanded="false">
                    <span class="nav-label">Maintenance Inventory</span>
                    <span class="nav-caret" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>
                    </span>
                </button>
                <div class="nav-submenu">
                    <a href="/CIANANMRO/public/index.php?page=items"
                       class="nav-link nav-sub <?= $miPages['items'] ? 'active' : '' ?>">
                        <span class="nav-label">Inventory Dashboard</span>
                    </a>
                    <a href="/CIANANMRO/public/index.php?page=stock_in"
                       class="nav-link nav-sub <?= $miPages['stock_in'] ? 'active' : '' ?>">
                        <span class="nav-label">Stock In (Request)</span>
                    </a>
                    <a href="/CIANANMRO/public/index.php?page=stock_out"
                       class="nav-link nav-sub <?= $miPages['stock_out'] ? 'active' : '' ?>">
                        <span class="nav-label">Stock Out (Withdrawal)</span>
                    </a>
                    <a href="/CIANANMRO/public/index.php?page=consumables"
                       class="nav-link nav-sub <?= $miPages['consumables'] ? 'active' : '' ?>">
                        <span class="nav-label">Maintenance Consumables</span>
                    </a>
                    <a href="/CIANANMRO/public/index.php?page=reports"
                       class="nav-link nav-sub <?= $miPages['reports'] ? 'active' : '' ?>">
                        <span class="nav-label">Reports</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Future top-level modules (e.g. Work Orders, Machines) go here -->

            <?php
            $srPages = ['service_request', 'completed_sr', 'create_service_request', 'assign_service_request', 'complete_service_request'];
            $srActive = in_array($currentPage, $srPages, true);
            ?>
            <div class="nav-group<?= (!$freshLogin && ($srActive || (isset($_COOKIE['sr_group_open']) && $_COOKIE['sr_group_open'] === '1'))) ? ' open' : '' ?><?= $srActive ? ' group-active' : '' ?>">
                <button type="button" class="nav-group-toggle" aria-expanded="false">
                    <span class="nav-label">Service Request</span>
                    <span class="nav-caret" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>
                    </span>
                </button>
                <div class="nav-submenu">
                    <a href="/CIANANMRO/public/index.php?page=service_request"
                       class="nav-link nav-sub <?= $currentPage === 'service_request' ? 'active' : '' ?>">
                        <span class="nav-label">SR Dashboard</span>
                    </a>
                    <a href="/CIANANMRO/public/index.php?page=completed_sr"
                       class="nav-link nav-sub <?= in_array($currentPage, ['completed_sr', 'complete_service_request']) ? 'active' : '' ?>">
                        <span class="nav-label">Completed Service Requests</span>
                    </a>
                </div>
            </div>
        </nav>
        <?php if (isset($_SESSION['username'])): ?>
        <div class="sidebar-user">
            <div class="sidebar-user-name" title="<?= htmlspecialchars($_SESSION['full_name']) ?>">
                <?= htmlspecialchars($_SESSION['full_name']) ?>
                <span class="sidebar-user-role"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></span>
            </div>
            <a href="/CIANANMRO/public/index.php?page=logout" class="sidebarlogout">Logout</a>
        </div>
        <?php endif; ?>
    </aside>
    <div class="main-content">
