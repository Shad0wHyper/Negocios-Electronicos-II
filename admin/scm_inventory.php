<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

$products = [];
$documents = $db->collection('products')->orderBy('name')->documents();
foreach ($documents as $doc) {
    if ($doc->exists()) {
        $p = $doc->data();
        $p['id'] = $doc->id();
        $products[] = $p;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
        <h2 class="text-3xl font-bold text-[var(--text-primary)]">Vista de Inventario (SCM)</h2>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Producto</th>
                    <th scope="col" class="px-6 py-3 text-center">Stock Actual</th>
                    <th scope="col" class="px-6 py-3 text-center">Stock Mínimo</th>
                    <th scope="col" class="px-6 py-3 text-center">Estado</th>
                    <th scope="col" class="px-6 py-3 text-right">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($products) > 0): ?>
                    <?php foreach($products as $p): 
                        $stock = (int)($p['stock'] ?? 0);
                        $min = (int)($p['stock_minimo'] ?? 0);
                        $isLow = $stock <= $min;
                    ?>
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">
                                <div class="flex items-center gap-3">
                                    <img src="<?php echo htmlspecialchars($p['image']); ?>" class="w-10 h-10 rounded object-cover" alt="">
                                    <?php echo htmlspecialchars($p['name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-lg font-semibold <?php echo $isLow ? 'text-red-600' : 'text-gray-900'; ?>">
                                <?php echo $stock; ?>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-500">
                                <?php echo $min; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($isLow): ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Stock Bajo
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Normal
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="scm_add_movement.php?product_id=<?php echo $p['id']; ?>" class="text-[var(--primary-color)] hover:underline">Ajustar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="bg-white border-b">
                        <td colspan="5" class="text-center py-4">No hay productos en el inventario.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
