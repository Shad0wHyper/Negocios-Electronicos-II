<?php
require_once '../includes/config.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Buscar el ID del producto en la URL, ej: api/producto.php?id=1
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400); // 400 Bad Request
        echo json_encode(["status" => "error", "message" => "Debes proporcionar un ID válido en la URL (?id=X)."]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name, description, price, discount_percentage, image, stock FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if ($product) {
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "data" => $product
            ]);
        } else {
            http_response_code(404); // 404 Not Found
            echo json_encode(["status" => "error", "message" => "Producto no encontrado."]);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Error de base de datos."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido. Usa GET."]);
}
?>