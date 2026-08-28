<?php
// add_product.php
require_once 'includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $stock       = intval($_POST['stock'] ?? 0);

    if (!$name || !is_numeric($price) || $price <= 0 || !$description) {
        $error = 'Completa todos los campos obligatorios con valores válidos.';
    } elseif (empty($_FILES['image']['name'])) {
        $error = 'Debes subir una imagen del producto.';
    } else {
        $img = $_FILES['image'];
        $ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if (!in_array($ext, $allowed) || $img['error'] !== UPLOAD_ERR_OK) {
            $error = 'Formato de imagen no válido (jpg, png, gif).';
        } else {
            $destDir = 'Imagenes/Productos/';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            $fileName = uniqid('prod_').'.'.$ext;
            $destPath = $destDir . $fileName;
            if (move_uploaded_file($img['tmp_name'], $destPath)) {
                $stmt = $pdo->prepare(
                    'INSERT INTO products (name, description, price, image, stock) VALUES (:name, :desc, :price, :image, :stock)'
                );
                $stmt->execute([
                    ':name'  => $name,
                    ':desc'  => $description,
                    ':price' => $price,
                    ':image' => $destPath,
                    ':stock' => $stock
                ]);
                $success = 'Producto agregado correctamente.';
            } else {
                $error = 'Error al mover la imagen subida.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once 'includes/header.php'; ?>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Panel Vendedor – Agregar Producto</title>
    <link rel="stylesheet" href="styles.css" />
    <style>
        .product-form { max-width:600px; margin:2rem auto; padding:1.5rem; background:#f9f9f9; border-radius:8px; }
        .product-form h2 { text-align:center; margin-bottom:1rem; }
        .product-form label { display:block; margin-top:1rem; font-weight:600; }
        .product-form input[type=text], .product-form input[type=number], .product-form textarea, .product-form input[type=file] {
            width:100%; padding:0.5rem; margin-top:0.5rem; border:1px solid #ccc; border-radius:4px;
        }
        .product-form button { margin-top:1.5rem; background:#7c6b61; color:#fff; border:none; padding:0.75rem 1.5rem; border-radius:4px; cursor:pointer; }
        .product-form button:hover { background:#5a503f; }
        .message { text-align:center; padding:0.75rem; margin-bottom:1rem; border-radius:4px; }
        .error   { background:#ffcdd2; color:#b71c1c; }
        .success { background:#c8e6c9; color:#256029; }
    </style>
</head>
<body>
<main>
    <section class="product-form">
        <h2>AÑADIR NUEVO PRODUCTO</h2>
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <label for="name">Nombre del producto *</label>
            <input id="name" name="name" type="text" value="<?= htmlspecialchars($name ?? '') ?>" required />
            <label for="price">Precio (MXN) *</label>
            <input id="price" name="price" type="number" step="0.01" min="0" value="<?= htmlspecialchars($price ?? '') ?>" required />
            <label for="stock">Cantidad en stock *</label>
            <input id="stock" name="stock" type="number" min="0" value="<?= htmlspecialchars($stock ?? '') ?>" required />
            <label for="description">Descripción *</label>
            <textarea id="description" name="description" rows="4" required><?= htmlspecialchars($description ?? '') ?></textarea>
            <label for="image">Imagen del producto *</label>
            <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.gif" required />
            <button type="submit">SUBIR PRODUCTO</button>
        </form>
    </section>
</main>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
