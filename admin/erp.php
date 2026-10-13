<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

$customers = $products = $orders = [];
foreach ($db->collection('users')->where('role', '=', 'customer')->documents() as $doc) {
    if ($doc->exists()) { $item = $doc->data(); $item['id'] = $doc->id(); $customers[] = $item; }
}
foreach ($db->collection('products')->documents() as $doc) {
    if ($doc->exists()) { $item = $doc->data(); $item['id'] = $doc->id(); $products[] = $item; }
}
foreach ($db->collection('orders')->documents() as $doc) {
    if ($doc->exists()) { $item = $doc->data(); $item['id'] = $doc->id(); $orders[] = $item; }
}
usort($orders, fn($a, $b) => strtotime($b['created_at'] ?? '0') <=> strtotime($a['created_at'] ?? '0'));
$paidOrders = array_filter($orders, fn($o) => in_array($o['status'] ?? '', ['paid', 'shipped'], true));
$revenue = array_sum(array_map(fn($o) => (float)($o['total'] ?? 0), $paidOrders));
$lowStock = count(array_filter($products, fn($p) => (int)($p['stock'] ?? 0) <= (int)($p['stock_minimo'] ?? 0)));
require_once __DIR__ . '/includes/header.php';
?>
<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div><h2 class="text-3xl font-bold">Dashboard ERP</h2><p class="mt-2 text-sm text-[var(--text-secondary)]">Vista integrada de clientes, productos, inventario y órdenes.</p></div>
        <a href="erp_new_order.php" class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">+ Nueva orden</a>
    </div>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-8">
        <a href="crm.php" class="rounded-lg border bg-white p-5 shadow-sm hover:border-blue-400"><p class="text-sm text-gray-500">Clientes activos</p><p class="mt-2 text-3xl font-bold text-blue-600"><?php echo count($customers); ?></p><span class="text-xs text-gray-500">Abrir CRM →</span></a>
        <a href="orders.php" class="rounded-lg border bg-white p-5 shadow-sm hover:border-emerald-400"><p class="text-sm text-gray-500">Órdenes de clientes</p><p class="mt-2 text-3xl font-bold text-emerald-600"><?php echo count($orders); ?></p><span class="text-xs text-gray-500">Gestionar órdenes →</span></a>
        <a href="scm_inventory.php" class="rounded-lg border bg-white p-5 shadow-sm hover:border-orange-400"><p class="text-sm text-gray-500">Productos en catálogo</p><p class="mt-2 text-3xl font-bold text-orange-500"><?php echo count($products); ?></p><span class="text-xs text-gray-500"><?php echo $lowStock; ?> con stock bajo</span></a>
        <a href="reports.php" class="rounded-lg border bg-white p-5 shadow-sm hover:border-violet-400"><p class="text-sm text-gray-500">Ventas totales</p><p class="mt-2 text-3xl font-bold text-violet-600">$<?php echo number_format($revenue, 2); ?></p><span class="text-xs text-gray-500">Reportes empresariales →</span></a>
    </div>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="rounded-lg border bg-white shadow-sm lg:col-span-2"><div class="flex items-center justify-between border-b p-5"><h3 class="text-lg font-semibold">Órdenes de clientes</h3><a href="orders.php" class="text-sm font-semibold text-blue-600">Ver todas</a></div><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-600"><tr><th class="px-5 py-3">Folio</th><th class="px-5 py-3">Cliente</th><th class="px-5 py-3">Total</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3"></th></tr></thead><tbody>
        <?php foreach (array_slice($orders, 0, 6) as $order): ?><tr class="border-b hover:bg-gray-50"><td class="px-5 py-3 font-semibold">#<?php echo htmlspecialchars($order['id']); ?></td><td class="px-5 py-3"><?php echo htmlspecialchars($order['customer_name'] ?? $order['user_id'] ?? 'Cliente'); ?></td><td class="px-5 py-3">$<?php echo number_format((float)($order['total'] ?? 0), 2); ?></td><td class="px-5 py-3"><span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700"><?php echo htmlspecialchars(ucfirst($order['status'] ?? 'pending')); ?></span></td><td class="px-5 py-3"><a class="text-blue-600" href="view_order.php?id=<?php echo urlencode($order['id']); ?>">Ver</a></td></tr><?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="5" class="p-8 text-center text-gray-500">Aún no hay órdenes registradas.</td></tr><?php endif; ?></tbody></table></div></section>
        <section class="rounded-lg border bg-white p-5 shadow-sm"><h3 class="text-lg font-semibold">Integración ERP</h3><p class="mt-2 text-sm text-gray-500">Módulos conectados para operar el negocio desde un solo lugar.</p><div class="mt-5 space-y-3"><?php foreach ([['CRM','Clientes e interacciones','crm.php','bg-blue-50 text-blue-700'],['ERP','Órdenes y ventas','orders.php','bg-emerald-50 text-emerald-700'],['SCM','Productos e inventario','scm_dashboard.php','bg-orange-50 text-orange-700']] as $module): ?><a href="<?php echo $module[2]; ?>" class="flex items-center justify-between rounded-md px-4 py-3 <?php echo $module[3]; ?>"><span><strong><?php echo $module[0]; ?></strong><br><small><?php echo $module[1]; ?></small></span><span>✓</span></a><?php endforeach; ?></div><a href="erp_maturity.php" class="mt-5 block text-center text-sm font-semibold text-violet-600">Ver nivel de implementación →</a></section>
    </div>
    <section class="mt-6 rounded-lg border bg-white p-5 shadow-sm"><h3 class="text-lg font-semibold">Panel por rol</h3><p class="mt-1 text-sm text-gray-500">Accesos operativos según la responsabilidad del equipo.</p><div class="mt-4 grid gap-4 md:grid-cols-3"><a href="users.php" class="rounded-md border-l-4 border-violet-500 bg-violet-50 p-4"><strong class="text-violet-800">Administrador</strong><p class="mt-1 text-sm text-gray-600">CRM · ERP · SCM · Usuarios · Reportes</p></a><a href="orders.php" class="rounded-md border-l-4 border-blue-500 bg-blue-50 p-4"><strong class="text-blue-800">Ventas</strong><p class="mt-1 text-sm text-gray-600">Clientes · Órdenes · Actividad</p></a><a href="scm_inventory.php" class="rounded-md border-l-4 border-emerald-500 bg-emerald-50 p-4"><strong class="text-emerald-800">Logística</strong><p class="mt-1 text-sm text-gray-600">Productos · Inventario · Movimientos</p></a></div></section>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
