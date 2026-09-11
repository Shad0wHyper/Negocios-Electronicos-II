<?php
// profile.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Obtener dirección principal del usuario
$addressesRef = $db->collection('addresses');
$query = $addressesRef->where('user_id', '=', (string)$_SESSION['user']['id']);
$documents = $query->documents();
$addresses = [];
foreach ($documents as $doc) {
    if ($doc->exists()) {
        $a = $doc->data();
        $a['id'] = $doc->id();
        $addresses[] = $a;
    }
}
usort($addresses, function($a, $b) {
    $dateA = $a['created_at'] ?? '2000-01-01 00:00:00';
    $dateB = $b['created_at'] ?? '2000-01-01 00:00:00';
    return strtotime($dateB) - strtotime($dateA);
});
$address = !empty($addresses) ? $addresses[0] : null;
?>
<main>
    <style>
        /* Botón de Cerrar Sesión en perfil */
        .logout-button { margin-left: auto; }
        .logout-btn {
            display: inline-block; padding: 0.5rem 1rem;
            background: #7c6b61; color: #fff; border-radius: 4px;
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
                <h2><?= htmlspecialchars(
                        $_SESSION['user']['name'], ENT_QUOTES
                    ) ?></h2>
                <p><?= htmlspecialchars(
                        $_SESSION['user']['email'], ENT_QUOTES
                    ) ?></p>
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
                            <?= htmlspecialchars($address['first_name'].' '.$address['last_name']) ?> <br>
                            <?= htmlspecialchars($address['address'].', '.$address['city'].', '.$address['state'].' CP '.$address['zip']) ?> <br>
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
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
