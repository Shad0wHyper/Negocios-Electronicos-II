<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

$totalCustomers = 0;
$activeCustomers = 0;

$stageOrder = ['Prospecto', 'Activo', 'Frecuente', 'Inactivo'];
$stageData = [];
foreach ($stageOrder as $stage) {
    $stageData[$stage] = 0;
}

$allCustomerIds = [];
$usersQuery = $db->collection('users')->where('role', '=', 'customer')->documents();
foreach ($usersQuery as $doc) {
    if ($doc->exists()) {
        $u = $doc->data();
        $id = $doc->id();
        $allCustomerIds[] = $id;
        $totalCustomers++;
        
        $stage = $u['crm_stage'] ?? 'Prospecto';
        if (in_array($stage, ['Activo', 'Frecuente'])) {
            $activeCustomers++;
        }
        
        if (array_key_exists($stage, $stageData)) {
            $stageData[$stage]++;
        }
    }
}

$monthStart = date('Y-m-01 00:00:00');
$monthInteractions = 0;
$customersWithInteractions = [];

$interactionCounts = [];

$interQuery = $db->collection('crm_interactions')->documents();
foreach ($interQuery as $doc) {
    if ($doc->exists()) {
        $inter = $doc->data();
        if (isset($inter['user_id'])) {
            $customersWithInteractions[] = $inter['user_id'];
        }
        
        $createdAt = $inter['created_at'] ?? '0';
        if ($createdAt >= $monthStart) {
            $monthInteractions++;
            $type = $inter['type'] ?? 'Nota';
            if (!isset($interactionCounts[$type])) {
                $interactionCounts[$type] = 0;
            }
            $interactionCounts[$type]++;
        }
    }
}

$customersWithInteractions = array_unique($customersWithInteractions);
$customersWithoutInteraction = count(array_diff($allCustomerIds, $customersWithInteractions));

$activePercentage = $totalCustomers > 0
    ? round(($activeCustomers / $totalCustomers) * 100, 1)
    : 0;
$withoutInteractionPercentage = $totalCustomers > 0
    ? round(($customersWithoutInteraction / $totalCustomers) * 100, 1)
    : 0;

arsort($interactionCounts); // Sort descending by count
$interactionLabels = array_keys($interactionCounts);
$interactionData = array_values($interactionCounts);

$stageLabels = array_keys($stageData);

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-[var(--text-primary)]">Reportes y métricas</h2>
        <p class="mt-2 text-sm text-[var(--text-secondary)]">Indicadores para evaluar el desempeño del CRM.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4 mb-8">
        <div class="rounded-lg border border-[var(--border-color)] bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Total de clientes</p>
            <p class="mt-2 text-4xl font-bold text-green-600"><?php echo $totalCustomers; ?></p>
            <p class="mt-2 text-xs text-gray-500">Clientes registrados</p>
        </div>
        <div class="rounded-lg border border-[var(--border-color)] bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Clientes activos</p>
            <p class="mt-2 text-4xl font-bold text-[var(--primary-color)]"><?php echo $activeCustomers; ?></p>
            <p class="mt-2 text-xs text-gray-500"><?php echo $activePercentage; ?>% del total</p>
        </div>
        <div class="rounded-lg border border-[var(--border-color)] bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Interacciones este mes</p>
            <p class="mt-2 text-4xl font-bold text-gray-900"><?php echo $monthInteractions; ?></p>
            <p class="mt-2 text-xs text-gray-500">Registradas en CRM</p>
        </div>
        <div class="rounded-lg border border-[var(--border-color)] bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Clientes sin interacción</p>
            <p class="mt-2 text-4xl font-bold text-orange-500"><?php echo $customersWithoutInteraction; ?></p>
            <p class="mt-2 text-xs text-gray-500"><?php echo $withoutInteractionPercentage; ?>% del total</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-lg border border-[var(--border-color)] bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-gray-800">Interacciones por tipo</h3>
            <div class="h-80">
                <canvas id="interactionChart"></canvas>
            </div>
        </div>
        <div class="rounded-lg border border-[var(--border-color)] bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-gray-800">Clientes por etapa CRM</h3>
            <div class="h-80">
                <canvas id="stageChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const interactionLabels = <?php echo json_encode($interactionLabels, JSON_UNESCAPED_UNICODE); ?>;
    const interactionData = <?php echo json_encode($interactionData); ?>;
    const stageLabels = <?php echo json_encode($stageLabels, JSON_UNESCAPED_UNICODE); ?>;
    const stageData = <?php echo json_encode(array_values($stageData)); ?>;

    new Chart(document.getElementById('interactionChart'), {
        type: 'bar',
        data: {
            labels: interactionLabels,
            datasets: [{
                label: 'Interacciones',
                data: interactionData,
                backgroundColor: ['#f59e0b', '#3b82f6', '#06b6d4', '#22c55e', '#8b5cf6'],
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: { legend: { display: false } }
        }
    });

    new Chart(document.getElementById('stageChart'), {
        type: 'doughnut',
        data: {
            labels: stageLabels,
            datasets: [{
                data: stageData,
                backgroundColor: ['#14b8a6', '#22c55e', '#8b5cf6', '#f97316'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
