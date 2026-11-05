<?php
// seller/logout.php
require_once __DIR__ . '/../includes/config.php';

$_SESSION = [];
session_unset();

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

session_destroy();

header('Location: ../login.php?logged_out=1');
exit;
?>

