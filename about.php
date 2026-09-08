<?php
require_once 'includes/config.php';
require_once 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>El Manifiesto | Xanarchy</title>
    <link rel="stylesheet" href="styles.css" />
</head>

<body class="xanarchy-manifesto-page">

    <!-- HERO MANIFESTO -->
    <section class="manifesto-hero">
        <div class="manifesto-hero-bg">
            <img src="Imagenes/manifesto_hero.jpg" alt="El Manifiesto Xanarchy" />
            <div class="hero-overlay"></div>
        </div>
        <div class="manifesto-hero-content">
            <h1>EL MANIFIESTO<br>XANARCHY</h1>
            <p>No seguimos tendencias. Las dictamos.</p>
        </div>
    </section>

    <!-- VALUES GRID -->
    <section class="manifesto-values">
        <div class="value-block black-block">
            <h2>VISIÓN</h2>
            <p>Redefinir el streetwear contemporáneo. Xanarchy nace de la necesidad de estructurar el caos urbano en
                prendas arquitectónicas, diseñadas para aquellos que rechazan lo ordinario y exigen lo extraordinario.
            </p>
        </div>
        <div class="value-block image-block">
            <img src="Imagenes/manifesto_atelier.jpg" alt="Precisión en la confección" />
        </div>

        <div class="value-block blue-block">
            <h2>PRECISIÓN</h2>
            <p>Materiales de primera línea y confección de alto rigor. Cada corte, cada textura y cada silueta está
                meticulosamente calculada. La calidad no es negociable; es nuestra firma.</p>
        </div>
        <div class="value-block white-block">
            <h2>IDENTIDAD</h2>
            <p>Creamos armaduras modernas. Vestir Xanarchy es un statement, una declaración de individualidad absoluta y
                de pertenencia a una élite que entiende el lenguaje del lujo subversivo.</p>
        </div>
    </section>

    <!-- THE ARCHITECTS (FOUNDERS) - 
<section class="architects-section">
    <div class="architects-header">
        <h2>LOS ARQUITECTOS</h2>
        <p>Las mentes maestras detrás de la revolución visual y técnica de Xanarchy.</p>
    </div>
    
    <div class="architects-grid">
        <div class="architect-card">
            <div class="architect-image">
                <img src="Imagenes/Casi.jpg" alt="Harold Isai Almonaci Miranda" />
            </div>
            <div class="architect-info">
                <h3>HAROLD ISAI<br>ALMONACI MIRANDA</h3>
                <span class="architect-role">DIRECTOR DE INGENIERÍA FRONTEND</span>
            </div>
        </div>
        
        <div class="architect-card">
            <div class="architect-image">
                <img src="Imagenes/Alejandro.jpg" alt="Luis Alejandro Garduño Valdivia" />
            </div>
            <div class="architect-info">
                <h3>LUIS ALEJANDRO<br>GARDUÑO VALDIVIA</h3>
                <span class="architect-role">DIRECTOR DE ARQUITECTURA BACKEND</span>
            </div>
        </div>
    </div>
</section>
-->

    <!-- NEWSLETTER DARK BLOCK  -->
    <?php if (!isset($_SESSION['user'])): ?>
        <section class="newsletter-dark">
            <div class="newsletter-content">
                <h2>ACCESO CLASIFICADO</h2>
                <p>Únete a la élite. Regístrate para tener acceso anticipado a nuestras colecciones de edición limitada y
                    piezas de archivo.</p>
                <a href="register.php" class="btn-primary-luxury inverse">SOLICITAR ACCESO</a>
            </div>
        </section>
    <?php endif; ?>

    <?php require_once 'includes/footer.php'; ?>
</body>

</html>