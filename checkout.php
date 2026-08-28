<?php
// checkout.php
require_once 'includes/config.php';
require_once 'includes/auth.php';  // Asegura que el usuario esté logueado

// 1) Verificar address_id
if (!isset($_GET['address_id']) || !is_numeric($_GET['address_id'])) {
    header('Location: address.php');
    exit;
}
$address_id = (int)$_GET['address_id'];

// 2) Cargar la dirección
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
$stmt->execute([$address_id, $_SESSION['user']['id']]);
$address = $stmt->fetch();
if (!$address) {
    header('Location: address.php');
    exit;
}

// 3) Cargar carrito
$cart = $_SESSION['cart'] ?? [];
$product_ids = array_keys($cart);
if (empty($product_ids)) {
    header('Location: cart.php');
    exit;
}

// 4) Cargar productos
$placeholders = implode(',', array_fill(0, count($product_ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($product_ids);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5) Calcular totales
$subtotal = 0;
foreach ($products as $p) {
    $subtotal += $p['price'] * $cart[$p['id']];
}
$total = $subtotal + 120; // envío fijo

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once 'includes/header.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pago – Estampa-TLA</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .order-summary { max-width:600px; margin:2rem auto; padding:1.5rem; background:#f4f4f4; border-radius:8px; }
        .order-summary h2 { margin-bottom:1rem; }
        .order-summary ul { list-style:none; padding:0; margin-bottom:1rem; }
        .order-summary li { display:flex; align-items:center; margin-bottom:0.75rem; }
        .order-summary li img { width:60px; height:60px; object-fit:cover; border-radius:4px; margin-right:0.75rem; }
        .order-summary .order-totals div { margin-bottom:0.5rem; }
        .paypal-standard-btn { background:#0070ba;color:#fff;padding:0.75rem 1.5rem;border:none;border-radius:4px;cursor:pointer; }
        .paypal-standard-btn:hover { background:#005c99; }
    </style>
</head>
<body>
<main>
    <section class="order-summary">
        <h2>Resumen de tu pedido</h2>

        <p><strong>Dirección de envío:</strong><br>
            <?= htmlspecialchars($address['first_name'].' '.$address['last_name']) ?><br>
            <?= htmlspecialchars($address['address'].', '.$address['city'].', '.$address['state'].' CP '.$address['zip']) ?><br>
            T: <?= htmlspecialchars($address['phone']) ?>
        </p>

        <ul>
            <?php foreach ($products as $p):
                $qty = $cart[$p['id']];
                ?>
                <li>
                    <img src="<?= htmlspecialchars($p['image'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>">
                    <div>
                        <strong><?= htmlspecialchars($p['name'],ENT_QUOTES) ?></strong><br>
                        Cantidad: <?= $qty ?><br>
                        Subtotal: $<?= number_format($p['price'] * $qty,2) ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <div><strong>Subtotal:</strong> $<?= number_format($subtotal,2) ?></div>
        <div><strong>Envío:</strong> $120.00</div>
        <div><strong>Total:</strong> $<?= number_format($total,2) ?></div>

        <!-- PayPal Payments Standard Form -->
        <form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">
            <input type="hidden" name="cmd" value="_xclick">
            <input type="hidden" name="business" value="sb-fyveq42755301@business.example.com">
            <input type="hidden" name="item_name" value="Pedido #<?= time() ?>">
            <input type="hidden" name="amount" value="<?= number_format($total,2,'.','') ?>">
            <input type="hidden" name="currency_code" value="MXN">
            <input type="hidden" name="return" value="http://localhost/estampa/thankyou.php">
            <input type="hidden" name="notify_url" value="http://localhost/estampa/process_order.php">
            <button type="submit" class="paypal-standard-btn">Pagar con PayPal</button>
        </form>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
