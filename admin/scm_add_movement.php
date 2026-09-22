<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

$errorMessage = '';
$successMessage = '';

// Get products for dropdown
$products = [];
$pDocs = $db->collection('products')->orderBy('name')->documents();
foreach ($pDocs as $doc) {
    if ($doc->exists()) {
        $p = $doc->data();
        $p['id'] = $doc->id();
        $products[] = $p;
    }
}

$preSelectedId = $_GET['product_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? '';
    $type = $_POST['type'] ?? 'Entrada';
    $quantity = (int)($_POST['quantity'] ?? 0);
    $reason = $_POST['reason'] ?? '';

    if (empty($product_id) || $quantity <= 0) {
        $errorMessage = 'Debe seleccionar un producto y una cantidad mayor a 0.';
    } else {
        try {
            // Obtener el producto actual para actualizar su stock
            $productRef = $db->collection('products')->document($product_id);
            $productSnap = $productRef->snapshot();
            
            if ($productSnap->exists()) {
                $currentStock = (int)($productSnap->data()['stock'] ?? 0);
                
                // Calcular nuevo stock
                $newStock = $type === 'Entrada' ? $currentStock + $quantity : $currentStock - $quantity;
                if ($newStock < 0) $newStock = 0;

                // 1. Registrar el movimiento
                $db->collection('scm_movements')->add([
                    'product_id' => $product_id,
                    'type' => $type,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'user_id' => $_SESSION['user']['id'] ?? '',
                    'user_name' => $_SESSION['user']['name'] ?? 'Admin',
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                // 2. Actualizar el stock del producto
                $productRef->set(['stock' => $newStock], ['merge' => true]);

                header('Location: scm_movements.php');
                exit;
            } else {
                $errorMessage = 'El producto seleccionado no existe.';
            }
        } catch (Exception $e) {
            $errorMessage = "Error al registrar movimiento: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <h2 class="text-3xl font-bold text-[var(--text-primary)] mb-8">Registrar Movimiento de Inventario</h2>

    <?php if ($errorMessage): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($errorMessage); ?></span>
        </div>
    <?php endif; ?>

    <form action="scm_add_movement.php" method="POST" class="bg-white p-8 rounded-lg shadow-sm border border-[var(--border-color)] space-y-6">
        <div>
            <label for="product_id" class="block text-sm font-medium text-gray-700">Producto</label>
            <select name="product_id" id="product_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Selecciona un producto</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?php echo htmlspecialchars($p['id']); ?>" <?php echo $preSelectedId === $p['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['name']); ?> (Stock actual: <?php echo $p['stock']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Movimiento</label>
                <div class="flex items-center space-x-6">
                    <label class="flex items-center">
                        <input type="radio" name="type" value="Entrada" checked class="text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-gray-700">Entrada (Sumar)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="type" value="Salida" class="text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-gray-700">Salida (Restar)</span>
                    </label>
                </div>
            </div>
            <div>
                <label for="quantity" class="block text-sm font-medium text-gray-700">Cantidad</label>
                <input type="number" name="quantity" id="quantity" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>

        <div>
            <label for="reason" class="block text-sm font-medium text-gray-700">Motivo / Notas</label>
            <select name="reason" id="reason" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 mb-2">
                <option value="Compra a Proveedor">Compra a Proveedor</option>
                <option value="Devolución de cliente">Devolución de cliente</option>
                <option value="Ajuste de inventario">Ajuste de inventario</option>
                <option value="Merma / Defecto">Merma / Defecto</option>
                <option value="Otro">Otro</option>
            </select>
        </div>

        <div class="text-right">
            <a href="scm_inventory.php" class="text-gray-600 mr-4">Cancelar</a>
            <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[var(--primary-color)] hover:bg-[var(--primary-color-hover)]">
                Guardar Movimiento
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
