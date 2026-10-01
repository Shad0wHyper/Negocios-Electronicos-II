<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['product_id'] ?? '');
    $strategy = ($_POST['strategy'] ?? '') === 'PULL' ? 'PULL' : 'PUSH';
    if ($id !== '') {
        $db->collection('products')->document($id)->set(['strategy' => $strategy], ['merge' => true]);
        $message = 'Estrategia actualizada correctamente.';
    }
}
$products = [];
foreach ($db->collection('products')->documents() as $doc) {
    if ($doc->exists()) { $p = $doc->data(); $p['id'] = $doc->id(); $products[] = $p; }
}
usort($products, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
require_once __DIR__ . '/includes/header.php';
?>
<div class="max-w-5xl mx-auto">
    <h2 class="text-3xl font-bold mb-2">Logística: estrategia de reposición</h2>
    <p class="text-gray-500 mb-8">Define si cada producto se anticipa por planificación (PUSH) o por demanda real (PULL).</p>
    <?php if ($message): ?><div class="mb-4 rounded bg-green-100 text-green-800 px-4 py-3"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-6"><b class="text-orange-800">PUSH</b><p class="text-sm mt-2">Producción o compra anticipada con base en previsiones y stock mínimo.</p></div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6"><b class="text-blue-800">PULL</b><p class="text-sm mt-2">Reposición activada por pedidos y demanda observada.</p></div>
    </div>
    <div class="bg-white border rounded-lg overflow-hidden"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="text-left px-6 py-3">Producto</th><th class="text-left px-6 py-3">Stock / mínimo</th><th class="text-left px-6 py-3">Estrategia</th><th class="px-6 py-3">Guardar</th></tr></thead><tbody>
    <?php foreach ($products as $p): ?><tr class="border-b"><td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($p['name'] ?? ''); ?></td><td class="px-6 py-4"><?php echo (int)($p['stock'] ?? 0); ?> / <?php echo (int)($p['stock_minimo'] ?? 0); ?></td><td class="px-6 py-4"><form method="post" class="flex gap-2"><input type="hidden" name="product_id" value="<?php echo htmlspecialchars($p['id']); ?>"><select name="strategy" class="rounded border-gray-300"><option value="PUSH" <?php echo ($p['strategy'] ?? 'PUSH') === 'PUSH' ? 'selected' : ''; ?>>PUSH</option><option value="PULL" <?php echo ($p['strategy'] ?? '') === 'PULL' ? 'selected' : ''; ?>>PULL</option></select></td><td class="px-6 py-4"><button class="rounded bg-gray-800 text-white px-3 py-1">Guardar</button></form></td></tr><?php endforeach; ?>
    </tbody></table></div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
