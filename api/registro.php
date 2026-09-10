<?php
require_once '../includes/config.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Leemos el cuerpo de la petición (JSON)
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    // Recoger datos (soportando JSON o POST normal)
    $name = trim($input['name'] ?? $_POST['name'] ?? '');
    $email = trim($input['email'] ?? $_POST['email'] ?? '');
    $password = $input['password'] ?? $_POST['password'] ?? '';
    $confirm_pass = $input['confirm_password'] ?? $_POST['confirm_password'] ?? '';

    // Validaciones
    if (empty($name) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Nombre, email y contraseña son obligatorios."]);
        exit;
    }

    if ($password !== $confirm_pass) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Las contraseñas no coinciden."]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Email no válido."]);
        exit;
    }

    try {
        // Verificar si el correo ya existe
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(409); // 409 Conflict
            echo json_encode(["status" => "error", "message" => "Este correo ya está registrado."]);
            exit;
        }

        // Hashear la contraseña antes de guardar
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Insertar el usuario en la BD
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hash, 'customer']);

        $newUserId = $pdo->lastInsertId();

        http_response_code(201); // 201 Created
        echo json_encode([
            "status" => "success",
            "message" => "Usuario registrado exitosamente.",
            "data" => [
                "id" => $newUserId,
                "name" => $name,
                "email" => $email,
                "role" => "customer"
            ]
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Error de base de datos."]);
    }

} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido. Usa POST."]);
}
?>