<?php
// seller/dashboard.php - Panel de Ventas (Clientes · Órdenes · Actividad)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_seller.php';

$currentSellerId = (string)($_SESSION['user']['id'] ?? '');

// 1. Clientes registrados
$totalCustomers = 0;
$customersQuery = $db->collection('users')->where('role', '=', 'customer')->documents();
foreach ($customersQuery as $doc) {
    if ($doc->exists()) {
        $totalCustomers++;
    }
}

// 2. Órdenes e ingresos
$totalOrders = 0;
$totalRevenue = 0;
$allOrders = [];
$ordersQuery = $db->collection('orders')->documents();
foreach ($ordersQuery as $doc) {
    if ($doc->exists()) {
        $totalOrders++;
        $o = $doc->data();
        $o['id'] = $doc->id();
        if (in_array($o['status'] ?? '', ['paid', 'shipped'], true)) {
            $totalRevenue += (float)($o['total'] ?? 0);
        }
        $allOrders[] = $o;
    }
}

usort($allOrders, function ($a, $b) {
    return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
});

// 3. Actividad registrada por el vendedor actual
$myActivitiesCount = 0;
if ($currentSellerId !== '') {
    $interQuery = $db->collection('crm_interactions')->where('admin_id', '=', $currentSellerId)->documents();
    foreach ($interQuery as $doc) {
        if ($doc->exists()) {
            $myActivitiesCount++;
        }
    }
}

// 4. Pedidos recientes con nombre del cliente
$recentOrders = array_slice($allOrders, 0, 6);
$usersCache = [];
foreach ($recentOrders as &$ro) {
    $uid = $ro['user_id'] ?? '';
    if (!empty($ro['customer_name'])) {
        $ro['user_name'] = $ro['customer_name'];
    } elseif ($uid) {
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

// 5. Datos para la gráfica de los últimos 7 días
$dateRange = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dateRange[$date] = ['revenue' => 0, 'orders' => 0];
}

$sevenDaysAgo = date('Y-m-d 00:00:00', strtotime('-6 days'));
foreach ($allOrders as $o) {
    if (in_array($o['status'] ?? '', ['paid', 'shipped'], true) && ($o['created_at'] ?? '') >= $sevenDaysAgo) {
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

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-bold text-[var(--text-primary)]">Dashboard de Ventas</h2>
            <p class="mt-1 text-sm text-[var(--text-secondary)]">Panel operativo para gestión de clientes, órdenes y seguimiento de actividad.</p>
        </div>
        <a href="new_order.php" class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors">
            + Nueva orden
        </a>
    </div>

    <!-- Tarjeta de Rol Activo -->
    <div class="mb-8 rounded-md border-l-4 border-blue-500 bg-blue-50 p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div>
            <strong class="text-blue-800 text-base">Ventas</strong>
            <p class="mt-1 text-sm text-gray-600">Clientes · Órdenes · Actividad</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="crm.php" class="rounded bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 border border-blue-200 hover:bg-blue-100">Ir a Clientes</a>
            <a href="orders.php" class="rounded bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 border border-blue-200 hover:bg-blue-100">Ir a Órdenes</a>
            <a href="activity.php" class="rounded bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 border border-blue-200 hover:bg-blue-100">Ir a Actividad</a>
        </div>
    </div>

    <!-- KPIs de Ventas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <a href="crm.php" class="bg-white shadow rounded-lg p-6 flex items-center gap-4 border border-transparent hover:border-blue-400 transition-colors">
            <div class="bg-blue-100 p-3 rounded-full">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Clientes</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo $totalCustomers; ?></p>
                <span class="text-xs text-blue-600">Abrir CRM &rarr;</span>
            </div>
        </a>

        <a href="orders.php" class="bg-white shadow rounded-lg p-6 flex items-center gap-4 border border-transparent hover:border-emerald-400 transition-colors">
            <div class="bg-emerald-100 p-3 rounded-full">
                <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002-2h2a2 2 0 002 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Órdenes</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo $totalOrders; ?></p>
                <span class="text-xs text-emerald-600">Gestionar órdenes &rarr;</span>
            </div>
        </a>

        <div class="bg-white shadow rounded-lg p-6 flex items-center gap-4">
            <div class="bg-green-100 p-3 rounded-full">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 10v-1m0 0a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"></path></svg>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Ventas Confirmadas</h3>
                <p class="text-2xl font-bold text-gray-900">$<?php echo number_format($totalRevenue, 2); ?></p>
            </div>
        </div>

        <a href="activity.php" class="bg-white shadow rounded-lg p-6 flex items-center gap-4 border border-transparent hover:border-amber-400 transition-colors">
            <div class="bg-amber-100 p-3 rounded-full">
                <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path></svg>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Mi Actividad</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo $myActivitiesCount; ?></p>
                <span class="text-xs text-amber-600">Ver historial &rarr;</span>
            </div>
        </a>
    </div>

    <!-- Tabla de Pedidos Recientes -->
    <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Órdenes Recientes</h3>
            <a href="orders.php" class="text-sm font-semibold text-blue-600 hover:underline">Ver todas &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">ID Pedido</th>
                        <th scope="col" class="px-6 py-3">Cliente</th>
                        <th scope="col" class="px-6 py-3">Total</th>
                        <th scope="col" class="px-6 py-3">Estado</th>
                        <th scope="col" class="px-6 py-3">Fecha</th>
                        <th scope="col" class="px-6 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($recentOrders) > 0): foreach ($recentOrders as $order): ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900">#<?php echo htmlspecialchars($order['id']); ?></th>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($order['user_name']); ?></td>
                        <td class="px-6 py-4">$<?php echo number_format((float)($order['total'] ?? 0), 2); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 font-semibold leading-tight rounded-full <?php
                                if (($order['status'] ?? '') === 'paid') {
                                    echo 'text-green-700 bg-green-100';
                                } elseif (($order['status'] ?? '') === 'shipped') {
                                    echo 'text-blue-700 bg-blue-100';
                                } elseif (($order['status'] ?? '') === 'pending') {
                                    echo 'text-yellow-700 bg-yellow-100';
                                } else {
                                    echo 'text-red-700 bg-red-100';
                                }
                            ?>">
                                <?php echo htmlspecialchars(ucfirst($order['status'] ?? 'pending')); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4"><?php echo date("d M, Y", strtotime($order['created_at'] ?? 'now')); ?></td>
                        <td class="px-6 py-4">
                            <a href="view_order.php?id=<?php echo urlencode($order['id']); ?>" class="font-medium text-[var(--primary-color)] hover:underline">Ver Detalles</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center py-6">No hay pedidos recientes.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Gráfica de Ventas -->
    <div class="mt-8 bg-white rounded-lg shadow-sm border border-[var(--border-color)] p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Ingresos y Órdenes (Últimos 7 días)</h3>
        <div style="height: 360px;">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [
                    {
                        label: 'Ingresos ($)',
                        data: <?php echo json_encode($chartRevenueData); ?>,
                        borderColor: 'rgba(61, 152, 244, 1)',
                        backgroundColor: 'rgba(61, 152, 244, 0.2)',
                        borderWidth: 2,
                        yAxisID: 'y-revenue',
                        tension: 0.3
                    },
                    {
                        label: 'Órdenes',
                        data: <?php echo json_encode($chartOrdersData); ?>,
                        borderColor: 'rgba(234, 179, 8, 1)',
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
                        title: { display: true, text: 'Ingresos ($)' }
                    },
                    'y-orders': {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        title: { display: true, text: 'Nº de Órdenes' },
                        grid: { drawOnChartArea: false },
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

