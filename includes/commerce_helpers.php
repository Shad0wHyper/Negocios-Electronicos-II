<?php

function getStoreTaxSettings($db): array
{
    $snapshot = $db->collection('store_settings')->document('taxes')->snapshot();
    $settings = $snapshot->exists() ? $snapshot->data() : [];

    return [
        'enabled' => (bool)($settings['enabled'] ?? true),
        'rate' => min(100, max(0, (float)($settings['rate'] ?? 16))),
        'name' => trim((string)($settings['name'] ?? 'IVA')) ?: 'IVA',
    ];
}

function getActivePromotions($db, ?int $now = null): array
{
    $now = $now ?? time();
    $promotions = [];

    foreach ($db->collection('promotions')->documents() as $document) {
        if (!$document->exists()) {
            continue;
        }
        $promotion = $document->data();
        if (empty($promotion['active'])) {
            continue;
        }
        $startsAt = strtotime((string)($promotion['starts_at'] ?? '')) ?: 0;
        $endsAt = strtotime((string)($promotion['ends_at'] ?? '')) ?: PHP_INT_MAX;
        if ($now < $startsAt || $now > $endsAt) {
            continue;
        }
        $promotion['id'] = $document->id();
        $promotions[] = $promotion;
    }

    return $promotions;
}

function getBestPromotionForProduct(array $product, array $promotions): ?array
{
    $price = max(0, (float)($product['price'] ?? 0));
    $productId = (string)($product['id'] ?? '');
    $best = null;
    $now = time();

    foreach ($promotions as $promotion) {
        $startsAt = strtotime((string)($promotion['starts_at'] ?? '')) ?: 0;
        $endsAt = strtotime((string)($promotion['ends_at'] ?? '')) ?: PHP_INT_MAX;
        if (empty($promotion['active']) || $now < $startsAt || $now > $endsAt) {
            continue;
        }
        $applicableProducts = array_map('strval', (array)($promotion['product_ids'] ?? []));
        if ($applicableProducts && !in_array($productId, $applicableProducts, true)) {
            continue;
        }

        $value = max(0, (float)($promotion['value'] ?? 0));
        $discount = ($promotion['type'] ?? '') === 'fixed'
            ? min($price, $value)
            : min($price, $price * min(100, $value) / 100);
        if ($discount <= 0 || ($best !== null && $discount <= $best['discount'])) {
            continue;
        }

        $best = [
            'id' => (string)$promotion['id'],
            'name' => (string)($promotion['name'] ?? 'Promoción'),
            'discount' => round($discount, 2),
        ];
    }

    return $best;
}

function calculateCartTotals(array $products, array $cart, array $promotions, array $taxSettings, float $shippingCost = 120.0): array
{
    $subtotal = 0.0;
    $discountTotal = 0.0;
    $items = [];

    foreach ($products as $product) {
        $productId = (string)$product['id'];
        $quantity = max(0, (int)($cart[$productId] ?? 0));
        if ($quantity === 0) {
            continue;
        }

        $unitPrice = max(0, (float)($product['price'] ?? 0));
        $promotion = getBestPromotionForProduct($product, $promotions);
        $discountPerUnit = (float)($promotion['discount'] ?? 0);
        $lineSubtotal = round($unitPrice * $quantity, 2);
        $lineDiscount = round($discountPerUnit * $quantity, 2);
        $subtotal += $lineSubtotal;
        $discountTotal += $lineDiscount;
        $items[] = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => round($unitPrice, 2),
            'discount_per_unit' => round($discountPerUnit, 2),
            'promotion_id' => $promotion['id'] ?? '',
            'promotion_name' => $promotion['name'] ?? '',
            'line_total' => round($lineSubtotal - $lineDiscount, 2),
        ];
    }

    $subtotal = round($subtotal, 2);
    $discountTotal = round($discountTotal, 2);
    $taxableSubtotal = max(0, round($subtotal - $discountTotal, 2));
    $taxRate = !empty($taxSettings['enabled']) ? min(100, max(0, (float)($taxSettings['rate'] ?? 16))) : 0;
    $taxAmount = round($taxableSubtotal * $taxRate / 100, 2);
    $shippingCost = round(max(0, $shippingCost), 2);

    return [
        'items' => $items,
        'subtotal' => $subtotal,
        'discount_total' => $discountTotal,
        'taxable_subtotal' => $taxableSubtotal,
        'tax_rate' => $taxRate,
        'tax_name' => (string)($taxSettings['name'] ?? 'IVA'),
        'tax_amount' => $taxAmount,
        'shipping_cost' => $shippingCost,
        'total' => round($taxableSubtotal + $taxAmount + $shippingCost, 2),
    ];
}
