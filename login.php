<?php
// login.php
require_once 'includes/config.php';

// Si ya está logueado, redirigir al inicio
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    // Validación básica
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email no válido.';
    } else {
        // Buscar usuario en la BD
        $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;

            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit;

        } else {
            $error = 'Email o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Xanarchy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body class="login-body">
    <div class="login-split">
        <!-- Mitad Izquierda: Formulario -->
        <div class="login-form-container">
            <a href="index.php" class="login-logo-link" title="Volver a la tienda">
                <span class="back-arrow">←</span>
                <img src="Imagenes/Logo_Final.png" alt="Xanarchy Logo" class="login-logo">
            </a>

            <div class="form-wrapper">
                <p class="subtitle">Comienza tu viaje</p>
                <h2>Inicia sesión en Xanarchy</h2>

                <?php if ($error): ?>
                    <p class="error-msg"><?= htmlspecialchars($error, ENT_QUOTES) ?></p>
                <?php endif; ?>

                <form method="post" action="login.php" class="modern-form">
                    <div class="floating-input">
                        <input type="email" name="email" id="email" placeholder=" " required />
                        <label for="email">E-mail</label>
                    </div>
                    <div class="floating-input">
                        <input type="password" name="password" id="password" placeholder=" " required />
                        <label for="password">Contraseña</label>
                    </div>

                    <button type="submit" class="btn-primary">Entrar</button>
                </form>

                <p class="alt-action">
                    ¿No tienes cuenta? <a href="register.php">Regístrate</a>
                </p>
            </div>
        </div>

        <!-- Mitad Derecha: Visual -->
        <div class="login-visual">
            <!-- Imagen abstracta random temporal -->
        </div>
    </div>
</body>

</html>