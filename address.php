<?php
// address.php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Procesar POST para agregar una nueva dirección
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $pdo->prepare("INSERT INTO addresses (user_id, first_name, last_name, country, address, city, state, zip, phone, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user']['id'],
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['country'],
        $_POST['address'],
        $_POST['city'],
        $_POST['state'],
        $_POST['zip'],
        $_POST['phone'],
        $_POST['notes']
    ]);
    header('Location: address.php'); exit;
}

// Traer direcciones existentes
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once 'includes/header.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis Direcciones – Estampa-TLA</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .addresses-container, .new-address-form { max-width: 600px; margin: 2rem auto; }
        .address-card { border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }
        .address-card input { margin-right: 1rem; }
        .actions { text-align: center; margin-top: 1.5rem; }
        .btn { background: #7c6b61; color: #fff; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #5a503f; }
        .btn-secondary { background: transparent; color: #7c6b61; border: 2px solid #7c6b61; }
        .btn-secondary:hover { background: #7c6b61; color: #fff; }
        .hidden { display: none; }
        .address-form input, .address-form select, .address-form textarea { width: 100%; padding: 0.5rem; margin-bottom: 1rem; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>
<main>
    <section class="addresses-container">
        <?php if ($addresses): ?>
            <h2>Elige tu dirección</h2>
            <?php foreach ($addresses as $addr): ?>
            <div class="address-card">
                <label>
                    <input type="radio" name="address_id" value="<?= $addr['id'] ?>">
                    <?= htmlspecialchars("{$addr['first_name']} {$addr['last_name']} - {$addr['address']}, {$addr['city']}, {$addr['state']} CP {$addr['zip']}") ?>
                </label>
                <a href="checkout.php?address_id=<?= $addr['id'] ?>" class="btn">Usar esta dirección</a>
            </div>
        <?php endforeach; ?>
            <div class="actions">
                <button id="show-form" class="btn-secondary">Agregar nueva dirección</button>
            </div>
        <?php else: ?>
            <p>No tienes direcciones guardadas. Agrega una a continuación.</p>
            <script>var autoShowForm = true;</script>
        <?php endif; ?>
    </section>

    <section class="new-address-form hidden">
        <h2>Agregar Dirección</h2>
        <form method="post" class="address-form">
            <input type="hidden" name="action" value="add">
            <div class="input-group">
                <input name="first_name" type="text" placeholder="Nombre *" required>
                <input name="last_name"  type="text" placeholder="Apellidos *" required>
            </div>
            <select name="country" required>
                <option value="">País *</option>
                <option value="Mexico">México</option>
            </select>
            <input name="address" type="text" placeholder="Calle y número *" required>
            <div class="input-group">
                <input name="city"  type="text" placeholder="Ciudad *" required>
                <select name="state" required>
                    <option value="">Estado *</option>
                    <option value="Aguascalientes">Aguascalientes</option>
                </select>
            </div>
            <div class="input-group">
                <input name="zip"   type="text" placeholder="Código Postal *" required>
                <input name="phone" type="tel" placeholder="Teléfono *" required>
            </div>
            <textarea name="notes" placeholder="Referencias"></textarea>
            <div class="actions">
                <button type="submit" class="btn">Guardar dirección</button>
                <button type="button" id="cancel-form" class="btn-secondary">Cancelar</button>
            </div>
        </form>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>

<script>
    window.addEventListener('load', () => {
        const showBtn = document.getElementById('show-form');
        const cancelBtn = document.getElementById('cancel-form');
        const formSection = document.querySelector('.new-address-form');
        const listSection = document.querySelector('.addresses-container');

        // Auto mostrar si no hay direcciones
        if (typeof autoShowForm !== 'undefined' && autoShowForm) {
            listSection.style.display = 'none';
            formSection.classList.remove('hidden');
        }

        showBtn?.addEventListener('click', () => {
            listSection.style.display = 'none';
            formSection.classList.remove('hidden');
        });
        cancelBtn?.addEventListener('click', () => {
            formSection.classList.add('hidden');
            listSection.style.display = 'block';
        });
    });
</script>
</body>
</html>