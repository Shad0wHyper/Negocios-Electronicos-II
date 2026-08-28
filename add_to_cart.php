<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

    if ($product_id > 0) {
        // Asegurarse de que existe el carrito
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Si ya existe el producto en el carrito, suma la cantidad
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }

        header('Location: cart.php'); // Redirige al carrito
        exit;
    }
}

header('Location: index.php');
exit;
?>
