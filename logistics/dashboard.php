<?php
// logistics/dashboard.php - Panel de Logística (Productos · Inventario · Movimientos)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_logistics.php';
require_once __DIR__ . '/../includes/scm_functions.php';

// 1. Métricas de Productos e Inventario
$totalProducts = 0;
$lowStockProducts = 0;
$totalInventoryValue = 0;
$lowStockList = [];
$productsMap = [];

$pDocs = $db->collection('products')->documents();
foreach ($pDocs as $doc) {
    if ($doc->exists()) {
        $totalProducts++;
        $p = $doc->data();
        $p['id'] = $doc->id();
        $productsMap[$p['id']] = $p['name'] ?? 'Producto';

        $stock = (int)($p['stock'] ?? 0);
        $cost = (float)($p['unit_cost'] ?? 0);
        $totalInventoryValue += ($stock * $cost);

        if (scmIsLowStock($p)) {
            $lowStockProducts++;
            $lowStockList[] = $p;
        }
    }
}
$normalStockProducts = max(0, $totalProducts - $lowStockProducts);

// 2. Mapa de roles de usuarios para atribución de movimientos
$usersRoleCache = [];
$uDocs = $db->collection('users')->documents();
foreach ($uDocs as $uDoc) {
    if ($uDoc->exists()) {
        $usersRoleCache[$uDoc->id()] = normalizeUserRole($uDoc->data()['role'] ?? 'user');
    }
}

// 3. Métricas e historial de Movimientos
$totalMovements = 0;
$totalEntradas = 0;
$totalSalidas = 0;
$recentMovements = [];

$mDocs = $db->collection('scm_movements')->documents();
foreach ($mDocs as $doc) {
    if ($doc->exists()) {
        $totalMovements++;
        $m = $doc->data();
        $m['id'] = $doc->id();
        $m['product_name'] = $productsMap[$m['product_id'] ?? ''] ?? 'Desconocido';
        $m['actor_label'] = resolveMovementActorLabel($m, $usersRoleCache);
        $type = $m['type'] ?? '';
        if ($type === 'Entrada') {
            $totalEntradas++;
        } elseif ($type === 'Salida') {
            $totalSalidas++;
        }
        $recentMovements[] = $m;
    }
}

