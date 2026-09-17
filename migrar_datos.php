<?php
// migrar_datos.php
// IMPORTANTE: Este archivo lee de MySQL y escribe en Firestore.
// Una vez completada la migración, se puede eliminar.

require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;

// 1. Conexión a Firebase Firestore
try {
    $factory = new Factory();
    $credentialsPath = __DIR__ . '/firebase_credentials.json';
    
    if (file_exists($credentialsPath)) {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialsPath);
        $factory = $factory->withServiceAccount($credentialsPath);
    } elseif (getenv('FIREBASE_CREDENTIALS')) {
        $factory = $factory->withServiceAccount(getenv('FIREBASE_CREDENTIALS'));
    }

    $firestore = $factory->createFirestore();
    $db = $firestore->database();
    echo "Conectado a Firebase correctamente.<br>";
} catch (Exception $e) {
    die("Error de conexión Firebase: " . $e->getMessage());
}
