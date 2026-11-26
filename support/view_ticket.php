<?php
// support/view_ticket.php - Detalle, conversación y resolución de un ticket
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_support.php';
require_once __DIR__ . '/../includes/support_helpers.php';

$ticketId = trim($_GET['id'] ?? '');
if ($ticketId === '') {
    header('Location: tickets.php');
    exit;
}

$ticketRef = $db->collection('support_tickets')->document($ticketId);
$snapshot = $ticketRef->snapshot();
if (!$snapshot->exists()) {
    header('Location: tickets.php');
    exit;
}

$ticket = $snapshot->data();
$ticket['id'] = $snapshot->id();

$agentId = (string)($_SESSION['user']['id'] ?? '');
$agentName = trim((string)($_SESSION['user']['name'] ?? 'Soporte'));
if ($agentName === '') {
    $agentName = 'Soporte';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $now = date('Y-m-d H:i:s');
    $currentStatus = $ticket['status'] ?? 'abierto';

    if ($action === 'reply') {
        $messageText = trim($_POST['message'] ?? '');
        $submitMode = $_POST['submit_mode'] ?? 'reply';

        if ($currentStatus === 'cerrado') {
            $error = 'Este ticket ya está cerrado. Reábrelo si necesitas enviar otra respuesta.';
        } elseif ($messageText === '') {
            $error = 'La respuesta no puede estar vacía.';
        } else {
            $messages = is_array($ticket['messages'] ?? null) ? $ticket['messages'] : [];
            $messages[] = [
                'sender_id' => $agentId,
                'sender_name' => $agentName,
                'sender_role' => 'soporte',
                'message' => $messageText,
                'created_at' => $now,
            ];

            $shouldClose = ($submitMode === 'reply_and_close');
            $updateData = [
                'messages' => $messages,
                'status' => $shouldClose ? 'cerrado' : 'en_proceso',
                'assigned_to_id' => $agentId,
                'assigned_to_name' => $agentName,
                'last_reply_by' => 'soporte',
                'updated_at' => $now,
            ];

            if ($shouldClose) {
                $updateData['closed_at'] = $now;
                $updateData['closed_by'] = $agentName;
            }

            $ticketRef->set($updateData, ['merge' => true]);
            $msgCode = $shouldClose ? 'replied_closed' : 'replied';
            header('Location: view_ticket.php?id=' . urlencode($ticketId) . '&msg=' . $msgCode);
            exit;
        }
    } elseif ($action === 'close_ticket') {
        $updateData = [
            'status' => 'cerrado',
            'closed_at' => $now,
            'closed_by' => $agentName,
            'updated_at' => $now,
        ];
        if (empty($ticket['assigned_to_id'])) {
            $updateData['assigned_to_id'] = $agentId;
            $updateData['assigned_to_name'] = $agentName;
        }
        $ticketRef->set($updateData, ['merge' => true]);
        header('Location: view_ticket.php?id=' . urlencode($ticketId) . '&msg=closed');
        exit;
    } elseif ($action === 'reopen_ticket') {
        $ticketRef->set([
            'status' => 'en_proceso',
            'closed_at' => '',
            'closed_by' => '',
            'assigned_to_id' => $agentId,
            'assigned_to_name' => $agentName,
            'updated_at' => $now,
        ], ['merge' => true]);
        header('Location: view_ticket.php?id=' . urlencode($ticketId) . '&msg=reopened');
        exit;
    } elseif ($action === 'update_priority') {
        $newPriority = $_POST['priority'] ?? 'normal';
        if (in_array($newPriority, ['baja', 'normal', 'alta'], true)) {
            $ticketRef->set([
                'priority' => $newPriority,
                'updated_at' => $now,
            ], ['merge' => true]);
            header('Location: view_ticket.php?id=' . urlencode($ticketId) . '&msg=priority');
            exit;
        }
    }
}

$status = $ticket['status'] ?? 'abierto';
$statusMeta = getTicketStatusMeta($status);
$priority = $ticket['priority'] ?? 'normal';
$priorityMeta = getTicketPriorityMeta($priority);
$messages = is_array($ticket['messages'] ?? null) ? $ticket['messages'] : [];

