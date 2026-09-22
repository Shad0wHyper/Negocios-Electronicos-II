<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

// 1. Total Suppliers
$totalSuppliers = 0;
$sDocs = $db->collection('scm_suppliers')->documents();
foreach ($sDocs as $doc) { if ($doc->exists()) $totalSuppliers++; }

// 2. Products Metrics
$totalProducts = 0;
$lowStockProducts = 0;
$totalInventoryValue = 0;
$products = [];
$pDocs = $db->collection('products')->documents();
foreach ($pDocs as $doc) {
    if ($doc->exists()) {
        $totalProducts++;
        $p = $doc->data();
        $stock = (int)($p['stock'] ?? 0);
        $min = (int)($p['stock_minimo'] ?? 0);
        $cost = (float)($p['unit_cost'] ?? 0);
        
        $totalInventoryValue += ($stock * $cost);
        
        if ($stock <= $min) {
            $lowStockProducts++;
            $p['id'] = $doc->id();
            $products[] = $p; // Save for low stock table
        }
    }
}
$normalStockProducts = $totalProducts - $lowStockProducts;

// 3. Total Movements
$totalMovements = 0;
$totalEntradas = 0;
$totalSalidas = 0;
$mDocs = $db->collection('scm_movements')->documents();
foreach ($mDocs as $doc) { 
    if ($doc->exists()) {
        $totalMovements++; 
        $type = $doc->data()['type'] ?? '';
        if ($type === 'Entrada') $totalEntradas++;
        if ($type === 'Salida') $totalSalidas++;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
        <h2 class="text-3xl font-bold text-[var(--text-primary)]">Dashboard SCM (Supply Chain)</h2>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg p-6 border border-[var(--border-color)] shadow-sm">
            <div class="text-sm font-medium text-gray-500 mb-1">Total Fabricantes</div>
            <div class="text-3xl font-bold text-gray-900"><?php echo $totalSuppliers; ?></div>
            <a href="scm_suppliers.php" class="text-sm text-[var(--primary-color)] hover:underline mt-2 inline-block">Ver todos &rarr;</a>
        </div>
        <div class="bg-white rounded-lg p-6 border border-[var(--border-color)] shadow-sm">
            <div class="text-sm font-medium text-gray-500 mb-1">Stock Crítico (Bajo)</div>
            <div class="text-3xl font-bold <?php echo $lowStockProducts > 0 ? 'text-red-600' : 'text-green-600'; ?>"><?php echo $lowStockProducts; ?></div>
            <a href="scm_inventory.php" class="text-sm text-[var(--primary-color)] hover:underline mt-2 inline-block">Revisar inventario &rarr;</a>
        </div>
        <div class="bg-white rounded-lg p-6 border border-[var(--border-color)] shadow-sm">
            <div class="text-sm font-medium text-gray-500 mb-1">Valor de Inventario (Costo)</div>
            <div class="text-3xl font-bold text-gray-900">$<?php echo number_format($totalInventoryValue, 2); ?></div>
        </div>
        <div class="bg-white rounded-lg p-6 border border-[var(--border-color)] shadow-sm">
            <div class="text-sm font-medium text-gray-500 mb-1">Total Movimientos</div>
            <div class="text-3xl font-bold text-gray-900"><?php echo $totalMovements; ?></div>
            <a href="scm_movements.php" class="text-sm text-[var(--primary-color)] hover:underline mt-2 inline-block">Ver historial &rarr;</a>
        </div>
    </div>

    <!-- Charts Section -->
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
        <!-- Low Stock Alerts -->
        <div class="bg-white rounded-lg border border-[var(--border-color)] shadow-sm">
            <div class="px-6 py-4 border-b border-[var(--border-color)]">
                <h3 class="text-lg font-semibold text-gray-900">Alertas de Inventario (<?php echo $lowStockProducts; ?>)</h3>
            </div>
            <div class="p-6 max-h-96 overflow-y-auto">
                <?php if (count($products) > 0): ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach($products as $p): ?>
                            <li class="py-3 flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <img src="<?php echo htmlspecialchars($p['image']); ?>" class="w-10 h-10 rounded object-cover" alt="">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($p['name']); ?></p>
                                        <p class="text-xs text-gray-500">Stock Min: <?php echo $p['stock_minimo'] ?? 0; ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Quedan <?php echo $p['stock'] ?? 0; ?>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">El inventario está en niveles óptimos.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Checklist Madurez SCM -->
        <div class="bg-white rounded-lg border border-[var(--border-color)] shadow-sm">
            <div class="px-6 py-4 border-b border-[var(--border-color)]">
                <h3 class="text-lg font-semibold text-gray-900">Nivel de Madurez SCM</h3>
            </div>
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800">En Desarrollo (Fase 1)</span>
                </div>
                <div class="space-y-3">
                    <label class="flex items-center space-x-3 text-sm text-gray-700">
                        <input type="checkbox" checked disabled class="h-4 w-4 text-indigo-600 rounded border-gray-300">
                        <span>Productos y proveedores integrados (Completado)</span>
                    </label>
                    <label class="flex items-center space-x-3 text-sm text-gray-700">
                        <input type="checkbox" checked disabled class="h-4 w-4 text-indigo-600 rounded border-gray-300">
                        <span>Inventario funcionando (Stock Mínimo) (Completado)</span>
                    </label>
                    <label class="flex items-center space-x-3 text-sm text-gray-700">
                        <input type="checkbox" checked disabled class="h-4 w-4 text-indigo-600 rounded border-gray-300">
                        <span>Trazabilidad de movimientos (Completado)</span>
                    </label>
                    <label class="flex items-center space-x-3 text-sm text-gray-400">
                        <input type="checkbox" disabled class="h-4 w-4 text-gray-300 rounded border-gray-300">
                        <span>Estrategia Push/Pull implementada (Pendiente - Fase 2)</span>
                    </label>
                    <label class="flex items-center space-x-3 text-sm text-gray-400">
                        <input type="checkbox" disabled class="h-4 w-4 text-gray-300 rounded border-gray-300">
                        <span>Pedidos de Reposición (Pendiente - Fase 2)</span>
                    </label>
                </div>
                <div class="mt-6 p-4 bg-gray-50 rounded-md">
                    <p class="text-sm text-gray-600">El sistema cuenta con los módulos principales funcionando (Proveedores, Productos, Inventario y Movimientos). Se implementarán estrategias logísticas y pedidos en la siguiente etapa.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    Chart.register(ChartDataLabels);

    // Chart 1: Inventory Health
    const healthCtx = document.getElementById('inventoryHealthChart').getContext('2d');
    new Chart(healthCtx, {
        type: 'doughnut',
        data: {
            labels: ['Stock Normal', 'Stock Bajo/Crítico'],
            datasets: [{
                data: [<?php echo $normalStockProducts; ?>, <?php echo $lowStockProducts; ?>],
                backgroundColor: ['#10B981', '#EF4444'], // green, red
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
                    formatter: (value) => {
                        return value > 0 ? value : '';
                    }
                }
            }
        }
    });

    // Chart 2: Movements
    const movementsCtx = document.getElementById('movementsChart').getContext('2d');
    new Chart(movementsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Entradas', 'Salidas'],
            datasets: [{
                data: [<?php echo $totalEntradas; ?>, <?php echo $totalSalidas; ?>],
                backgroundColor: ['#3B82F6', '#F59E0B'], // blue, amber
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
                    formatter: (value) => {
                        return value > 0 ? value : '';
                    }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
