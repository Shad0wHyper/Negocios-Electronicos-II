<?php
// checkout.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/stock.php';
require_once 'includes/wallet_helpers.php';
require_once 'includes/commerce_helpers.php';

// 1) Verificar address_id
if (!isset($_GET['address_id']) || empty($_GET['address_id'])) {
    header('Location: address.php');
    exit;
}
$address_id = trim($_GET['address_id']);

// 2) Cargar la dirección
$addressDoc = $db->collection('addresses')->document($address_id)->snapshot();
if (!$addressDoc->exists()) {
    header('Location: address.php');
    exit;
}
$address = $addressDoc->data();
if (($address['user_id'] ?? '') !== $_SESSION['user']['id']) {
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

// 4) Cargar productos y validar stock
$products = [];
$stockErrors = [];
foreach ($product_ids as $pid) {
    $p = getProductForStock($db, $pid);
    if ($p) {
        $requested = (int)$cart[$p['id']];
        $message = stockMessage($p, $requested);
        if ($message !== '') {
            $stockErrors[] = $message;
        }
        $products[] = $p;
    } else {
        $stockErrors[] = 'Uno o más productos del carrito ya no están disponibles.';
    }
}

if (!empty($stockErrors)) {
    $_SESSION['cart_error'] = implode(' ', $stockErrors);
    header('Location: cart.php');
    exit;
}

// 5) Calcular totales con promociones e impuestos configurados
$shippingCost = 120.00;
$promotions = getActivePromotions($db);
$taxSettings = getStoreTaxSettings($db);
$pricing = calculateCartTotals($products, $cart, $promotions, $taxSettings, $shippingCost);
$subtotal = $pricing['subtotal'];
$total = $pricing['total'];
$pricingByProduct = [];
foreach ($pricing['items'] as $itemPricing) {
    $pricingByProduct[$itemPricing['product_id']] = $itemPricing;
}

$currentUserId = (string)$_SESSION['user']['id'];
$checkoutError = '';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 6) Procesar pago con Monedero (antes de imprimir HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay_wallet') {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        $checkoutError = 'La sesión del checkout expiró. Recarga e inténtalo de nuevo.';
    } else {
        try {
            $result = processWalletCheckout($db, $currentUserId, $_SESSION['user'], $address, $address_id, $cart, $shippingCost);
            $_SESSION['cart'] = [];
            header('Location: order_confirmation.php?id=' . urlencode($result['order_id']));
            exit;
        } catch (Throwable $e) {
            $checkoutError = $e->getMessage();
        }
    }
}

$walletBalance = getUserWalletBalance($db, $currentUserId);
$balanceAfterPayment = round($walletBalance - $total, 2);
$hasEnoughBalance = ($walletBalance >= $total);

require_once 'includes/header.php';
?>

