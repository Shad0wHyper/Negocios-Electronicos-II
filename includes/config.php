<?php
// includes/config.php
if (session_status() === PHP_SESSION_NONE) {
    session_name('__session');
    session_start();
}

require_once __DIR__ . '/../vendor/autoload.php';

use Kreait\Firebase\Factory;

try {
    $factory = new Factory();
    $credentialsPath = __DIR__ . '/../firebase_credentials.json';
    
    // 1. Entorno local: usamos el archivo si existe
    if (file_exists($credentialsPath)) {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialsPath);
        $factory = $factory->withServiceAccount($credentialsPath);
    } 
    // 2. Entorno Cloud Run: leemos desde variable de entorno si existe
    elseif (getenv('FIREBASE_CREDENTIALS')) {
        // En Cloud Run agregaremos una variable de entorno con el JSON de las credenciales
        $factory = $factory->withServiceAccount(getenv('FIREBASE_CREDENTIALS'));
    }
    // 3. Si no hay nada, intentará usar ADC (Credenciales Automáticas de Google Cloud)

    $factory = $factory->withDefaultStorageBucket('xanarchy-store.firebasestorage.app');
    
    $firestore = $factory->createFirestore();
    $db = $firestore->database();
    $storage = $factory->createStorage();
    $bucket = $storage->getBucket();
    $auth = $factory->createAuth();
} catch (Exception $e) {
    die("Error de conexión Firebase: " . $e->getMessage());
}

if (!function_exists('normalizeUserRole')) {
    function normalizeUserRole(?string $role): string
    {
        $r = mb_strtolower(trim((string)$role), 'UTF-8');
        $r = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $r);
        if ($r === 'ventas' || $r === 'seller') {
            return 'vendedor';
        }
        if ($r === 'scm' || $r === 'almacen') {
            return 'logistica';
        }
        return $r !== '' ? $r : 'user';
    }
}

if (!function_exists('isStaffRole')) {
    function isStaffRole(?string $role): bool
    {
        return in_array(normalizeUserRole($role), ['admin', 'vendedor', 'logistica'], true);
    }
}

if (!function_exists('getRoleDashboardUrl')) {
    function getRoleDashboardUrl(?string $role, string $prefix = ''): string
    {
        $normalized = normalizeUserRole($role);
        switch ($normalized) {
            case 'admin':
                return $prefix . 'admin/dashboard.php';
            case 'vendedor':
                return $prefix . 'seller/dashboard.php';
            case 'logistica':
                return $prefix . 'logistics/dashboard.php';
            default:
                return $prefix . 'index.php';
        }
    }
}

if (!function_exists('getRoleActorLabel')) {
    function getRoleActorLabel(?string $role): string
    {
        $normalized = normalizeUserRole($role);
        switch ($normalized) {
            case 'admin':
                return 'Admin';
            case 'vendedor':
                return 'Vendedor';
            case 'logistica':
                return 'Logística';
            case 'system':
            case 'sistema':
                return 'Sistema';
            default:
                return 'Cliente';
        }
    }
}

if (!function_exists('resolveMovementActorLabel')) {
    function resolveMovementActorLabel(array $movement, array $usersRoleCache = []): string
    {
        $uid = (string)($movement['user_id'] ?? '');
        if ($uid === 'system' || ($movement['user_name'] ?? '') === 'Sistema') {
            return 'Sistema';
        }
        if (!empty($movement['user_role'])) {
            return getRoleActorLabel($movement['user_role']);
        }
        if ($uid !== '' && isset($usersRoleCache[$uid])) {
            return getRoleActorLabel($usersRoleCache[$uid]);
        }
        $name = trim((string)($movement['user_name'] ?? ''));
        if ($name !== '' && in_array(normalizeUserRole($name), ['admin', 'vendedor', 'logistica'], true)) {
            return getRoleActorLabel($name);
        }
        return $name !== '' ? $name : 'Admin';
    }
}


