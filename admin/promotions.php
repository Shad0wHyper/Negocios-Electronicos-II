<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_admin.php';

$error = '';
$success = '';
$products = [];
foreach ($db->collection('products')->orderBy('name')->documents() as $document) {
    if ($document->exists()) {
        $product = $document->data();
        $product['id'] = $document->id();
        $products[] = $product;
    }
    $editingPromotion = null;
    $editId = trim((string)($_GET['edit'] ?? ''));
    if ($editId !== '') {
        $editSnapshot = $db->collection('promotions')->document($editId)->snapshot();
        if ($editSnapshot->exists()) {
            $editingPromotion = $editSnapshot->data();
            $editingPromotion['id'] = $editId;
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'La sesión del formulario expiró. Recarga e inténtalo de nuevo.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $promotionId = trim((string)($_POST['promotion_id'] ?? ''));
        if ($action === 'delete' && $promotionId !== '') {
            $db->collection('promotions')->document($promotionId)->delete();
            $success = 'Promoción eliminada.';
        } elseif ($action === 'save') {
            $name = trim((string)($_POST['name'] ?? ''));
            $type = (string)($_POST['type'] ?? '');
            $value = filter_var($_POST['value'] ?? null, FILTER_VALIDATE_FLOAT);
            $startsAt = trim((string)($_POST['starts_at'] ?? ''));
            $endsAt = trim((string)($_POST['ends_at'] ?? ''));
            $productIds = array_values(array_unique(array_map('strval', (array)($_POST['product_ids'] ?? []))));
            $validProductIds = array_column($products, 'id');
            $productsExist = count(array_diff($productIds, $validProductIds)) === 0;
            $startTime = strtotime($startsAt);
            $endTime = strtotime($endsAt);

            if ($name === '' || !in_array($type, ['percentage', 'fixed'], true) || $value === false || $value <= 0
                || ($type === 'percentage' && $value > 100) || !$startTime || !$endTime || $endTime <= $startTime || !$productsExist) {
                $error = 'Revisa nombre, descuento, vigencia y productos de la promoción.';
            } else {
                $data = [
                    'name' => $name,
                    'type' => $type,
                    'value' => round((float)$value, 2),
                    'starts_at' => date('Y-m-d H:i:s', $startTime),
                    'ends_at' => date('Y-m-d H:i:s', $endTime),
                    'product_ids' => $productIds,
                    'active' => isset($_POST['active']),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                if ($promotionId !== '') {
                    $db->collection('promotions')->document($promotionId)->set($data, ['merge' => true]);
                } else {
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $db->collection('promotions')->add($data);
                }
                header('Location: promotions.php?saved=1');
                exit;
            }
        }
    }
}

$promotions = [];
foreach ($db->collection('promotions')->documents() as $document) {
    if ($document->exists()) {
        $promotion = $document->data();
        $promotion['id'] = $document->id();
        $promotions[] = $promotion;
    }
}
usort($promotions, static fn($a, $b) => strcmp($b['starts_at'] ?? '', $a['starts_at'] ?? ''));
$success = $success ?: (isset($_GET['saved']) ? 'Promoción guardada.' : '');
require_once __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-7xl">
    <div class="mb-6"><h2 class="text-3xl font-bold">Promociones</h2><p class="mt-2 text-sm text-gray-500">Descuentos automáticos por producto, con vigencia y porcentaje o monto fijo.</p></div>
    <?php if ($error): ?><div class="mb-4 rounded-md bg-red-50 p-4 text-red-700"><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="mb-4 rounded-md bg-green-50 p-4 text-green-700"><?= htmlspecialchars($success, ENT_QUOTES) ?></div><?php endif; ?>
    <form method="post" class="mb-8 space-y-4 rounded-lg border bg-white p-6 shadow-sm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="promotion_id" value="<?= htmlspecialchars($editingPromotion['id'] ?? '', ENT_QUOTES) ?>">
        <div class="grid gap-4 md:grid-cols-2">
            <label class="text-sm font-medium">Nombre<input name="name" required maxlength="100" value="<?= htmlspecialchars($editingPromotion['name'] ?? '', ENT_QUOTES) ?>" class="mt-1 w-full rounded-md border-gray-300"></label>
            <label class="text-sm font-medium">Productos (sin selección = todos)
                <select name="product_ids[]" multiple size="4" class="mt-1 w-full rounded-md border-gray-300">
                    <?php foreach ($products as $product): ?><option value="<?= htmlspecialchars($product['id'], ENT_QUOTES) ?>" <?= in_array($product['id'], array_map('strval', (array)($editingPromotion['product_ids'] ?? [])), true) ? 'selected' : '' ?>><?= htmlspecialchars($product['name'] ?? '', ENT_QUOTES) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="text-sm font-medium">Tipo de descuento<select name="type" class="mt-1 w-full rounded-md border-gray-300"><option value="percentage" <?= ($editingPromotion['type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Porcentaje (%)</option><option value="fixed" <?= ($editingPromotion['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Monto fijo por unidad ($)</option></select></label>
            <label class="text-sm font-medium">Valor<input name="value" type="number" min="0.01" step="0.01" value="<?= htmlspecialchars((string)($editingPromotion['value'] ?? ''), ENT_QUOTES) ?>" required class="mt-1 w-full rounded-md border-gray-300"></label>
            <label class="text-sm font-medium">Inicio<input name="starts_at" type="datetime-local" value="<?= !empty($editingPromotion['starts_at']) ? date('Y-m-d\TH:i', strtotime($editingPromotion['starts_at'])) : '' ?>" required class="mt-1 w-full rounded-md border-gray-300"></label>
            <label class="text-sm font-medium">Fin<input name="ends_at" type="datetime-local" value="<?= !empty($editingPromotion['ends_at']) ? date('Y-m-d\TH:i', strtotime($editingPromotion['ends_at'])) : '' ?>" required class="mt-1 w-full rounded-md border-gray-300"></label>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="active" <?= !isset($editingPromotion) || !empty($editingPromotion['active']) ? 'checked' : '' ?>> Activa</label>
        <button class="rounded-md bg-blue-600 px-5 py-2 font-semibold text-white hover:bg-blue-700"><?= $editingPromotion ? 'Guardar cambios' : 'Crear promoción' ?></button>
        <?php if ($editingPromotion): ?><a href="promotions.php" class="ml-3 text-sm text-gray-600">Cancelar edición</a><?php endif; ?>
    </form>
    <div class="overflow-x-auto rounded-lg border bg-white">
        <table class="w-full text-left text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-600"><tr><th class="p-4">Promoción</th><th class="p-4">Descuento</th><th class="p-4">Productos</th><th class="p-4">Vigencia</th><th class="p-4">Estado</th><th class="p-4"></th></tr></thead><tbody>
        <?php foreach ($promotions as $promotion): ?>
            <tr class="border-t"><td class="p-4 font-medium"><?= htmlspecialchars($promotion['name'] ?? '', ENT_QUOTES) ?></td><td class="p-4"><?= ($promotion['type'] ?? '') === 'fixed' ? '$' . number_format((float)($promotion['value'] ?? 0), 2) : number_format((float)($promotion['value'] ?? 0), 2) . '%' ?></td><td class="p-4"><?= empty($promotion['product_ids']) ? 'Todos' : count($promotion['product_ids']) . ' producto(s)' ?></td><td class="p-4"><?= htmlspecialchars(($promotion['starts_at'] ?? '') . ' — ' . ($promotion['ends_at'] ?? ''), ENT_QUOTES) ?></td><td class="p-4"><?= !empty($promotion['active']) ? 'Activa' : 'Pausada' ?></td><td class="p-4"><a class="mr-3 font-semibold text-blue-600" href="promotions.php?edit=<?= urlencode($promotion['id']) ?>">Editar</a><form class="inline" method="post" onsubmit="return confirm('¿Eliminar esta promoción?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="promotion_id" value="<?= htmlspecialchars($promotion['id'], ENT_QUOTES) ?>"><button class="font-semibold text-red-600">Eliminar</button></form></td></tr>
        <?php endforeach; ?>
        <?php if (!$promotions): ?><tr><td colspan="6" class="p-8 text-center text-gray-500">Aún no hay promociones registradas.</td></tr><?php endif; ?>
        </tbody></table>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
