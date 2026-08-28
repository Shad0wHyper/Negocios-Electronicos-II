<?php
require_once 'includes/config.php';

// Función para obtener los productos desde la BD
function getProductsByIds($pdo, $ids) {
    if (empty($ids)) return [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener carrito de sesión
$cart = $_SESSION['cart'] ?? [];
$product_ids = array_keys($cart);
$products = getProductsByIds($pdo, $product_ids);

$total = 0;
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
                    $quantity = $cart[$id];
                    $subtotal = $product['price'] * $quantity;
                    $total += $subtotal;
                    ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" /></td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td>$<?= number_format($product['price'], 2) ?></td>
                        <td>
                            <input type="number" name="quantities[<?= $id ?>]" value="<?= $quantity ?>" min="1" />
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
            <a href="checkout.php" class="checkout-btn btn">Proceder al Pago</a>

        </div>

    <?php else : ?>
        <p>Tu carrito está vacío.</p>
    <?php endif; ?>
</section>

</body>
</html>
