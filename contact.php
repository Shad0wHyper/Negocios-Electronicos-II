<!DOCTYPE html>
<html lang="es">
<head>
    <?php
    require_once 'includes/config.php';
    require_once 'includes/header.php';
    ?>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Estampa-TLA - Contacto</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>

<section class="contact-us">
  <div class="contact-hero">
    <div class="contact-info">
      <div class="icon">
        <img src="Imagenes/Iconos/Icon1.png" alt="Icono" style="width: 40px; height: 40px;" />
      </div>
      <h2>CONTACT US</h2>
      <p>______________________________</p>
      <p>Síguenos en nuestras redes sociales</p>
      <div class="social-icons">
        <img src="Imagenes/Iconos/Facebook.png" alt="Facebook" class="icon" />
        <img src="Imagenes/Iconos/Twitter.png" alt="Twitterr" class="icon" />
        <img src="Imagenes/Iconos/Instagram.png" alt="Instagram" class="icon" />
      </div>
    </div>
    <div class="contact-image">
      <img src="Imagenes/contact us.png" alt="Playeras sonriendo" />
    </div>
  </div>

  <div class="contact-details">
    <h2>PONTE EN CONTACTO CON NOSOTROS</h2>
    <p><strong>Horario:</strong><br/>Lunes a Viernes 7:00 am - 11:00 pm<br/>Sábado y Domingo 9:00 am - 9:00 pm</p>
    <p><strong>Email:</strong><br/><a href="mailto:estampa-tla@gmail.com">estampa-tla@gmail.com</a></p>
    <p><strong>Teléfono:</strong><br/>(449) 574 - 5782</p>
    <p><strong>Ubicación:</strong><br/>Aguascalientes, Ags, México</p>
  </div>

  <form class="contact-form">
    <div class="input-group">
      <input type="text" placeholder="Name" required />
      <input type="email" placeholder="Email" required />
    </div>
    <div class="input-group">
      <input type="tel" placeholder="Phone" />
      <input type="text" placeholder="Company" />
    </div>
    <textarea placeholder="Message" rows="5" required></textarea>
    <button type="submit">SEND MESSAGE →</button>
  </form>
</section>

<?php require_once 'includes/footer.php'; ?>

</body>
</html>
