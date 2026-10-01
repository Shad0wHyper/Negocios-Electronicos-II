<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$products = [];
$docs = $db->collection('products')->documents();
foreach ($docs as $doc) {
    if (!$doc->exists()) continue;
    $p = $doc->data();
    $p['id'] = $doc->id();
    if ($search !== '' && stripos($p['name'] ?? '', $search) === false) continue;
    if ($category !== '' && ($p['category'] ?? '') !== $category) continue;
    $products[] = $p;
}
usort($products, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
$categories = array_values(array_unique(array_filter(array_map(fn($p) => $p['category'] ?? '', $products))));
require_once __DIR__ . '/includes/header.php';
?>
<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
        <h2 class="text-3xl font-bold text-[var(--text-primary)]">Productos SCM</h2>
        <a href="add_product.php" class="rounded-md px-5 py-2 bg-[var(--primary-color)] text-white text-sm font-semibold">+ Nuevo producto</a>
    </div>
    <form class="bg-white border rounded-lg p-4 mb-6 flex flex-wrap gap-3">
        <input name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar producto..." class="rounded-md border-gray-300 flex-1 min-w-[220px]">
        <select name="category" class="rounded-md border-gray-300">
            <option value="">Todas las categorías</option>
            <?php foreach ($categories as $item): ?><option value="<?php echo htmlspecialchars($item); ?>" <?php echo $category === $item ? 'selected' : ''; ?>><?php echo htmlspecialchars($item); ?></option><?php endforeach; ?>
        </select>
        <button class="rounded-md bg-gray-800 text-white px-5">Buscar</button>
    </form>
    <div class="bg-white rounded-lg shadow-sm border overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-600">
            <thead class="text-xs uppercase bg-gray-50 text-gray-700"><tr><th class="px-6 py-3">Imagen</th><th class="px-6 py-3">Nombre</th><th class="px-6 py-3">Categoría</th><th class="px-6 py-3">Stock</th><th class="px-6 py-3">Stock mín.</th><th class="px-6 py-3">Estrategia</th><th class="px-6 py-3">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): $low = (int)($p['stock'] ?? 0) <= (int)($p['stock_minimo'] ?? 0); ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-6 py-4"><img src="<?php echo htmlspecialchars($p['image'] ?? ''); ?>" class="w-10 h-10 object-cover rounded" alt=""></td>
                    <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($p['name'] ?? ''); ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($p['category'] ?? 'Sin categoría'); ?></td>
                    <td class="px-6 py-4 font-semibold <?php echo $low ? 'text-red-600' : ''; ?>"><?php echo (int)($p['stock'] ?? 0); ?></td>
                    <td class="px-6 py-4"><?php echo (int)($p['stock_minimo'] ?? 0); ?></td>
                    <td class="px-6 py-4"><span class="px-2 py-1 rounded-full text-xs <?php echo ($p['strategy'] ?? 'PUSH') === 'PULL' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'; ?>"><?php echo htmlspecialchars($p['strategy'] ?? 'PUSH'); ?></span></td>
                    <td class="px-6 py-4"><a class="text-[var(--primary-color)] hover:underline" href="edit_product.php?id=<?php echo urlencode($p['id']); ?>">Editar</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$products): ?><tr><td colspan="7" class="px-6 py-8 text-center">No se encontraron productos.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
