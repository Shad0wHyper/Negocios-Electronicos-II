<?php
// includes/auth_seller.php - Protección exclusiva para el módulo /seller (rol 'vendedor')

require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$currentUserRole = normalizeUserRole($_SESSION['user']['role'] ?? '');
$_SESSION['user']['role'] = $currentUserRole;

if ($currentUserRole !== 'vendedor') {
    header('Location: ' . getRoleDashboardUrl($currentUserRole, '../'));
    exit;
}
?>

