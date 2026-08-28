<?php
// privacy.php
require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Política de Privacidad - Estampa</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f9f9f9;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
        }

        h2 {
            margin-top: 25px;
        }

        p, li {
            line-height: 1.7;
        }

        ul {
            margin-left: 20px;
        }

        .policy-footer {
            margin-top: 40px;
            text-align: center;
            font-size: 0.9em;
            color: #777;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Política de Privacidad</h1>

    <p>En <strong>Estampa-Tla</strong> valoramos tu privacidad. Esta política describe cómo recopilamos, usamos y protegemos tus datos personales al usar nuestro sitio web.</p>

    <h2>1. Información que recopilamos</h2>
    <ul>
        <li><strong>Datos de cuenta:</strong> nombre, correo electrónico, contraseña.</li>
        <li><strong>Datos de compra:</strong> dirección de envío, historial de pedidos.</li>
        <li><strong>Datos de navegación:</strong> cookies, IP, páginas visitadas.</li>
    </ul>

    <h2>2. Uso de la información</h2>
    <ul>
        <li>Procesar y gestionar tus pedidos.</li>
        <li>Mejorar nuestra tienda y ofrecer recomendaciones.</li>
        <li>Enviar comunicaciones sobre ofertas y novedades (si diste tu consentimiento).</li>
    </ul>

    <h2>3. Cookies y tecnologías similares</h2>
    <ul>
        <li>Recordar tu sesión y preferencias.</li>
        <li>Analizar el tráfico web.</li>
        <li>Mostrarte anuncios relevantes en otros sitios.</li>
    </ul>
    <p>Puedes desactivar las cookies desde la configuración de tu navegador, aunque esto podría afectar la funcionalidad del sitio.</p>

    <h2>4. Protección de datos</h2>
    <p>Mantenemos medidas de seguridad técnicas y organizativas para evitar accesos no autorizados, alteraciones o pérdida de datos.</p>

    <h2>5. Compartir datos con terceros</h2>
    <ul>
        <li>Proveedores de envío y pasarelas de pago (solo lo estrictamente necesario).</li>
        <li>Agentes externos que nos ayudan con marketing y análisis.</li>
    </ul>

    <h2>6. Derechos del usuario</h2>
    <ul>
        <li>Acceder a tus datos.</li>
        <li>Solicitar la corrección o eliminación de tu información.</li>
        <li>Oponerte al tratamiento o pedir la portabilidad de tus datos.</li>
    </ul>
    <p>Para ejercer cualquiera de estos derechos, contáctanos al correo <a href="mailto:info@estampa-tla.com">info@estampa-tla.com</a>.</p>

    <h2>7. Cambios en esta política</h2>
    <p>Podemos actualizar esta Política de Privacidad en cualquier momento. La fecha de última actualización aparece al final.</p>

    <div class="policy-footer">
        <p>Última actualización: <?php echo date("d \\d\\e F \\d\\e Y"); ?></p>
    </div>
    <br><br><p>Cualquier aclaracion ponte en contacto con nuestro equipo <a href="contact.php"><u>aqui</u></a></p>

</div>

<?php
require_once 'includes/footer.php';
?>
