<?php
require_once '../includes/config.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Buscar el ID del producto en la URL
    $id = isset($_GET['id']) ? trim($_GET['id']) : '';

    if (empty($id)) {
        http_response_code(400); // 400 Bad Request
        echo json_encode(["status" => "error", "message" => "Debes proporcionar un ID válido en la URL (?id=X)."]);
        exit;
    }

    try {
        $doc = $db->collection('products')->document($id)->snapshot();

        if ($doc->exists()) {
            $product = $doc->data();
            $product['id'] = $doc->id();
            
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "data" => $product
            ]);
        } else {
            http_response_code(404); // 404 Not Found
            echo json_encode(["status" => "error", "message" => "Producto no encontrado."]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Error de base de datos.", "details" => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido. Usa GET."]);
}
?>