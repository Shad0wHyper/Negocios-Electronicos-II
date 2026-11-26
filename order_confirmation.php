<?php
// order_confirmation.php - Confirmación de compra del cliente
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/wallet_helpers.php';

$orderId = trim($_GET['id'] ?? '');
if ($orderId === '') {
    header('Location: orders.php');
    exit;
}

$orderSnapshot = $db->collection('orders')->document($orderId)->snapshot();
if (!$orderSnapshot->exists()) {
    header('Location: orders.php');
    exit;
}

$order = $orderSnapshot->data();
$order['id'] = $orderSnapshot->id();

$currentUserId = (string)$_SESSION['user']['id'];
if ((string)($order['user_id'] ?? '') !== $currentUserId) {
    header('Location: orders.php');
    exit;
}

$orderCode = $order['order_code'] ?? $order['id'];
$paymentMethod = $order['payment_method'] ?? 'paypal';
$paymentLabel = getPaymentMethodLabel($paymentMethod);
$walletBalance = ($paymentMethod === 'monedero') ? getUserWalletBalance($db, $currentUserId) : null;

$orderItems = [];
if (!empty($order['items']) && is_array($order['items'])) {
    foreach ($order['items'] as $item) {
        $pid = (string)($item['product_id'] ?? '');
        $pName = 'Producto';
        if ($pid !== '') {
            $pSnap = $db->collection('products')->document($pid)->snapshot();
            if ($pSnap->exists()) {
                $pName = $pSnap->data()['name'] ?? 'Producto';
            }
        }
        $item['product_name'] = $pName;
        $orderItems[] = $item;
    }
}

require_once 'includes/header.php';
?>

<section class="order-confirm-page">
    <div class="order-confirm-card">
        <div class="order-confirm-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <h1>¡Compra realizada con éxito!</h1>
        <p class="order-confirm-subtitle">
            Hemos registrado tu pedido y el inventario se ha actualizado correctamente.
        </p>

        <div class="order-confirm-summary">
            <div class="order-confirm-row">
                <span>Orden:</span>
                <strong><?= htmlspecialchars($orderCode, ENT_QUOTES) ?></strong>
            </div>
            <div class="order-confirm-row">
                <span>Método de pago:</span>
                <strong><?= htmlspecialchars($paymentLabel, ENT_QUOTES) ?></strong>
            </div>
            <div class="order-confirm-row">
                <span>Total pagado:</span>
                <strong>$<?= number_format((float)($order['total'] ?? 0), 2) ?></strong>
            </div>
            <?php if ($walletBalance !== null): ?>
                <div class="order-confirm-row">
                    <span>Saldo actual en Monedero:</span>
                    <strong class="text-balance-ok">$<?= number_format($walletBalance, 2) ?></strong>
                </div>
            <?php endif; ?>
            <div class="order-confirm-row">
                <span>Correo de confirmación:</span>
                <strong><?= htmlspecialchars((string)($_SESSION['user']['email'] ?? ''), ENT_QUOTES) ?></strong>
            </div>
        </div>

        <?php if (!empty($orderItems)): ?>
            <div class="order-confirm-items">
                <h3>Artículos incluidos</h3>
                <ul>
                    <?php foreach ($orderItems as $item): ?>
                        <li>
                            <span><?= htmlspecialchars($item['product_name'], ENT_QUOTES) ?> &times; <?= (int)($item['quantity'] ?? 1) ?></span>
                            <strong>$<?= number_format((float)($item['unit_price'] ?? 0) * (int)($item['quantity'] ?? 1), 2) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="order-confirm-actions">
            <a href="orders.php?view=<?= urlencode($order['id']) ?>" class="order-btn-primary">Ver mi pedido</a>
            <a href="shop.php" class="order-btn-secondary">Seguir comprando</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
