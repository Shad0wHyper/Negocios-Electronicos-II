<?php
// shop.php - Catálogo editorial Xanarchy
require_once 'includes/config.php';
require_once 'includes/header.php';

$q = trim($_GET['q'] ?? '');
$selectedCategory = trim($_GET['category'] ?? '');
$sort = trim($_GET['sort'] ?? 'recent');
$sale = trim($_GET['sale'] ?? '');

if (!in_array($sort, ['recent', 'price_asc', 'price_desc', 'name_asc'], true)) {
    $sort = 'recent';
}

$allProducts = [];
$categoriesMap = [];
$documents = $db->collection('products')->documents();
foreach ($documents as $doc) {
    if ($doc->exists()) {
        $p = $doc->data();
        $p['id'] = $doc->id();
        $cat = trim((string)($p['category'] ?? ''));
        if ($cat !== '') {
            $categoriesMap[mb_strtolower($cat, 'UTF-8')] = $cat;
        }
        $allProducts[] = $p;
    }
}

$categories = array_values($categoriesMap);
sort($categories, SORT_NATURAL | SORT_FLAG_CASE);

$products = array_values(array_filter($allProducts, function ($p) use ($q, $selectedCategory) {
    if ($q !== '') {
        $haystack = ($p['name'] ?? '') . ' ' . ($p['description'] ?? '') . ' ' . ($p['category'] ?? '');
        if (mb_stripos($haystack, $q, 0, 'UTF-8') === false) {
            return false;
        }
    }

    if ($selectedCategory !== '') {
        $catNorm = mb_strtolower(trim((string)($p['category'] ?? '')), 'UTF-8');
        $selNorm = mb_strtolower($selectedCategory, 'UTF-8');
        if ($selNorm === 'new') {
            return true;
        } elseif ($selNorm === 'best') {
            return true;
        } elseif ($selNorm === 'accessories') {
            if (mb_stripos($catNorm, 'accesor', 0, 'UTF-8') === false && $catNorm !== 'accessories') {
                return false;
            }
        } elseif ($catNorm !== $selNorm) {
            return false;
        }
    }

    return true;
}));

if ($sale === 'gothic') {
    $gothicProducts = array_values(array_filter($products, function ($p) {
        $haystack = ($p['name'] ?? '') . ' ' . ($p['description'] ?? '') . ' ' . ($p['category'] ?? '');
        return mb_stripos($haystack, 'gotic', 0, 'UTF-8') !== false
            || mb_stripos($haystack, 'gótic', 0, 'UTF-8') !== false
            || mb_stripos($haystack, 'gothic', 0, 'UTF-8') !== false;
    }));
    if (!empty($gothicProducts)) {
        $products = $gothicProducts;
    }
}

usort($products, function ($a, $b) use ($sort) {
    switch ($sort) {
        case 'price_asc':
            return (float)($a['price'] ?? 0) <=> (float)($b['price'] ?? 0);
        case 'price_desc':
            return (float)($b['price'] ?? 0) <=> (float)($a['price'] ?? 0);
        case 'name_asc':
            return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
        case 'recent':
        default:
            return strtotime($b['created_at'] ?? '2000-01-01') <=> strtotime($a['created_at'] ?? '2000-01-01');
    }
});

$hasActiveFilters = ($q !== '' || $selectedCategory !== '' || $sort !== 'recent' || $sale !== '');
?>

<section class="shop-hero">
    <div class="shop-hero-inner">
        <span class="shop-hero-kicker">COLECCIÓN XANARCHY</span>
        <h1>CATÁLOGO</h1>
        <p>
            <?= count($products) ?> <?= count($products) === 1 ? 'pieza encontrada' : 'piezas encontradas' ?>
            <?php if ($q !== ''): ?>
                para &ldquo;<?= htmlspecialchars($q, ENT_QUOTES) ?>&rdquo;
            <?php endif; ?>
            <?php if ($selectedCategory !== '' && !in_array(mb_strtolower($selectedCategory, 'UTF-8'), ['new', 'best'], true)): ?>
                en <?= htmlspecialchars($selectedCategory, ENT_QUOTES) ?>
            <?php endif; ?>
        </p>
    </div>
</section>

