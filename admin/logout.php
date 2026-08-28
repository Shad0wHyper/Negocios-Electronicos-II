<?php
// admin/logout.php

// Iniciar o reanudar la sesión
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Finalmente, destruir la sesión.
session_destroy();

// Redirigir a la página de login
// Usamos ../login.php para subir un nivel desde la carpeta /admin
header('Location: ../login.php');
exit;
?>