<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

$error = '';
$selectedClient = $_POST['client_id'] ?? '';
$quantities = $_POST['quantity'] ?? [];
$customers = $products = [];
foreach ($db->collection('users')->where('role', '=', 'customer')->documents() as $doc) { if ($doc->exists()) { $v = $doc->data(); $v['id'] = $doc->id(); $customers[] = $v; } }
foreach ($db->collection('products')->orderBy('name')->documents() as $doc) { if ($doc->exists()) { $v = $doc->data(); $v['id'] = $doc->id(); $products[] = $v; } }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    $items = []; $total = 0;
    foreach ($products as $product) {
        $quantity = max(0, (int)($quantities[$product['id']] ?? 0));
        if ($quantity > (int)($product['stock'] ?? 0)) { $error = 'La cantidad solicitada de "' . ($product['name'] ?? 'producto') . '" supera el stock disponible.'; break; }
        if ($quantity > 0) { $price = (float)($product['price'] ?? 0); $items[] = ['product_id' => $product['id'], 'quantity' => $quantity, 'unit_price' => $price]; $total += $price * $quantity; }
    }
    if (!$selectedClient) $error = 'Selecciona un cliente para continuar.';
    elseif (!$items && !$error) $error = 'Agrega al menos un producto a la orden.';
    if (!$error) {
        $client = current(array_filter($customers, fn($c) => $c['id'] === $selectedClient));
        if ($client === false) {
            $error = 'El cliente seleccionado no existe.';
        }
    }
    if (!$error) {
        $orderRef = $db->collection('orders')->add(['user_id' => $selectedClient, 'customer_name' => $client['name'] ?? 'Cliente', 'items' => $items, 'total' => $total, 'status' => 'paid', 'source' => 'erp_admin', 'created_at' => date('Y-m-d H:i:s')]);
        foreach ($items as $item) {
            $ref = $db->collection('products')->document($item['product_id']); $snapshot = $ref->snapshot(); $stock = (int)($snapshot->data()['stock'] ?? 0);
            $ref->set(['stock' => $stock - $item['quantity']], ['merge' => true]);
            $db->collection('scm_movements')->add(['product_id' => $item['product_id'], 'type' => 'Salida', 'quantity' => $item['quantity'], 'reason' => 'Orden ERP ' . $orderRef->id(), 'user_id' => $_SESSION['user']['id'] ?? '', 'created_at' => date('Y-m-d H:i:s')]);
        }
        header('Location: view_order.php?id=' . urlencode($orderRef->id())); exit;
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<div class="max-w-5xl mx-auto">
    <div class="mb-8"><a href="erp.php" class="text-sm text-gray-500 hover:text-blue-600">← Dashboard ERP</a><h2 class="mt-3 text-3xl font-bold">Nueva orden</h2><p class="mt-2 text-sm text-gray-500">Selecciona el cliente, agrega productos y confirma el movimiento de inventario.</p></div>
    <?php if ($error): ?><div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post" class="space-y-6">
        <section class="rounded-lg border bg-white p-6 shadow-sm"><div class="mb-5 flex items-center gap-3"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 font-bold text-white">1</span><h3 class="text-lg font-semibold">Cliente</h3></div><select name="client_id" required class="w-full rounded-md border-gray-300"><option value="">Seleccionar cliente...</option><?php foreach ($customers as $customer): ?><option value="<?php echo htmlspecialchars($customer['id']); ?>" <?php echo $selectedClient === $customer['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($customer['name'] ?? ''); ?> — <?php echo htmlspecialchars($customer['email'] ?? ''); ?></option><?php endforeach; ?></select><a href="add_user.php" class="mt-3 inline-block text-sm font-semibold text-blue-600">+ Registrar nuevo cliente</a></section>
        <section class="rounded-lg border bg-white p-6 shadow-sm"><div class="mb-5 flex items-center gap-3"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 font-bold text-white">2</span><h3 class="text-lg font-semibold">Productos y cantidades</h3></div><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-gray-50 text-xs uppercase"><tr><th class="px-4 py-3">Producto</th><th class="px-4 py-3">Stock</th><th class="px-4 py-3">Precio</th><th class="px-4 py-3 w-32">Cantidad</th></tr></thead><tbody><?php foreach ($products as $product): ?><tr class="border-b"><td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($product['name'] ?? ''); ?></td><td class="px-4 py-3"><?php echo (int)($product['stock'] ?? 0); ?></td><td class="px-4 py-3">$<?php echo number_format((float)($product['price'] ?? 0), 2); ?></td><td class="px-4 py-3"><input type="number" name="quantity[<?php echo htmlspecialchars($product['id']); ?>]" min="0" max="<?php echo (int)($product['stock'] ?? 0); ?>" value="<?php echo (int)($quantities[$product['id']] ?? 0); ?>" class="w-24 rounded-md border-gray-300"></td></tr><?php endforeach; ?></tbody></table></div></section>
        <section class="rounded-lg border bg-white p-6 shadow-sm"><div class="mb-5 flex items-center gap-3"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 font-bold text-white">3</span><h3 class="text-lg font-semibold">Confirmar orden</h3></div><p class="text-sm text-gray-500">Al confirmar se registra la orden como pagada y se descuenta el inventario automáticamente.</p><div class="mt-5 flex justify-end gap-3"><a href="erp.php" class="rounded-md border px-5 py-2 text-sm font-semibold text-gray-700">Cancelar</a><button name="confirm_order" value="1" class="rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Confirmar orden</button></div></section>
    </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
