<?php
// includes/auth_logistics.php - Protección exclusiva para el módulo /logistics (rol 'logistica')

require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$currentUserRole = normalizeUserRole($_SESSION['user']['role'] ?? '');
$_SESSION['user']['role'] = $currentUserRole;

if ($currentUserRole !== 'logistica') {
    header('Location: ' . getRoleDashboardUrl($currentUserRole, '../'));
    exit;
}
?>

