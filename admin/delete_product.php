<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
// 1. Validar y obtener el ID del producto de la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: products.php');
    exit;
}
$productId = trim($_GET['id']);

$productRef = $db->collection('products')->document($productId);
$doc = $productRef->snapshot();

if ($doc->exists()) {
    $product = $doc->data();
    // Extraer nombre del archivo del URL
    if (isset($product['image']) && strpos($product['image'], 'firebasestorage') !== false) {
        $parsedUrl = parse_url($product['image']);
        $path = $parsedUrl['path'];
        $pathParts = explode('/o/', $path);
        if (count($pathParts) > 1) {
            $objectName = urldecode($pathParts[1]);
            // Opcional: Borrar de Storage si es posible
            try {
                $bucket->object($objectName)->delete();
            } catch(Exception $e) {}
        }
    }

    // Borrar el producto de la base de datos Firestore
    $productRef->delete();
}

// 5. Redirigir de vuelta a la lista de productos
header('Location: products.php');
exit;