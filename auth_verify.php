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
    
    // Verificar si el usuario existe en Firestore (por UID o por email si fue migrado/creado desde panel)
    $userRef = $db->collection('users')->document($uid);
    $snapshot = $userRef->snapshot();
    $docId = $uid;
    
    if (!$snapshot->exists()) {
        $existingByEmail = null;
        if (!empty($email)) {
            $emailQuery = $db->collection('users')->where('email', '=', $email)->documents();
            foreach ($emailQuery as $doc) {
                if ($doc->exists()) {
                    $existingByEmail = $doc;
                    break;
                }
            }
        }

        if ($existingByEmail !== null) {
            $userData = $existingByEmail->data();
            $docId = $existingByEmail->id();
        } else {
            // Es un usuario nuevo (por ejemplo, inició sesión con Google por primera vez)
            $userData = [
                'name' => $displayName,
                'email' => $email,
                'role' => 'user', // Rol por defecto
                'created_at' => date('Y-m-d H:i:s')
            ];
            $userRef->set($userData);
        }
    } else {
        $userData = $snapshot->data();
    }
    
    // Normalizar rol y añadir el ID al array de sesión
    $userData['role'] = normalizeUserRole($userData['role'] ?? 'user');
    $userData['id'] = $docId;
    $userData['uid'] = $uid;
    
    // Iniciar la sesión de PHP
    $_SESSION['user'] = $userData;
    
    echo json_encode([
        'success' => true,
        'role' => $userData['role'],
        'redirect' => getRoleDashboardUrl($userData['role'])
    ]);
    
} catch (\Kreait\Firebase\Exception\Auth\FailedToVerifyToken $e) {
    echo json_encode(['success' => false, 'error' => 'Token inválido: ' . $e->getMessage()]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
}
