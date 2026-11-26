<?php
// wallet.php - Mi Monedero Electrónico (saldo, recargas simuladas e historial)
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/wallet_helpers.php';

$currentUserId = (string)$_SESSION['user']['id'];
$returnTarget = trim($_GET['return'] ?? '');
$addressId = trim($_GET['address_id'] ?? '');

$allowedMethods = [
    'Tarjeta de crédito / débito',
    'PayPal',
    'Transferencia bancaria',
];

$walletError = '';
$amountInput = $_POST['amount'] ?? '';
$selectedMethod = $_POST['method'] ?? $allowedMethods[0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'recharge') {
    $amount = (float)($_POST['amount'] ?? 0);
    if (!in_array($selectedMethod, $allowedMethods, true)) {
        $selectedMethod = $allowedMethods[0];
    }

    try {
        $res = rechargeUserWallet($db, $currentUserId, $amount, $selectedMethod);
        $redirectUrl = 'wallet.php?recharged=1&amount=' . urlencode((string)$res['amount']) . '&ref=' . urlencode($res['reference']);
        if ($returnTarget === 'checkout' && $addressId !== '') {
            $redirectUrl .= '&return=checkout&address_id=' . urlencode($addressId);
        }
        header('Location: ' . $redirectUrl);
        exit;
    } catch (Throwable $e) {
        $walletError = $e->getMessage();
    }
}

$walletBalance = getUserWalletBalance($db, $currentUserId);

$txDocs = $db->collection('wallet_transactions')->where('user_id', '=', $currentUserId)->documents();
$transactions = [];
foreach ($txDocs as $doc) {
    if ($doc->exists()) {
        $tx = $doc->data();
        $tx['id'] = $doc->id();
        $transactions[] = $tx;
    }
}
usort($transactions, function ($a, $b) {
    return strtotime($b['created_at'] ?? '0') <=> strtotime($a['created_at'] ?? '0');
});

$rechargedSuccess = (($_GET['recharged'] ?? '') === '1');
$rechargedAmount = (float)($_GET['amount'] ?? 0);
$rechargedRef = trim($_GET['ref'] ?? '');

require_once 'includes/header.php';
?>