<section class="shop-container">
    <form method="get" action="shop.php" class="shop-toolbar">
        <div class="shop-toolbar-field shop-toolbar-search">
            <label for="shop-q">Buscar producto</label>
            <input type="text" id="shop-q" name="q" placeholder="Nombre, descripción o categoría..." value="<?= htmlspecialchars($q, ENT_QUOTES) ?>">
        </div>

        <div class="shop-toolbar-field">
            <label for="shop-category">Categoría</label>
            <select id="shop-category" name="category">
                <option value="">Todas las categorías</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat, ENT_QUOTES) ?>" <?= (mb_strtolower($selectedCategory, 'UTF-8') === mb_strtolower($cat, 'UTF-8')) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat, ENT_QUOTES) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="shop-toolbar-field">
            <label for="shop-sort">Ordenar por</label>
            <select id="shop-sort" name="sort">
                <option value="recent" <?= ($sort === 'recent') ? 'selected' : '' ?>>Más recientes</option>
                <option value="price_asc" <?= ($sort === 'price_asc') ? 'selected' : '' ?>>Precio: menor a mayor</option>
                <option value="price_desc" <?= ($sort === 'price_desc') ? 'selected' : '' ?>>Precio: mayor a menor</option>
                <option value="name_asc" <?= ($sort === 'name_asc') ? 'selected' : '' ?>>Nombre: A–Z</option>
            </select>
        </div>

        <div class="shop-toolbar-actions">
            <button type="submit" class="shop-filter-btn">Filtrar</button>
            <?php if ($hasActiveFilters): ?>
                <a href="shop.php" class="shop-clear-btn">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (!empty($products)): ?>
        <div class="shop-grid">
            <?php foreach ($products as $product):
                $stock = max(0, (int)($product['stock'] ?? 0));
                $categoryLabel = trim((string)($product['category'] ?? 'Colección'));
                if ($categoryLabel === '') {
                    $categoryLabel = 'Colección';
                }
            ?>
                <article class="shop-card">
                    <a href="product.php?id=<?= urlencode($product['id']) ?>" class="shop-card-media">
                        <img src="<?= htmlspecialchars($product['image'] ?? '', ENT_QUOTES) ?>"
                             alt="<?= htmlspecialchars($product['name'] ?? 'Producto', ENT_QUOTES) ?>"
                             loading="lazy">
                        <span class="shop-badge shop-badge-category"><?= htmlspecialchars($categoryLabel, ENT_QUOTES) ?></span>
                        <?php if ($stock === 0): ?>
                            <span class="shop-badge shop-badge-out">Agotado</span>
                        <?php elseif ($stock <= 3): ?>
                            <span class="shop-badge shop-badge-low">Últimas piezas</span>
                        <?php endif; ?>
                        <div class="shop-card-overlay">
                            <span>VER DETALLES</span>
                        </div>
                    </a>

                    <div class="shop-card-body">
                        <h3>
                            <a href="product.php?id=<?= urlencode($product['id']) ?>">
                                <?= htmlspecialchars($product['name'] ?? 'Producto', ENT_QUOTES) ?>
                            </a>
                        </h3>
                        <p class="shop-card-desc">
                            <?= htmlspecialchars(mb_strimwidth((string)($product['description'] ?? ''), 0, 90, '...'), ENT_QUOTES) ?>
                        </p>

                        <div class="shop-card-meta-row">
                            <span class="shop-card-price">$<?= number_format((float)($product['price'] ?? 0), 2) ?></span>
                            <span class="shop-card-stock <?= $stock === 0 ? 'is-out' : '' ?>">
                                <?= $stock > 0 ? $stock . ' en stock' : 'Sin stock' ?>
                            </span>
                        </div>

                        <?php if ($stock > 0): ?>
                            <form method="post" action="add_to_cart.php" class="shop-card-form">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id'], ENT_QUOTES) ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="shop-add-btn">AGREGAR AL CARRITO</button>
                            </form>
                        <?php else: ?>
                            <button type="button" class="shop-add-btn is-disabled" disabled>AGOTADO</button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="shop-empty-state">
            <h3>No se encontraron productos</h3>
            <p>Prueba ajustando los filtros o limpiando tu búsqueda para explorar toda la colección.</p>
            <a href="shop.php" class="shop-filter-btn">Ver todo el catálogo</a>
        </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
