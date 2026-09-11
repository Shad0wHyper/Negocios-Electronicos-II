<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
// 1. Validar y obtener el ID del producto de la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: products.php');
    exit;
}
$productId = trim($_GET['id']);

// 2. Obtener los datos actuales del producto para mostrarlos en el formulario
$productRef = $db->collection('products')->document($productId);
$doc = $productRef->snapshot();

if (!$doc->exists()) {
    // Si el producto no existe, redirigir a la lista
    header('Location: products.php');
    exit;
}
$product = $doc->data();

$errorMessage = '';

// 3. Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $currentImage = $product['image']; // Guardamos la ruta de la imagen actual

    // Validar datos básicos
    if (empty($name) || empty($price) || empty($stock)) {
        $errorMessage = 'Los campos nombre, precio y stock son obligatorios.';
    } else {
        // 4. Manejar la subida de una NUEVA imagen (si se proporciona)
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = $_FILES['image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5 MB

            if (in_array($image['type'], $allowedTypes) && $image['size'] <= $maxSize) {
                // Subir la nueva imagen a Firebase Storage
                $imageExtension = pathinfo($image['name'], PATHINFO_EXTENSION);
                $newImageName = uniqid('prod_', true) . '.' . $imageExtension;
                $storagePath = 'products/' . $newImageName;

                try {
                    $bucket->upload(
                        fopen($image['tmp_name'], 'r'),
                        ['name' => $storagePath]
                    );
                    
                    $currentImage = sprintf('https://firebasestorage.googleapis.com/v0/b/%s/o/%s?alt=media', 
                        $bucket->name(), 
                        rawurlencode($storagePath)
                    );
                } catch (Exception $e) {
                    $errorMessage = 'Error al subir la nueva imagen: ' . $e->getMessage();
                }
            } else {
                $errorMessage = 'Archivo de imagen no válido (tipo o tamaño).';
            }
        }

        // 5. Actualizar la base de datos (solo si no hubo errores con la imagen)
        if (empty($errorMessage)) {
            try {
                $productRef->set([
                    'name' => $name,
                    'description' => $description,
                    'price' => (float)$price,
                    'stock' => (int)$stock,
                    'image' => $currentImage
                ], ['merge' => true]);

                header('Location: products.php');
                exit;
            } catch (Exception $e) {
                $errorMessage = "Error al actualizar la base de datos: " . $e->getMessage();
            }
        }
    }
}

// Incluir el header
require_once __DIR__ . '/includes/header.php';
?>

    <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold text-[var(--text-primary)] mb-8">Editar Producto</h2>

        <?php if ($errorMessage): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $errorMessage; ?></span>
            </div>
        <?php endif; ?>

        <form action="edit_product.php?id=<?php echo $productId; ?>" method="POST" enctype="multipart/form-data" class="bg-white p-8 rounded-lg shadow-sm border border-[var(--border-color)] space-y-6">

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nombre del Producto</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($product['name']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Descripción</label>
                <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">Precio ($)</label>
                    <input type="number" name="price" id="price" value="<?php echo htmlspecialchars($product['price']); ?>" step="0.01" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label for="stock" class="block text-sm font-medium text-gray-700">Stock</label>
                    <input type="number" name="stock" id="stock" min="0" value="<?php echo htmlspecialchars($product['stock']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Imagen Actual</label>
                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Imagen actual" class="mt-2 h-24 w-24 object-cover rounded-md border">
            </div>

            <div>
                <label for="image" class="block text-sm font-medium text-gray-700">Cambiar Imagen (Opcional)</label>
                <input type="file" name="image" id="image" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <div class="text-right">
                <a href="products.php" class="text-gray-600 mr-4">Cancelar</a>
                <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[var(--primary-color)] hover:bg-[var(--primary-color-hover)]">
                    Guardar Cambios
                </button>
            </div>

        </form>
    </div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>