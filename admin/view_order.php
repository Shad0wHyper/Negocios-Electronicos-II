<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
// 1. Validar y obtener el ID del pedido de la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: orders.php');
    exit;
}
$orderId = trim($_GET['id']);

// --- NUEVO: Procesar la actualización de estado si se envía el formulario ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    $allowedStatuses = ['pending', 'paid', 'shipped', 'cancelled'];

    if (in_array($newStatus, $allowedStatuses)) {
        try {
            $db->collection('orders')->document($orderId)->set(['status' => $newStatus], ['merge' => true]);
        } catch (Exception $e) {}

        // Redirigir a la misma página para ver el cambio y evitar reenvío del formulario
        header('Location: view_order.php?id=' . $orderId);
        exit;
    }
}
// --- FIN DEL NUEVO CÓDIGO ---


// 2. Obtener los datos principales del pedido y del usuario
$orderDoc = $db->collection('orders')->document($orderId)->snapshot();
if (!$orderDoc->exists()) {
    header('Location: orders.php');
    exit;
}
$order = $orderDoc->data();
$order['id'] = $orderDoc->id();

$userDoc = $db->collection('users')->document($order['user_id'] ?? '')->snapshot();
if ($userDoc->exists()) {
    $uData = $userDoc->data();
    $order['user_name'] = $uData['name'] ?? 'Desconocido';
    $order['user_email'] = $uData['email'] ?? 'Desconocido';
} else {
    $order['user_name'] = 'Desconocido';
    $order['user_email'] = 'Desconocido';
}

// 3. Obtener los artículos específicos de este pedido
$orderItems = [];
if (isset($order['items']) && is_array($order['items'])) {
    foreach ($order['items'] as $item) {
        $pId = $item['product_id'];
        $pDoc = $db->collection('products')->document($pId)->snapshot();
        if ($pDoc->exists()) {
            $pData = $pDoc->data();
            $item['product_name'] = $pData['name'] ?? 'Producto Desconocido';
            $item['product_image'] = $pData['image'] ?? '';
        } else {
            $item['product_name'] = 'Producto Eliminado';
            $item['product_image'] = '';
        }
        $orderItems[] = $item;
    }
}

// 4. Obtener la dirección más reciente del usuario
$addressesQuery = $db->collection('addresses')->where('user_id', '=', $order['user_id'] ?? '')->documents();
$addresses = [];
foreach ($addressesQuery as $doc) {
    if ($doc->exists()) {
        $addr = $doc->data();
        $addr['id'] = $doc->id();
        $addresses[] = $addr;
    }
}
usort($addresses, function($a, $b) {
    return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
});
$address = !empty($addresses) ? $addresses[0] : null;

// 5. Calcular subtotales
$itemsSubtotal = 0;
foreach ($orderItems as $item) {
    $itemsSubtotal += $item['unit_price'] * $item['quantity'];
}
$shippingCost = $order['total'] - $itemsSubtotal;


// Incluir el header
require_once __DIR__ . '/includes/header.php';
?>

    <div class="max-w-7xl mx-auto">
        <div class="flex items-center mb-4">
            <a href="orders.php" class="text-gray-600 hover:text-[var(--primary-color)] mr-2">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-3xl font-bold text-[var(--text-primary)]">
                Detalles del Pedido #<?php echo $order['id']; ?>
            </h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border">
                    <h3 class="text-lg font-semibold mb-4 border-b pb-2">Resumen del Pedido</h3>
                    <div class="space-y-2">
                        <p><strong>Fecha:</strong> <?php echo date("d M, Y H:i", strtotime($order['created_at'])); ?></p>
                        <p><strong>Subtotal (Artículos):</strong> $<?php echo number_format($itemsSubtotal, 2); ?></p>
                        <p><strong>Envío:</strong> $<?php echo number_format($shippingCost, 2); ?></p>
                        <p class="font-bold border-t pt-2 mt-2"><strong>Total:</strong> $<?php echo number_format($order['total'], 2); ?></p>
                        <div class="border-t pt-3 mt-3">
                            <form action="view_order.php?id=<?php echo $orderId; ?>" method="POST">
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1"><strong>Actualizar Estado:</strong></label>
                                <div class="flex items-center gap-2">
                                    <select name="status" id="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="pending" <?php echo ($order['status'] === 'pending') ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="paid" <?php echo ($order['status'] === 'paid') ? 'selected' : ''; ?>>Pagado</option>
                                        <option value="shipped" <?php echo ($order['status'] === 'shipped') ? 'selected' : ''; ?>>Enviado</option>
                                        <option value="cancelled" <?php echo ($order['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelado</option>
                                    </select>
                                    <button type="submit" name="update_status" class="px-4 py-2 text-sm font-medium rounded-md text-white bg-[var(--primary-color)] hover:bg-[var(--primary-color-hover)]">
                                        Guardar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border">
                    <h3 class="text-lg font-semibold mb-4 border-b pb-2">Cliente y Dirección de Envío</h3>
                    <?php if ($address): ?>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($order['user_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['user_email']); ?></p>
                        <hr class="my-3">
                        <p><strong>Dirección:</strong><br>
                            <?php echo htmlspecialchars($address['address']); ?><br>
                            <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' CP ' . $address['zip']); ?><br>
                            <?php echo htmlspecialchars($address['country']); ?>
                        </p>
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($address['phone']); ?></p>
                    <?php else: ?>
                        <p>Este cliente no tiene una dirección registrada.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm border">
                    <h3 class="text-lg font-semibold p-6 border-b">Artículos del Pedido</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3">Producto</th>
                                <th scope="col" class="px-6 py-3">Precio Unitario</th>
                                <th scope="col" class="px-6 py-3">Cantidad</th>
                                <th scope="col" class="px-6 py-3 text-right">Subtotal</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap flex items-center">
                                        <img src="<?php echo htmlspecialchars($item['product_image']); ?>" class="h-12 w-12 object-cover rounded mr-4">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </th>
                                    <td class="px-6 py-4">$<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td class="px-6 py-4"><?php echo $item['quantity']; ?></td>
                                    <td class="px-6 py-4 text-right">$<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>