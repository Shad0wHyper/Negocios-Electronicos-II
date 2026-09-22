<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

// Get products map for names
$productsCache = [];
$pDocs = $db->collection('products')->documents();
foreach ($pDocs as $doc) {
    if ($doc->exists()) {
        $productsCache[$doc->id()] = $doc->data()['name'] ?? 'Producto Desconocido';
    }
}

// Get movements
$movements = [];
$documents = $db->collection('scm_movements')->orderBy('created_at', 'DESC')->documents();
foreach ($documents as $doc) {
    if ($doc->exists()) {
        $m = $doc->data();
        $m['id'] = $doc->id();
        $m['product_name'] = $productsCache[$m['product_id'] ?? ''] ?? 'Desconocido';
        $movements[] = $m;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
        <h2 class="text-3xl font-bold text-[var(--text-primary)]">Movimientos de Inventario</h2>
        <a href="scm_add_movement.php" class="flex items-center justify-center gap-2 rounded-md h-10 px-5 bg-[var(--primary-color)] text-white text-sm font-semibold hover:bg-[var(--primary-color-hover)] transition-colors duration-200 shadow-sm">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>Nuevo Movimiento</span>
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Fecha</th>
                    <th scope="col" class="px-6 py-3">Producto</th>
                    <th scope="col" class="px-6 py-3 text-center">Tipo</th>
                    <th scope="col" class="px-6 py-3 text-center">Cantidad</th>
                    <th scope="col" class="px-6 py-3">Motivo / Notas</th>
                    <th scope="col" class="px-6 py-3">Usuario</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($movements) > 0): ?>
                    <?php foreach($movements as $m): 
                        $isEntrada = ($m['type'] ?? '') === 'Entrada';
                    ?>
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo htmlspecialchars($m['created_at'] ?? ''); ?>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                <?php echo htmlspecialchars($m['product_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($isEntrada): ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Entrada</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Salida</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center font-bold <?php echo $isEntrada ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $isEntrada ? '+' : '-'; ?><?php echo abs((int)($m['quantity'] ?? 0)); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo htmlspecialchars($m['reason'] ?? ''); ?>
                            </td>
                            <td class="px-6 py-4 text-gray-400 text-xs">
                                <?php echo htmlspecialchars($m['user_name'] ?? 'Admin'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="bg-white border-b">
                        <td colspan="6" class="text-center py-4">No se han registrado movimientos.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
