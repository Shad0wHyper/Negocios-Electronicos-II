<?php
// admin/logout.php
// Importante: usar config.php para asegurar que se destruya la sesión con nombre '__session'
require_once __DIR__ . '/../includes/config.php';

// Vaciar variables de sesión
$_SESSION = [];
session_unset();

// Eliminar la cookie de sesión en el navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'] ?: '/',
        $params['domain'] ?? '',
        $params['secure'] ?? false,
        $params['httponly'] ?? false
    );
}
setcookie('__session', '', time() - 42000, '/');

// Destruir la sesión en el servidor
session_destroy();

// Redirigir directamente al login (no al home de cliente) e indicar cierre de sesión de Firebase
header('Location: ../login.php?logged_out=1');
exit;
?>