<?php

use Google\Cloud\Firestore\Transaction;

function scmIsLowStock(array $product): bool
{
    return (int)($product['stock'] ?? 0) < (int)($product['stock_minimo'] ?? 0);
}

function scmProductStock(array $product): int
{
    return max(0, (int)($product['stock'] ?? 0));
}

function scmFindAdministratorIds($db): array
{
    $ids = [];
    foreach ($db->collection('users')->documents() as $doc) {
        if (!$doc->exists()) {
            continue;
        }
        $user = $doc->data();
        if (($user['role'] ?? '') === 'admin') {
            $ids[] = $doc->id();
        }
    }
    return $ids;
}

/**
 * Ejecuta una reposición PUSH idempotente. La orden, su entrada, el stock y
 * las notificaciones se confirman en la misma transacción de Firestore.
 */
function aplicarReposicionPush($db, ?string $productId = null): array
{
    $productIds = $productId !== null && trim($productId) !== ''
        ? [trim($productId)]
        : array_map(static fn($doc) => $doc->id(), iterator_to_array($db->collection('products')->documents()));
    $administratorIds = scmFindAdministratorIds($db);
    $results = [];

    foreach ($productIds as $id) {
        $productRef = $db->collection('products')->document($id);
        $counterRef = $db->collection('scm_counters')->document('purchase_orders');
        $orderRef = $db->collection('scm_orders')->newDocument();
        $movementRef = $db->collection('scm_movements')->newDocument();
        $notificationRefs = [];
        foreach ($administratorIds as $adminId) {
            $notificationRefs[] = $db->collection('notifications')->newDocument();
        }

        $result = $db->runTransaction(function (Transaction $transaction) use (
            $productRef,
            $counterRef,
            $orderRef,
            $movementRef,
            $notificationRefs,
            $id
        ) {
            $productSnapshot = $transaction->snapshot($productRef);
            if (!$productSnapshot->exists()) {
                return null;
            }
            $product = $productSnapshot->data();
            $strategy = strtoupper((string)($product['strategy'] ?? 'PUSH'));
            $stock = scmProductStock($product);
            $minimum = max(0, (int)($product['stock_minimo'] ?? 0));
            if ($strategy !== 'PUSH' || $stock >= $minimum) {
                return null;
            }

            $quantity = $minimum - $stock;
            $counterSnapshot = $transaction->snapshot($counterRef);
            $nextNumber = $counterSnapshot->exists()
                ? max(1, (int)($counterSnapshot->data()['next_number'] ?? 1))
                : 1;
            $folio = sprintf('PC-AUTO-%04d', $nextNumber);
            $now = date('Y-m-d H:i:s');
            $supplierId = (string)($product['supplier_id'] ?? '');
            $order = [
                'folio' => $folio,
                'product_id' => $id,
                'quantity' => $quantity,
                'quantity_received' => $quantity,
                'type' => 'Reposición',
                'strategy' => 'PUSH',
                'origen' => 'AUTO',
                'supplier_id' => $supplierId,
                'supplier_name' => (string)($product['supplier_name'] ?? ''),
                'status' => 'Surtido',
                'entrada_registrada' => true,
                'entry_registered' => true,
                'entry_registered_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
                'notes' => 'Reposición PUSH automática hasta alcanzar el stock mínimo.',
            ];
            $movement = [
                'product_id' => $id,
                'type' => 'Entrada',
                'quantity' => $quantity,
                'reason' => 'Reposición PUSH automática',
                'purchase_order_id' => $orderRef->id(),
                'order_id' => $orderRef->id(),
                'user_id' => 'system',
                'user_name' => 'Sistema',
                'created_at' => $now,
            ];

            $transaction->set($counterRef, ['next_number' => $nextNumber + 1], ['merge' => true]);
            $transaction->create($orderRef, $order);
            $transaction->create($movementRef, $movement);
            $transaction->update($productRef, [['path' => 'stock', 'value' => $minimum]]);
            return ['folio' => $folio, 'quantity' => $quantity, 'order_id' => $orderRef->id(), 'movement_id' => $movementRef->id()];
        });

        if ($result !== null) {
            $now = date('Y-m-d H:i:s');
            foreach ($notificationRefs as $notificationRef) {
                $notificationRef->create([
                    'type' => 'scm_replenishment',
                    'title' => 'Reposición automática creada',
                    'message' => "Se surtió {$result['folio']} para reponer {$result['quantity']} unidades.",
                    'order_id' => $result['order_id'],
                    'read' => false,
                    'created_at' => $now,
                ]);
            }
            $results[] = $result;
        }
    }
    return $results;
}

