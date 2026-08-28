<?php
// profile.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Obtener dirección principal del usuario
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user']['id']]);
$address = $stmt->fetch(PDO::FETCH_ASSOC);
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
                        src="Imagenes/Iconos/marcador-de-posicion 1.png"
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
                <img src="Imagenes/Iconos/Shopping.png" alt="Pedidos" class="icon" />
                <div>
                    <h3>Pedidos</h3>
                    <p><a href="orders.php" class="link-btn">Ver mis pedidos</a></p>
                </div>
            </div>

            <div class="profile-item">
                <img src="Imagenes/Iconos/Historial_compras.png" alt="Historial de compras" class="icon" />
                <div>
                    <h3>Historial de Compras</h3>
                    <p><a href="orders.php" class="link-btn">Ver historial</a></p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
