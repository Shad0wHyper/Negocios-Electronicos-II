<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/scm_functions.php';
$error = '';
$products = []; $suppliers = [];
foreach ($db->collection('products')->documents() as $doc) if ($doc->exists()) { $p = $doc->data(); $products[$doc->id()] = $p; }
foreach ($db->collection('scm_suppliers')->documents() as $doc) if ($doc->exists()) $suppliers[$doc->id()] = $doc->data()['name'] ?? '';
$prefillProductId = trim($_GET['product_id'] ?? '');
$prefillQuantity = max(1, (int)($_GET['quantity'] ?? 1));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = trim($_POST['product_id'] ?? ''); $quantity = (int)($_POST['quantity'] ?? 0); $supplierId = trim($_POST['supplier_id'] ?? '');
    if (!isset($products[$productId]) || $quantity < 1) $error = 'Selecciona un producto y una cantidad válida.';
    else {
        $db->collection('scm_orders')->add(['folio' => 'PC-' . date('ymdHis'), 'product_id' => $productId, 'quantity' => $quantity, 'quantity_received' => 0, 'type' => $_POST['type'] ?? 'Reposición', 'strategy' => (($products[$productId]['strategy'] ?? 'PUSH') === 'PULL' ? 'PULL' : 'PUSH'), 'origen' => 'MANUAL', 'supplier_id' => $supplierId, 'supplier_name' => $suppliers[$supplierId] ?? '', 'status' => 'Pendiente', 'entrada_registrada' => false, 'entry_registered' => false, 'notes' => trim($_POST['notes'] ?? ''), 'created_at' => date('Y-m-d H:i:s'), 'user_name' => $_SESSION['user']['name'] ?? 'Admin']);
        header('Location: scm_orders.php'); exit;
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<div class="max-w-3xl mx-auto"><h2 class="text-3xl font-bold mb-8">Nuevo pedido de reposición</h2><?php if ($error): ?><div class="bg-red-100 text-red-800 rounded px-4 py-3 mb-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?><form method="post" class="bg-white border rounded-lg p-8 space-y-6">
<div><label class="block text-sm font-medium">Producto</label><select name="product_id" required class="mt-1 w-full rounded border-gray-300"><option value="">Selecciona un producto</option><?php foreach ($products as $id => $p): ?><option value="<?php echo htmlspecialchars($id); ?>" <?php echo $prefillProductId === $id ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['name'] ?? ''); ?> (stock <?php echo (int)($p['stock'] ?? 0); ?>)</option><?php endforeach; ?></select></div>
<div class="grid md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium">Cantidad</label><input type="number" name="quantity" min="1" value="<?php echo $prefillQuantity; ?>" required class="mt-1 w-full rounded border-gray-300"></div><div><label class="block text-sm font-medium">Tipo</label><select name="type" class="mt-1 w-full rounded border-gray-300"><option>Reposición</option><option>Compra</option><option>Urgente</option></select></div></div>
<div><label class="block text-sm font-medium">Proveedor</label><select name="supplier_id" class="mt-1 w-full rounded border-gray-300"><option value="">Selecciona un proveedor</option><?php foreach ($suppliers as $id => $name): ?><option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($name); ?></option><?php endforeach; ?></select></div>
<div><label class="block text-sm font-medium">Notas</label><textarea name="notes" rows="3" class="mt-1 w-full rounded border-gray-300"></textarea></div><div class="text-right"><a href="scm_orders.php" class="mr-4 text-gray-600">Cancelar</a><button class="rounded bg-[var(--primary-color)] text-white px-6 py-2">Guardar</button></div></form></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
