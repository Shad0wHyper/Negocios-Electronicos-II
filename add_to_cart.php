<?php
require_once 'includes/config.php';
require_once 'includes/stock.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? trim($_POST['product_id']) : '';
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

    if (!empty($product_id) && $quantity > 0) {
        $product = getProductForStock($db, $product_id);
        if (!$product) {
            $_SESSION['cart_error'] = 'El producto seleccionado ya no está disponible.';
            header('Location: cart.php');
            exit;
        }

        $currentQuantity = (int)($_SESSION['cart'][$product_id] ?? 0);
        $message = stockMessage($product, $quantity, $currentQuantity);
        if ($message !== '') {
            $_SESSION['cart_error'] = $message;
            header('Location: cart.php');
            exit;
        }

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $_SESSION['cart'][$product_id] = $currentQuantity + $quantity;

        header('Location: cart.php');
        exit;
    }
}

header('Location: index.php');
exit;
?>
