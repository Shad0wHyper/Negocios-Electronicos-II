<?php
// support/dashboard.php - Panel de Soporte (Tickets · Atención al Cliente · Resolución)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_support.php';
require_once __DIR__ . '/../includes/support_helpers.php';

$currentSupportId = (string)($_SESSION['user']['id'] ?? '');

$allTickets = [];
$openCount = 0;
$inProgressCount = 0;
$closedCount = 0;
$myTicketsCount = 0;

$ticketsDocs = $db->collection('support_tickets')->documents();
foreach ($ticketsDocs as $doc) {
    if ($doc->exists()) {
        $t = $doc->data();
        $t['id'] = $doc->id();
        $status = $t['status'] ?? 'abierto';

        if ($status === 'abierto') {
            $openCount++;
        } elseif ($status === 'en_proceso') {
            $inProgressCount++;
        } elseif ($status === 'cerrado') {
            $closedCount++;
        }

        if ($currentSupportId !== '' && (string)($t['assigned_to_id'] ?? '') === $currentSupportId) {
            $myTicketsCount++;
        }

        $allTickets[] = $t;
    }
}

// Priorizar tickets no cerrados y ordenar por última actualización DESC
usort($allTickets, function ($a, $b) {
    $aClosed = (($a['status'] ?? 'abierto') === 'cerrado') ? 1 : 0;
    $bClosed = (($b['status'] ?? 'abierto') === 'cerrado') ? 1 : 0;
    if ($aClosed !== $bClosed) {
        return $aClosed - $bClosed;
    }
    $timeA = strtotime($a['updated_at'] ?? $a['created_at'] ?? '0');
    $timeB = strtotime($b['updated_at'] ?? $b['created_at'] ?? '0');
    return $timeB - $timeA;
});

$recentTickets = array_slice($allTickets, 0, 8);

// Datos para gráfica de los últimos 7 días
$dateRange = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dateRange[$date] = ['created' => 0, 'closed' => 0];
}

$sevenDaysAgo = date('Y-m-d 00:00:00', strtotime('-6 days'));
foreach ($allTickets as $t) {
    $createdAt = (string)($t['created_at'] ?? '');
    if ($createdAt >= $sevenDaysAgo) {
        $cDate = substr($createdAt, 0, 10);
        if (isset($dateRange[$cDate])) {
            $dateRange[$cDate]['created']++;
        }
    }
    $closedAt = (string)($t['closed_at'] ?? '');
    if (($t['status'] ?? '') === 'cerrado' && $closedAt >= $sevenDaysAgo) {
        $clDate = substr($closedAt, 0, 10);
        if (isset($dateRange[$clDate])) {
            $dateRange[$clDate]['closed']++;
        }
    }
}

