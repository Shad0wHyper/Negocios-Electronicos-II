<?php
// auth_verify.php
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$idTokenString = $input['idToken'] ?? null;

if (!$idTokenString) {
    echo json_encode(['success' => false, 'error' => 'Token no proporcionado']);
    exit;
}

try {
    // Verificar el token con Kreait Firebase Auth
    $verifiedIdToken = $auth->verifyIdToken($idTokenString);
    $uid = $verifiedIdToken->claims()->get('sub');
    
    // Optimización: Extraer email y nombre directamente del Token (sin hacer petición extra a Google)
    $email = $verifiedIdToken->claims()->get('email');
    $displayName = $verifiedIdToken->claims()->get('name') ?? 'Usuario';
    
    // Verificar si el usuario existe en Firestore
    $userRef = $db->collection('users')->document($uid);
    $snapshot = $userRef->snapshot();
    
    if (!$snapshot->exists()) {
        // Es un usuario nuevo (por ejemplo, inició sesión con Google por primera vez)
        $userData = [
            'name' => $displayName,
            'email' => $email,
            'role' => 'user', // Rol por defecto
            'created_at' => date('Y-m-d H:i:s')
        ];
        $userRef->set($userData);
    } else {
        $userData = $snapshot->data();
    }
    
    // Añadir el ID al array de sesión
    $userData['id'] = $uid;
    
    // Iniciar la sesión de PHP
    $_SESSION['user'] = $userData;
    
    echo json_encode(['success' => true, 'redirect' => ($userData['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php')]);
    
} catch (\Kreait\Firebase\Exception\Auth\FailedToVerifyToken $e) {
    echo json_encode(['success' => false, 'error' => 'Token inválido: ' . $e->getMessage()]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
}
