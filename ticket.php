<?php
// ticket.php - Conversación de ticket del lado del cliente
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/support_helpers.php';

$ticketId = trim($_GET['id'] ?? '');
if ($ticketId === '') {
    header('Location: profile.php#mis-tickets');
    exit;
}

$ticketRef = $db->collection('support_tickets')->document($ticketId);
$snapshot = $ticketRef->snapshot();
if (!$snapshot->exists()) {
    header('Location: profile.php#mis-tickets');
    exit;
}

$ticket = $snapshot->data();
$ticket['id'] = $snapshot->id();

$currentUserId = (string)$_SESSION['user']['id'];
if ((string)($ticket['user_id'] ?? '') !== $currentUserId) {
    header('Location: profile.php#mis-tickets');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reply') {
    $currentStatus = $ticket['status'] ?? 'abierto';
    $messageText = trim($_POST['message'] ?? '');

    if ($currentStatus === 'cerrado') {
        $error = 'Este ticket ya fue marcado como cerrado. Si tienes otra duda, crea un nuevo ticket desde Contacto.';
    } elseif ($messageText === '') {
        $error = 'Escribe un mensaje antes de enviar.';
    } else {
        $now = date('Y-m-d H:i:s');
        $messages = is_array($ticket['messages'] ?? null) ? $ticket['messages'] : [];
        $senderName = trim((string)($_SESSION['user']['name'] ?? 'Cliente'));
        if ($senderName === '') {
            $senderName = 'Cliente';
        }

        $messages[] = [
            'sender_id' => $currentUserId,
            'sender_name' => $senderName,
            'sender_role' => 'cliente',
            'message' => $messageText,
            'created_at' => $now,
        ];

        $ticketRef->set([
            'messages' => $messages,
            'last_reply_by' => 'cliente',
            'updated_at' => $now,
        ], ['merge' => true]);

        header('Location: ticket.php?id=' . urlencode($ticketId) . '&sent=1');
        exit;
    }
}

$status = $ticket['status'] ?? 'abierto';
$statusMeta = getTicketStatusMeta($status);
$messages = is_array($ticket['messages'] ?? null) ? $ticket['messages'] : [];
$sentSuccess = (($_GET['sent'] ?? '') === '1');

require_once 'includes/header.php';
?>

<section class="ticket-detail-container">
    <div class="ticket-back-row">
        <a href="profile.php#mis-tickets" class="ticket-back-link">&larr; Volver a Mi Perfil</a>
    </div>

    <div class="ticket-detail-header">
        <div class="ticket-card-meta">
            <span class="ticket-code">#<?= htmlspecialchars($ticket['ticket_code'] ?? $ticket['id']) ?></span>
            <span class="ticket-badge <?= htmlspecialchars($statusMeta['client_class']) ?>">
                <?= htmlspecialchars($statusMeta['label']) ?>
            </span>
            <span class="ticket-category-pill"><?= htmlspecialchars($ticket['category'] ?? 'Otro') ?></span>
        </div>
        <h1><?= htmlspecialchars($ticket['subject'] ?? 'Sin asunto') ?></h1>
        <p class="ticket-detail-meta">
            Creado el <?= !empty($ticket['created_at']) ? date('d/m/Y H:i', strtotime($ticket['created_at'])) : 'N/A' ?>
            <?php if (!empty($ticket['assigned_to_name'])): ?>
                &middot; Atendido por <strong><?= htmlspecialchars($ticket['assigned_to_name']) ?></strong>
            <?php endif; ?>
        </p>
    </div>

    <?php if ($sentSuccess): ?>
        <div class="contact-alert-success">
            Tu mensaje fue enviado al equipo de soporte.
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="contact-alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="ticket-thread">
        <?php if (count($messages) > 0): ?>
            <?php foreach ($messages as $m):
                $isSupport = (($m['sender_role'] ?? 'cliente') === 'soporte');
            ?>
                <div class="ticket-msg <?= $isSupport ? 'ticket-msg-support' : 'ticket-msg-client' ?>">
                    <div class="ticket-msg-header">
                        <strong>
                            <?= htmlspecialchars($m['sender_name'] ?? ($isSupport ? 'Soporte Xanarchy' : 'Tú')) ?>
                            <span>(<?= $isSupport ? 'Soporte' : 'Cliente' ?>)</span>
                        </strong>
                        <small><?= !empty($m['created_at']) ? date('d/m/Y H:i', strtotime($m['created_at'])) : '' ?></small>
                    </div>
                    <p><?= nl2br(htmlspecialchars($m['message'] ?? '')) ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="ticket-empty-thread">Aún no hay mensajes en este ticket.</p>
        <?php endif; ?>
    </div>

    <?php if ($status !== 'cerrado'): ?>
        <form method="POST" action="ticket.php?id=<?= urlencode($ticketId) ?>" class="ticket-reply-box">
            <input type="hidden" name="action" value="reply">
            <label for="message">Agregar respuesta</label>
            <textarea id="message" name="message" rows="4" placeholder="Escribe más detalles o responde al equipo de soporte..." required></textarea>
            <div class="ticket-reply-actions">
                <button type="submit" class="btn-primary-luxury">ENVIAR RESPUESTA</button>
            </div>
        </form>
    <?php else: ?>
        <div class="ticket-closed-banner">
            <div>
                <strong>Ticket resuelto y cerrado</strong>
                <p>
                    Este ticket fue cerrado<?= !empty($ticket['closed_at']) ? ' el ' . date('d/m/Y H:i', strtotime($ticket['closed_at'])) : '' ?>.
                    Si necesitas ayuda con algo más, puedes abrir un nuevo ticket.
                </p>
            </div>
            <a href="contact.php#soporte-contacto" class="ticket-new-btn">Abrir nuevo ticket</a>
        </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
