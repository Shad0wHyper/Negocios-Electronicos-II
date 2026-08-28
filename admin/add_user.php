<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'customer'; // Recoger el rol

    // Validaciones
    if ($password !== $confirm_pass) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email no válido.';
    } elseif (empty($name)) {
        $error = 'El nombre no puede estar vacío.';
    } elseif (!in_array($role, ['customer', 'admin'])) {
        $error = 'Rol no válido.';
    } else {
        // Comprobar si el email ya existe
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Este correo ya está registrado.';
        } else {
            // Insertar nuevo usuario con su rol
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$name, $email, $hash, $role]);

            // Redirigir a la lista de usuarios
            header('Location: users.php');
            exit;
        }
    }
}

// Incluir el header
require_once __DIR__ . '/includes/header.php';
?>

    <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold text-[var(--text-primary)] mb-8">Agregar Nuevo Usuario</h2>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="add_user.php" method="POST" class="bg-white p-8 rounded-lg shadow-sm border border-[var(--border-color)] space-y-6">

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                <input type="text" name="name" id="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                    <input type="password" name="password" id="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirmar Contraseña</label>
                    <input type="password" name="confirm_password" id="confirm_password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700">Rol del Usuario</label>
                <select name="role" id="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="customer" selected>Customer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="text-right">
                <a href="users.php" class="text-gray-600 mr-4">Cancelar</a>
                <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[var(--primary-color)] hover:bg-[var(--primary-color-hover)]">
                    Crear Usuario
                </button>
            </div>
        </form>
    </div>

<?php
// Incluir el footer
require_once __DIR__ . '/includes/footer.php';
?>