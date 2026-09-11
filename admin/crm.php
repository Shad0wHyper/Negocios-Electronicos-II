<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

// 0 & 1. Obtener datos y auto-clasificar
$usersDocs = $db->collection('users')->where('role', '=', 'customer')->documents();
$ordersDocs = $db->collection('orders')->documents();

$ordersByUser = [];
foreach ($ordersDocs as $od) {
    if ($od->exists()) {
        $o = $od->data();
        $uid = $o['user_id'] ?? '';
        if ($uid) {
            if (!isset($ordersByUser[$uid])) {
                $ordersByUser[$uid] = ['count' => 0, 'last_date' => ''];
            }
            $ordersByUser[$uid]['count']++;
            if (!isset($o['created_at'])) $o['created_at'] = '';
            if ($o['created_at'] > $ordersByUser[$uid]['last_date']) {
                $ordersByUser[$uid]['last_date'] = $o['created_at'];
            }
        }
    }
}

$stagesData = ['Prospecto' => 0, 'Activo' => 0, 'Frecuente' => 0, 'Inactivo' => 0];
$totalCustomers = 0;
$customers = [];

foreach ($usersDocs as $ud) {
    if ($ud->exists()) {
        $u = $ud->data();
        $u['id'] = $ud->id();
        $totalCustomers++;
        
        $uid = $u['id'];
        $manual = !empty($u['crm_stage_manual']) ? true : false;
        
        $total_orders = $ordersByUser[$uid]['count'] ?? 0;
        $last_order_date = $ordersByUser[$uid]['last_date'] ?? null;
        
        // Auto-classify
        if (!$manual) {
            $new_stage = 'Prospecto';
            $three_months_ago = date('Y-m-d H:i:s', strtotime('-3 months'));
            
            if ($total_orders > 0) {
                if ($last_order_date < $three_months_ago) {
                    $new_stage = 'Inactivo';
                } elseif ($total_orders >= 3) {
                    $new_stage = 'Frecuente';
                } else {
                    $new_stage = 'Activo';
                }
            } else {
                if (isset($u['created_at']) && $u['created_at'] < $three_months_ago) {
                    $new_stage = 'Inactivo';
                } else {
                    $new_stage = 'Prospecto';
                }
            }
            
            if (!isset($u['crm_stage']) || $u['crm_stage'] !== $new_stage) {
                $u['crm_stage'] = $new_stage;
                $db->collection('users')->document($uid)->set(['crm_stage' => $new_stage], ['merge' => true]);
            }
        }
        
        $stage = $u['crm_stage'] ?? 'Prospecto';
        if (!isset($stagesData[$stage])) {
            $stagesData[$stage] = 0;
        }
        $stagesData[$stage]++;
        
        $u['total_orders'] = $total_orders;
        $customers[] = $u;
    }
}

// Sort by created_at DESC
usort($customers, function($a, $b) {
    return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
});

// Preparar datos para gráfica
$stageLabels = array_keys($stagesData);
$stageCounts = array_values($stagesData);

// Colores para las etiquetas de estado
$stageColors = [
    'Prospecto' => 'bg-gray-100 text-gray-800',
    'Activo' => 'bg-green-100 text-green-800',
    'Frecuente' => 'bg-blue-100 text-blue-800',
    'Inactivo' => 'bg-red-100 text-red-800'
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-8">
        <h2 class="text-3xl font-bold text-[var(--text-primary)]">CRM - Gestión de Clientes</h2>
        <a href="activity.php" class="inline-flex items-center justify-center gap-2 rounded-md bg-[var(--primary-color)] px-4 py-2 text-sm font-semibold text-white hover:bg-[var(--primary-color-hover)]">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
            Mi actividad
        </a>
    </div>

    <!-- Resumen y Gráfica -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        
        <!-- Tarjeta de Métricas rápidas -->
        <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] p-6 flex flex-col justify-center">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Total de Clientes</h3>
            <p class="text-4xl font-bold text-[var(--primary-color)]"><?php echo $totalCustomers; ?></p>
            <p class="text-sm text-gray-500 mt-2">Registrados en la plataforma</p>
        </div>

        <!-- Gráfica de Etapas CRM -->
        <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] p-6 md:col-span-2">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Clientes por Etapa</h3>
            <div style="height: 200px; display: flex; justify-content: center;">
                <canvas id="crmStageChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Lista de Clientes -->
    <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] overflow-hidden">
        <div class="p-4 border-b flex justify-between items-center bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800">Listado de Clientes</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                    <tr>
                        <th scope="col" class="px-6 py-3">ID</th>
                        <th scope="col" class="px-6 py-3">Cliente</th>
                        <th scope="col" class="px-6 py-3">Email</th>
                        <th scope="col" class="px-6 py-3">Pedidos</th>
                        <th scope="col" class="px-6 py-3">Etapa CRM</th>
                        <th scope="col" class="px-6 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($customers) > 0): ?>
                    <?php foreach($customers as $client): 
                        $colorClass = $stageColors[$client['crm_stage']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-900">#<?php echo $client['id']; ?></td>
                        <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($client['name']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($client['email']); ?></td>
                        <td class="px-6 py-4 font-semibold"><?php echo $client['total_orders']; ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $colorClass; ?>">
                                <?php echo htmlspecialchars($client['crm_stage']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="crm_client.php?id=<?php echo $client['id']; ?>" class="text-[var(--primary-color)] hover:text-blue-700 font-medium">Ver Detalles &rarr;</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-6 text-gray-500">No hay clientes registrados aún.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('crmStageChart').getContext('2d');
    
    // Registrar el plugin
    Chart.register(ChartDataLabels);

    // Datos de PHP
    const labels = <?php echo json_encode($stageLabels); ?>;
    const dataCounts = <?php echo json_encode($stageCounts); ?>;
    
    // Paleta de colores consistente
    const backgroundColors = labels.map(label => {
        switch(label) {
            case 'Activo': return 'rgba(34, 197, 94, 0.8)'; // green-500
            case 'Frecuente': return 'rgba(59, 130, 246, 0.8)'; // blue-500
            case 'Inactivo': return 'rgba(239, 68, 68, 0.8)'; // red-500
            default: return 'rgba(156, 163, 175, 0.8)'; // gray-400 (Prospecto)
        }
    });

    if(dataCounts.length > 0) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: dataCounts,
                    backgroundColor: backgroundColors,
                    borderWidth: 1,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: { family: 'Inter', size: 13 }
                        }
                    },
                    datalabels: {
                        color: '#ffffff',
                        font: {
                            family: 'Inter',
                            weight: 'bold',
                            size: 16
                        },
                        formatter: function(value, context) {
                            return value > 0 ? value : ''; // Solo mostrar si es > 0
                        }
                    }
                },
                cutout: '55%' // Ligeramente más pequeña la rosquilla para dar espacio a los números
            }
        });
    } else {
        // Mostrar mensaje si no hay datos
        ctx.font = "14px Inter";
        ctx.fillStyle = "#6b7280";
        ctx.textAlign = "center";
        ctx.fillText("No hay datos suficientes", ctx.canvas.width/2, ctx.canvas.height/2);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
