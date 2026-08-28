<!DOCTYPE html>
<html lang="es">
<head>
    <?php
    require_once 'includes/config.php';
    require_once 'includes/header.php';
    ?>
    <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Estampa-TLA - Acerca de Nosotros</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>

<section class="about-us">
  <h2>ACERCA DE NOSOTROS</h2>
  <p class="about-description">
    Estampa-Tla nace de la pasión por el diseño y la necesidad de expresarse a través de la ropa. Somos un negocio mexicano dedicado a ofrecer playeras y prendas con estilo, actitud y mucha personalidad.
  </p>

  <div class="about-grid">
    <div class="about-item">
      <h3>OBJETIVO</h3>
      <p>Cada diseño que ves en nuestra tienda está pensado para conectar contigo, para que vistas lo que sientes y muestres al mundo quién eres.</p>
    </div>
    <div class="about-image">
      <img src="Imagenes/Objetivo.png" alt="Objetivo" />
    </div>

    <div class="about-image">
      <img src="Imagenes/Meta.png" alt="Meta" />
    </div>
    <div class="about-item">
      <h3>META</h3>
      <p>Nos encanta mezclar arte, cultura urbana, humor y estilo en cada estampado. Desde lo minimalista hasta lo más atrevido, aquí encuentras de todo para armar tu flow.</p>
    </div>

    <div class="about-item">
      <h3>CALIDAD</h3>
      <p>Trabajamos con materiales de calidad y procesos responsables, porque creemos que el buen gusto también se lleva con conciencia.</p>
    </div>
    <div class="about-image">
      <img src="Imagenes/Calidad.png" alt="Calida" />
    </div>
  </div>

  <div class="offer-section">
    <div class="offer-image">
      <img src="Imagenes/Ofecemos.png" alt="Ofrecemos" />
    </div>
    <div class="offer-text">
      <h3>OFRECEMOS</h3>
      <p><strong>Diseños con pasión</strong><br/>Creamos estampados únicos que reflejan estilo, cultura y actitud.</p>
      <p><strong>Productos de calidad</strong><br/>Usamos materiales cómodos y duraderos para que cada prenda se sienta y se vea increíble.</p>
      <p><strong>Identidad</strong><br/>En Estampa-Tla no solo vendemos ropa, creamos <em>identidad</em>.</p>
    </div>
  </div>

  <h2 class="team-title">MEET OUR TEAM</h2>
  <div class="team-grid">

    <div class="team-member">
      <img src="Imagenes/Casi.jpg" alt="Kim" />
      <h4>Harold Isai Almonaci Miranda</h4>
      <p>Programador Frontend</p>
    </div>
    <div class="team-member">
      <img src="Imagenes/Alejandro.jpg" alt="Alejandro" />
      <h4>Luis Alejandro Garduño Valdivia</h4>
      <p>Programador Backend</p>
    </div>
  </div>

    <?php if (!isset($_SESSION['user'])): ?>
  <section class="newsletter">
    <h3>NOTICIAS, COLECCIONES Y MAS...</h3>
    <button><a href="register.php">REGÍSTRATE</a></button>
  </section>
    <?php endif; ?>

</section>

<?php require_once 'includes/footer.php'; ?>

</body>

</html>
