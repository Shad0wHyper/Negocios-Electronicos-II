<?php
// 1. Incluir archivos de configuración y autenticación
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
// 2. Obtener todos los productos de Firestore
$products = [];
$documents = $db->collection('products')->orderBy('created_at', 'DESC')->documents();
foreach ($documents as $doc) {
    if ($doc->exists()) {
        $p = $doc->data();
        $p['id'] = $doc->id();
        $products[] = $p;
    }
}

// 3. Incluir el header
require_once __DIR__ . '/includes/header.php';
?>

    <div class="max-w-7xl mx-auto">
        <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
            <h2 class="text-3xl font-bold text-[var(--text-primary)]">Gestión de Productos</h2>
            <a href="add_product.php" class="flex items-center justify-center gap-2 rounded-md h-10 px-5 bg-[var(--primary-color)] text-white text-sm font-semibold hover:bg-[var(--primary-color-hover)] transition-colors duration-200 shadow-sm">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"></path></svg>
                <span>Agregar Producto</span>
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Imagen</th>
                        <th scope="col" class="px-6 py-3 min-w-[250px]">Producto</th>
                        <th scope="col" class="px-6 py-3">Precio</th>
                        <th scope="col" class="px-6 py-3">Stock</th>
                        <th scope="col" class="px-6 py-3">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($products) > 0): ?>
                        <?php foreach($products as $product): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="h-12 w-12 object-cover rounded">                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </th>
                                <td class="px-6 py-4">$<?php echo number_format($product['price'], 2); ?></td>
                                <td class="px-6 py-4"><?php echo $product['stock']; ?></td>
                                <td class="px-6 py-4 text-right">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="font-medium text-[var(--primary-color)] hover:underline mr-4">Editar</a>
                                    <a href="delete_product.php?id=<?php echo $product['id']; ?>" class="font-medium text-red-600 hover:underline" onclick="return confirm('¿Estás seguro de que quieres eliminar este producto?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="bg-white border-b">
                            <td colspan="5" class="text-center py-4">No se encontraron productos.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php
// 4. Incluir el footer
require_once __DIR__ . '/includes/footer.php';
?>