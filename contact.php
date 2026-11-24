<?php
require_once 'includes/config.php';
require_once 'includes/support_helpers.php';

$formError = '';
$categories = getSupportCategories();
$isCustomer = isAuthenticatedCustomer();
$isLoggedIn = isset($_SESSION['user']);
$isStaff = $isLoggedIn && isStaffRole($_SESSION['user']['role'] ?? '');

$customerName = trim((string)($_SESSION['user']['name'] ?? ''));
$customerEmail = trim((string)($_SESSION['user']['email'] ?? ''));

$selectedCategory = $_POST['category'] ?? $categories[0];
$subjectInput = trim($_POST['subject'] ?? '');
$messageInput = trim($_POST['message'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isCustomer) {
        $formError = 'Debes iniciar sesión como cliente para enviar un ticket de soporte.';
    } else {
        $nameInput = trim($_POST['name'] ?? $customerName);
        if ($nameInput === '') {
            $nameInput = $customerName !== '' ? $customerName : 'Cliente';
        }
        if (!in_array($selectedCategory, $categories, true)) {
            $selectedCategory = 'Otro';
        }

        if ($subjectInput === '') {
            $formError = 'Por favor escribe un asunto para tu ticket.';
        } elseif (mb_strlen($subjectInput, 'UTF-8') > 150) {
            $formError = 'El asunto no puede superar los 150 caracteres.';
        } elseif ($messageInput === '') {
            $formError = 'Por favor describe tu consulta o problema en el mensaje.';
        } else {
            try {
                $now = date('Y-m-d H:i:s');
                $ticketCode = generateTicketCode();
                $userId = (string)($_SESSION['user']['id'] ?? '');
                $userUid = (string)($_SESSION['user']['uid'] ?? $userId);

                $createdDoc = $db->collection('support_tickets')->add([
                    'ticket_code' => $ticketCode,
                    'user_id' => $userId,
                    'user_uid' => $userUid,
                    'customer_name' => $nameInput,
                    'customer_email' => $customerEmail,
                    'subject' => $subjectInput,
                    'category' => $selectedCategory,
                    'priority' => 'normal',
                    'status' => 'abierto',
                    'assigned_to_id' => '',
                    'assigned_to_name' => '',
                    'last_reply_by' => 'cliente',
                    'messages' => [
                        [
                            'sender_id' => $userId,
                            'sender_name' => $nameInput,
                            'sender_role' => 'cliente',
                            'message' => $messageInput,
                            'created_at' => $now,
                        ],
                    ],
                    'created_at' => $now,
                    'updated_at' => $now,
                    'closed_at' => '',
                    'closed_by' => '',
                ]);

                $createdId = $createdDoc->id();
                header('Location: contact.php?created=1&ticket_id=' . urlencode($createdId) . '&code=' . urlencode($ticketCode) . '#soporte-contacto');
                exit;
            } catch (Exception $e) {
                $formError = 'No pudimos registrar tu ticket en este momento. Intenta de nuevo.';
            }
        }
    }
}

$createdSuccess = (($_GET['created'] ?? '') === '1');
$createdTicketId = trim($_GET['ticket_id'] ?? '');
$createdTicketCode = trim($_GET['code'] ?? '');

$bodyClass = 'xanarchy-contact-page';
require_once 'includes/header.php';
?>

<!-- HERO CONTACT -->
<section class="contact-hero">
    <div class="contact-hero-bg">
        <img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/ui-assets%2Fcontact_hero.jpg?alt=media" alt="Contacto Xanarchy" />
        <div class="hero-overlay"></div>
    </div>
    <div class="contact-hero-content">
        <h1>CONEXIÓN</h1>
        <p>ACCESO DIRECTO A LA ÉLITE</p>
    </div>
</section>

