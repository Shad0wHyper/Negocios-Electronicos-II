<?php
require_once 'includes/config.php';

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y sanitizar datos
    $name         = trim($_POST['name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    // Validaciones básicas
    if ($password !== $confirm_pass) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email no válido.';
    } elseif (empty($name)) {
        $error = 'El nombre no puede estar vacío.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Este correo ya está registrado.';
        } else {
            // Insertar nuevo usuario
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password) VALUES (?, ?, ?)'
            );
            $stmt->execute([$name, $email, $hash]);

            // Loguear automáticamente
            $_SESSION['user'] = [
                'id'    => $pdo->lastInsertId(),
                'name'  => $name,
                'email' => $email,
                'role'  => 'customer'
            ];

            header('Location: index.php');
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Xanarchy</title>
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
                <p class="subtitle">Únete a la nueva era</p>
                <h2>Crea tu cuenta</h2>

                <?php if ($error): ?>
                    <p class="error-msg"><?= htmlspecialchars($error, ENT_QUOTES) ?></p>
                <?php endif; ?>

                <form method="post" action="register.php" class="modern-form">
                    <div class="floating-input">
                        <input type="text" name="name" id="name" placeholder=" " required />
                        <label for="name">Nombre completo</label>
                    </div>
                    <div class="floating-input">
                        <input type="email" name="email" id="email" placeholder=" " required />
                        <label for="email">E-mail</label>
                    </div>
                    <div class="floating-input">
                        <input type="password" name="password" id="password" placeholder=" " required />
                        <label for="password">Contraseña</label>
                    </div>
                    <div class="floating-input">
                        <input type="password" name="confirm_password" id="confirm_password" placeholder=" " required />
                        <label for="confirm_password">Confirmar contraseña</label>
                    </div>

                    <button type="submit" class="btn-primary">Registrarse</button>
                </form>

                <p class="alt-action">
                    ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
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
