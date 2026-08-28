<?php
// shop.php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Recoger término de búsqueda
$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    // Consulta parametrizada: filtrar por name
    $stmt = $pdo->prepare('SELECT * FROM products WHERE name LIKE :term');
    $stmt->execute([':term' => "%{$q}%"]);
} else {
    // Sin búsqueda: todos los productos
    $stmt = $pdo->query('SELECT * FROM products');
}

$products = $stmt->fetchAll();
?>

<main class="shop-page">
    <h1 style="text-align: center; margin: 2rem 0;">
        Catálogo de Productos
        <?php if ($q): ?>
            <br><small>Resultados para “<?= htmlspecialchars($q, ENT_QUOTES) ?>”</small>
        <?php endif; ?>
    </h1>
    <div class="products-grid">
        <?php if ($products): ?>
            <?php foreach ($products as $product): ?>
                <article class="product-card">
                    <a href="product.php?id=<?= $product['id'] ?>">
                        <img src="<?= htmlspecialchars($product['image'], ENT_QUOTES) ?>"
                             alt="<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>">
                    </a>
                    <div class="product-info">
                        <h3>
                            <a href="product.php?id=<?= $product['id'] ?>">
                                <?= htmlspecialchars($product['name'], ENT_QUOTES) ?>
                            </a>
                        </h3>
                        <p><?= htmlspecialchars(mb_strimwidth($product['description'], 0, 80, '...'), ENT_QUOTES) ?></p>
                    </div>
                    <div class="product-price">$<?= number_format($product['price'], 2) ?></div>
                    <form method="post" action="add_to_cart.php">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="add-to-cart btn">AGREGAR AL CARRITO</button>
                    </form>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center;">
                <?php if ($q): ?>
                    No se encontraron productos que coincidan con “<?= htmlspecialchars($q, ENT_QUOTES) ?>”.
                <?php else: ?>
                    No hay productos disponibles en este momento.
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
