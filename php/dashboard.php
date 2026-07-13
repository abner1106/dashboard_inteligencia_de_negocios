<?php
require_once 'conexion.php';

function obtenerDatos($pdo, $sql, $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Productos más vendidos (cantidad)
$productos = obtenerDatos($pdo, "
    SELECT p.descripcion, SUM(dv.cantidad) AS total
    FROM detalle_venta dv
    JOIN productos p ON dv.clave_producto = p.clave_producto
    GROUP BY p.descripcion
    ORDER BY total DESC
    LIMIT 5
");

// Empleados que más venden (monto)
$empleados = obtenerDatos($pdo, "
    SELECT CONCAT(e.nombre,' ',e.apellido_p) AS nombre, SUM(v.total) AS total
    FROM venta v
    JOIN empleados e ON v.rfc_empleado = e.rfc
    GROUP BY e.rfc
    ORDER BY total DESC
    LIMIT 5
");

// Sucursales con mayores ventas
$sucursales = obtenerDatos($pdo, "
    SELECT s.nombre, SUM(v.total) AS total
    FROM venta v
    JOIN sucursal s ON v.sucursal = s.nombre
    GROUP BY s.nombre
    ORDER BY total DESC
");

// Meses con mayores ventas (monto)
$mesesVentas = obtenerDatos($pdo, "
    SELECT DATE_FORMAT(v.fecha, '%Y-%m') AS mes, SUM(v.total) AS total
    FROM venta v
    GROUP BY mes
    ORDER BY mes ASC
    LIMIT 12
");

// Categoría que genera más ingresos
$categorias = obtenerDatos($pdo, "
    SELECT p.tipo_producto AS categoria,
           SUM((dv.precio * dv.cantidad) - dv.descuento) AS ingreso
    FROM detalle_venta dv
    JOIN productos p ON dv.clave_producto = p.clave_producto
    GROUP BY p.tipo_producto
    ORDER BY ingreso DESC
");

// Meses con mayor demanda (unidades)
$mesesDemanda = obtenerDatos($pdo, "
    SELECT DATE_FORMAT(v.fecha, '%Y-%m') AS mes, SUM(dv.cantidad) AS total
    FROM venta v
    JOIN detalle_venta dv ON v.folio = dv.folio_venta
    GROUP BY mes
    ORDER BY mes ASC
    LIMIT 12
");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Llantera</title>
    <link rel="stylesheet" href="dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>

<body>
    <div class="dashboard-container">
        <!-- Barra lateral -->
        <aside class="sidebar">
            <div class="logo">🏁 LlanteraPro</div>
            <nav>
                <a href="#" class="active">📊 Dashboard</a>
                <a href="#">📦 Inventario</a>
                <a href="#">💵 Ventas</a>
                <a href="#">🔄 Traspasos</a>
            </nav>
        </aside>

        <!-- Contenido principal -->
        <main class="main-content">
            <header class="top-bar">
                <h1>Panel de Análisis</h1>
                <span>Actualizado: <?= date('d/m/Y H:i') ?></span>
            </header>

            <div class="cards-grid">
                <!-- Tarjeta 1: Productos más vendidos -->
                <div class="card">
                    <h3>🏆 Top 5 Productos (cantidad)</h3>
                    <div class="chart-container">
                        <canvas id="chartProductos"></canvas>
                    </div>
                </div>

                <!-- Tarjeta 2: Empleados estrella -->
                <div class="card">
                    <h3>👤 Top 5 Vendedores</h3>
                    <div class="chart-container">
                        <canvas id="chartEmpleados"></canvas>
                    </div>
                </div>

                <!-- Tarjeta 3: Sucursales -->
                <div class="card">
                    <h3>🏢 Ventas por Sucursal</h3>
                    <div class="chart-container">
                        <canvas id="chartSucursales"></canvas>
                    </div>
                </div>

                <!-- Tarjeta 4: Ingresos por categoría -->
                <div class="card">
                    <h3>📦 Ingresos por Categoría</h3>
                    <div class="chart-container">
                        <canvas id="chartCategorias"></canvas>
                    </div>
                </div>

                <!-- Tarjeta 5: Ventas mensuales (monto) -->
                <div class="card full-width">
                    <h3>📅 Evolución de Ventas Mensuales</h3>
                    <div class="chart-container">
                        <canvas id="chartMesesVentas"></canvas>
                    </div>
                </div>

                <!-- Tarjeta 6: Demanda mensual (unidades) -->
                <div class="card full-width">
                    <h3>📈 Demanda Mensual (unidades)</h3>
                    <div class="chart-container">
                        <canvas id="chartMesesDemanda"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Datos JSON para JavaScript -->
    <script id="data-productos" type="application/json"><?= json_encode($productos) ?></script>
    <script id="data-empleados" type="application/json"><?= json_encode($empleados) ?></script>
    <script id="data-sucursales" type="application/json"><?= json_encode($sucursales) ?></script>
    <script id="data-categorias" type="application/json"><?= json_encode($categorias) ?></script>
    <script id="data-mesesVentas" type="application/json"><?= json_encode($mesesVentas) ?></script>
    <script id="data-mesesDemanda" type="application/json"><?= json_encode($mesesDemanda) ?></script>

    <script src="dashboard.js"></script>
</body>

</html>