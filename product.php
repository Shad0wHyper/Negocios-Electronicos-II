<?php
require_once 'includes/config.php';

// Validar ID de producto recibido por GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de producto inválido.');
}

$id = (int)$_GET['id'];

// Consultar el producto por ID
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die('Producto no encontrado.');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once 'includes/header.php'; ?>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Estampa-TLA - <?= htmlspecialchars($product['name']) ?></title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>

<section class="product-page">
    <nav class="breadcrumb">
        <a href="index.php">Inicio</a> / <a href="shop.php">Tienda</a> / <?= htmlspecialchars($product['name']) ?>
    </nav>

    <div class="product-main">
        <div class="product-image">
            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" />
            <div class="product-thumbnails">
                <img src="<?= htmlspecialchars($product['image']) ?>" alt="Vista 1" />
                <img src="<?= htmlspecialchars($product['image']) ?>" alt="Vista 2" />
                <img src="<?= htmlspecialchars($product['image']) ?>" alt="Vista 3" />
                <img src="<?= htmlspecialchars($product['image']) ?>" alt="Vista 4" />
            </div>
        </div>

        <div class="product-details">
            <h1><?= htmlspecialchars($product['name']) ?></h1>

            <div class="rating-stock">
                <div class="rating">⭐⭐⭐⭐☆ (1256 Reseñas)</div>
                <div class="stock"><?= $product['stock'] > 0 ? 'En stock' : 'Agotado' ?></div>
            </div>

            <div class="price">
                $<?= number_format($product['price'], 2) ?>
            </div>

            <div class="color-options">
                <span>Color:</span>
                <button class="color-black selected"></button>
                <button class="color-grey"></button>
                <button class="color-white"></button>
                <button class="color-red"></button>
                <button class="color-green"></button>
            </div>

            <?php if ($product['stock'] > 0): ?>
                <form method="post" action="add_to_cart.php" class="actions">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <div class="quantity-control">
                        <button type="button" onclick="this.nextElementSibling.stepDown()">-</button>
                        <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" />
                        <button type="button" onclick="this.previousElementSibling.stepUp()">+</button>
                    </div>
                    <button type="submit" class="add-to-cart">AGREGAR AL CARRITO</button>
                </form>
            <?php else: ?>
                <p style="color:red; font-weight:bold;">Producto agotado</p>
            <?php endif; ?>

            <div class="share">
                Compartir:
                <a href="#">Facebook</a>
                <a href="#">Twitter</a>
                <a href="#">Instagram</a>
                <a href="#">LinkedIn</a>
            </div>

            <details>
                <summary>Detalles</summary>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </details>

            <details>
                <summary>Dimensiones</summary>
                <p>Medidas estándar. Consulta la guía de tallas.</p>
            </details>

            <details>
                <summary>Reseñas</summary>
                <p>Este producto tiene más de 1200 reseñas positivas. Calidad garantizada.</p>
            </details>
        </div>
    </div>

    <section class="related-products">
        <h2>ARTÍCULOS SIMILARES</h2>
        <div class="products-grid">
            <?php
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id != ? ORDER BY RAND() LIMIT 4");
            $stmt->execute([$id]);
            $related = $stmt->fetchAll();

            foreach ($related as $rel): ?>
                <div class="product-card">
                    <a href="product.php?id=<?= $rel['id'] ?>">
                        <img src="<?= htmlspecialchars($rel['image']) ?>" alt="<?= htmlspecialchars($rel['name']) ?>" />
                        <div class="product-info">
                            <h3><?= htmlspecialchars($rel['name']) ?></h3>
                            <p><?= mb_strimwidth(htmlspecialchars($rel['description']), 0, 60, "...") ?></p>
                        </div>
                        <div class="product-price">$<?= number_format($rel['price'], 2) ?></div>
                    </a>
                    <form method="post" action="add_to_cart.php">
                        <input type="hidden" name="product_id" value="<?= $rel['id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="add-to-cart">AGREGAR AL CARRITO</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</section>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