<section class="checkout-page">
    <nav class="breadcrumb">
        <a href="index.php">Home</a> / <a href="cart.php">Carrito</a> / <a href="address.php">Dirección</a> / <span>Checkout</span>
    </nav>

    <div class="checkout-header">
        <h1>Finalizar Compra</h1>
        <p>Revisa tu dirección de entrega, los productos y selecciona tu método de pago.</p>
    </div>

    <?php if ($checkoutError !== ''): ?>
        <div class="contact-alert-error" role="alert">
            <?= htmlspecialchars($checkoutError, ENT_QUOTES) ?>
        </div>
    <?php endif; ?>

    <div class="checkout-grid">
        <!-- Columna izquierda: Dirección y Resumen -->
        <div class="checkout-left">
            <div class="checkout-card">
                <div class="checkout-card-title-row">
                    <h2>1. Datos de entrega</h2>
                    <a href="address.php" class="checkout-edit-link">Cambiar dirección</a>
                </div>
                <p class="checkout-address-text">
                    <strong><?= htmlspecialchars(($address['first_name'] ?? '') . ' ' . ($address['last_name'] ?? ''), ENT_QUOTES) ?></strong><br>
                    <?= htmlspecialchars(($address['address'] ?? '') . ', ' . ($address['city'] ?? '') . ', ' . ($address['state'] ?? '') . ' CP ' . ($address['zip'] ?? ''), ENT_QUOTES) ?><br>
                    Tel: <?= htmlspecialchars($address['phone'] ?? '', ENT_QUOTES) ?>
                </p>
            </div>

            <div class="checkout-card">
                <h2>2. Resumen de tu pedido</h2>
                <ul class="checkout-items-list">
                    <?php foreach ($products as $p):
                        $qty = (int)$cart[$p['id']];
                        $itemPricing = $pricingByProduct[$p['id']] ?? [];
                        $lineTotal = $itemPricing['line_total'] ?? ((float)$p['price'] * $qty);
                    ?>
                        <li class="checkout-item-row">
                            <img src="<?= htmlspecialchars($p['image'] ?? '', ENT_QUOTES) ?>" alt="<?= htmlspecialchars($p['name'] ?? '', ENT_QUOTES) ?>">
                            <div class="checkout-item-info">
                                <strong><?= htmlspecialchars($p['name'] ?? '', ENT_QUOTES) ?></strong>
                                <span>Cantidad: <?= $qty ?> &times; $<?= number_format((float)$p['price'], 2) ?></span>
                                <?php if (!empty($itemPricing['promotion_name'])): ?><span class="text-green-700"><?= htmlspecialchars($itemPricing['promotion_name'], ENT_QUOTES) ?>: −$<?= number_format($itemPricing['discount_per_unit'] * $qty, 2) ?></span><?php endif; ?>
                            </div>
                            <div class="checkout-item-total">$<?= number_format($lineTotal, 2) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="checkout-totals">
                    <div class="checkout-total-row">
                        <span>Subtotal</span>
                        <span>$<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <?php if ($pricing['discount_total'] > 0): ?>
                    <div class="checkout-total-row">
                        <span>Descuentos</span>
                        <span>−$<?= number_format($pricing['discount_total'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="checkout-total-row">
                        <span><?= htmlspecialchars($pricing['tax_name'], ENT_QUOTES) ?> (<?= number_format($pricing['tax_rate'], 2) ?>%)</span>
                        <span>$<?= number_format($pricing['tax_amount'], 2) ?></span>
                    </div>
                    <div class="checkout-total-row">
                        <span>Envío estándar</span>
                        <span>$<?= number_format($shippingCost, 2) ?></span>
                    </div>
                    <div class="checkout-total-row checkout-total-final">
                        <span>Total a pagar</span>
                        <span>$<?= number_format($total, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna derecha: Métodos de pago -->
        <div class="checkout-right">
            <div class="checkout-card">
                <h2>3. Método de pago</h2>

                <div class="payment-options">
                    <label class="payment-option is-selected" data-method="monedero">
                        <input type="radio" name="payment_selector" value="monedero" checked>
                        <div>
                            <strong>Monedero Electrónico Xanarchy</strong>
                            <span>Saldo disponible: $<?= number_format($walletBalance, 2) ?></span>
                        </div>
                    </label>

                    <label class="payment-option" data-method="paypal">
                        <input type="radio" name="payment_selector" value="paypal">
                        <div>
                            <strong>PayPal</strong>
                            <span>Pago externo mediante PayPal Sandbox</span>
                        </div>
                    </label>
                </div>

                <!-- Panel Pago con Monedero -->
                <div id="panel-monedero" class="payment-panel">
                    <div class="wallet-summary-box">
                        <div class="wallet-summary-row">
                            <span>Saldo disponible:</span>
                            <strong>$<?= number_format($walletBalance, 2) ?></strong>
                        </div>
                        <div class="wallet-summary-row">
                            <span>Total a pagar:</span>
                            <strong>$<?= number_format($total, 2) ?></strong>
                        </div>
                        <div class="wallet-summary-row wallet-summary-after <?= $hasEnoughBalance ? 'is-positive' : 'is-negative' ?>">
                            <span>Saldo después del pago:</span>
                            <strong>$<?= number_format($balanceAfterPayment, 2) ?></strong>
                        </div>
                    </div>

                    <?php if ($hasEnoughBalance): ?>
                        <form method="post" action="checkout.php?address_id=<?= urlencode($address_id) ?>">
                            <input type="hidden" name="action" value="pay_wallet">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
                            <button type="submit" class="wallet-confirm-btn">Confirmar pago con Monedero</button>
                        </form>
                    <?php else: ?>
                        <div class="wallet-insufficient-box">
                            <p>Tu saldo actual no es suficiente para cubrir el total de esta compra.</p>
                            <a href="wallet.php?return=checkout&address_id=<?= urlencode($address_id) ?>" class="wallet-recharge-link">
                                Recargar monedero ahora
                            </a>
                        </div>
                        <button type="button" class="wallet-confirm-btn" disabled>Saldo insuficiente</button>
                    <?php endif; ?>
                </div>

                <!-- Panel Pago con PayPal -->
                <div id="panel-paypal" class="payment-panel" style="display: none;">
                    <p class="paypal-panel-note">Serás redirigido a la pasarela de PayPal Sandbox para completar tu pago.</p>
                    <form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">
                        <input type="hidden" name="cmd" value="_xclick">
                        <input type="hidden" name="business" value="sb-fyveq42755301@business.example.com">
                        <input type="hidden" name="item_name" value="Pedido #<?= time() ?>">
                        <input type="hidden" name="amount" value="<?= number_format($total, 2, '.', '') ?>">
                        <input type="hidden" name="currency_code" value="MXN">
                        <input type="hidden" name="return" value="http://localhost/estampa/thankyou.php">
                        <input type="hidden" name="notify_url" value="http://localhost/estampa/process_order.php">
                        <button type="submit" class="paypal-standard-btn">Pagar con PayPal</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const radios = document.querySelectorAll('input[name="payment_selector"]');
        const options = document.querySelectorAll('.payment-option');
        const panelMonedero = document.getElementById('panel-monedero');
        const panelPaypal = document.getElementById('panel-paypal');

        radios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                options.forEach(function (opt) {
                    opt.classList.toggle('is-selected', opt.dataset.method === radio.value);
                });
                if (radio.value === 'monedero') {
                    panelMonedero.style.display = 'block';
                    panelPaypal.style.display = 'none';
                } else {
                    panelMonedero.style.display = 'none';
                    panelPaypal.style.display = 'block';
                }
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
