<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
// 1. Validar y obtener el ID del usuario de la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: users.php');
    exit;
}
$userIdToDelete = trim($_GET['id']);

// 2. Comprobación de seguridad: Un administrador no puede eliminarse a sí mismo
if ($userIdToDelete === (string)$_SESSION['user']['id']) {
    header('Location: users.php');
    exit;
}

// 3. Borrar el usuario de la base de datos
$db->collection('users')->document($userIdToDelete)->delete();

// 4. Redirigir de vuelta a la lista de usuarios
header('Location: users.php');
exit;