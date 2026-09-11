<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
$ordersDocs = $db->collection('orders')->documents();
$orders = [];
$usersCache = [];

foreach ($ordersDocs as $doc) {
    if ($doc->exists()) {
        $o = $doc->data();
        $o['id'] = $doc->id();
        
        $userId = $o['user_id'] ?? '';
        if ($userId) {
            if (!isset($usersCache[$userId])) {
                $uDoc = $db->collection('users')->document($userId)->snapshot();
                $usersCache[$userId] = $uDoc->exists() ? ($uDoc->data()['name'] ?? 'Desconocido') : 'Desconocido';
            }
            $o['customer_name'] = $usersCache[$userId];
        } else {
            $o['customer_name'] = 'Desconocido';
        }
        
        $orders[] = $o;
    }
}

usort($orders, function($a, $b) {
    return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
});

// Incluir el header
require_once __DIR__ . '/includes/header.php';
?>

    <div class="max-w-7xl mx-auto">
        <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
            <h2 class="text-3xl font-bold text-[var(--text-primary)]">Gestión de Pedidos</h2>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-[var(--border-color)] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">ID Pedido</th>
                        <th scope="col" class="px-6 py-3">Cliente</th>
                        <th scope="col" class="px-6 py-3">Total</th>
                        <th scope="col" class="px-6 py-3">Estado</th>
                        <th scope="col" class="px-6 py-3">Fecha</th>
                        <th scope="col" class="px-6 py-3">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900">
                                    #<?php echo $order['id']; ?>
                                </th>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td class="px-6 py-4">$<?php echo number_format($order['total'], 2); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 font-semibold leading-tight text-xs rounded-full
                                        <?php
                                    switch ($order['status']) {
                                        case 'paid': echo 'text-green-700 bg-green-100'; break;
                                        case 'shipped': echo 'text-blue-700 bg-blue-100'; break;
                                        case 'pending': echo 'text-yellow-700 bg-yellow-100'; break;
                                        case 'cancelled': echo 'text-red-700 bg-red-100'; break;
                                        default: echo 'text-gray-700 bg-gray-100';
                                    }
                                    ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4"><?php echo date("d M, Y", strtotime($order['created_at'])); ?></td>
                                <td class="px-6 py-4">
                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" class="font-medium text-[var(--primary-color)] hover:underline">Ver Detalles</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="bg-white border-b">
                            <td colspan="6" class="text-center py-4">No se han realizado pedidos.</td>
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