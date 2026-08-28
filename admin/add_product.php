<?php
global $pdo;
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
$errorMessage = '';
$successMessage = '';

// Lógica para procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Recoger datos del formulario
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $image = $_FILES['image'];

    // 2. Validar datos
    if (empty($name) || empty($price)) {
        $errorMessage = 'Los campos nombre y precio son obligatorios.';
    } elseif ($stock < 0) {
        $errorMessage = 'El stock no puede ser un número negativo.';
    } elseif ($image['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'Hubo un error al subir la imagen.';
    } else {
        // 3. Procesar la imagen
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5 MB

        if (!in_array($image['type'], $allowedTypes)) {
            $errorMessage = 'Tipo de archivo no permitido. Solo se aceptan JPEG, PNG, GIF, WEBP.';
        } elseif ($image['size'] > $maxSize) {
            $errorMessage = 'El archivo es demasiado grande. El máximo es 5 MB.';
        } else {
            $imageFolder = __DIR__ . '/../Imagenes/';
            $imageExtension = pathinfo($image['name'], PATHINFO_EXTENSION);
            $newImageName = uniqid('prod_', true) . '.' . $imageExtension;
            $targetPath = $imageFolder . $newImageName;

            if (move_uploaded_file($image['tmp_name'], $targetPath)) {
                //Insert con Stored Procedure
                try {
                    $dbPath = 'Imagenes/' . $newImageName;

                    // Stored Procedure
                    $stmt = $pdo->prepare("CALL InsertarNuevoProducto(?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $description, $price, $stock, $dbPath]);

                    header('Location: products.php');
                    exit;

                } catch (PDOException $e) {
                    $errorMessage = "Error al guardar en la base de datos: " . $e->getMessage();
                }
            } else {
                $errorMessage = 'No se pudo mover el archivo subido. Revisa los permisos de la carpeta /Imagenes.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

    <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold text-[var(--text-primary)] mb-8">Agregar Nuevo Producto</h2>

        <?php if ($errorMessage): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $errorMessage; ?></span>
            </div>
        <?php endif; ?>

        <form action="add_product.php" method="POST" enctype="multipart/form-data" class="bg-white p-8 rounded-lg shadow-sm border border-[var(--border-color)] space-y-6">

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nombre del Producto</label>
                <input type="text" name="name" id="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Descripción</label>
                <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">Precio ($)</label>
                    <input type="number" name="price" id="price" step="0.01" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="stock" class="block text-sm font-medium text-gray-700">Stock (Cantidad disponible)</label>
                    <input type="number" name="stock" id="stock" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label for="image" class="block text-sm font-medium text-gray-700">Imagen del Producto</label>
                <input type="file" name="image" id="image" required class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <div class="text-right">
                <a href="products.php" class="text-gray-600 mr-4">Cancelar</a>
                <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[var(--primary-color)] hover:bg-[var(--primary-color-hover)]">
                    Guardar Producto
                </button>
            </div>

        </form>
    </div>


<?php
// Incluir el footer
require_once __DIR__ . '/includes/footer.php';
?>