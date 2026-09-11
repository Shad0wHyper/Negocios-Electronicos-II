<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantities = $_POST['quantities'] ?? [];

    foreach ($quantities as $id => $qty) {
        $id = trim($id);
        $qty = (int)$qty;
        if ($qty > 0) {
            $_SESSION['cart'][$id] = $qty;
        } else {
            unset($_SESSION['cart'][$id]);
        }
    }
}

header('Location: cart.php');
exit;
