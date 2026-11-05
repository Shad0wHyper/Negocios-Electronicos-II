<?php
// logistics/delete_product.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_logistics.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: products.php');
    exit;
}
$productId = trim($_GET['id']);

$productRef = $db->collection('products')->document($productId);
$doc = $productRef->snapshot();

if ($doc->exists()) {
    $product = $doc->data();
    if (isset($product['image']) && strpos($product['image'], 'firebasestorage') !== false) {
        $parsedUrl = parse_url($product['image']);
        $path = $parsedUrl['path'] ?? '';
        $pathParts = explode('/o/', $path);
        if (count($pathParts) > 1) {
            $objectName = urldecode($pathParts[1]);
            try {
                $bucket->object($objectName)->delete();
            } catch(Exception $e) {}
        }
    }
    $productRef->delete();
}

header('Location: products.php');
exit;

