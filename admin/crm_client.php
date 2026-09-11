<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

// Validar ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: crm.php');
    exit;
}

$clientId = trim($_GET['id']);

// Obtener datos del cliente
$clientDoc = $db->collection('users')->document($clientId)->snapshot();
if (!$clientDoc->exists()) {
    header('Location: crm.php');
    exit;
}
$client = $clientDoc->data();
$client['id'] = $clientDoc->id();

if (($client['role'] ?? '') !== 'customer') {
    header('Location: crm.php');
    exit;
}

$message = '';
$error = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_stage' && isset($_POST['crm_stage'])) {
            $newStage = $_POST['crm_stage'];
            $validStages = ['Prospecto', 'Activo', 'Frecuente', 'Inactivo'];
            if (in_array($newStage, $validStages)) {
                try {
                    $db->collection('users')->document($clientId)->set([
                        'crm_stage' => $newStage,
                        'crm_stage_manual' => true
                    ], ['merge' => true]);
                    $client['crm_stage'] = $newStage; // Actualizar vista local
                    $message = 'Etapa CRM actualizada manualmente.';
                } catch (Exception $e) {
                    $error = 'Error al actualizar la etapa.';
                }
            }
        } elseif ($_POST['action'] === 'add_interaction' && isset($_POST['type'], $_POST['description'])) {
            $type = $_POST['type'];
            $description = trim($_POST['description']);
            $adminId = $_SESSION['user']['id'];
            
            if (!empty($description)) {
                try {
                    $db->collection('crm_interactions')->add([
                        'user_id' => $clientId,
                        'admin_id' => (string)$adminId,
                        'admin_name' => $_SESSION['user']['name'],
                        'type' => $type,
                        'description' => $description,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    $message = 'Interacción registrada correctamente.';
                } catch (Exception $e) {
                    $error = 'Error al registrar la interacción.';
                }
            } else {
                $error = 'La descripción de la interacción no puede estar vacía.';
            }
        }
    }
}

// Obtener pedidos del cliente y calcular producto favorito
$ordersQuery = $db->collection('orders')->where('user_id', '=', $clientId);
$ordersDocs = $ordersQuery->documents();
$orders = [];
$productStats = [];

foreach ($ordersDocs as $doc) {
    if ($doc->exists()) {
        $o = $doc->data();
        $o['id'] = $doc->id();
        $orders[] = $o;
        
        if (isset($o['items']) && is_array($o['items'])) {
            foreach ($o['items'] as $item) {
                $pid = $item['product_id'];
                if (!isset($productStats[$pid])) {
                    $productStats[$pid] = ['total_quantity' => 0, 'order_count' => 0];
                }
                $productStats[$pid]['total_quantity'] += (int)$item['quantity'];
                $productStats[$pid]['order_count']++; // Simplified: count per line item occurrence
            }
        }
    }
}

// Sort orders by date DESC
usort($orders, function($a, $b) {
    return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
});

// Encontrar producto favorito
$favoriteProduct = null;
if (!empty($productStats)) {
    $bestPid = null;
    $bestQty = -1;
    foreach ($productStats as $pid => $stats) {
        if ($stats['total_quantity'] > $bestQty) {
            $bestQty = $stats['total_quantity'];
            $bestPid = $pid;
        }
    }
    
    if ($bestPid) {
        $pDoc = $db->collection('products')->document($bestPid)->snapshot();
        if ($pDoc->exists()) {
            $favoriteProduct = $pDoc->data();
            $favoriteProduct['id'] = $pDoc->id();
            $favoriteProduct['total_quantity'] = $productStats[$bestPid]['total_quantity'];
            $favoriteProduct['order_count'] = $productStats[$bestPid]['order_count'];
        }
    }
}

// Obtener interacciones
$interQuery = $db->collection('crm_interactions')->where('user_id', '=', $clientId);
$interDocs = $interQuery->documents();
$interactions = [];

foreach ($interDocs as $doc) {
    if ($doc->exists()) {
        $interactions[] = $doc->data();
    }
}

// Sort interactions by date DESC
usort($interactions, function($a, $b) {
    return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
});

