<?php
// includes/auth.php - Para páginas de clientes (checkout, perfil, etc.)
require_once __DIR__ . '/config.php'; // config.php ya inicia la sesión

// Si NO hay un usuario en la sesión, redirige a login.
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Si un usuario de staff (admin, vendedor, logistica) entra al módulo de perfil de cliente, redirigirlo a su panel
if (isStaffRole($_SESSION['user']['role'] ?? '')) {
    header('Location: ' . getRoleDashboardUrl($_SESSION['user']['role']));
    exit;
}
?>