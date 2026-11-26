<?php
// support/tickets.php - Listado y filtrado de tickets de soporte
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_support.php';
require_once __DIR__ . '/../includes/support_helpers.php';

$statusFilter = $_GET['status'] ?? 'pendientes';
if (!in_array($statusFilter, ['pendientes', 'abierto', 'en_proceso', 'cerrado', 'todos'], true)) {
    $statusFilter = 'pendientes';
}
$searchQuery = trim($_GET['q'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');

$allTickets = [];
$counts = [
    'pendientes' => 0,
    'abierto' => 0,
    'en_proceso' => 0,
    'cerrado' => 0,
    'todos' => 0,
];

$docs = $db->collection('support_tickets')->documents();
foreach ($docs as $doc) {
    if ($doc->exists()) {
        $t = $doc->data();
        $t['id'] = $doc->id();
        $st = $t['status'] ?? 'abierto';

        $counts['todos']++;
        if ($st === 'abierto') {
            $counts['abierto']++;
            $counts['pendientes']++;
        } elseif ($st === 'en_proceso') {
            $counts['en_proceso']++;
            $counts['pendientes']++;
        } elseif ($st === 'cerrado') {
            $counts['cerrado']++;
        }

        $allTickets[] = $t;
    }
}

$filteredTickets = array_filter($allTickets, function ($t) use ($statusFilter, $searchQuery, $categoryFilter) {
    $st = $t['status'] ?? 'abierto';
    if ($statusFilter === 'pendientes') {
        if (!in_array($st, ['abierto', 'en_proceso'], true)) {
            return false;
        }
    } elseif ($statusFilter !== 'todos' && $st !== $statusFilter) {
        return false;
    }

    if ($categoryFilter !== '' && ($t['category'] ?? '') !== $categoryFilter) {
        return false;
    }

    if ($searchQuery !== '') {
        $haystack = mb_strtolower(
            ($t['ticket_code'] ?? '') . ' ' .
            ($t['customer_name'] ?? '') . ' ' .
            ($t['customer_email'] ?? '') . ' ' .
            ($t['subject'] ?? ''),
            'UTF-8'
        );
        if (mb_strpos($haystack, mb_strtolower($searchQuery, 'UTF-8')) === false) {
            return false;
        }
    }

    return true;
});

$filteredTickets = array_values($filteredTickets);
usort($filteredTickets, function ($a, $b) {
    $timeA = strtotime($a['updated_at'] ?? $a['created_at'] ?? '0');
    $timeB = strtotime($b['updated_at'] ?? $b['created_at'] ?? '0');
    return $timeB - $timeA;
});

$categories = getSupportCategories();

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-bold text-[var(--text-primary)]">Tickets de Soporte</h2>
            <p class="mt-1 text-sm text-[var(--text-secondary)]">Responde consultas de clientes y marca como cerrados los casos resueltos.</p>
        </div>
    </div>

    <!-- Pestañas de estado -->
    <div class="flex flex-wrap gap-2 mb-6">
        <?php
        $tabs = [
            'pendientes' => 'Pendientes',
            'abierto' => 'Abiertos',
            'en_proceso' => 'En Proceso',
            'cerrado' => 'Cerrados',
            'todos' => 'Todos',
        ];
        foreach ($tabs as $key => $label):
            $isActive = ($statusFilter === $key);
            $tabClass = $isActive
                ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)]'
                : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50';
        ?>
            <a href="tickets.php?status=<?php echo urlencode($key); ?><?php echo $searchQuery !== '' ? '&q=' . urlencode($searchQuery) : ''; ?><?php echo $categoryFilter !== '' ? '&category=' . urlencode($categoryFilter) : ''; ?>"
               class="px-4 py-2 rounded-md text-sm font-semibold border transition-colors <?php echo $tabClass; ?>">
                <?php echo htmlspecialchars($label); ?> (<?php echo $counts[$key]; ?>)
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Barra de búsqueda y categoría -->
    <form method="GET" action="tickets.php" class="bg-white p-4 rounded-lg shadow-sm border border-[var(--border-color)] mb-6 flex flex-wrap gap-4 items-end">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
        <div class="flex-1 min-w-[220px]">
            <label for="q" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Buscar</label>
            <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Código, cliente, correo o asunto..." class="w-full rounded-md border-gray-300 text-sm shadow-sm">
        </div>
        <div class="w-full sm:w-56">
            <label for="category" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Categoría</label>
            <select id="category" name="category" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                <option value="">Todas las categorías</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($categoryFilter === $cat) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 rounded-md bg-[var(--primary-color)] text-white text-sm font-semibold hover:bg-[var(--primary-color-hover)]">Filtrar</button>
            <?php if ($searchQuery !== '' || $categoryFilter !== ''): ?>
                <a href="tickets.php?status=<?php echo urlencode($statusFilter); ?>" class="px-4 py-2 rounded-md bg-gray-100 text-gray-700 text-sm font-semibold hover:bg-gray-200">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Tabla de tickets -->
    <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Código</th>
                        <th scope="col" class="px-6 py-3">Cliente</th>
                        <th scope="col" class="px-6 py-3">Asunto</th>
                        <th scope="col" class="px-6 py-3">Categoría</th>
                        <th scope="col" class="px-6 py-3">Prioridad</th>
                        <th scope="col" class="px-6 py-3">Estado</th>
                        <th scope="col" class="px-6 py-3">Mensajes</th>
                        <th scope="col" class="px-6 py-3">Asignado a</th>
                        <th scope="col" class="px-6 py-3">Actualizado</th>
                        <th scope="col" class="px-6 py-3">Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($filteredTickets) > 0): foreach ($filteredTickets as $ticket):
                    $st = $ticket['status'] ?? 'abierto';
                    $statusMeta = getTicketStatusMeta($st);
                    $priorityMeta = getTicketPriorityMeta($ticket['priority'] ?? 'normal');
                    $messagesCount = is_array($ticket['messages'] ?? null) ? count($ticket['messages']) : 0;
                    $needsReply = ($st !== 'cerrado' && ($ticket['last_reply_by'] ?? 'cliente') === 'cliente');
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
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($ticket['category'] ?? 'Otro'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?php echo $priorityMeta['tw_class']; ?>">
                                <?php echo htmlspecialchars($priorityMeta['label']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $statusMeta['tw_class']; ?>">
                                <?php echo htmlspecialchars($statusMeta['label']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center font-medium text-gray-700"><?php echo $messagesCount; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php echo !empty($ticket['assigned_to_name']) ? htmlspecialchars($ticket['assigned_to_name']) : '<span class="text-gray-400">Sin asignar</span>'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M, Y H:i', strtotime($updatedAt)); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="view_ticket.php?id=<?php echo urlencode($ticket['id']); ?>" class="font-medium text-[var(--primary-color)] hover:underline">
                                <?php echo ($st === 'cerrado') ? 'Ver detalle' : 'Atender'; ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="10" class="text-center py-8 text-gray-500">No se encontraron tickets con los filtros seleccionados.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
