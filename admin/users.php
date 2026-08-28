<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
// Obtener todos los usuarios de la base de datos
$users = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();

// Incluir el header
require_once __DIR__ . '/includes/header.php';
?>

    <div class="max-w-7xl mx-auto">
        <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
            <h2 class="text-3xl font-bold text-[var(--text-primary)]">Gestión de Usuarios</h2>
            <a href="add_user.php" class="flex items-center justify-center gap-2 rounded-md h-10 px-5 bg-[var(--primary-color)] text-white text-sm font-semibold hover:bg-[var(--primary-color-hover)] transition-colors duration-200 shadow-sm">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"></path></svg>
                <span>Agregar Usuario</span>
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">ID</th>
                        <th scope="col" class="px-6 py-3">Nombre</th>
                        <th scope="col" class="px-6 py-3">Email</th>
                        <th scope="col" class="px-6 py-3">Rol</th>
                        <th scope="col" class="px-6 py-3">Fecha de Registro</th>
                        <th scope="col" class="px-6 py-3">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach($users as $user): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4"><?php echo $user['id']; ?></td>
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </th>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 font-semibold leading-tight text-xs rounded-full
                                        <?php echo ($user['role'] === 'admin') ? 'text-green-700 bg-green-100' : 'text-gray-700 bg-gray-100'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4"><?php echo date("d M, Y", strtotime($user['created_at'])); ?></td>
                                <td class="px-6 py-4">
                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="font-medium text-[var(--primary-color)] hover:underline mr-4">Editar</a>
                                    <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="font-medium text-red-600 hover:underline" onclick="return confirm('¿Estás seguro de que quieres eliminar este usuario? Se borrarán también todos sus pedidos y direcciones.');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="bg-white border-b">
                            <td colspan="6" class="text-center py-4">No se encontraron usuarios.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php
// Incluir el footer
require_once __DIR__ . '/includes/footer.php';
?>