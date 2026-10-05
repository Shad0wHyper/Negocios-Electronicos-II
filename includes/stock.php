<?php

function getProductForStock($db, string $productId): ?array
{
    $productId = trim($productId);
    if ($productId === '') {
        return null;
    }

    $doc = $db->collection('products')->document($productId)->snapshot();
    if (!$doc->exists()) {
        return null;
    }

    $product = $doc->data();
    $product['id'] = $doc->id();
    $product['stock'] = max(0, (int)($product['stock'] ?? 0));

    return $product;
}

function stockMessage(array $product, int $requested, int $inCart = 0): string
{
    $available = max(0, $product['stock'] - $inCart);
    $name = $product['name'] ?? 'Este producto';

    if ($product['stock'] === 0) {
        return "{$name} está agotado y ya no se puede vender.";
    }

    if ($requested > $available) {
        return "No puedes vender {$requested} unidades de {$name}. Solo hay {$available} disponibles.";
    }

    return '';
}

