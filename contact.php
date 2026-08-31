<?php
require_once 'includes/config.php';
require_once 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Contacto | Xanarchy</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body class="xanarchy-contact-page">

<!-- HERO CONTACT -->
<section class="contact-hero">
    <div class="contact-hero-bg">
        <img src="Imagenes/contact_hero.jpg" alt="Contacto Xanarchy" />
        <div class="hero-overlay"></div>
    </div>
    <div class="contact-hero-content">
        <h1>CONEXIÓN</h1>
        <p>ACCESO DIRECTO A LA ÉLITE</p>
    </div>
</section>

<!-- CONTACT SPLIT -->
<section class="contact-split">
    <!-- INFO BLOCK -->
    <div class="contact-info-block">
        <h2>CENTRO DE<br>OPERACIONES</h2>
        
        <div class="contact-detail">
            <h3>CORREO</h3>
            <p><a href="mailto:contacto@xanarchy.com">contacto@xanarchy.com</a></p>
        </div>
        
        <div class="contact-detail">
            <h3>LÍNEA DIRECTA</h3>
            <p>+52 (449) 555 - 0199</p>
        </div>
        
        <div class="contact-detail">
            <h3>UBICACIÓN</h3>
            <p>Aguascalientes, Ags, México</p>
        </div>
        
        <div class="contact-socials">
            <a href="#"><img src="Imagenes/Iconos/Instagram.png" alt="Instagram" /></a>
            <a href="#"><img src="Imagenes/Iconos/Twitter.png" alt="Twitter" /></a>
            <a href="#"><img src="Imagenes/Iconos/Facebook.png" alt="Facebook" /></a>
        </div>
    </div>
    
    <!-- FORM BLOCK -->
    <div class="contact-form-block">
        <form class="xanarchy-form">
            <h2>ENVIAR TRANSMISIÓN</h2>
            <div class="form-group">
                <input type="text" id="name" name="name" placeholder="NOMBRE" required />
            </div>
            <div class="form-group">
                <input type="email" id="email" name="email" placeholder="EMAIL" required />
            </div>
            <div class="form-group">
                <textarea id="message" name="message" rows="4" placeholder="MENSAJE" required></textarea>
            </div>
            <button type="submit" class="btn-primary-luxury">ENVIAR</button>
        </form>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
