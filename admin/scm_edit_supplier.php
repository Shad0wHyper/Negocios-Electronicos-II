<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: scm_suppliers.php');
    exit;
}
$supplierId = trim($_GET['id']);
$supplierRef = $db->collection('scm_suppliers')->document($supplierId);
$doc = $supplierRef->snapshot();

if (!$doc->exists()) {
    header('Location: scm_suppliers.php');
    exit;
}
$supplier = $doc->data();
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $contact_person = $_POST['contact_person'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';

    if (empty($name)) {
        $errorMessage = 'El nombre del fabricante es obligatorio.';
    } else {
        try {
            $supplierRef->set([
                'name' => $name,
                'contact_person' => $contact_person,
                'email' => $email,
                'phone' => $phone,
                'address' => $address
            ], ['merge' => true]);
            header('Location: scm_suppliers.php');
            exit;
        } catch (Exception $e) {
            $errorMessage = "Error al actualizar: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <h2 class="text-3xl font-bold text-[var(--text-primary)] mb-8">Editar Fabricante / Proveedor</h2>

    <?php if ($errorMessage): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($errorMessage); ?></span>
        </div>
    <?php endif; ?>

    <form action="scm_edit_supplier.php?id=<?php echo $supplierId; ?>" method="POST" class="bg-white p-8 rounded-lg shadow-sm border border-[var(--border-color)] space-y-6">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nombre del Fabricante (Empresa)</label>
            <input type="text" name="name" id="name" required value="<?php echo htmlspecialchars($supplier['name'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label for="contact_person" class="block text-sm font-medium text-gray-700">Nombre de Contacto</label>
            <input type="text" name="contact_person" id="contact_person" value="<?php echo htmlspecialchars($supplier['contact_person'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($supplier['email'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Teléfono</label>
                <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($supplier['phone'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>
        <div>
            <label for="address" class="block text-sm font-medium text-gray-700">Dirección</label>
            <textarea name="address" id="address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><?php echo htmlspecialchars($supplier['address'] ?? ''); ?></textarea>
        </div>
        <div class="text-right">
            <a href="scm_suppliers.php" class="text-gray-600 mr-4">Cancelar</a>
            <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[var(--primary-color)] hover:bg-[var(--primary-color-hover)]">
                Guardar Cambios
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
