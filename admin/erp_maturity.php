<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

$checks = [
    ['Clientes integrados (CRM)', 'crm.php', true],
    ['Productos e inventario (SCM)', 'scm_dashboard.php', true],
    ['Órdenes de clientes (ERP)', 'orders.php', true],
    ['Movimientos de inventario automáticos', 'scm_movements.php', true],
    ['Reportes empresariales completos', 'reports.php', true],
    ['Procesos optimizados', 'activity.php', false],
];
$completed = count(array_filter($checks, fn($check) => $check[2]));
$percentage = (int)round($completed / count($checks) * 100);
require_once __DIR__ . '/includes/header.php';
?>
<div class="max-w-5xl mx-auto">
    <div class="mb-8"><a href="erp.php" class="text-sm text-gray-500 hover:text-blue-600">← Dashboard ERP</a><h2 class="mt-3 text-3xl font-bold">Nivel de implementación ERP</h2><p class="mt-2 text-sm text-gray-500">Evaluación del avance en la integración de procesos empresariales.</p></div>
    <div class="rounded-lg border bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-5"><div><p class="text-sm font-semibold uppercase tracking-wide text-blue-600">Nivel actual</p><h3 class="mt-1 text-2xl font-bold">Integrado</h3><p class="mt-1 text-sm text-gray-500">CRM, ERP y SCM operan de manera coordinada.</p></div><div class="relative h-28 w-28"><svg class="h-full w-full -rotate-90"><circle cx="56" cy="56" r="45" fill="none" stroke="#e5e7eb" stroke-width="10"/><circle cx="56" cy="56" r="45" fill="none" stroke="#2563eb" stroke-width="10" stroke-linecap="round" stroke-dasharray="<?php echo 2 * pi() * 45; ?>" stroke-dashoffset="<?php echo 2 * pi() * 45 * (1 - $percentage / 100); ?>"/></svg><span class="absolute inset-0 flex items-center justify-center text-xl font-bold"><?php echo $percentage; ?>%</span></div></div>
        <div class="mt-8"><div class="mb-4 flex justify-between text-sm"><span class="font-semibold">Checklist de avance</span><span class="text-gray-500"><?php echo $completed; ?> de <?php echo count($checks); ?> completados</span></div><div class="space-y-3"><?php foreach ($checks as $check): ?><a href="<?php echo $check[1]; ?>" class="flex items-center justify-between rounded-md border px-4 py-3 hover:bg-gray-50"><span class="flex items-center gap-3"><?php if ($check[2]): ?><span class="text-lg text-emerald-600">✓</span><?php else: ?><span class="text-lg text-gray-400">□</span><?php endif; ?><span class="<?php echo $check[2] ? 'text-gray-800' : 'text-gray-500'; ?>"><?php echo htmlspecialchars($check[0]); ?></span></span><span class="text-xs text-blue-600">Abrir →</span></a><?php endforeach; ?></div></div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
