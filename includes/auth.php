<?php
// includes/auth.php - Para páginas de clientes (checkout, perfil, etc.)
require_once __DIR__ . '/config.php'; // config.php ya inicia la sesión

// Si NO hay un usuario en la sesión, redirige a login.
// Ya no comprueba el rol.
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
?>