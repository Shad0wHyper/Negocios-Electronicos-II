<?php
// orders.php - Historial y detalle de pedidos del cliente
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/wallet_helpers.php';

$currentUserId = (string)$_SESSION['user']['id'];
$viewOrderId = trim($_GET['view'] ?? '');

$ordersDocs = $db->collection('orders')->where('user_id', '=', $currentUserId)->documents();
$orders = [];
$selectedOrder = null;

foreach ($ordersDocs as $doc) {
    if ($doc->exists()) {
        $o = $doc->data();
        $o['id'] = $doc->id();
        $orders[] = $o;
        if ($viewOrderId !== '' && $o['id'] === $viewOrderId) {
            $selectedOrder = $o;
        }
    }
}

usort($orders, function ($a, $b) {
    return strtotime($b['created_at'] ?? '0') <=> strtotime($a['created_at'] ?? '0');
});

$selectedOrderItems = [];
if ($selectedOrder !== null && !empty($selectedOrder['items']) && is_array($selectedOrder['items'])) {
    foreach ($selectedOrder['items'] as $item) {
        $pid = (string)($item['product_id'] ?? '');
        $pName = 'Producto';
        $pImage = '';
        if ($pid !== '') {
            $pSnap = $db->collection('products')->document($pid)->snapshot();
            if ($pSnap->exists()) {
                $pData = $pSnap->data();
                $pName = $pData['name'] ?? 'Producto';
                $pImage = $pData['image'] ?? '';
            }
        }
        $item['product_name'] = $pName;
        $item['product_image'] = $pImage;
        $selectedOrderItems[] = $item;
    }
}

require_once 'includes/header.php';
?>

<section class="orders-page">
    <div class="wallet-top-nav">
        <a href="profile.php" class="ticket-back-link">&larr; Volver a Mi Perfil</a>
    </div>

    <div class="orders-header">
        <div>
            <h1>Mis Pedidos</h1>
            <p>Consulta el estado, método de pago y detalle de todas tus compras.</p>
        </div>
        <a href="shop.php" class="ticket-new-btn">Ir a la tienda</a>
    </div>

    <?php if ($selectedOrder !== null):
        $statusMeta = getOrderStatusMeta((string)($selectedOrder['status'] ?? 'pending'));
        $itemsSubtotal = 0.0;
        foreach ($selectedOrderItems as $it) {
            $itemsSubtotal += (float)($it['unit_price'] ?? 0) * (int)($it['quantity'] ?? 1);
        }
        $shippingCost = isset($selectedOrder['shipping_cost'])
            ? (float)$selectedOrder['shipping_cost']
            : max(0.0, (float)($selectedOrder['total'] ?? 0) - $itemsSubtotal);
        $shipAddr = $selectedOrder['shipping_address'] ?? null;
    ?>
        <div class="order-detail-panel">
            <div class="order-detail-top">
                <div>
                    <span class="ticket-code">ORDEN <?= htmlspecialchars($selectedOrder['order_code'] ?? $selectedOrder['id'], ENT_QUOTES) ?></span>
                    <h2>Detalle del pedido</h2>
                    <p class="order-detail-date">
                        Realizado el <?= !empty($selectedOrder['created_at']) ? date('d/m/Y H:i', strtotime($selectedOrder['created_at'])) : 'N/A' ?>
                        &middot; Pago: <strong><?= htmlspecialchars(getPaymentMethodLabel($selectedOrder['payment_method'] ?? null), ENT_QUOTES) ?></strong>
                    </p>
                </div>
                <span class="order-status-badge <?= htmlspecialchars($statusMeta['class']) ?>">
                    <?= htmlspecialchars($statusMeta['label']) ?>
                </span>
            </div>

            <div class="order-detail-items">
                <?php foreach ($selectedOrderItems as $item): ?>
                    <div class="order-detail-item">
                        <?php if (!empty($item['product_image'])): ?>
                            <img src="<?= htmlspecialchars($item['product_image'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars($item['product_name'], ENT_QUOTES) ?>">
                        <?php endif; ?>
                        <div class="order-detail-item-info">
                            <strong><?= htmlspecialchars($item['product_name'], ENT_QUOTES) ?></strong>
                            <span><?= (int)($item['quantity'] ?? 1) ?> &times; $<?= number_format((float)($item['unit_price'] ?? 0), 2) ?></span>
                        </div>
                        <div class="order-detail-item-sub">
                            $<?= number_format((float)($item['unit_price'] ?? 0) * (int)($item['quantity'] ?? 1), 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-detail-footer">
                <?php if (is_array($shipAddr)): ?>
                    <div class="order-detail-address">
                        <strong>Envío a:</strong>
                        <p>
                            <?= htmlspecialchars(trim(($shipAddr['first_name'] ?? '') . ' ' . ($shipAddr['last_name'] ?? '')), ENT_QUOTES) ?><br>
                            <?= htmlspecialchars(($shipAddr['address'] ?? '') . ', ' . ($shipAddr['city'] ?? '') . ', ' . ($shipAddr['state'] ?? '') . ' CP ' . ($shipAddr['zip'] ?? ''), ENT_QUOTES) ?>
                        </p>
                    </div>
                <?php endif; ?>
                <div class="order-detail-totals">
                    <div><span>Subtotal:</span> <strong>$<?= number_format($itemsSubtotal, 2) ?></strong></div>
                    <div><span>Envío:</span> <strong>$<?= number_format($shippingCost, 2) ?></strong></div>
                    <div class="order-detail-total-final"><span>Total:</span> <strong>$<?= number_format((float)($selectedOrder['total'] ?? 0), 2) ?></strong></div>
                </div>
            </div>

            <div class="order-detail-support">
                <a href="contact.php#soporte-contacto" class="ticket-action-btn ticket-action-btn-muted">¿Problemas con tu pedido? Contactar a soporte</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($orders)): ?>
        <div class="wallet-history-card">
            <div class="wallet-table-wrap">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Fecha</th>
                            <th>Método de pago</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o):
                            $stMeta = getOrderStatusMeta((string)($o['status'] ?? 'pending'));
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($o['order_code'] ?? $o['id'], ENT_QUOTES) ?></strong></td>
                                <td><?= !empty($o['created_at']) ? date('d/m/Y H:i', strtotime($o['created_at'])) : 'N/A' ?></td>
                                <td><?= htmlspecialchars(getPaymentMethodLabel($o['payment_method'] ?? null), ENT_QUOTES) ?></td>
                                <td><strong>$<?= number_format((float)($o['total'] ?? 0), 2) ?></strong></td>
                                <td>
                                    <span class="order-status-badge <?= htmlspecialchars($stMeta['class']) ?>">
                                        <?= htmlspecialchars($stMeta['label']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="orders.php?view=<?= urlencode($o['id']) ?>" class="ticket-action-btn">Ver detalle</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="ticket-empty-state">
            <p>Aún no has realizado ningún pedido.</p>
            <a href="shop.php" class="ticket-new-btn">Ir a la tienda</a>
        </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
