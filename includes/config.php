<?php
// includes/config.php

require_once __DIR__ . '/../vendor/autoload.php';

use Kreait\Firebase\Factory;

// Evitar que grpc intente usar credenciales por defecto (ADC) antes de que Factory las establezca
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/../firebase_credentials.json');

try {
    $factory = (new Factory)
        ->withServiceAccount(__DIR__ . '/../firebase_credentials.json')
        ->withDefaultStorageBucket('xanarchy-store.firebasestorage.app');
    $firestore = $factory->createFirestore();
    $db = $firestore->database();
    $storage = $factory->createStorage();
    $bucket = $storage->getBucket();
} catch (Exception $e) {
    die("Error de conexión Firebase: " . $e->getMessage());
}


// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>