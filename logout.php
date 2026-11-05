<?php
// logout.php
require_once 'includes/config.php';

$wasStaff = isset($_SESSION['user']) && isStaffRole($_SESSION['user']['role'] ?? '');

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

if ($wasStaff) {
    header('Location: login.php?logged_out=1');
} else {
    header('Location: index.php');
}
exit;
