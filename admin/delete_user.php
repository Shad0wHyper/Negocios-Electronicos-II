<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
// 1. Validar y obtener el ID del usuario de la URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: users.php');
    exit;
}
$userIdToDelete = (int)$_GET['id'];

// 2. Comprobación de seguridad: Un administrador no puede eliminarse a sí mismo
if ($userIdToDelete === (int)$_SESSION['user']['id']) {
    header('Location: users.php');
    exit;
}

// 3. Borrar el usuario de la base de datos
// La base de datos se encargará de borrar en cascada los pedidos y direcciones
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$userIdToDelete]);

// 4. Redirigir de vuelta a la lista de usuarios
header('Location: users.php');
exit;