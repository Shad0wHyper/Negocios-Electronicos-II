<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
// Validar y obtener el ID del usuario de la URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: users.php');
    exit;
}
$userId = (int)$_GET['id'];

// Obtener los datos actuales del usuario
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: users.php');
    exit;
}

$error = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    $password = $_POST['password'] ?? '';

    // Validaciones
    if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['customer', 'admin'])) {
        $error = 'Por favor, completa los campos correctamente.';
    } else {
        // Comprobar si el nuevo email ya está en uso por OTRO usuario
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            $error = 'Este correo ya está registrado por otro usuario.';
        } else {
            // Actualización condicional de la contraseña
            if (!empty($password)) {
                // Si se proporcionó una nueva contraseña, la actualizamos
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare(
                    "UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?"
                );
                $updateStmt->execute([$name, $email, $role, $hash, $userId]);
            } else {
                // Si no se proporcionó contraseña, la dejamos como está
                $updateStmt = $pdo->prepare(
                    "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?"
                );
                $updateStmt->execute([$name, $email, $role, $userId]);
            }
            header('Location: users.php');
            exit;
        }
    }
}

// Incluir el header
require_once __DIR__ . '/includes/header.php';
?>

    <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold text-[var(--text-primary)] mb-8">Editar Usuario</h2>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="edit_user.php?id=<?php echo $userId; ?>" method="POST" class="bg-white p-8 rounded-lg shadow-sm border border-[var(--border-color)] space-y-6">

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Nueva Contraseña</label>
                <input type="password" name="password" id="password" placeholder="Dejar en blanco para no cambiar" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700">Rol del Usuario</label>
                <select name="role" id="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="customer" <?php echo ($user['role'] === 'customer') ? 'selected' : ''; ?>>Customer</option>
                    <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>

            <div class="text-right">
                <a href="users.php" class="text-gray-600 mr-4">Cancelar</a>
                <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[var(--primary-color)] hover:bg-[var(--primary-color-hover)]">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

<?php
// Incluir el footer
require_once __DIR__ . '/includes/footer.php';
?><?php