$chartLabels = [];
$chartCreatedData = [];
$chartClosedData = [];
foreach ($dateRange as $date => $counts) {
    $chartLabels[] = date('d M', strtotime($date));
    $chartCreatedData[] = $counts['created'];
    $chartClosedData[] = $counts['closed'];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-bold text-[var(--text-primary)]">Dashboard de Soporte</h2>
            <p class="mt-1 text-sm text-[var(--text-secondary)]">Panel operativo para atención al cliente, seguimiento y cierre de tickets.</p>
        </div>
        <a href="tickets.php?status=pendientes" class="rounded-md bg-[var(--primary-color)] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[var(--primary-color-hover)] transition-colors">
            Ver tickets pendientes
        </a>
    </div>

    <!-- Tarjeta de Rol Activo -->
    <div class="mb-8 rounded-md border-l-4 border-amber-500 bg-amber-50 p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div>
            <strong class="text-amber-800 text-base">Soporte</strong>
            <p class="mt-1 text-sm text-gray-600">Atención al Cliente · Tickets · Resolución</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="tickets.php?status=abierto" class="rounded bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 border border-amber-200 hover:bg-amber-100">Abiertos (<?php echo $openCount; ?>)</a>
            <a href="tickets.php?status=en_proceso" class="rounded bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 border border-blue-200 hover:bg-blue-100">En Proceso (<?php echo $inProgressCount; ?>)</a>
            <a href="tickets.php?status=cerrado" class="rounded bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 border border-emerald-200 hover:bg-emerald-100">Cerrados (<?php echo $closedCount; ?>)</a>
        </div>
    </div>

    <!-- KPIs de Soporte -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <a href="tickets.php?status=abierto" class="bg-white shadow rounded-lg p-6 flex items-center gap-4 border border-transparent hover:border-amber-400 transition-colors">
            <div class="bg-amber-100 p-3 rounded-full">
                <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Tickets Abiertos</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo $openCount; ?></p>
                <span class="text-xs text-amber-600">Atender ahora &rarr;</span>
            </div>
        </a>

        <a href="tickets.php?status=en_proceso" class="bg-white shadow rounded-lg p-6 flex items-center gap-4 border border-transparent hover:border-blue-400 transition-colors">
            <div class="bg-blue-100 p-3 rounded-full">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">En Proceso</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo $inProgressCount; ?></p>
                <span class="text-xs text-blue-600">Dar seguimiento &rarr;</span>
            </div>
        </a>

        <a href="tickets.php?status=cerrado" class="bg-white shadow rounded-lg p-6 flex items-center gap-4 border border-transparent hover:border-emerald-400 transition-colors">
            <div class="bg-emerald-100 p-3 rounded-full">
                <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Tickets Cerrados</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo $closedCount; ?></p>
                <span class="text-xs text-emerald-600">Ver historial &rarr;</span>
            </div>
        </a>

        <a href="tickets.php?status=todos" class="bg-white shadow rounded-lg p-6 flex items-center gap-4 border border-transparent hover:border-slate-400 transition-colors">
            <div class="bg-slate-100 p-3 rounded-full">
                <svg class="h-6 w-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Atendidos por mí</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo $myTicketsCount; ?></p>
                <span class="text-xs text-slate-600">Ver todos &rarr;</span>
            </div>
        </a>
    </div>

    <!-- Tabla de Tickets Recientes / Por Atender -->
    <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Tickets Recientes / Por Atender</h3>
            <a href="tickets.php?status=pendientes" class="text-sm font-semibold text-blue-600 hover:underline">Ver todos &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Código</th>
                        <th scope="col" class="px-6 py-3">Cliente</th>
                        <th scope="col" class="px-6 py-3">Asunto</th>
                        <th scope="col" class="px-6 py-3">Categoría</th>
                        <th scope="col" class="px-6 py-3">Estado</th>
                        <th scope="col" class="px-6 py-3">Última actualización</th>
                        <th scope="col" class="px-6 py-3">Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($recentTickets) > 0): foreach ($recentTickets as $ticket):
                    $status = $ticket['status'] ?? 'abierto';
                    $statusMeta = getTicketStatusMeta($status);
                    $needsReply = ($status !== 'cerrado' && ($ticket['last_reply_by'] ?? 'cliente') === 'cliente');
                    $updatedAt = $ticket['updated_at'] ?? $ticket['created_at'] ?? 'now';
                ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <th scope="row" class="px-6 py-4 font-semibold text-gray-900 whitespace-nowrap">
                            <?php echo htmlspecialchars($ticket['ticket_code'] ?? $ticket['id']); ?>
                        </th>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($ticket['customer_name'] ?? 'Cliente'); ?></div>
                            <div class="text-xs text-gray-400"><?php echo htmlspecialchars($ticket['customer_email'] ?? ''); ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($ticket['subject'] ?? 'Sin asunto'); ?></div>
                            <?php if ($needsReply): ?>
                                <span class="inline-block mt-1 px-2 py-0.5 text-[11px] font-semibold rounded bg-red-100 text-red-700">Requiere respuesta</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($ticket['category'] ?? 'General'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $statusMeta['tw_class']; ?>">
                                <?php echo htmlspecialchars($statusMeta['label']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M, Y H:i', strtotime($updatedAt)); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="view_ticket.php?id=<?php echo urlencode($ticket['id']); ?>" class="font-medium text-[var(--primary-color)] hover:underline">Atender</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center py-6">No hay tickets registrados todavía.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Gráfica de Tickets de los últimos 7 días -->
    <div class="mt-8 bg-white rounded-lg shadow-sm border border-[var(--border-color)] p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Tickets Creados y Cerrados (Últimos 7 días)</h3>
        <div style="height: 320px;">
            <canvas id="supportChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('supportChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [
                    {
                        label: 'Tickets creados',
                        data: <?php echo json_encode($chartCreatedData); ?>,
                        borderColor: 'rgba(245, 158, 11, 1)',
                        backgroundColor: 'rgba(245, 158, 11, 0.2)',
                        borderWidth: 2,
                        tension: 0.3
                    },
                    {
                        label: 'Tickets cerrados',
                        data: <?php echo json_encode($chartClosedData); ?>,
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.2)',
                        borderWidth: 2,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