<!-- CONTACT SPLIT -->
<section class="contact-split">
    <!-- INFO BLOCK -->
    <div class="contact-info-block">
        <h2>CENTRO DE<br>OPERACIONES</h2>

        <div class="contact-detail">
            <h3>CORREO</h3>
            <p><a href="mailto:contacto@xanarchy.com">contacto@xanarchy.com</a></p>
        </div>

        <div class="contact-detail">
            <h3>LÍNEA DIRECTA</h3>
            <p>+52 (449) 555 - 0199</p>
        </div>

        <div class="contact-detail">
            <h3>UBICACIÓN</h3>
            <p>Aguascalientes, Ags, México</p>
        </div>

        <div class="contact-socials">
            <a href="#"><img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/ui-assets%2FInstagram.png?alt=media" alt="Instagram" /></a>
            <a href="#"><img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/ui-assets%2FTwitter.png?alt=media" alt="Twitter" /></a>
            <a href="#"><img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/ui-assets%2FFacebook.png?alt=media" alt="Facebook" /></a>
        </div>
    </div>

    <!-- FORM BLOCK -->
    <div class="contact-form-block" id="soporte-contacto">
        <div class="xanarchy-form">
            <h2>CONTACTA CON NOSOTROS</h2>
            <p class="contact-form-subtitle">Abre un ticket de soporte y nuestro equipo te responderá directamente.</p>

            <?php if (!$isLoggedIn): ?>
                <div class="contact-auth-gate">
                    <h3>INICIA SESIÓN PARA CONTACTAR A SOPORTE</h3>
                    <p>Para levantar un ticket de soporte y dar seguimiento a tus respuestas desde tu perfil, necesitas iniciar sesión con tu cuenta de cliente.</p>
                    <a href="login.php" class="btn-primary-luxury">INICIAR SESIÓN</a>
                    <p class="contact-auth-secondary">
                        ¿Aún no tienes cuenta? <a href="register.php">Regístrate aquí</a>
                    </p>
                </div>
            <?php elseif ($isStaff): ?>
                <div class="contact-auth-gate">
                    <h3>SESIÓN DE EQUIPO ACTIVA</h3>
                    <p>Has iniciado sesión con el rol <strong><?= htmlspecialchars(getRoleActorLabel($_SESSION['user']['role'] ?? '')) ?></strong>. La creación de tickets desde contacto es exclusiva para clientes.</p>
                    <a href="<?= htmlspecialchars(getRoleDashboardUrl($_SESSION['user']['role'] ?? '')) ?>" class="btn-primary-luxury">IR A MI PANEL</a>
                </div>
            <?php else: ?>
                <?php if ($createdSuccess): ?>
                    <div class="contact-alert-success">
                        <strong>¡Ticket creado correctamente!</strong>
                        <p>
                            Tu solicitud fue registrada<?= $createdTicketCode !== '' ? ' con el código <strong>' . htmlspecialchars($createdTicketCode) . '</strong>' : '' ?>. Nuestro equipo de soporte te responderá pronto.
                        </p>
                        <div class="contact-alert-links">
                            <?php if ($createdTicketId !== ''): ?>
                                <a href="ticket.php?id=<?= urlencode($createdTicketId) ?>">Ver mi ticket</a>
                            <?php endif; ?>
                            <a href="profile.php#mis-tickets">Ver todos en mi perfil</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($formError !== ''): ?>
                    <div class="contact-alert-error">
                        <?= htmlspecialchars($formError) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="contact.php#soporte-contacto">
                    <div class="form-group">
                        <label for="name" class="contact-field-label">NOMBRE</label>
                        <input type="text" id="name" name="name" placeholder="NOMBRE" value="<?= htmlspecialchars($customerName) ?>" required />
                    </div>
                    <div class="form-group">
                        <label for="email" class="contact-field-label">EMAIL</label>
                        <input type="email" id="email" name="email" placeholder="EMAIL" value="<?= htmlspecialchars($customerEmail) ?>" readonly required />
                    </div>
                    <div class="form-group">
                        <label for="category" class="contact-field-label">CATEGORÍA</label>
                        <select id="category" name="category" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= ($selectedCategory === $cat) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subject" class="contact-field-label">ASUNTO</label>
                        <input type="text" id="subject" name="subject" maxlength="150" placeholder="¿EN QUÉ PODEMOS AYUDARTE?" value="<?= htmlspecialchars($subjectInput) ?>" required />
                    </div>
                    <div class="form-group">
                        <label for="message" class="contact-field-label">MENSAJE</label>
                        <textarea id="message" name="message" rows="4" placeholder="DESCRIBE TU CONSULTA O PROBLEMA..." required><?= htmlspecialchars($messageInput) ?></textarea>
                    </div>
                    <button type="submit" class="btn-primary-luxury">ENVIAR TICKET</button>
                </form>

                <p class="contact-tickets-shortcut">
                    <a href="profile.php#mis-tickets">Ver mis tickets abiertos &rarr;</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