$flashMessage = '';
switch ($_GET['msg'] ?? '') {
    case 'replied':
        $flashMessage = 'Respuesta enviada al cliente. El ticket pasó a estado En Proceso.';
        break;
    case 'replied_closed':
        $flashMessage = 'Respuesta enviada y ticket marcado como Cerrado.';
        break;
    case 'closed':
        $flashMessage = 'El ticket fue marcado como Cerrado.';
        break;
    case 'reopened':
        $flashMessage = 'El ticket fue reabierto y asignado a tu cuenta.';
        break;
    case 'priority':
        $flashMessage = 'Prioridad del ticket actualizada.';
        break;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <a href="tickets.php?status=pendientes" class="inline-flex items-center text-sm font-semibold text-gray-600 hover:text-[var(--primary-color)]">
            &larr; Volver a Tickets
        </a>
        <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">
            Código: <?php echo htmlspecialchars($ticket['ticket_code'] ?? $ticket['id']); ?>
        </span>
    </div>

    <?php if ($flashMessage !== ''): ?>
        <div class="mb-6 rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm font-medium text-emerald-800">
            <?php echo htmlspecialchars($flashMessage); ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="mb-6 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm font-medium text-red-700">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Columna Izquierda: Conversación y Respuesta -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">
                                <?php echo htmlspecialchars($ticket['ticket_code'] ?? $ticket['id']); ?>
                            </span>
                            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full <?php echo $statusMeta['tw_class']; ?>">
                                <?php echo htmlspecialchars($statusMeta['label']); ?>
                            </span>
                            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full <?php echo $priorityMeta['tw_class']; ?>">
                                Prioridad: <?php echo htmlspecialchars($priorityMeta['label']); ?>
                            </span>
                            <span class="px-2.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700">
                                <?php echo htmlspecialchars($ticket['category'] ?? 'Otro'); ?>
                            </span>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($ticket['subject'] ?? 'Sin asunto'); ?></h2>
                    </div>
                </div>
            </div>

            <!-- Hilo de mensajes -->
            <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] p-6 space-y-4">
                <h3 class="text-base font-semibold text-gray-800 border-b pb-3">Conversación (<?php echo count($messages); ?>)</h3>

                <?php if (count($messages) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($messages as $m):
                            $isSupport = (($m['sender_role'] ?? 'cliente') === 'soporte');
                        ?>
                            <div class="flex <?php echo $isSupport ? 'justify-end' : 'justify-start'; ?>">
                                <div class="max-w-[85%] rounded-lg p-4 border <?php echo $isSupport ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200'; ?>">
                                    <div class="flex items-center justify-between gap-4 mb-1.5">
                                        <span class="text-xs font-bold <?php echo $isSupport ? 'text-blue-800' : 'text-gray-800'; ?>">
                                            <?php echo htmlspecialchars($m['sender_name'] ?? ($isSupport ? 'Soporte' : 'Cliente')); ?>
                                            <span class="ml-1 font-normal text-gray-500">
                                                (<?php echo $isSupport ? 'Soporte' : 'Cliente'; ?>)
                                            </span>
                                        </span>
                                        <span class="text-[11px] text-gray-400">
                                            <?php echo !empty($m['created_at']) ? date('d M, Y H:i', strtotime($m['created_at'])) : ''; ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-800 whitespace-pre-line leading-relaxed"><?php echo htmlspecialchars($m['message'] ?? ''); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-500">Este ticket aún no tiene mensajes registrados.</p>
                <?php endif; ?>
            </div>

            <!-- Formulario de respuesta o banner de cerrado -->
            <?php if ($status !== 'cerrado'): ?>
                <form method="POST" action="view_ticket.php?id=<?php echo urlencode($ticketId); ?>" class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] p-6 space-y-4">
                    <input type="hidden" name="action" value="reply">
                    <div>
                        <label for="message" class="block text-sm font-semibold text-gray-800 mb-2">Responder al cliente</label>
                        <textarea id="message" name="message" rows="4" required placeholder="Escribe la solución o seguimiento para el cliente..." class="w-full rounded-md border-gray-300 text-sm shadow-sm"></textarea>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button type="submit" name="submit_mode" value="reply" class="px-5 py-2.5 rounded-md bg-[var(--primary-color)] text-white text-sm font-semibold hover:bg-[var(--primary-color-hover)] transition-colors">
                            Enviar respuesta
                        </button>
                        <button type="submit" name="submit_mode" value="reply_and_close" class="px-5 py-2.5 rounded-md bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition-colors">
                            Enviar y cerrar ticket
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-6">
                    <h4 class="text-base font-bold text-emerald-900">Ticket Cerrado</h4>
                    <p class="mt-1 text-sm text-emerald-800">
                        Este caso fue marcado como resuelto/cerrado
                        <?php if (!empty($ticket['closed_by'])): ?>
                            por <strong><?php echo htmlspecialchars($ticket['closed_by']); ?></strong>
                        <?php endif; ?>
                        <?php if (!empty($ticket['closed_at'])): ?>
                            el <?php echo date('d M, Y H:i', strtotime($ticket['closed_at'])); ?>.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Columna Derecha: Datos del Cliente y Acciones -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] p-6 space-y-4">
                <h3 class="text-base font-semibold text-gray-800 border-b pb-3">Detalles del Ticket</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="block text-xs font-semibold uppercase text-gray-400">Cliente</span>
                        <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($ticket['customer_name'] ?? 'Cliente'); ?></span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold uppercase text-gray-400">Correo electrónico</span>
                        <span class="text-gray-700"><?php echo htmlspecialchars($ticket['customer_email'] ?? ''); ?></span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold uppercase text-gray-400">Agente asignado</span>
                        <span class="text-gray-700"><?php echo !empty($ticket['assigned_to_name']) ? htmlspecialchars($ticket['assigned_to_name']) : 'Sin asignar'; ?></span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold uppercase text-gray-400">Fecha de creación</span>
                        <span class="text-gray-700"><?php echo !empty($ticket['created_at']) ? date('d M, Y H:i', strtotime($ticket['created_at'])) : 'N/A'; ?></span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold uppercase text-gray-400">Última actualización</span>
                        <span class="text-gray-700"><?php echo !empty($ticket['updated_at']) ? date('d M, Y H:i', strtotime($ticket['updated_at'])) : 'N/A'; ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] p-6 space-y-4">
                <h3 class="text-base font-semibold text-gray-800 border-b pb-3">Gestión</h3>

                <form method="POST" action="view_ticket.php?id=<?php echo urlencode($ticketId); ?>" class="space-y-3">
                    <input type="hidden" name="action" value="update_priority">
                    <label for="priority" class="block text-xs font-semibold uppercase text-gray-500">Prioridad</label>
                    <div class="flex gap-2">
                        <select id="priority" name="priority" class="flex-1 rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="baja" <?php echo ($priority === 'baja') ? 'selected' : ''; ?>>Baja</option>
                            <option value="normal" <?php echo ($priority === 'normal') ? 'selected' : ''; ?>>Normal</option>
                            <option value="alta" <?php echo ($priority === 'alta') ? 'selected' : ''; ?>>Alta</option>
                        </select>
                        <button type="submit" class="px-3 py-2 rounded-md bg-gray-800 text-white text-xs font-semibold hover:bg-gray-700">
                            Guardar
                        </button>
                    </div>
                </form>

                <div class="pt-3 border-t">
                    <?php if ($status !== 'cerrado'): ?>
                        <form method="POST" action="view_ticket.php?id=<?php echo urlencode($ticketId); ?>" onsubmit="return confirm('¿Confirmas marcar este ticket como cerrado? El cliente ya no podrá enviar nuevas respuestas.');">
                            <input type="hidden" name="action" value="close_ticket">
                            <button type="submit" class="w-full py-2.5 px-4 rounded-md bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition-colors">
                                Marcar como cerrado
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="view_ticket.php?id=<?php echo urlencode($ticketId); ?>">
                            <input type="hidden" name="action" value="reopen_ticket">
                            <button type="submit" class="w-full py-2.5 px-4 rounded-md bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 transition-colors">
                                Reabrir ticket
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