// Colores para las etiquetas de estado
$stageColors = [
    'Prospecto' => 'bg-gray-100 text-gray-800',
    'Activo' => 'bg-green-100 text-green-800',
    'Frecuente' => 'bg-blue-100 text-blue-800',
    'Inactivo' => 'bg-red-100 text-red-800'
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold text-[var(--text-primary)]">
            Detalle del Cliente
        </h2>
        <a href="crm.php" class="text-gray-500 hover:text-[var(--primary-color)] transition-colors font-medium">
            &larr; Volver a CRM
        </a>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?php echo $message; ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Columna Izquierda: Perfil y Clasificación -->
        <div class="space-y-8">
            <!-- Tarjeta de Perfil -->
            <div class="bg-white shadow-sm rounded-lg border border-[var(--border-color)] p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="h-16 w-16 rounded-full bg-[var(--primary-color)] flex items-center justify-center text-white text-2xl font-bold uppercase">
                        <?php echo substr($client['name'], 0, 1); ?>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($client['name']); ?></h3>
                        <p class="text-gray-500 text-sm">Registrado: <?php echo date("d M Y", strtotime($client['created_at'])); ?></p>
                    </div>
                </div>
                <div class="border-t pt-4 space-y-4">
                    <p class="text-sm text-gray-700"><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>" class="text-[var(--primary-color)] hover:underline"><?php echo htmlspecialchars($client['email']); ?></a></p>
                    
                    <!-- Botones de Acción Rápida -->
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>?subject=Información de Xanarchy" target="_blank" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-semibold py-2 px-4 rounded border text-center transition-colors">
                            ✉️ Enviar Correo
                        </a>
                        <a href="edit_user.php?id=<?php echo (int)$client['id']; ?>&return=crm" class="flex-1 bg-[var(--primary-color)] hover:bg-[var(--primary-color-hover)] text-white text-sm font-semibold py-2 px-4 rounded border text-center transition-colors">
                            Editar cliente
                        </a>
                    </div>
                </div>
            </div>

            <!-- Cambiar Etapa CRM -->
            <div class="bg-white shadow-sm rounded-lg border border-[var(--border-color)] p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Clasificación CRM</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_stage">
                    
                    <div>
                        <label for="crm_stage" class="block text-sm font-medium text-gray-700 mb-1">Etapa actual:</label>
                        <select name="crm_stage" id="crm_stage" class="w-full border-gray-300 rounded-md shadow-sm focus:border-[var(--primary-color)] focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-gray-700">
                            <option value="Prospecto" <?php echo $client['crm_stage'] === 'Prospecto' ? 'selected' : ''; ?>>Prospecto (Registrado sin pedidos recientes)</option>
                            <option value="Activo" <?php echo $client['crm_stage'] === 'Activo' ? 'selected' : ''; ?>>Activo (Compras recientes o en curso)</option>
                            <option value="Frecuente" <?php echo $client['crm_stage'] === 'Frecuente' ? 'selected' : ''; ?>>Frecuente (Cliente recurrente/leal)</option>
                            <option value="Inactivo" <?php echo $client['crm_stage'] === 'Inactivo' ? 'selected' : ''; ?>>Inactivo (Sin actividad prolongada)</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-[var(--primary-color)] hover:bg-[var(--primary-color-hover)] text-white font-medium py-2 px-4 rounded-md transition-colors">
                        Guardar Cambios
                    </button>
                </form>
            </div>

            <!-- Producto más comprado -->
            <div class="bg-white shadow-sm rounded-lg border border-[var(--border-color)] overflow-hidden">
                <div class="p-4 border-b bg-gray-50 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Producto más comprado</h3>
                    <span class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-700">Destacado</span>
                </div>
                <?php if ($favoriteProduct): ?>
                    <div class="p-6">
                        <?php if (!empty($favoriteProduct['image'])): ?>
                            <img
                                src="<?php echo htmlspecialchars($favoriteProduct['image']); ?>"
                                alt="<?php echo htmlspecialchars($favoriteProduct['name']); ?>"
                                class="mb-4 h-48 w-full rounded-lg border border-gray-200 object-cover"
                            >
                        <?php endif; ?>
                        <h4 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($favoriteProduct['name']); ?></h4>
                        <p class="mt-1 text-sm text-gray-500">
                            <?php echo number_format((int)$favoriteProduct['total_quantity']); ?> unidades en
                            <?php echo number_format((int)$favoriteProduct['order_count']); ?> pedidos
                        </p>
                        <a href="../product.php?id=<?php echo (int)$favoriteProduct['id']; ?>" class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-[var(--primary-color)] px-4 py-2 text-sm font-semibold text-[var(--primary-color)] hover:bg-blue-50">
                            Ver producto
                        </a>
                    </div>
                <?php else: ?>
                    <p class="p-6 text-center text-sm text-gray-500">Este cliente aún no ha comprado productos.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Columna Derecha: Pedidos e Interacciones -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Historial de Pedidos -->
            <div class="bg-white shadow-sm rounded-lg border border-[var(--border-color)] overflow-hidden">
                <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Historial de Pedidos</h3>
                    <span class="text-sm font-medium text-gray-500">Total: <?php echo count($orders); ?> pedidos</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                            <tr>
                                <th scope="col" class="px-6 py-3">ID Pedido</th>
                                <th scope="col" class="px-6 py-3">Total</th>
                                <th scope="col" class="px-6 py-3">Estado</th>
                                <th scope="col" class="px-6 py-3">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach($orders as $order): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900"><a href="view_order.php?id=<?php echo $order['id']; ?>" class="text-[var(--primary-color)] hover:underline">#<?php echo $order['id']; ?></a></td>
                                <td class="px-6 py-4">$<?php echo number_format($order['total'], 2); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 font-semibold leading-tight rounded-full 
                                        <?php 
                                            if($order['status'] == 'paid'){ echo 'text-green-700 bg-green-100'; } 
                                            elseif($order['status'] == 'shipped'){ echo 'text-blue-700 bg-blue-100'; } 
                                            elseif($order['status'] == 'pending'){ echo 'text-yellow-700 bg-yellow-100'; } 
                                            else { echo 'text-red-700 bg-red-100'; } 
                                        ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4"><?php echo date("d M, Y H:i", strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-6 text-gray-500">Este cliente aún no ha realizado pedidos.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Registro de Interacciones -->
            <div class="bg-white shadow-sm rounded-lg border border-[var(--border-color)]">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-800">Interacciones y Notas</h3>
                </div>
                
                <div class="p-6 border-b">
                    <!-- Formulario de nueva interacción -->
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_interaction">
                        <div class="flex gap-4 flex-col sm:flex-row">
                            <div class="w-full sm:w-1/3">
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                                <select name="type" id="type" class="w-full border-gray-300 rounded-md shadow-sm focus:border-[var(--primary-color)] focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-gray-700" required>
                                    <option value="Nota Interna">Nota Interna</option>
                                    <option value="Correo">Correo Electrónico</option>
                                    <option value="Redes">Mensaje (Redes Sociales)</option>
                                    <option value="Soporte">Problema / Soporte</option>
                                </select>
                            </div>
                            <div class="w-full sm:w-2/3">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Descripción / Detalles</label>
                                <textarea name="description" id="description" rows="2" class="w-full border-gray-300 rounded-md shadow-sm focus:border-[var(--primary-color)] focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-gray-700" placeholder="Ej. Se envió correo notificando retraso de paquetería..." required></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-medium py-2 px-6 rounded-md transition-colors text-sm">
                                Registrar Interacción
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Historial (Timeline) -->
                <div class="p-6">
                    <h4 class="font-medium text-gray-700 mb-4">Historial</h4>
                    <?php if (count($interactions) > 0): ?>
                        <div class="space-y-6">
                            <?php foreach($interactions as $inter): ?>
                            <div class="flex gap-4">
                                <div class="flex flex-col items-center">
                                    <div class="h-10 w-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                                        <?php if($inter['type'] == 'Correo'): ?>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        <?php elseif($inter['type'] == 'Soporte'): ?>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        <?php elseif($inter['type'] == 'Redes'): ?>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                        <?php else: ?>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="h-full w-px bg-gray-200 my-2"></div>
                                </div>
                                <div class="flex-1 pb-4">
                                    <div class="flex justify-between items-start mb-1">
                                        <h5 class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($inter['type']); ?></h5>
                                        <span class="text-xs text-gray-500"><?php echo date("d M Y H:i", strtotime($inter['created_at'])); ?></span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2"><?php echo nl2br(htmlspecialchars($inter['description'])); ?></p>
                                    <p class="text-xs text-gray-400">Registrado por: <?php echo htmlspecialchars($inter['admin_name']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 text-center py-4">No hay interacciones registradas.</p>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
