<?php
require_once '../includes/config.php';

// Cabeceras para que el navegador sepa que respondemos con JSON
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *"); // Permite que otras apps consuman tu API
header("Access-Control-Allow-Methods: GET");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        // Obtenemos los productos de la base de datos
        $stmt = $pdo->query("SELECT id, name, description, price, discount_percentage, image, stock FROM products");
        $products = $stmt->fetchAll();

        // Respondemos con los productos en formato JSON
        http_response_code(200); // 200 OK
        echo json_encode([
            "status" => "success",
            "data" => $products
        ]);
    } catch (PDOException $e) {
        http_response_code(500); // 500 Internal Server Error
        echo json_encode([
            "status" => "error",
            "message" => "Error al obtener productos: " . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405); // 405 Method Not Allowed
    echo json_encode([
        "status" => "error",
        "message" => "Método no permitido. Usa GET."
    ]);
}
?>