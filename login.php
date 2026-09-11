<?php
// login.php
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
    <title>Iniciar Sesión - Xanarchy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .google-btn {
            background-color: #fff;
            color: #444;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 20px;
        }
        .google-btn:hover {
            background-color: #f7f7f7;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .google-icon {
            width: 20px;
            height: 20px;
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: #888;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #eee;
        }
        .divider::before { margin-right: .5em; }
        .divider::after { margin-left: .5em; }
    </style>
</head>

<body class="login-body">
    <div class="login-split">
        <!-- Mitad Izquierda: Formulario -->
        <div class="login-form-container">
            <a href="index.php" class="login-logo-link" title="Volver a la tienda">
                <span class="back-arrow">←</span>
                <img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/ui-assets%2FLogo_Final.png?alt=media" alt="Xanarchy Logo" class="login-logo">
            </a>

            <div class="form-wrapper">
                <p class="subtitle">Comienza tu viaje</p>
                <h2>Inicia sesión en Xanarchy</h2>

                <p class="error-msg" id="error-msg" style="display: none;"></p>

                <button class="google-btn" id="google-login-btn">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" class="google-icon">
                    Continuar con Google
                </button>

                <div class="divider">o</div>

                <form id="login-form" class="modern-form">
                    <div class="floating-input">
                        <input type="email" id="email" placeholder=" " required />
                        <label for="email">E-mail</label>
                    </div>
                    <div class="floating-input" style="margin-bottom: 5px;">
                        <input type="password" id="password" placeholder=" " required />
                        <label for="password">Contraseña</label>
                    </div>
                    <div style="text-align: right; margin-bottom: 20px;">
                        <a href="forgot_password.php" style="font-size: 0.85rem; color: #666; text-decoration: none;">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="btn-primary" id="submit-btn">Entrar</button>
                </form>

                <p class="alt-action">
                    ¿No tienes cuenta? <a href="register.php">Regístrate</a>
                </p>
            </div>
        </div>

        <!-- Mitad Derecha: Visual -->
        <div class="login-visual">
        </div>
    </div>

    <!-- Firebase Auth Script -->
    <script type="module">
        import { auth, googleProvider, signInWithPopup, signInWithEmailAndPassword, sendPasswordResetEmail } from './js/firebase-auth.js';

        const form = document.getElementById('login-form');
        const googleBtn = document.getElementById('google-login-btn');
        const errorMsg = document.getElementById('error-msg');
        const submitBtn = document.getElementById('submit-btn');

        // Función para enviar el token al servidor PHP
        async function verifyTokenOnServer(user) {
            try {
                const idToken = await user.getIdToken();
                const response = await fetch('auth_verify.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idToken })
                });
                
                const data = await response.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    showError(data.error || 'Error al iniciar sesión en el servidor.');
                }
            } catch (error) {
                showError('Error de red al verificar la sesión.');
            }
        }

        function showError(message, isSuccess = false) {
            errorMsg.textContent = message;
            errorMsg.style.display = 'block';
            errorMsg.style.color = isSuccess ? '#28a745' : '#dc3545';
            submitBtn.textContent = 'Entrar';
            submitBtn.disabled = false;
        }

        // Inicio de sesión con Correo/Contraseña
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorMsg.style.display = 'none';
            submitBtn.textContent = 'Cargando...';
            submitBtn.disabled = true;

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            try {
                const userCredential = await signInWithEmailAndPassword(auth, email, password);
                await verifyTokenOnServer(userCredential.user);
            } catch (error) {
                let msg = 'Credenciales incorrectas.';
                if (error.code === 'auth/user-not-found') msg = 'Usuario no encontrado.';
                if (error.code === 'auth/wrong-password') msg = 'Contraseña incorrecta.';
                showError(msg);
            }
        });

        // Inicio de sesión con Google
        googleBtn.addEventListener('click', async () => {
            errorMsg.style.display = 'none';
            try {
                const result = await signInWithPopup(auth, googleProvider);
                await verifyTokenOnServer(result.user);
            } catch (error) {
                showError('Error al iniciar sesión con Google.');
                console.error(error);
            }
        });
    </script>
</body>

</html>