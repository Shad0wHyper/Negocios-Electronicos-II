<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';
$push = $pull = 0; $products = [];
foreach ($db->collection('products')->documents() as $doc) {
    if (!$doc->exists()) continue; $p = $doc->data(); $p['id'] = $doc->id(); $products[] = $p;
    if (($p['strategy'] ?? 'PUSH') === 'PULL') $pull++; else $push++;
}
require_once __DIR__ . '/includes/header.php';
?>
<div class="max-w-6xl mx-auto"><h2 class="text-3xl font-bold mb-2">Comparativo Push vs Pull</h2><p class="text-gray-500 mb-8">Distribución de productos por estrategia logística.</p>
<div class="grid md:grid-cols-2 gap-6 mb-8"><div class="rounded-lg bg-orange-50 border border-orange-200 p-6"><div class="text-sm text-orange-700">PUSH</div><div class="text-4xl font-bold text-orange-900"><?php echo $push; ?></div><p class="text-sm mt-2">productos planificados</p></div><div class="rounded-lg bg-blue-50 border border-blue-200 p-6"><div class="text-sm text-blue-700">PULL</div><div class="text-4xl font-bold text-blue-900"><?php echo $pull; ?></div><p class="text-sm mt-2">productos bajo demanda</p></div></div>
<div class="bg-white border rounded-lg overflow-hidden"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="text-left px-6 py-3">Producto</th><th class="text-left px-6 py-3">Categoría</th><th class="text-left px-6 py-3">Stock</th><th class="text-left px-6 py-3">Estrategia</th></tr></thead><tbody><?php foreach ($products as $p): ?><tr class="border-b"><td class="px-6 py-4"><?php echo htmlspecialchars($p['name'] ?? ''); ?></td><td class="px-6 py-4"><?php echo htmlspecialchars($p['category'] ?? ''); ?></td><td class="px-6 py-4"><?php echo (int)($p['stock'] ?? 0); ?></td><td class="px-6 py-4"><?php echo htmlspecialchars($p['strategy'] ?? 'PUSH'); ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
