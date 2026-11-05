<?php
// includes/auth_admin.php - Protección exclusiva para el módulo /admin (rol 'admin')

require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$currentUserRole = normalizeUserRole($_SESSION['user']['role'] ?? '');
$_SESSION['user']['role'] = $currentUserRole;

if ($currentUserRole !== 'admin') {
    header('Location: ' . getRoleDashboardUrl($currentUserRole, '../'));
    exit;
}
?>
