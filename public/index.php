<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../controllers/InventoryController.php';
require_once __DIR__ . '/../controllers/ConsumablesController.php';
require_once __DIR__ . '/../controllers/StockController.php';
require_once __DIR__ . '/../controllers/ReportController.php';
require_once __DIR__ . '/../controllers/PrintController.php';
require_once __DIR__ . '/../controllers/ServiceRequestController.php';
require_once __DIR__ . '/../controllers/AuthController.php';

session_start();

$controller = new InventoryController();
$stockController = new StockController();
$authController = new AuthController();

$page = $_GET['page'] ?? 'items';
$action = $_GET['action'] ?? '';

// Public (pre-auth) routes — everything else requires a session
$publicPages = ['login', 'register'];
if (!in_array($page, $publicPages, true) && !isset($_SESSION['user_id'])) {
    header('Location: /CIANANMRO/public/index.php?page=login');
    exit;
}

// Role restrictions: CIANAN users & production operators use Service Request only.
// Maintenance Inventory stays off-limits to them (maintenance technicians/admin/legacy users only).
$role = $_SESSION['role'] ?? '';
$srOnlyPages = ['service_request', 'create_service_request', 'assign_service_request', 'complete_service_request', 'logout', 'logout_helper'];
if (in_array($role, ['cianan_user', 'production_operator'], true) && !in_array($page, $srOnlyPages, true)) {
    header('Location: /CIANANMRO/public/index.php?page=service_request');
    exit;
}

// Internal pages use the sidebar layout; auth pages render standalone
$standalonePages = ['login', 'register', 'thermal_slip'];
if (!in_array($page, $standalonePages, true)) {
    require __DIR__ . '/../views/layout/header.php';
}

switch ($page) {
    case 'login':
        $authController->login();
        break;

    case 'register':
        $authController->register();
        break;

    case 'logout':
        $authController->logout();
        break;

    case 'stock_in':
        $title = 'Stock In - Request';
        $stockController->showStockIn();
        break;

    case 'stock_out':
        $title = 'Stock Out - Withdrawal';
        $stockController->showStockOut();
        break;

    case 'submit_request':
        $title = 'Stock In - Request';
        $stockController->submitRequest();
        break;

    case 'receive_request':
        $title = 'Stock In - Request';
        $stockController->receiveRequest();
        break;

    case 'cancel_request':
        $title = 'Stock In - Request';
        $stockController->cancelRequest();
        break;

    case 'submit_withdrawal':
        $title = 'Stock Out - Withdrawal';
        $stockController->submitWithdrawal();
        break;

    case 'consumables':
        $title = 'Maintenance Consumables';
        $consumablesController = new ConsumablesController();
        $consumablesController->index();
        break;

    case 'submit_consumable':
        $title = 'Maintenance Consumables';
        $consumablesController = new ConsumablesController();
        $consumablesController->handleAdd();
        break;

    case 'update_consumable':
        $title = 'Maintenance Consumables';
        $consumablesController = new ConsumablesController();
        $consumablesController->handleUpdate();
        break;

    case 'upload_consumables':
        $title = 'Maintenance Consumables';
        $consumablesController = new ConsumablesController();
        $consumablesController->handleBulkUpload();
        break;

    case 'delete_consumable':
        $title = 'Maintenance Consumables';
        $consumablesController = new ConsumablesController();
        $consumablesController->handleDelete();
        break;

    case 'reports':
        $title = 'Reports';
        $reportController = new ReportController();
        $reportController->index();
        break;

    case 'export_report_csv':
        $reportController = new ReportController();
        $reportController->exportCsv();
        break;

    case 'thermal_slip':
        $printController = new PrintController();
        $printController->thermalSlip();
        break;

    case 'service_request':
        $title = 'Service Request';
        $serviceRequestController = new ServiceRequestController();
        $serviceRequestController->dashboard();
        break;

    case 'create_service_request':
        $title = 'Service Request';
        $serviceRequestController = new ServiceRequestController();
        $serviceRequestController->handleCreate();
        break;

    case 'assign_service_request':
        $title = 'Service Request';
        $serviceRequestController = new ServiceRequestController();
        $serviceRequestController->handleAssign();
        break;

    case 'complete_service_request':
        $title = 'Service Request';
        $serviceRequestController = new ServiceRequestController();
        $serviceRequestController->handleComplete();
        break;

    case 'items':
    default:
        $title = 'Dashboard';
        $controller->index();
        break;
}

if (!in_array($page, $standalonePages, true)) {
    require __DIR__ . '/../views/layout/footer.php';
}
