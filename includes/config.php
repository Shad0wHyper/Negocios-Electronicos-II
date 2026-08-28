<?php
// includes/config.php

// Parámetros de conexión
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'estampa_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Conexión PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión BD: " . $e->getMessage());
}

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>