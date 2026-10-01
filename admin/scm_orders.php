<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
$products = [];
foreach ($db->collection('products')->documents() as $doc) if ($doc->exists()) $products[$doc->id()] = $doc->data()['name'] ?? 'Producto';
$orders = [];
foreach ($db->collection('scm_orders')->documents() as $doc) if ($doc->exists()) { $o = $doc->data(); $o['id'] = $doc->id(); $orders[] = $o; }
usort($orders, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
require_once __DIR__ . '/includes/header.php';
?>
<div class="max-w-7xl mx-auto"><div class="flex flex-wrap justify-between items-center gap-4 mb-8"><h2 class="text-3xl font-bold">Pedidos de reposición</h2><a href="scm_add_order.php" class="rounded-md px-5 py-2 bg-[var(--primary-color)] text-white text-sm font-semibold">+ Generar pedido</a></div>
<div class="bg-white border rounded-lg overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 uppercase text-xs"><tr><th class="px-6 py-3 text-left">Folio</th><th class="px-6 py-3 text-left">Fecha</th><th class="px-6 py-3 text-left">Producto</th><th class="px-6 py-3 text-left">Cantidad</th><th class="px-6 py-3 text-left">Tipo</th><th class="px-6 py-3 text-left">Estado</th><th class="px-6 py-3 text-left">Proveedor</th></tr></thead><tbody>
<?php foreach ($orders as $o): ?><tr class="border-b"><td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($o['folio'] ?? $o['id']); ?></td><td class="px-6 py-4"><?php echo htmlspecialchars($o['created_at'] ?? ''); ?></td><td class="px-6 py-4"><?php echo htmlspecialchars($products[$o['product_id'] ?? ''] ?? 'Producto eliminado'); ?></td><td class="px-6 py-4"><?php echo (int)($o['quantity'] ?? 0); ?></td><td class="px-6 py-4"><?php echo htmlspecialchars($o['type'] ?? 'Reposición'); ?></td><td class="px-6 py-4"><span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800"><?php echo htmlspecialchars($o['status'] ?? 'Pendiente'); ?></span></td><td class="px-6 py-4"><?php echo htmlspecialchars($o['supplier_name'] ?? 'Sin proveedor'); ?></td></tr><?php endforeach; ?>
<?php if (!$orders): ?><tr><td colspan="7" class="px-6 py-8 text-center">No hay pedidos registrados.</td></tr><?php endif; ?></tbody></table></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
