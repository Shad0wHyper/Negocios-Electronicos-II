<?php
require_once 'includes/config.php';

if (($_GET['clear'] ?? '') === '1') {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
if (!empty($id) && isset($_SESSION['cart'][$id])) {
    unset($_SESSION['cart'][$id]);
}

header('Location: cart.php');
exit;
