<?php
// includes/config.php

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

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
