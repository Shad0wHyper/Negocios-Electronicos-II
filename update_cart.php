<?php
require_once 'includes/config.php';
require_once 'includes/stock.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantities = $_POST['quantities'] ?? [];
    $updatedCart = $_SESSION['cart'] ?? [];
    $errors = [];

    foreach ($quantities as $id => $qty) {
        $id = trim($id);
        $qty = (int)$qty;
        if ($qty > 0) {
            $product = getProductForStock($db, $id);
            if (!$product) {
                $errors[] = 'Uno de los productos seleccionados ya no está disponible.';
                continue;
            }

            $message = stockMessage($product, $qty);
            if ($message !== '') {
                $errors[] = $message;
                continue;
            }

            $updatedCart[$id] = $qty;
        } else {
            unset($updatedCart[$id]);
        }
    }

    if (empty($errors)) {
        $_SESSION['cart'] = $updatedCart;
    } else {
        $_SESSION['cart_error'] = implode(' ', $errors);
    }
}

header('Location: cart.php');
exit;
