<?php
require_once 'includes/config.php';
require_once 'includes/stock.php';

// Función para obtener los productos desde Firestore
function getProductsByIds($db, $ids) {
    if (empty($ids)) return [];
    $products = [];
    $productsRef = $db->collection('products');
    foreach ($ids as $id) {
        $doc = $productsRef->document($id)->snapshot();
        if ($doc->exists()) {
            $p = $doc->data();
            $p['id'] = $doc->id();
            $products[] = $p;
        }
    }
    return $products;
}

// Obtener carrito de sesión
$cart = $_SESSION['cart'] ?? [];
$product_ids = array_keys($cart);
$products = getProductsByIds($db, $product_ids);

$total = 0;
$stockErrors = [];
$missingProductIds = array_diff($product_ids, array_column($products, 'id'));
if (!empty($missingProductIds)) {
    $stockErrors[] = 'Uno o más productos del carrito ya no están disponibles.';
}
$cartError = $_SESSION['cart_error'] ?? '';
unset($_SESSION['cart_error']);
if (!empty($missingProductIds)) {
    $cartError = trim($cartError . ' ' . $stockErrors[0]);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once 'includes/header.php'; ?>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Estampa-TLA - Carrito</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>

<section class="cart-page">
    <nav class="breadcrumb">
        <a href="index.php">Home</a> / <span>Carrito</span>
    </nav>

    <h2>Carrito (<?= count($cart) ?> productos)</h2>

    <?php if ($cartError): ?>
        <div class="stock-alert stock-alert-error" role="alert">
            <?= htmlspecialchars($cartError) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($products)) : ?>
        <form method="post" action="update_cart.php">
            <table class="cart-table">
                <thead>
                <tr>
                    <th>Fotografía</th>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th>Eliminar</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product) :
                    $id = $product['id'];
                    $quantity = (int)$cart[$id];
                    $stock = max(0, (int)($product['stock'] ?? 0));
                    $stockMessage = stockMessage($product, $quantity);
                    if ($stockMessage !== '') {
                        $stockErrors[] = $stockMessage;
                    }
                    $subtotal = $product['price'] * $quantity;
                    $total += $subtotal;
                    ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" /></td>
                        <td>
                            <?= htmlspecialchars($product['name']) ?>
                            <?php if ($stock === 0): ?>
                                <span class="stock-status stock-status-out">Agotado</span>
                            <?php elseif ($quantity > $stock): ?>
                                <span class="stock-status stock-status-error">Máximo disponible: <?= $stock ?></span>
                            <?php else: ?>
                                <span class="stock-status">Disponibles: <?= $stock ?></span>
                            <?php endif; ?>
                        </td>
                        <td>$<?= number_format($product['price'], 2) ?></td>
                        <td>
                            <input type="number" name="quantities[<?= htmlspecialchars($id) ?>]" value="<?= $quantity ?>" min="1" max="<?= $stock ?>" />
                        </td>
                        <td class="subtotal">$<?= number_format($subtotal, 2) ?></td>
                        <td><a href="remove_from_cart.php?id=<?= $id ?>" class="remove-item">×</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-actions">
                <button type="submit" class="update-cart">Actualizar Carrito</button>
            </div>
        </form>

        <div class="cart-totals">
            <p>Subtotal: $<?= number_format($total, 2) ?></p>
            <p>Total: $<?= number_format($total * 1.10, 2) ?> <small>(+10% impuestos)</small></p>
            <?php if (!empty($stockErrors)): ?>
                <div class="stock-alert stock-alert-error" role="alert">
                    <strong>No se puede continuar con la compra.</strong>
                    <?= htmlspecialchars(implode(' ', $stockErrors)) ?>
                </div>
                <button type="button" class="checkout-btn btn" disabled>Stock insuficiente</button>
            <?php else: ?>
                <a href="checkout.php" class="checkout-btn btn">Proceder al Pago</a>
            <?php endif; ?>

        </div>

    <?php else : ?>
        <p>Tu carrito está vacío.</p>
    <?php endif; ?>
</section>

</body>
</html>
