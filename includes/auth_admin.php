<?php
require_once __DIR__ . '/config.php';

$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? '';
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');

$rolePages = [
    'vendedor' => [
        'dashboard.php', 'erp.php', 'erp_new_order.php', 'orders.php', 'view_order.php',
        'crm.php', 'crm_client.php', 'scm_products.php', 'reports.php', 'activity.php'
    ],
    'logistica' => [
        'dashboard.php', 'erp.php', 'orders.php', 'view_order.php', 'reports.php',
        'scm_products.php', 'scm_inventory.php', 'scm_movements.php', 'scm_orders.php',
        'scm_add_movement.php', 'scm_add_order.php'
    ],
];

$isAllowed = $role === 'admin'
    || (isset($rolePages[$role]) && in_array($currentPage, $rolePages[$role], true));

if (!$user || !$isAllowed) {
    if ($user && isset($rolePages[$role])) {
        header('Location: dashboard.php');
    } else {
        header('Location: ../login.php');
    }
    exit;
}
?>
