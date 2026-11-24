<?php
// profile.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/support_helpers.php';
require_once 'includes/header.php';

$currentUserId = (string)$_SESSION['user']['id'];

// Obtener dirección principal del usuario
$addressesRef = $db->collection('addresses');
$query = $addressesRef->where('user_id', '=', $currentUserId);
$documents = $query->documents();
$addresses = [];
foreach ($documents as $doc) {
    if ($doc->exists()) {
        $a = $doc->data();
        $a['id'] = $doc->id();
        $addresses[] = $a;
    }
}
usort($addresses, function ($a, $b) {
    $dateA = $a['created_at'] ?? '2000-01-01 00:00:00';
    $dateB = $b['created_at'] ?? '2000-01-01 00:00:00';
    return strtotime($dateB) - strtotime($dateA);
});
$address = !empty($addresses) ? $addresses[0] : null;

// Obtener tickets de soporte del cliente
$ticketsQuery = $db->collection('support_tickets')->where('user_id', '=', $currentUserId)->documents();
$allUserTickets = [];
foreach ($ticketsQuery as $doc) {
    if ($doc->exists()) {
        $t = $doc->data();
        $t['id'] = $doc->id();
        $allUserTickets[] = $t;
    }
}
usort($allUserTickets, function ($a, $b) {
    $timeA = strtotime($a['updated_at'] ?? $a['created_at'] ?? '0');
    $timeB = strtotime($b['updated_at'] ?? $b['created_at'] ?? '0');
    return $timeB - $timeA;
});

$openTickets = [];
$closedTickets = [];
foreach ($allUserTickets as $t) {
    if (($t['status'] ?? 'abierto') === 'cerrado') {
        $closedTickets[] = $t;
    } else {
        $openTickets[] = $t;
    }
}
?>

