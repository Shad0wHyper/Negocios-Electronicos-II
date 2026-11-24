<?php
// includes/auth_support.php - Protección exclusiva para el módulo /support (rol 'soporte')

require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$currentUserRole = normalizeUserRole($_SESSION['user']['role'] ?? '');
$_SESSION['user']['role'] = $currentUserRole;

if ($currentUserRole !== 'soporte') {
    header('Location: ' . getRoleDashboardUrl($currentUserRole, '../'));
    exit;
}
?>
