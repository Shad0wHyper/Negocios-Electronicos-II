<!DOCTYPE html>
<html lang="es">
<head>
    <?php
    require_once 'includes/config.php';
    require_once 'includes/header.php';
    ?>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Xanarchy - La Nueva Era del Streetwear</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body class="xanarchy-home">

<!-- HERO FULLSCREEN -->
<section class="hero-fullscreen">
    <div class="hero-background">
        <img src="Imagenes/hero_v2.jpg" alt="Xanarchy Hero" />
        <div class="hero-overlay"></div>
    </div>
    <div class="hero-content">
        <h2>PURA GEOMETRÍA.<br>PURO LUJO.</h2>
        <p>La nueva era del streetwear contemporáneo.</p>
        <a href="shop.php" class="btn-primary-luxury">EXPLORAR COLECCIÓN</a>
    </div>
</section>

<!-- CATEGORIES LOOKBOOK -->
<section class="lookbook-section">
    <div class="lookbook-grid">
        <a href="shop.php?category=Caballero" class="lookbook-item">
            <img src="Imagenes/Caballero_v2.jpg" alt="Caballero" />
            <div class="lookbook-label">CABALLERO</div>
        </a>
        <a href="shop.php?category=Dama" class="lookbook-item">
            <img src="Imagenes/Dama_v2.jpg" alt="Dama" />
            <div class="lookbook-label">DAMA</div>
        </a>
        <a href="shop.php?category=Ediciones Limitadas" class="lookbook-item">
            <img src="Imagenes/Ediciones_Limitadas_v2.jpg" alt="Ediciones Limitadas" />
            <div class="lookbook-label">LIMITED EDITION</div>
        </a>
        <a href="shop.php?category=Colecciones" class="lookbook-item">
            <img src="Imagenes/Colecciones_v2.jpg" alt="Colecciones" />
            <div class="lookbook-label">COLECCIONES</div>
        </a>
    </div>
</section>

<!-- POPULAR DESIGNS -->
<section class="minimal-products-section">
    <div class="section-header">
        <h2>DISEÑOS ICÓNICOS</h2>
        <a href="shop.php" class="view-all-link">VER TODO</a>
    </div>
    <div class="minimal-products-grid">
        <?php
        $stmt = $pdo->query('SELECT id, name, price, image FROM products WHERE stock > 0 ORDER BY created_at DESC LIMIT 8');
        $featured = $stmt->fetchAll();
        if ($featured):
            foreach ($featured as $p): ?>
                <div class="minimal-product-card">
                    <a href="product.php?id=<?= $p['id'] ?>" class="product-image-link">
                        <img src="<?= htmlspecialchars($p['image'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>" loading="lazy" />
                        <div class="product-overlay">
                            <span>VER DETALLES</span>
                        </div>
                    </a>
                    <div class="product-meta">
                        <h3 class="product-title"><a href="product.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['name'], ENT_QUOTES) ?></a></h3>
                        <span class="product-price">$<?= number_format($p['price'], 2) ?></span>
                    </div>
                </div>
            <?php
            endforeach;
        else: ?>
            <p class="empty-state">La bóveda está vacía por el momento.</p>
        <?php endif; ?>
    </div>
</section>

<!-- LUXURY SPLIT PROMO -->
<section class="luxury-split-promo">
    <div class="split-image">
        <img src="Imagenes/Descuento1_v2.jpg" alt="Gothic Collection" />
    </div>
    <div class="split-content">
        <span class="promo-badge">ARCHIVOS EXCLUSIVOS</span>
        <h2>40% OFF<br>GOTHIC COLLECTION</h2>
        <p>Una exploración de la oscuridad a través de siluetas estructuradas y proporciones vanguardistas.</p>
        <a href="shop.php?sale=gothic" class="btn-outline-luxury">DESCUBRIR</a>
    </div>
</section>

<!-- EDITORIAL INFO SECTION -->
<section class="editorial-info-section">
    <div class="editorial-content">
        <h2>IDENTIDAD XANARCHY</h2>
        <p>No creamos simple ropa; esculpimos identidad. Cada pieza de Xanarchy es un manifiesto de diseño arquitectónico y precisión minimalista, pensado para quienes dictan las reglas del mañana.</p>
        <a href="about.php" class="link-luxury">CONOCE EL MANIFIESTO</a>
    </div>
    <div class="editorial-image">
        <img src="Imagenes/quienes_somos_v2.jpg" alt="Identidad Xanarchy" />
    </div>
</section>

<!-- NEW RELEASES -->
<section class="minimal-products-section bg-light">
    <div class="section-header">
        <h2>NUEVOS LANZAMIENTOS</h2>
    </div>
    <div class="minimal-products-grid cols-4">
        <?php
        $stmt = $pdo->query('SELECT id, name, price, image, description FROM products WHERE stock > 0 ORDER BY created_at DESC LIMIT 4');
        $newDesigns = $stmt->fetchAll();
        if ($newDesigns):
            foreach ($newDesigns as $product): ?>
                <div class="minimal-product-card">
                    <a href="product.php?id=<?= $product['id'] ?>" class="product-image-link">
                        <img src="<?= htmlspecialchars($product['image'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>" loading="lazy" />
                        <div class="product-overlay">
                            <span>VER DETALLES</span>
                        </div>
                    </a>
                    <div class="product-meta">
                        <h3 class="product-title"><a href="product.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name'], ENT_QUOTES) ?></a></h3>
                        <span class="product-price">$<?= number_format($product['price'], 2) ?></span>
                    </div>
                </div>
            <?php endforeach;
        else: ?>
            <p class="empty-state">Próximamente nuevos cortes.</p>
        <?php endif; ?>
    </div>
</section>

<!-- NEWSLETTER DARK BLOCK -->
<?php if (!isset($_SESSION['user'])): ?>
<section class="newsletter-dark">
    <div class="newsletter-content">
        <h2>ACCESO CLASIFICADO</h2>
        <p>Únete a la élite. Regístrate para tener acceso anticipado a nuestras colecciones de edición limitada y piezas de archivo.</p>
        <a href="register.php" class="btn-primary-luxury inverse">SOLICITAR ACCESO</a>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
