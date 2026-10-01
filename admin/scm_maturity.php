<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
$products = 0; $suppliers = 0; $movements = 0; $orders = 0;
foreach ($db->collection('products')->documents() as $d) if ($d->exists()) $products++;
foreach ($db->collection('scm_suppliers')->documents() as $d) if ($d->exists()) $suppliers++;
foreach ($db->collection('scm_movements')->documents() as $d) if ($d->exists()) $movements++;
foreach ($db->collection('scm_orders')->documents() as $d) if ($d->exists()) $orders++;
$checks = [$products > 0, $suppliers > 0, $movements > 0, $products > 0, $orders > 0];
$complete = count(array_filter($checks)); $percent = (int)round($complete / count($checks) * 100);
require_once __DIR__ . '/includes/header.php';
?>
<div class="max-w-4xl mx-auto"><h2 class="text-3xl font-bold mb-2">Nivel de madurez SCM</h2><p class="text-gray-500 mb-8">Evaluación del avance de la implementación de la cadena de suministro.</p><div class="bg-white border rounded-lg p-8"><div class="flex justify-between mb-2"><span class="font-semibold">Nivel actual</span><span class="font-semibold"><?php echo $percent; ?>% · <?php echo $percent >= 80 ? 'Optimizado' : ($percent >= 40 ? 'En desarrollo' : 'Inicial'); ?></span></div><div class="h-4 bg-gray-200 rounded-full overflow-hidden mb-8"><div class="h-full bg-emerald-500" style="width: <?php echo $percent; ?>%"></div></div><div class="space-y-4"><?php $labels = ['Productos integrados', 'Proveedores registrados', 'Inventario y movimientos funcionando', 'Estrategia Push/Pull configurada', 'Pedidos de reposición habilitados']; foreach ($labels as $i => $label): ?><div class="flex items-center gap-3"><span class="<?php echo $checks[$i] ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-500'; ?> rounded-full w-6 h-6 text-center"><?php echo $checks[$i] ? '✓' : '○'; ?></span><span><?php echo $label; ?></span></div><?php endforeach; ?></div><div class="mt-8 bg-gray-50 rounded p-4 text-sm text-gray-600">Indicadores: <?php echo $products; ?> productos, <?php echo $suppliers; ?> proveedores, <?php echo $movements; ?> movimientos y <?php echo $orders; ?> pedidos.</div></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