<section class="wallet-page">
    <div class="wallet-top-nav">
        <a href="profile.php" class="ticket-back-link">&larr; Volver a Mi Perfil</a>
    </div>

    <?php if ($returnTarget === 'checkout' && $addressId !== ''): ?>
        <div class="wallet-checkout-banner">
            <div>
                <strong>Recarga para completar tu compra</strong>
                <p>Cuando tengas saldo suficiente, regresa directamente al checkout con tu dirección guardada.</p>
            </div>
            <a href="checkout.php?address_id=<?= urlencode($addressId) ?>" class="ticket-new-btn">&larr; Volver al Checkout</a>
        </div>
    <?php endif; ?>

    <?php if ($rechargedSuccess): ?>
        <div class="contact-alert-success">
            <strong>¡Recarga simulada exitosa!</strong>
            <p>
                Se abonaron <strong>$<?= number_format($rechargedAmount, 2) ?></strong> a tu monedero
                <?= $rechargedRef !== '' ? '(Referencia: <strong>' . htmlspecialchars($rechargedRef, ENT_QUOTES) . '</strong>)' : '' ?>.
            </p>
        </div>
    <?php endif; ?>

    <?php if ($walletError !== ''): ?>
        <div class="contact-alert-error">
            <?= htmlspecialchars($walletError, ENT_QUOTES) ?>
        </div>
    <?php endif; ?>

    <div class="wallet-grid">
        <!-- Tarjeta de saldo actual -->
        <div class="wallet-balance-card">
            <span class="wallet-kicker">MONEDERO ELECTRÓNICO XANARCHY</span>
            <h1>Mi Monedero</h1>
            <div class="wallet-balance-amount">$<?= number_format($walletBalance, 2) ?> <small>MXN</small></div>
            <p class="wallet-balance-note">
                Usa tu saldo disponible para pagar tus compras al instante en el checkout o simula recargas de prueba cuando lo necesites.
            </p>
        </div>

        <!-- Formulario de recarga simulada -->
        <div class="wallet-recharge-card">
            <h2>Recargar monedero</h2>
            <p class="wallet-card-sub">Simulación de abono inmediato al saldo de tu cuenta.</p>

            <form method="post" action="wallet.php<?= ($returnTarget === 'checkout' && $addressId !== '') ? '?return=checkout&address_id=' . urlencode($addressId) : '' ?>">
                <input type="hidden" name="action" value="recharge">

                <div class="wallet-field-group">
                    <label>Monto rápido</label>
                    <div class="wallet-quick-btns">
                        <button type="button" class="wallet-quick-btn" data-amount="100">$100</button>
                        <button type="button" class="wallet-quick-btn" data-amount="200">$200</button>
                        <button type="button" class="wallet-quick-btn" data-amount="500">$500</button>
                        <button type="button" class="wallet-quick-btn" data-amount="1000">$1,000</button>
                    </div>
                </div>

                <div class="wallet-field-group">
                    <label for="amount">Otro monto ($10.00 – $50,000.00)</label>
                    <input type="number" id="amount" name="amount" min="10" max="50000" step="0.01" placeholder="Ej. 500.00" value="<?= htmlspecialchars((string)$amountInput, ENT_QUOTES) ?>" required>
                </div>

                <div class="wallet-field-group">
                    <label>Método de pago simulado</label>
                    <div class="wallet-method-list">
                        <?php foreach ($allowedMethods as $idx => $method): ?>
                            <label class="wallet-method-item">
                                <input type="radio" name="method" value="<?= htmlspecialchars($method, ENT_QUOTES) ?>" <?= ($selectedMethod === $method || ($idx === 0 && !in_array($selectedMethod, $allowedMethods, true))) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($method, ENT_QUOTES) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="wallet-confirm-btn">Recargar saldo</button>
            </form>
        </div>
    </div>

    <!-- Historial de movimientos -->
    <div class="wallet-history-card">
        <div class="wallet-history-header">
            <h2>Historial de movimientos</h2>
            <span><?= count($transactions) ?> <?= count($transactions) === 1 ? 'movimiento' : 'movimientos' ?></span>
        </div>

        <?php if (!empty($transactions)): ?>
            <div class="wallet-table-wrap">
                <table class="wallet-history-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Descripción / Referencia</th>
                            <th>Método</th>
                            <th>Monto</th>
                            <th>Saldo resultante</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx):
                            $amt = (float)($tx['amount'] ?? 0);
                            $isPositive = ($amt >= 0);
                        ?>
                            <tr>
                                <td><?= !empty($tx['created_at']) ? date('d/m/Y H:i', strtotime($tx['created_at'])) : 'N/A' ?></td>
                                <td>
                                    <span class="wallet-type-badge <?= $isPositive ? 'is-in' : 'is-out' ?>">
                                        <?= $isPositive ? 'Recarga' : 'Compra' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="wallet-tx-desc"><?= htmlspecialchars((string)($tx['description'] ?? ''), ENT_QUOTES) ?></div>
                                    <?php if (!empty($tx['reference'])): ?>
                                        <small class="wallet-tx-ref">Ref: <?= htmlspecialchars($tx['reference'], ENT_QUOTES) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string)($tx['method'] ?? 'Monedero'), ENT_QUOTES) ?></td>
                                <td class="<?= $isPositive ? 'wallet-amount-pos' : 'wallet-amount-neg' ?>">
                                    <?= $isPositive ? '+' : '-' ?>$<?= number_format(abs($amt), 2) ?>
                                </td>
                                <td><strong>$<?= number_format((float)($tx['balance_after'] ?? 0), 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="ticket-empty-state">
                <p>Aún no tienes movimientos en tu monedero. Realiza una recarga simulada para comenzar.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const amountInput = document.getElementById('amount');
        const quickBtns = document.querySelectorAll('.wallet-quick-btn');
        quickBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                amountInput.value = btn.dataset.amount;
                quickBtns.forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
