<?php
require_once 'includes/config.php';

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
                <img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/ui-assets%2FLogo_Final.png?alt=media" alt="Xanarchy Logo" class="login-logo">
            </a>

            <div class="form-wrapper">
                <p class="subtitle">Únete a la nueva era</p>
                <h2>Crea tu cuenta</h2>

                <p class="error-msg" id="error-msg" style="display: none;"></p>

                <form id="register-form" class="modern-form">
                    <div class="floating-input">
                        <input type="text" id="name" placeholder=" " required />
                        <label for="name">Nombre completo</label>
                    </div>
                    <div class="floating-input">
                        <input type="email" id="email" placeholder=" " required />
                        <label for="email">E-mail</label>
                    </div>
                    <div class="floating-input">
                        <input type="password" id="password" placeholder=" " required />
                        <label for="password">Contraseña</label>
                    </div>
                    <div class="floating-input">
                        <input type="password" id="confirm_password" placeholder=" " required />
                        <label for="confirm_password">Confirmar contraseña</label>
                    </div>

                    <button type="submit" class="btn-primary" id="submit-btn">Registrarse</button>
                </form>

                <p class="alt-action">
                    ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
                </p>
            </div>
        </div>

        <!-- Mitad Derecha: Visual -->
        <div class="login-visual">
        </div>
    </div>

    <!-- Firebase Auth Script -->
    <script type="module">
        import { auth, createUserWithEmailAndPassword } from './js/firebase-auth.js';
        // Para actualizar el perfil con el nombre
        import { updateProfile } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js";

        const form = document.getElementById('register-form');
        const errorMsg = document.getElementById('error-msg');
        const submitBtn = document.getElementById('submit-btn');

        function showError(message) {
            errorMsg.textContent = message;
            errorMsg.style.display = 'block';
            submitBtn.textContent = 'Registrarse';
            submitBtn.disabled = false;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorMsg.style.display = 'none';
            
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;

            if (password !== confirm) {
                showError('Las contraseñas no coinciden.');
                return;
            }

            submitBtn.textContent = 'Cargando...';
            submitBtn.disabled = true;

            try {
                // Crear usuario en Firebase Auth
                const userCredential = await createUserWithEmailAndPassword(auth, email, password);
                const user = userCredential.user;
                
                // Actualizar su perfil de Firebase con su nombre
                await updateProfile(user, { displayName: name });

                // Obtener Token y enviarlo al servidor PHP para crear la sesión
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
                let msg = 'Error en el registro.';
                if (error.code === 'auth/email-already-in-use') msg = 'Este correo ya está registrado.';
                if (error.code === 'auth/weak-password') msg = 'La contraseña debe tener al menos 6 caracteres.';
                showError(msg);
            }
        });
    </script>
</body>

</html>
