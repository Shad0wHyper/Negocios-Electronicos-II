<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

$suppliers = [];
$documents = $db->collection('scm_suppliers')->orderBy('created_at', 'DESC')->documents();
foreach ($documents as $doc) {
    if ($doc->exists()) {
        $s = $doc->data();
        $s['id'] = $doc->id();
        $suppliers[] = $s;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
        <h2 class="text-3xl font-bold text-[var(--text-primary)]">Gestión de Fabricantes / Proveedores</h2>
        <a href="scm_add_supplier.php" class="flex items-center justify-center gap-2 rounded-md h-10 px-5 bg-[var(--primary-color)] text-white text-sm font-semibold hover:bg-[var(--primary-color-hover)] transition-colors duration-200 shadow-sm">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>Añadir Fabricante</span>
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Nombre</th>
                    <th scope="col" class="px-6 py-3">Contacto</th>
                    <th scope="col" class="px-6 py-3">Correo / Teléfono</th>
                    <th scope="col" class="px-6 py-3">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($suppliers) > 0): ?>
                    <?php foreach($suppliers as $supplier): ?>
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">
                                <?php echo htmlspecialchars($supplier['name'] ?? ''); ?>
                            </td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($supplier['contact_person'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4">
                                <?php echo htmlspecialchars($supplier['email'] ?? ''); ?><br>
                                <span class="text-xs text-gray-400"><?php echo htmlspecialchars($supplier['phone'] ?? ''); ?></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="scm_edit_supplier.php?id=<?php echo $supplier['id']; ?>" class="font-medium text-[var(--primary-color)] hover:underline mr-4">Editar</a>
                                <a href="scm_delete_supplier.php?id=<?php echo $supplier['id']; ?>" class="font-medium text-red-600 hover:underline" onclick="return confirm('¿Eliminar este fabricante?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="bg-white border-b">
                        <td colspan="4" class="text-center py-4">No se encontraron fabricantes registrados.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
