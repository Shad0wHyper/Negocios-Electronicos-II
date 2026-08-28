<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
// 1. Validar y obtener el ID del producto de la URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit;
}
$productId = (int)$_GET['id'];

// 2. Obtener la ruta de la imagen antes de borrar el registro
$stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if ($product) {
    // 3. Borrar el archivo de imagen del servidor para no dejar basura
    $imagePath = __DIR__ . '/../' . $product['image'];
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }

    // 4. Borrar el producto de la base de datos
    $deleteStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $deleteStmt->execute([$productId]);
}

// 5. Redirigir de vuelta a la lista de productos
header('Location: products.php');
exit;