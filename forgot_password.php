<?php
// forgot_password.php
require_once 'includes/config.php';

// Si ya está logueado, redirigir al inicio
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Xanarchy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body class="login-body">
    <div class="login-split">
        <!-- Mitad Izquierda: Formulario -->
        <div class="login-form-container">
            <a href="login.php" class="login-logo-link" title="Volver al Login">
                <span class="back-arrow">←</span>
                <img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/ui-assets%2FLogo_Final.png?alt=media" alt="Xanarchy Logo" class="login-logo">
            </a>

            <div class="form-wrapper">
                <p class="subtitle">Seguridad</p>
                <h2>Recuperar contraseña</h2>
                <p style="color: #666; margin-bottom: 20px; font-size: 0.95rem;">Ingresa el correo electrónico asociado a tu cuenta y te enviaremos un enlace para restablecer tu contraseña.</p>

                <p class="error-msg" id="error-msg" style="display: none; padding: 10px; border-radius: 5px;"></p>

                <form id="reset-form" class="modern-form">
                    <div class="floating-input" style="margin-bottom: 20px;">
                        <input type="email" id="email" placeholder=" " required />
                        <label for="email">E-mail</label>
                    </div>

                    <button type="submit" class="btn-primary" id="submit-btn">Enviar enlace de recuperación</button>
                </form>

                <p class="alt-action">
                    ¿Lo recordaste? <a href="login.php">Inicia sesión aquí</a>
                </p>
            </div>
        </div>

        <!-- Mitad Derecha: Visual -->
        <div class="login-visual">
        </div>
    </div>

    <!-- Firebase Auth Script -->
    <script type="module">
        import { auth, sendPasswordResetEmail } from './js/firebase-auth.js';

        const form = document.getElementById('reset-form');
        const errorMsg = document.getElementById('error-msg');
        const submitBtn = document.getElementById('submit-btn');

        function showMessage(message, isSuccess = false) {
            errorMsg.textContent = message;
            errorMsg.style.display = 'block';
            errorMsg.style.backgroundColor = isSuccess ? '#d4edda' : '#f8d7da';
            errorMsg.style.color = isSuccess ? '#155724' : '#721c24';
            errorMsg.style.border = `1px solid ${isSuccess ? '#c3e6cb' : '#f5c6cb'}`;
            
            submitBtn.textContent = 'Enviar enlace de recuperación';
            submitBtn.disabled = false;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorMsg.style.display = 'none';
            
            const email = document.getElementById('email').value.trim();
            
            if (!email) {
                showMessage('Por favor, escribe tu correo electrónico.', false);
                return;
            }

            submitBtn.textContent = 'Enviando...';
            submitBtn.disabled = true;

            try {
                await sendPasswordResetEmail(auth, email);
                showMessage('¡Correo enviado con éxito! Revisa tu bandeja de entrada (y la carpeta de SPAM).', true);
                document.getElementById('email').value = ''; // Limpiar campo
            } catch (error) {
                let msg = 'Ocurrió un error al intentar enviar el correo.';
                if (error.code === 'auth/user-not-found') msg = 'No hay ninguna cuenta registrada con este correo.';
                if (error.code === 'auth/invalid-email') msg = 'El formato del correo no es válido.';
                showMessage(msg, false);
            }
        });
    </script>
</body>

</html>
