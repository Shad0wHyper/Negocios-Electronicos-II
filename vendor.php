<!DOCTYPE html>
<html lang="es">
<head>
    <?php
    require_once 'includes/config.php';
    require_once 'includes/header.php';
    ?>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Estampa-TLA - Panel de Vendedor</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>

  <section class="seller-interface">
    <div class="product-preview">
      <img src="Imagenes/Colecciones.png" alt="Producto principal" />
      <div class="product-thumbnails">
        <img src="Imagenes/Colecciones.png" alt="Miniatura 1" />
        <img src="Imagenes/Colecciones.png" alt="Miniatura 2" />
        <div class="add-thumbnail">+</div>
      </div>
    </div>
  
    <form class="product-form">
      <h2>AÑADIR PRODUCTO</h2>

      <label for="product-name">Nombre del producto:</label>
      <input id="product-name" type="text" placeholder="Playera estampada..." />

      <label for="price">Precio:</label>
      <input id="price" type="number" placeholder="$" />

      <label for="description">Descripción:</label>
      <textarea id="description" placeholder="Descripción..."></textarea>

      <label>Colores:</label>
      <div class="color-options">
        <button class="color-option color-lightblue"></button>
        <button class="color-option color-white"></button>
        <button class="color-option color-brown"></button>
        <button class="color-option color-dark"></button>
        <button class="color-option color-beige"></button>
        <button class="color-option color-green"></button>
      </div>

      <div class="quantity-control">
        <button class="qty-btn" id="decrease">-</button>
        <input type="number" value="1" min="1" />
        <button class="qty-btn" id="increase">+</button>
      </div>

      <button class="submit-product">SUBIR PRODUCTO</button>

      <details>
        <summary>Agregar Detalles</summary>
        <textarea placeholder="Detalles adicionales..."></textarea>
      </details>

      <details>
        <summary>Agregar Dimensiones</summary>
        <textarea placeholder="Dimensiones del producto..."></textarea>
      </details>
    </form>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

</body>
</html>