function scmRegisterPurchaseEntry($db, string $orderId): array
{
    $orderRef = $db->collection('scm_orders')->document($orderId);
    return $db->runTransaction(function (Transaction $transaction) use ($db, $orderRef) {
        $orderSnapshot = $transaction->snapshot($orderRef);
        if (!$orderSnapshot->exists()) {
            throw new RuntimeException('El pedido no existe.');
        }
        $order = $orderSnapshot->data();
        if (($order['status'] ?? '') === 'Cancelado') {
            throw new RuntimeException('No se puede surtir un pedido cancelado.');
        }
        if (!empty($order['entrada_registrada']) || !empty($order['entry_registered'])) {
            return ['already_registered' => true];
        }
        $productId = (string)($order['product_id'] ?? '');
        $quantity = max(0, (int)($order['quantity'] ?? 0));
        $productRef = $db->collection('products')->document($productId);
        $productSnapshot = $transaction->snapshot($productRef);
        if (!$productSnapshot->exists() || $quantity < 1) {
            throw new RuntimeException('El producto o la cantidad del pedido no son válidos.');
        }
        $movementRef = $db->collection('scm_movements')->newDocument();
        $now = date('Y-m-d H:i:s');
        $transaction->create($movementRef, [
            'product_id' => $productId,
            'type' => 'Entrada',
            'quantity' => $quantity,
            'reason' => 'Recepción de pedido de proveedor',
            'purchase_order_id' => $orderRef->id(),
            'order_id' => $orderRef->id(),
            'user_id' => $_SESSION['user']['id'] ?? 'admin',
            'user_name' => $_SESSION['user']['name'] ?? 'Admin',
            'created_at' => $now,
        ]);
        $newStock = scmProductStock($productSnapshot->data()) + $quantity;
        $transaction->update($productRef, [['path' => 'stock', 'value' => $newStock]]);
        $transaction->update($orderRef, [
            ['path' => 'quantity_received', 'value' => $quantity],
            ['path' => 'entrada_registrada', 'value' => true],
            ['path' => 'entry_registered', 'value' => true],
            ['path' => 'entry_registered_at', 'value' => $now],
            ['path' => 'status', 'value' => 'Surtido'],
            ['path' => 'updated_at', 'value' => $now],
        ]);
        return ['already_registered' => false, 'quantity' => $quantity];
    });
}

function scmCancelPurchaseOrder($db, string $orderId): array
{
    $orderRef = $db->collection('scm_orders')->document($orderId);
    return $db->runTransaction(function (Transaction $transaction) use ($db, $orderRef) {
        $orderSnapshot = $transaction->snapshot($orderRef);
        if (!$orderSnapshot->exists()) {
            throw new RuntimeException('El pedido no existe.');
        }
        $order = $orderSnapshot->data();
        if (($order['status'] ?? '') === 'Cancelado') {
            return ['already_cancelled' => true];
        }
        $quantity = !empty($order['entrada_registrada']) || !empty($order['entry_registered'])
            ? max(0, (int)($order['quantity_received'] ?? $order['quantity'] ?? 0))
            : 0;
        if ($quantity > 0) {
            $productRef = $db->collection('products')->document((string)$order['product_id']);
            $productSnapshot = $transaction->snapshot($productRef);
            $stock = scmProductStock($productSnapshot->data());
            if ($stock < $quantity) {
                throw new RuntimeException('No hay stock suficiente para revertir el surtido.');
            }
            $movementRef = $db->collection('scm_movements')->newDocument();
            $now = date('Y-m-d H:i:s');
            $transaction->create($movementRef, [
                'product_id' => (string)$order['product_id'],
                'type' => 'Salida',
                'quantity' => $quantity,
                'reason' => 'Reversa por cancelación de pedido surtido',
                'purchase_order_id' => $orderRef->id(),
                'order_id' => $orderRef->id(),
                'user_id' => $_SESSION['user']['id'] ?? 'admin',
                'user_name' => $_SESSION['user']['name'] ?? 'Admin',
                'created_at' => $now,
            ]);
            $transaction->update($productRef, [['path' => 'stock', 'value' => $stock - $quantity]]);
        }
        $transaction->update($orderRef, [
            ['path' => 'status', 'value' => 'Cancelado'],
            ['path' => 'cancelled_at', 'value' => date('Y-m-d H:i:s')],
            ['path' => 'cancelled_by', 'value' => $_SESSION['user']['name'] ?? 'Admin'],
        ]);
        return ['already_cancelled' => false, 'reverted_quantity' => $quantity];
    });
}
