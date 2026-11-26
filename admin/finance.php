<?php
use Google\Cloud\Firestore\Transaction;

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/commerce_helpers.php';

$error = '';
$success = '';
$taxSettings = getStoreTaxSettings($db);
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'La sesión del formulario expiró. Recarga e inténtalo de nuevo.';
    } elseif (($_POST['action'] ?? '') === 'save_tax') {
        $rate = filter_var($_POST['rate'] ?? null, FILTER_VALIDATE_FLOAT);
        $name = trim((string)($_POST['tax_name'] ?? 'IVA'));
        if ($rate === false || $rate < 0 || $rate > 100 || $name === '') {
            $error = 'Indica un nombre y una tasa de impuesto entre 0 y 100%.';
        } else {
            $db->collection('store_settings')->document('taxes')->set([
                'name' => $name,
                'rate' => round((float)$rate, 2),
                'enabled' => isset($_POST['tax_enabled']),
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => (string)($_SESSION['user']['id'] ?? ''),
            ], ['merge' => true]);
            $success = 'Configuración de impuestos guardada.';
        }
    } elseif (($_POST['action'] ?? '') === 'adjust_wallet') {
        $userId = trim((string)($_POST['user_id'] ?? ''));
        $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($userId === '' || $amount === false || abs((float)$amount) < 0.01 || abs((float)$amount) > 50000 || $reason === '') {
            $error = 'Indica un cliente, un ajuste entre $0.01 y $50,000.00 y su motivo.';
        } else {
            try {
                $userRef = $db->collection('users')->document($userId);
                $transactionRef = $db->collection('wallet_transactions')->newDocument();
                $adminId = (string)($_SESSION['user']['id'] ?? '');
                $adjustment = round((float)$amount, 2);
                $db->runTransaction(function (Transaction $transaction) use ($userRef, $transactionRef, $userId, $adjustment, $reason, $adminId) {
                    $userSnapshot = $transaction->snapshot($userRef);
                    if (!$userSnapshot->exists()) {
                        throw new RuntimeException('No se encontró el cliente seleccionado.');
                    }
                    $userData = $userSnapshot->data();
                    $before = round(max(0, (float)($userData['wallet_balance'] ?? 0)), 2);
                    $after = round($before + $adjustment, 2);
                    if ($after < 0) {
                        throw new RuntimeException('El ajuste no puede dejar el monedero con saldo negativo.');
                    }
                    $transaction->set($userRef, ['wallet_balance' => $after], ['merge' => true]);
                    $transaction->create($transactionRef, [
                        'user_id' => $userId,
                        'type' => 'ajuste_administrativo',
                        'amount' => $adjustment,
                        'balance_before' => $before,
                        'balance_after' => $after,
                        'method' => 'Ajuste administrativo',
                        'reference' => $transactionRef->id(),
                        'order_id' => '',
                        'description' => $reason,
                        'admin_id' => $adminId,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                });
                $success = 'Ajuste aplicado y registrado en el historial del monedero.';
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }
        }
    }
}
$taxSettings = getStoreTaxSettings($db);
$users = [];
foreach ($db->collection('users')->where('role', '=', 'customer')->documents() as $document) {
    if ($document->exists()) {
        $user = $document->data();
        $user['id'] = $document->id();
        $users[] = $user;
    }
}
$usersById = [];
foreach ($users as $user) {
    $usersById[$user['id']] = $user['name'] ?? 'Cliente';
}
$transactions = [];
$credited = 0.0;
$debited = 0.0;
foreach ($db->collection('wallet_transactions')->documents() as $document) {
    if (!$document->exists()) {
        continue;
    }
    $entry = $document->data();
    $entry['id'] = $document->id();
    $entry['customer_name'] = $usersById[$entry['user_id'] ?? ''] ?? 'Cliente';
    if ((float)($entry['amount'] ?? 0) >= 0) {
        $credited += (float)($entry['amount'] ?? 0);
    } else {
        $debited += abs((float)$entry['amount']);
    }
    $transactions[] = $entry;
}
usort($transactions, static fn($a, $b) => strtotime($b['created_at'] ?? '0') <=> strtotime($a['created_at'] ?? '0'));
require_once __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-7xl">
    <div class="mb-6"><h2 class="text-3xl font-bold">Impuestos y monedero</h2><p class="mt-2 text-sm text-gray-500">Configura los impuestos del checkout y administra los saldos con un historial auditable.</p></div>
    <?php if ($error): ?><div class="mb-4 rounded-md bg-red-50 p-4 text-red-700"><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="mb-4 rounded-md bg-green-50 p-4 text-green-700"><?= htmlspecialchars($success, ENT_QUOTES) ?></div><?php endif; ?>
    <div class="grid gap-6 lg:grid-cols-2">
        <form method="post" class="space-y-4 rounded-lg border bg-white p-6 shadow-sm">
            <h3 class="text-xl font-semibold">Configuración de impuestos</h3><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>"><input type="hidden" name="action" value="save_tax">
            <label class="block text-sm font-medium">Nombre del impuesto<input name="tax_name" required maxlength="40" value="<?= htmlspecialchars($taxSettings['name'], ENT_QUOTES) ?>" class="mt-1 w-full rounded-md border-gray-300"></label>
            <label class="block text-sm font-medium">Tasa (%)<input name="rate" type="number" min="0" max="100" step="0.01" value="<?= number_format($taxSettings['rate'], 2, '.', '') ?>" required class="mt-1 w-full rounded-md border-gray-300"></label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="tax_enabled" <?= $taxSettings['enabled'] ? 'checked' : '' ?>> Aplicar automáticamente en el checkout</label>
            <p class="text-xs text-gray-500">La tasa predeterminada es 16%. El impuesto se calcula sobre el subtotal después de descuentos.</p>
            <button class="rounded-md bg-blue-600 px-5 py-2 font-semibold text-white hover:bg-blue-700">Guardar impuesto</button>
        </form>
        <form method="post" class="space-y-4 rounded-lg border bg-white p-6 shadow-sm">
            <h3 class="text-xl font-semibold">Ajuste administrativo</h3><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>"><input type="hidden" name="action" value="adjust_wallet">
            <label class="block text-sm font-medium">Cliente<select name="user_id" required class="mt-1 w-full rounded-md border-gray-300"><option value="">Seleccionar cliente</option><?php foreach ($users as $user): ?><option value="<?= htmlspecialchars($user['id'], ENT_QUOTES) ?>"><?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES) ?> — $<?= number_format((float)($user['wallet_balance'] ?? 0), 2) ?></option><?php endforeach; ?></select></label>
            <label class="block text-sm font-medium">Monto (+ abono / − cargo)<input name="amount" type="number" step="0.01" min="-50000" max="50000" required class="mt-1 w-full rounded-md border-gray-300"></label>
            <label class="block text-sm font-medium">Motivo<input name="reason" maxlength="200" required class="mt-1 w-full rounded-md border-gray-300"></label>
            <p class="text-xs text-gray-500">El saldo nunca puede ser negativo. Cada cambio genera un movimiento de auditoría.</p>
            <button class="rounded-md bg-emerald-700 px-5 py-2 font-semibold text-white hover:bg-emerald-800">Aplicar ajuste</button>
        </form>
    </div>
    <div class="my-8 grid gap-4 sm:grid-cols-2"><div class="rounded-lg border bg-white p-5"><p class="text-sm text-gray-500">Recargas y abonos registrados</p><p class="mt-2 text-2xl font-bold text-emerald-700">$<?= number_format($credited, 2) ?></p></div><div class="rounded-lg border bg-white p-5"><p class="text-sm text-gray-500">Consumos y cargos registrados</p><p class="mt-2 text-2xl font-bold text-red-700">$<?= number_format($debited, 2) ?></p></div></div>
    <section class="overflow-x-auto rounded-lg border bg-white"><h3 class="border-b p-5 text-lg font-semibold">Historial de recargas, consumos y ajustes</h3><table class="w-full text-left text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-600"><tr><th class="p-4">Fecha</th><th class="p-4">Cliente</th><th class="p-4">Tipo / concepto</th><th class="p-4">Método</th><th class="p-4">Monto</th><th class="p-4">Saldo resultante</th></tr></thead><tbody>
    <?php foreach (array_slice($transactions, 0, 100) as $entry): ?><tr class="border-t"><td class="p-4"><?= htmlspecialchars($entry['created_at'] ?? '', ENT_QUOTES) ?></td><td class="p-4"><?= htmlspecialchars($entry['customer_name'], ENT_QUOTES) ?></td><td class="p-4"><?= htmlspecialchars(($entry['type'] ?? 'movimiento') . ' — ' . ($entry['description'] ?? ''), ENT_QUOTES) ?></td><td class="p-4"><?= htmlspecialchars($entry['method'] ?? '', ENT_QUOTES) ?></td><td class="p-4 font-semibold <?= (float)($entry['amount'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-red-700' ?>">$<?= number_format((float)($entry['amount'] ?? 0), 2) ?></td><td class="p-4">$<?= number_format((float)($entry['balance_after'] ?? 0), 2) ?></td></tr><?php endforeach; ?>
    <?php if (!$transactions): ?><tr><td colspan="6" class="p-8 text-center text-gray-500">No hay movimientos de monedero.</td></tr><?php endif; ?>
    </tbody></table></section>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
