<?php
// 1. Incluir archivos de configuración y autenticación
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
// 2. Consultas para las tarjetas de estadísticas
$totalUsers = 0;
$usersQuery = $db->collection('users')->documents();
foreach ($usersQuery as $doc) { if ($doc->exists()) $totalUsers++; }

$totalProducts = 0;
$productsQuery = $db->collection('products')->documents();
foreach ($productsQuery as $doc) { if ($doc->exists()) $totalProducts++; }

$totalOrders = 0;
$totalRevenue = 0;
$allOrders = [];
$ordersQuery = $db->collection('orders')->documents();
foreach ($ordersQuery as $doc) {
    if ($doc->exists()) {
        $totalOrders++;
        $o = $doc->data();
        $o['id'] = $doc->id();
        if (($o['status'] ?? '') === 'paid') {
            $totalRevenue += (float)($o['total'] ?? 0);
        }
        $allOrders[] = $o;
    }
}

// Sort orders by date DESC
usort($allOrders, function($a, $b) {
    return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
});

// 3. Consulta para los pedidos recientes
$recentOrders = array_slice($allOrders, 0, 5);
$usersCache = [];
foreach ($recentOrders as &$ro) {
    $uid = $ro['user_id'] ?? '';
    if ($uid) {
        if (!isset($usersCache[$uid])) {
            $uDoc = $db->collection('users')->document($uid)->snapshot();
            $usersCache[$uid] = $uDoc->exists() ? ($uDoc->data()['name'] ?? 'Desconocido') : 'Desconocido';
        }
        $ro['user_name'] = $usersCache[$uid];
    } else {
        $ro['user_name'] = 'Desconocido';
    }
}
unset($ro);

// 4. --- NUEVO: Obtener datos para la gráfica de los últimos 7 días ---
$dateRange = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dateRange[$date] = ['revenue' => 0, 'orders' => 0];
}

$sevenDaysAgo = date('Y-m-d 00:00:00', strtotime("-6 days"));
foreach ($allOrders as $o) {
    if (($o['status'] ?? '') === 'paid' && ($o['created_at'] ?? '') >= $sevenDaysAgo) {
        $saleDate = substr($o['created_at'], 0, 10);
        if (isset($dateRange[$saleDate])) {
            $dateRange[$saleDate]['revenue'] += (float)($o['total'] ?? 0);
            $dateRange[$saleDate]['orders']++;
        }
    }
}

$chartLabels = [];
$chartRevenueData = [];
$chartOrdersData = [];
foreach ($dateRange as $date => $data) {
    $chartLabels[] = date('d M', strtotime($date));
    $chartRevenueData[] = $data['revenue'];
    $chartOrdersData[] = $data['orders'];
}


// 5. Incluir el header
require_once __DIR__ . '/includes/header.php';
?>

    <div class="max-w-7xl mx-auto">
        <h2 class="text-3xl font-bold text-[var(--text-primary)] mb-8">Dashboard</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white shadow rounded-lg p-6 flex items-center gap-4">
                <div class="bg-green-100 p-3 rounded-full"><svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 10v-1m0 0a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"></path></svg></div>
                <div><h3 class="text-sm font-medium text-gray-500">Ingresos Totales</h3><p class="text-2xl font-bold">$<?php echo number_format($totalRevenue, 2); ?></p></div>
            </div>
            <div class="bg-white shadow rounded-lg p-6 flex items-center gap-4">
                <div class="bg-blue-100 p-3 rounded-full"><svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002-2h2a2 2 0 002 2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg></div>
                <div><h3 class="text-sm font-medium text-gray-500">Pedidos</h3><p class="text-2xl font-bold"><?php echo $totalOrders; ?></p></div>
            </div>
            <div class="bg-white shadow rounded-lg p-6 flex items-center gap-4">
                <div class="bg-yellow-100 p-3 rounded-full"><svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg></div>
                <div><h3 class="text-sm font-medium text-gray-500">Usuarios</h3><p class="text-2xl font-bold"><?php echo $totalUsers; ?></p></div>
            </div>
            <div class="bg-white shadow rounded-lg p-6 flex items-center gap-4">
                <div class="bg-red-100 p-3 rounded-full"><svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg></div>
                <div><h3 class="text-sm font-medium text-gray-500">Productos</h3><p class="text-2xl font-bold"><?php echo $totalProducts; ?></p></div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] overflow-hidden">
            <h3 class="text-lg font-semibold text-gray-800 p-4 border-b">Pedidos Recientes</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50"><tr><th scope="col" class="px-6 py-3">ID Pedido</th><th scope="col" class="px-6 py-3">Cliente</th><th scope="col" class="px-6 py-3">Total</th><th scope="col" class="px-6 py-3">Estado</th><th scope="col" class="px-6 py-3">Fecha</th></tr></thead>
                    <tbody>
                    <?php if (count($recentOrders) > 0): foreach($recentOrders as $order): ?>
                        <tr class="bg-white border-b hover:bg-gray-50"><th scope="row" class="px-6 py-4 font-medium text-gray-900">#<?php echo $order['id']; ?></th><td class="px-6 py-4"><?php echo htmlspecialchars($order['user_name']); ?></td><td class="px-6 py-4">$<?php echo number_format($order['total'], 2); ?></td><td class="px-6 py-4"><span class="px-2 py-1 font-semibold leading-tight rounded-full <?php if($order['status'] == 'paid'){ echo 'text-green-700 bg-green-100'; } elseif($order['status'] == 'shipped'){ echo 'text-blue-700 bg-blue-100'; } elseif($order['status'] == 'pending'){ echo 'text-yellow-700 bg-yellow-100'; } else { echo 'text-red-700 bg-red-100'; } ?>"><?php echo ucfirst($order['status']); ?></span></td><td class="px-6 py-4"><?php echo date("d M, Y", strtotime($order['created_at'])); ?></td></tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center py-4">No hay pedidos recientes.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8 bg-white rounded-lg shadow-sm border border-[var(--border-color)] p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Ingresos y Pedidos (Últimos 7 días)</h3>
            <div style="height: 400px;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('salesChart').getContext('2d');

            const salesChart = new Chart(ctx, {
                type: 'line', // Tipo de gráfica
                data: {
                    labels: <?php echo json_encode($chartLabels); ?>,
                    datasets: [
                        {
                            label: 'Ingresos ($)',
                            data: <?php echo json_encode($chartRevenueData); ?>,
                            borderColor: 'rgba(61, 152, 244, 1)', // Azul
                            backgroundColor: 'rgba(61, 152, 244, 0.2)',
                            borderWidth: 2,
                            yAxisID: 'y-revenue',
                            tension: 0.3
                        },
                        {
                            label: 'Pedidos',
                            data: <?php echo json_encode($chartOrdersData); ?>,
                            borderColor: 'rgba(234, 179, 8, 1)', // Amarillo
                            backgroundColor: 'rgba(234, 179, 8, 0.2)',
                            borderWidth: 2,
                            yAxisID: 'y-orders',
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        'y-revenue': {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Ingresos ($)'
                            }
                        },
                        'y-orders': {
                            type: 'linear',
                            position: 'right',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nº de Pedidos'
                            },
                            grid: {
                                drawOnChartArea: false, // Evita que se superpongan las cuadrículas
                            },
                            ticks: {
                                stepSize: 1 // Asegura que el conteo de pedidos sea en números enteros
                            }
                        }
                    }
                }
            });
        });
    </script>

<?php
// Incluir el footer
require_once __DIR__ . '/includes/footer.php';
?>