<style>
    /* Botón de Cerrar Sesión en perfil */
    .logout-button { margin-left: auto; }
    .logout-btn {
        display: inline-block; padding: 0.55rem 1.1rem;
        background: #7c6b61; color: #fff; border-radius: 6px;
        text-decoration: none; font-size: 0.9rem; font-weight: 600;
        transition: background 0.2s;
    }
    .logout-btn:hover { background: #5a503f; }
    .link-btn {
        color: #7c6b61; text-decoration: none; font-weight: 500;
    }
    .link-btn:hover { text-decoration: underline; }
</style>

<section class="client-interface profile">
    <div class="profile-header">
        <!-- Foto de perfil genérica -->
        <img
            src="https://w7.pngwing.com/pngs/1000/665/png-transparent-computer-icons-profile-s-free-angle-sphere-profile-cliparts-free.png"
            alt="Foto de perfil" class="profile-pic"
        />
        <div class="profile-info">
            <h2><?= htmlspecialchars($_SESSION['user']['name'], ENT_QUOTES) ?></h2>
            <p><?= htmlspecialchars($_SESSION['user']['email'], ENT_QUOTES) ?></p>
        </div>
        <div class="logout-button">
            <a href="logout.php" class="btn logout-btn">Cerrar Sesión</a>
        </div>
    </div>

    <div class="profile-sections">
        <div class="profile-item">
            <img
                src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/ui-assets%2Fmarcador-de-posicion%201.png?alt=media"
                alt="Ubicacion" class="icon"
            />
            <div>
                <h3>Dirección</h3>
                <?php if ($address): ?>
                    <p>
                        <?= htmlspecialchars($address['first_name'] . ' ' . $address['last_name']) ?> <br>
                        <?= htmlspecialchars($address['address'] . ', ' . $address['city'] . ', ' . $address['state'] . ' CP ' . $address['zip']) ?> <br>
                        T: <?= htmlspecialchars($address['phone']) ?>
                    </p>
                    <a href="address.php" class="link-btn">Editar Dirección</a>
                <?php else: ?>
                    <p>No tienes direcciones guardadas.</p>
                    <a href="address.php" class="link-btn">Agregar Dirección</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-item">
            <img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/ui-assets%2FShopping.png?alt=media" alt="Pedidos" class="icon" />
            <div>
                <h3>Pedidos</h3>
                <p><a href="orders.php" class="link-btn">Ver mis pedidos</a></p>
            </div>
        </div>

        <div class="profile-item">
            <img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/ui-assets%2FHistorial_compras.png?alt=media" alt="Historial de compras" class="icon" />
            <div>
                <h3>Historial de Compras</h3>
                <p><a href="orders.php" class="link-btn">Ver historial</a></p>
            </div>
        </div>
    </div>

    <!-- APARTADO DE TICKETS DE SOPORTE -->
    <div class="profile-tickets-section" id="mis-tickets">
        <div class="profile-tickets-header">
            <div>
                <h3>Mis Tickets de Soporte</h3>
                <p class="profile-tickets-subtitle">
                    Tienes <strong><?= count($openTickets) ?></strong> <?= count($openTickets) === 1 ? 'ticket abierto' : 'tickets abiertos' ?>.
                </p>
            </div>
            <a href="contact.php#soporte-contacto" class="ticket-new-btn">+ Nuevo Ticket</a>
        </div>

        <h4 class="ticket-group-title">Tickets Abiertos</h4>
        <?php if (count($openTickets) > 0): ?>
            <div class="ticket-list">
                <?php foreach ($openTickets as $ticket):
                    $st = $ticket['status'] ?? 'abierto';
                    $statusMeta = getTicketStatusMeta($st);
                    $hasSupportReply = (($ticket['last_reply_by'] ?? 'cliente') === 'soporte');
                    $updatedAt = $ticket['updated_at'] ?? $ticket['created_at'] ?? 'now';
                ?>
                    <div class="ticket-card">
                        <div class="ticket-card-main">
                            <div class="ticket-card-meta">
                                <span class="ticket-code">#<?= htmlspecialchars($ticket['ticket_code'] ?? $ticket['id']) ?></span>
                                <span class="ticket-badge <?= htmlspecialchars($statusMeta['client_class']) ?>">
                                    <?= htmlspecialchars($statusMeta['label']) ?>
                                </span>
                                <span class="ticket-category-pill"><?= htmlspecialchars($ticket['category'] ?? 'Otro') ?></span>
                                <?php if ($hasSupportReply): ?>
                                    <span class="ticket-reply-pill">Soporte respondió</span>
                                <?php endif; ?>
                            </div>
                            <h5 class="ticket-subject"><?= htmlspecialchars($ticket['subject'] ?? 'Sin asunto') ?></h5>
                            <p class="ticket-updated">Última actualización: <?= date('d/m/Y H:i', strtotime($updatedAt)) ?></p>
                        </div>
                        <div class="ticket-card-actions">
                            <a href="ticket.php?id=<?= urlencode($ticket['id']) ?>" class="ticket-action-btn">Ver conversación</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="ticket-empty-state">
                <p>No tienes tickets abiertos en este momento.</p>
                <a href="contact.php#soporte-contacto" class="ticket-new-btn">Contactar a Soporte</a>
            </div>
        <?php endif; ?>

        <?php if (count($closedTickets) > 0): ?>
            <h4 class="ticket-group-title ticket-group-title-closed">Tickets Cerrados (Historial)</h4>
            <div class="ticket-list">
                <?php foreach ($closedTickets as $ticket):
                    $statusMeta = getTicketStatusMeta('cerrado');
                    $updatedAt = $ticket['closed_at'] ?: ($ticket['updated_at'] ?? $ticket['created_at'] ?? 'now');
                ?>
                    <div class="ticket-card ticket-card-closed">
                        <div class="ticket-card-main">
                            <div class="ticket-card-meta">
                                <span class="ticket-code">#<?= htmlspecialchars($ticket['ticket_code'] ?? $ticket['id']) ?></span>
                                <span class="ticket-badge <?= htmlspecialchars($statusMeta['client_class']) ?>">
                                    <?= htmlspecialchars($statusMeta['label']) ?>
                                </span>
                                <span class="ticket-category-pill"><?= htmlspecialchars($ticket['category'] ?? 'Otro') ?></span>
                            </div>
                            <h5 class="ticket-subject"><?= htmlspecialchars($ticket['subject'] ?? 'Sin asunto') ?></h5>
                            <p class="ticket-updated">Cerrado el <?= date('d/m/Y H:i', strtotime($updatedAt)) ?></p>
                        </div>
                        <div class="ticket-card-actions">
                            <a href="ticket.php?id=<?= urlencode($ticket['id']) ?>" class="ticket-action-btn ticket-action-btn-muted">Ver historial</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
