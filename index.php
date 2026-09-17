<?php 
$bodyClass = "xanarchy-home";
require_once 'includes/config.php'; 
require_once 'includes/header.php'; 
?>

<!-- HERO FULLSCREEN -->
<section class="hero-fullscreen">
    <div class="hero-background">
        <img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/ui-assets%2Fhero_v2.jpg?alt=media" alt="Xanarchy Hero" fetchpriority="high" decoding="sync" />
        <div class="hero-overlay"></div>
    </div>
    <div class="hero-content">
        <h1 class="hero-title">PURA GEOMETRÍA.<br>PURO LUJO.</h1>
        <p class="hero-subtitle">LA NUEVA ERA DEL STREETWEAR CONTEMPORÁNEO.</p>
        <a href="shop.php" class="btn btn-primary btn-lg">EXPLORAR COLECCIÓN</a>
    </div>
</section>

<!-- CATEGORÍAS -->
<section class="categories-section fade-in">
    <h2 class="section-title">NUESTRAS COLECCIONES</h2>
    <div class="categories-grid">
        <a href="shop.php?category=Hoodies" class="category-card">
            <img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/products%2Fhoodie_black.jpg?alt=media" alt="Hoodies" loading="lazy">
            <div class="category-overlay">
                <h3>HOODIES</h3>
            </div>
        </a>
        <a href="shop.php?category=T-Shirts" class="category-card">
            <img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/products%2Ftshirt_white.jpg?alt=media" alt="T-Shirts" loading="lazy">
            <div class="category-overlay">
                <h3>T-SHIRTS</h3>
            </div>
        </a>
        <a href="shop.php?category=Accesorios" class="category-card">
            <img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/products%2Fcap_black.jpg?alt=media" alt="Accesorios" loading="lazy">
            <div class="category-overlay">
                <h3>ACCESORIOS</h3>
            </div>
        </a>
    </div>
</section>

<!-- PRODUCTOS DESTACADOS -->
<section class="featured-products fade-in">
    <h2 class="section-title">DESTACADOS</h2>
    <div class="products-grid">
        <!-- Ejemplo de producto estático destacado -->
        <div class="product-card">
            <div class="product-image-container">
                <img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/products%2Fhoodie_black.jpg?alt=media" alt="Xanarchy Essential Hoodie" class="product-image">
                <div class="product-actions">
                    <a href="shop.php" class="btn btn-primary btn-block">VER EN TIENDA</a>
                </div>
            </div>
            <div class="product-info">
                <h3 class="product-title">Essential Hoodie Black</h3>
                <p class="product-price">,200 MXN</p>
            </div>
        </div>

        <div class="product-card">
            <div class="product-image-container">
                <img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/products%2Ftshirt_white.jpg?alt=media" alt="Xanarchy Basic Tee" class="product-image">
                <div class="product-actions">
                    <a href="shop.php" class="btn btn-primary btn-block">VER EN TIENDA</a>
                </div>
            </div>
            <div class="product-info">
                <h3 class="product-title">Basic Tee White</h3>
                <p class="product-price"> MXN</p>
            </div>
        </div>

        <div class="product-card">
            <div class="product-image-container">
                <img src="https://firebasestorage.googleapis.com/v0/b/xanarchy-store.firebasestorage.app/o/products%2Fcap_black.jpg?alt=media" alt="Xanarchy Logo Cap" class="product-image">
                <div class="product-actions">
                    <a href="shop.php" class="btn btn-primary btn-block">VER EN TIENDA</a>
                </div>
            </div>
            <div class="product-info">
                <h3 class="product-title">Logo Cap Black</h3>
                <p class="product-price"> MXN</p>
            </div>
        </div>
    </div>
    <div class="view-all-container">
        <a href="shop.php" class="btn btn-outline">VER TODO EL CATÁLOGO</a>
    </div>
</section>

<!-- MARQUESINA DE TEXTO -->
<div class="marquee-container">
    <div class="marquee-content">
        <span>STREETWEAR</span>
        <span>•</span>
        <span>XANARCHY</span>
        <span>•</span>
        <span>PREMIUM QUALITY</span>
        <span>•</span>
        <span>LIMITED EDITION</span>
        <span>•</span>
        <span>STREETWEAR</span>
        <span>•</span>
        <span>XANARCHY</span>
        <span>•</span>
        <span>PREMIUM QUALITY</span>
        <span>•</span>
        <span>LIMITED EDITION</span>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
