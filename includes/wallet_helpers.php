<?php
// includes/wallet_helpers.php - Funciones para Monedero Electrónico, recargas y checkout atómico

use Google\Cloud\Firestore\Transaction;

if (file_exists(__DIR__ . '/scm_functions.php')) {
    require_once __DIR__ . '/scm_functions.php';
}

if (!function_exists('getUserWalletBalance')) {
    function getUserWalletBalance($db, string $userId): float
    {
        $userId = trim($userId);
        if ($userId === '') {
            return 0.0;
        }
        $snapshot = $db->collection('users')->document($userId)->snapshot();
        if (!$snapshot->exists()) {
            return 0.0;
        }
        $data = $snapshot->data();
        return round(max(0.0, (float)($data['wallet_balance'] ?? 0)), 2);
    }
}

if (!function_exists('generateOrderCode')) {
    function generateOrderCode(): string
    {
        return 'OR-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    }
}

if (!function_exists('generateWalletRef')) {
    function generateWalletRef(): string
    {
        return 'REC-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    }
}

if (!function_exists('getPaymentMethodLabel')) {
    function getPaymentMethodLabel(?string $method): string
    {
        $m = mb_strtolower(trim((string)$method), 'UTF-8');
        if ($m === 'monedero') {
            return 'Monedero';
        }
        if ($m === 'paypal') {
            return 'PayPal';
        }
        return 'Estándar / PayPal';
    }
}

if (!function_exists('getOrderStatusMeta')) {
    function getOrderStatusMeta(string $status): array
    {
        switch ($status) {
            case 'paid':
                return ['label' => 'Pagado / Procesado', 'class' => 'order-badge-paid'];
            case 'shipped':
                return ['label' => 'En envío', 'class' => 'order-badge-shipped'];
            case 'cancelled':
                return ['label' => 'Cancelado', 'class' => 'order-badge-cancelled'];
            case 'pending':
            default:
                return ['label' => 'Pendiente', 'class' => 'order-badge-pending'];
        }
    }
}

if (!function_exists('rechargeUserWallet')) {
    function rechargeUserWallet($db, string $userId, float $amount, string $method): array
    {
        $amount = round($amount, 2);
        if ($amount < 10 || $amount > 50000) {
            throw new RuntimeException('El monto de recarga debe estar entre $10.00 y $50,000.00.');
        }

        $userRef = $db->collection('users')->document($userId);
        $txRef = $db->collection('wallet_transactions')->newDocument();
        $reference = generateWalletRef();
        $now = date('Y-m-d H:i:s');

        return $db->runTransaction(function (Transaction $transaction) use (
            $userRef,
            $txRef,
            $userId,
            $amount,
            $method,
            $reference,
            $now
        ) {
            $userSnapshot = $transaction->snapshot($userRef);
            if (!$userSnapshot->exists()) {
                throw new RuntimeException('La cuenta de usuario no existe.');
            }
            $uData = $userSnapshot->data();
            $balanceBefore = round(max(0.0, (float)($uData['wallet_balance'] ?? 0)), 2);
            $balanceAfter = round($balanceBefore + $amount, 2);

            $transaction->set($userRef, ['wallet_balance' => $balanceAfter], ['merge' => true]);
            $transaction->create($txRef, [
                'user_id' => $userId,
                'type' => 'recarga',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'method' => $method,
                'reference' => $reference,
                'order_id' => '',
                'description' => 'Recarga simulada vía ' . $method,
                'created_at' => $now,
            ]);

            return [
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference' => $reference,
                'amount' => $amount,
            ];
        });
    }
}

if (!function_exists('processWalletCheckout')) {
    function processWalletCheckout($db, string $userId, array $user, array $address, string $addressId, array $cart, float $shippingCost = 120.0): array
    {
        if (empty($cart)) {
            throw new RuntimeException('Tu carrito está vacío.');
        }

        $userRef = $db->collection('users')->document($userId);
        $orderRef = $db->collection('orders')->newDocument();
        $walletTxRef = $db->collection('wallet_transactions')->newDocument();

        $productRefs = [];
        $movementRefs = [];
        foreach ($cart as $pid => $qty) {
            $pid = trim((string)$pid);
            $qty = (int)$qty;
            if ($pid === '' || $qty <= 0) {
                continue;
            }
            $productRefs[$pid] = $db->collection('products')->document($pid);
            $movementRefs[$pid] = $db->collection('scm_movements')->newDocument();
        }

        if (empty($productRefs)) {
            throw new RuntimeException('No hay productos válidos en el carrito.');
        }

        $now = date('Y-m-d H:i:s');
        $orderCode = generateOrderCode();
        $userRole = function_exists('normalizeUserRole') ? normalizeUserRole($user['role'] ?? 'user') : 'user';
        $customerName = trim((string)($user['name'] ?? 'Cliente'));
        if ($customerName === '') {
            $customerName = 'Cliente';
        }
        $customerEmail = trim((string)($user['email'] ?? ''));

        $txResult = $db->runTransaction(function (Transaction $transaction) use (
            $userRef,
            $orderRef,
            $walletTxRef,
            $productRefs,
            $movementRefs,
            $cart,
            $userId,
            $userRole,
            $customerName,
            $customerEmail,
            $address,
            $addressId,
            $shippingCost,
            $now,
            $orderCode
        ) {
            // 1) Todas las lecturas primero (regla de transacciones de Firestore)
            $userSnapshot = $transaction->snapshot($userRef);
            if (!$userSnapshot->exists()) {
                throw new RuntimeException('No se encontró tu cuenta de usuario.');
            }
            $userData = $userSnapshot->data();
            $balanceBefore = round(max(0.0, (float)($userData['wallet_balance'] ?? 0)), 2);

            $productSnapshots = [];
            foreach ($productRefs as $pid => $pRef) {
                $pSnap = $transaction->snapshot($pRef);
                if (!$pSnap->exists()) {
                    throw new RuntimeException('Uno o más productos del carrito ya no están disponibles.');
                }
                $productSnapshots[$pid] = $pSnap;
            }

            // 2) Validaciones de stock y cálculo de totales
            $subtotal = 0.0;
            $items = [];
            $stockUpdates = [];

            foreach ($productSnapshots as $pid => $pSnap) {
                $pData = $pSnap->data();
                $qty = (int)$cart[$pid];
                $stock = max(0, (int)($pData['stock'] ?? 0));
                $pName = $pData['name'] ?? 'Producto';

                if ($stock === 0) {
                    throw new RuntimeException("{$pName} está agotado y ya no se puede vender.");
                }
                if ($qty > $stock) {
                    throw new RuntimeException("Stock insuficiente para {$pName}. Disponibles: {$stock}.");
                }

                $unitPrice = round((float)($pData['price'] ?? 0), 2);
                $subtotal += $unitPrice * $qty;
                $items[] = [
                    'product_id' => $pid,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                ];
                $stockUpdates[$pid] = $stock - $qty;
            }

            $subtotal = round($subtotal, 2);
            $total = round($subtotal + $shippingCost, 2);

            if ($balanceBefore < $total) {
                $missing = number_format($total - $balanceBefore, 2);
                throw new RuntimeException("Saldo insuficiente en tu monedero. Te faltan \${$missing} para completar esta compra.");
            }

            $balanceAfter = round($balanceBefore - $total, 2);

            // 3) Escrituras atómicas
            $transaction->set($userRef, ['wallet_balance' => $balanceAfter], ['merge' => true]);

            foreach ($stockUpdates as $pid => $newStock) {
                $transaction->update($productRefs[$pid], [['path' => 'stock', 'value' => $newStock]]);
                $transaction->create($movementRefs[$pid], [
                    'product_id' => $pid,
                    'type' => 'Salida',
                    'quantity' => (int)$cart[$pid],
                    'reason' => 'Compra tienda online ' . $orderCode,
                    'order_id' => $orderRef->id(),
                    'user_id' => $userId,
                    'user_role' => $userRole,
                    'user_name' => 'Cliente',
                    'created_at' => $now,
                ]);
            }

            $shippingAddress = [
                'first_name' => (string)($address['first_name'] ?? ''),
                'last_name' => (string)($address['last_name'] ?? ''),
                'address' => (string)($address['address'] ?? ''),
                'city' => (string)($address['city'] ?? ''),
                'state' => (string)($address['state'] ?? ''),
                'zip' => (string)($address['zip'] ?? ''),
                'country' => (string)($address['country'] ?? 'Mexico'),
                'phone' => (string)($address['phone'] ?? ''),
            ];

            $transaction->create($orderRef, [
                'order_code' => $orderCode,
                'user_id' => $userId,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'address_id' => $addressId,
                'shipping_address' => $shippingAddress,
                'items' => $items,
                'subtotal' => $subtotal,
                'shipping_cost' => round($shippingCost, 2),
                'total' => $total,
                'payment_method' => 'monedero',
                'status' => 'paid',
                'source' => 'online_store',
                'created_at' => $now,
            ]);

            $transaction->create($walletTxRef, [
                'user_id' => $userId,
                'type' => 'compra',
                'amount' => -$total,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'method' => 'Monedero',
                'reference' => $orderCode,
                'order_id' => $orderRef->id(),
                'description' => 'Compra en tienda — Orden ' . $orderCode,
                'created_at' => $now,
            ]);

            return [
                'order_id' => $orderRef->id(),
                'order_code' => $orderCode,
                'total' => $total,
                'balance_after' => $balanceAfter,
                'product_ids' => array_keys($stockUpdates),
            ];
        });

        // 4) Convergencia SCM PUSH tras la compra confirmada
        if (function_exists('aplicarReposicionPush')) {
            foreach ($txResult['product_ids'] as $pid) {
                try {
                    aplicarReposicionPush($db, $pid);
                } catch (\Throwable $e) {
                }
            }
        }

        unset($txResult['product_ids']);
        return $txResult;
    }
}
?>
