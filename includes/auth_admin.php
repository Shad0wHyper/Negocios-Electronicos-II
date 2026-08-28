<?php
// auth.php (en la raíz del proyecto)

require_once __DIR__ . '/config.php';
// Si no hay una sesión de usuario o el rol no es 'admin'
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    // Redirige a la página de login
    header('Location: login.php');
    exit;
}
?>