usort($recentMovements, function ($a, $b) {
    return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
});
$recentMovements = array_slice($recentMovements, 0, 6);

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-bold text-[var(--text-primary)]">Dashboard de Logística</h2>
            <p class="mt-1 text-sm text-[var(--text-secondary)]">Panel operativo para control de productos, inventario y movimientos.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="add_movement.php" class="rounded-md bg-[var(--primary-color)] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[var(--primary-color-hover)] transition-colors">
                + Nuevo movimiento
            </a>
            <a href="add_product.php" class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors">
                + Agregar producto
            </a>
        </div>
    </div>

    <!-- Tarjeta de Rol Activo -->
    <div class="mb-8 rounded-md border-l-4 border-emerald-500 bg-emerald-50 p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div>
            <strong class="text-emerald-800 text-base">Logística</strong>
            <p class="mt-1 text-sm text-gray-600">Productos · Inventario · Movimientos</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="products.php" class="rounded bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 border border-emerald-200 hover:bg-emerald-100">Ir a Productos</a>
            <a href="inventory.php" class="rounded bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 border border-emerald-200 hover:bg-emerald-100">Ir a Inventario</a>
            <a href="movements.php" class="rounded bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 border border-emerald-200 hover:bg-emerald-100">Ir a Movimientos</a>
        </div>
    </div>

    <!-- KPIs de Logística -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <a href="products.php" class="bg-white rounded-lg p-6 border border-[var(--border-color)] shadow-sm hover:border-blue-400 transition-colors">
            <div class="text-sm font-medium text-gray-500 mb-1">Productos en Catálogo</div>
            <div class="text-3xl font-bold text-gray-900"><?php echo $totalProducts; ?></div>
            <span class="text-sm text-[var(--primary-color)] mt-2 inline-block">Gestionar productos &rarr;</span>
        </a>

        <a href="inventory.php" class="bg-white rounded-lg p-6 border border-[var(--border-color)] shadow-sm hover:border-red-400 transition-colors">
            <div class="text-sm font-medium text-gray-500 mb-1">Stock Crítico (Bajo)</div>
            <div class="text-3xl font-bold <?php echo $lowStockProducts > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                <?php echo $lowStockProducts; ?>
            </div>
            <span class="text-sm text-[var(--primary-color)] mt-2 inline-block">Revisar inventario &rarr;</span>
        </a>

        <div class="bg-white rounded-lg p-6 border border-[var(--border-color)] shadow-sm">
            <div class="text-sm font-medium text-gray-500 mb-1">Valor de Inventario (Costo)</div>
            <div class="text-3xl font-bold text-gray-900">$<?php echo number_format($totalInventoryValue, 2); ?></div>
            <span class="text-xs text-gray-400 mt-2 inline-block"><?php echo $normalStockProducts; ?> productos en nivel óptimo</span>
        </div>

        <a href="movements.php" class="bg-white rounded-lg p-6 border border-[var(--border-color)] shadow-sm hover:border-emerald-400 transition-colors">
            <div class="text-sm font-medium text-gray-500 mb-1">Total Movimientos</div>
            <div class="text-3xl font-bold text-gray-900"><?php echo $totalMovements; ?></div>
            <span class="text-sm text-[var(--primary-color)] mt-2 inline-block">Ver historial &rarr;</span>
        </a>
    </div>

    <!-- Gráficas de Salud de Inventario y Movimientos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="bg-white rounded-lg border border-[var(--border-color)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Salud del Inventario</h3>
            <div style="height: 250px; display: flex; justify-content: center;">
                <canvas id="inventoryHealthChart"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-[var(--border-color)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Movimientos (Entradas vs Salidas)</h3>
            <div style="height: 250px; display: flex; justify-content: center;">
                <canvas id="movementsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Alertas de Inventario -->
        <div class="bg-white rounded-lg border border-[var(--border-color)] shadow-sm">
            <div class="px-6 py-4 border-b border-[var(--border-color)] flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Alertas de Inventario (<?php echo $lowStockProducts; ?>)</h3>
                <a href="inventory.php" class="text-sm font-semibold text-blue-600 hover:underline">Ver inventario &rarr;</a>
            </div>
            <div class="p-6 max-h-96 overflow-y-auto">
                <?php if (count($lowStockList) > 0): ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($lowStockList as $p): ?>
                            <li class="py-3 flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <img src="<?php echo htmlspecialchars($p['image'] ?? ''); ?>" class="w-10 h-10 rounded object-cover" alt="">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($p['name'] ?? ''); ?></p>
                                        <p class="text-xs text-gray-500">Stock Mín: <?php echo (int)($p['stock_minimo'] ?? 0); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Quedan <?php echo (int)($p['stock'] ?? 0); ?>
                                    </span>
                                    <a href="add_movement.php?product_id=<?php echo urlencode($p['id']); ?>" class="text-xs font-semibold text-[var(--primary-color)] hover:underline">Reponer</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">El inventario está en niveles óptimos.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Últimos Movimientos -->
        <div class="bg-white rounded-lg border border-[var(--border-color)] shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-[var(--border-color)] flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Movimientos Recientes</h3>
                <a href="movements.php" class="text-sm font-semibold text-blue-600 hover:underline">Ver todos &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Producto</th>
                            <th class="px-4 py-3 text-center">Tipo</th>
                            <th class="px-4 py-3 text-center">Cant.</th>
                            <th class="px-4 py-3">Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($recentMovements) > 0): foreach ($recentMovements as $m):
                        $isEntrada = ($m['type'] ?? '') === 'Entrada';
                    ?>
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-xs"><?php echo htmlspecialchars($m['created_at'] ?? ''); ?></td>
                            <td class="px-4 py-3 font-medium text-gray-900"><?php echo htmlspecialchars($m['product_name']); ?></td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?php echo $isEntrada ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo htmlspecialchars($m['type'] ?? ''); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center font-bold <?php echo $isEntrada ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $isEntrada ? '+' : '-'; ?><?php echo abs((int)($m['quantity'] ?? 0)); ?>
                            </td>
                            <td class="px-4 py-3 text-xs font-medium text-gray-500">
                                <?php echo htmlspecialchars($m['actor_label'] ?? 'Admin'); ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center py-6">No hay movimientos registrados.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    Chart.register(ChartDataLabels);

    const healthCtx = document.getElementById('inventoryHealthChart').getContext('2d');
    new Chart(healthCtx, {
        type: 'doughnut',
        data: {
            labels: ['Stock Normal', 'Stock Bajo/Crítico'],
            datasets: [{
                data: [<?php echo $normalStockProducts; ?>, <?php echo $lowStockProducts; ?>],
                backgroundColor: ['#10B981', '#EF4444'],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                datalabels: {
                    color: '#fff',
                    font: { weight: 'bold', size: 14 },
                    formatter: (value) => value > 0 ? value : ''
                }
            }
        }
    });

    const movementsCtx = document.getElementById('movementsChart').getContext('2d');
    new Chart(movementsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Entradas', 'Salidas'],
            datasets: [{
                data: [<?php echo $totalEntradas; ?>, <?php echo $totalSalidas; ?>],
                backgroundColor: ['#3B82F6', '#F59E0B'],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                datalabels: {
                    color: '#fff',
                    font: { weight: 'bold', size: 14 },
                    formatter: (value) => value > 0 ? value : ''
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

