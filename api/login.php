<?php
require_once '../includes/config.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Al ser una API, los datos pueden venir en JSON (ej. desde fetch() en JS) o como FormData.
    // Leemos el cuerpo de la petición (raw JSON):
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    // Verificamos si los datos vienen en JSON o en POST clásico
    $email = trim($input['email'] ?? $_POST['email'] ?? '');
    $pass = $input['password'] ?? $_POST['password'] ?? '';

    // Validaciones básicas
    if (empty($email) || empty($pass)) {
        http_response_code(400); // 400 Bad Request
        echo json_encode(["status" => "error", "message" => "Email y contraseña son obligatorios."]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Email no válido."]);
        exit;
    }

    try {
        // Buscar el usuario en la BD (Mismo código que tienes en tu login.php original)
        $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password'])) {
            unset($user['password']); // IMPORTANTE: Nunca enviar la contraseña por la API

            // Responder con éxito
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Inicio de sesión exitoso.",
                "data" => $user
            ]);
        } else {
            http_response_code(401); // 401 Unauthorized
            echo json_encode(["status" => "error", "message" => "Email o contraseña incorrectos."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Error de base de datos."]);
    }

} else {
    // Si intentan acceder con un método que no sea POST (como abrir la URL en el navegador)
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido. Usa POST."]);
}
?>