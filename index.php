<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Inteligencia de Negocios</title>
    <link rel="stylesheet" href="css/index.css">
</head>

<body>
    <div class="card">
        <h1>Dashboard de Inteligencia de Negocios</h1>
        <p>El propietario tiene acceso a este sistema,
            que está pensado para ayudarle a responder preguntas clave
            sobre sunegocio como pueden ser:.</p>
        <ul>
            <li>¿Cuál fue el producto más vendido?</li>
            <li>¿Qué empleado vende más?</li>
            <li>¿Qué sucursal tiene mayores ventas?</li>
            <li>¿Qué categoría genera mayores ingresos?</li>
            <li>¿Qué meses presentan mayor demanda?</li>
            <li>¿Cuáles son los clientes que más compran?</li>
        </ul>
        <p>En la siguiente pantalla se mostrarán los resultados en un dashboard con gráficas y análisis.</p>
        <a class="btn" href="php/dashboard.php">Ir al dashboard</a>
        <p class="note">Serás redirigido automáticamente en 20 segundos.</p>
    </div>

    <script>
        setTimeout(function () {
            window.location.href = 'php/dashboard.php';
        }, 20000);
    </script>
</body>

</html>