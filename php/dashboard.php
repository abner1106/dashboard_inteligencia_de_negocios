<?php
require_once 'conexion.php';

function obtenerDatos($pdo, $sql, $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- Filtros globales (fecha y sucursal) ---
$filtroSucursal = $_GET['sucursal'] ?? '';
$fechaIni = $_GET['fecha_ini'] ?? '';
$fechaFin = $_GET['fecha_fin'] ?? '';
$filterSql = '';
$filterParams = [];

if ($fechaIni) {
    $filterSql .= " AND v.fecha >= :fecha_ini";
    $filterParams['fecha_ini'] = $fechaIni . ' 00:00:00';
}
if ($fechaFin) {
    $filterSql .= " AND v.fecha <= :fecha_fin";
    $filterParams['fecha_fin'] = $fechaFin . ' 23:59:59';
}
if ($filtroSucursal) {
    $filterSql .= " AND v.sucursal = :sucursal";
    $filterParams['sucursal'] = $filtroSucursal;
}

$filtroTexto = [];
if ($filtroSucursal)
    $filtroTexto[] = "Sucursal: $filtroSucursal";
if ($fechaIni && $fechaFin)
    $filtroTexto[] = "Fechas: $fechaIni a $fechaFin";
elseif ($fechaIni)
    $filtroTexto[] = "Desde $fechaIni";
elseif ($fechaFin)
    $filtroTexto[] = "Hasta $fechaFin";
$filtroTitulo = $filtroTexto ? implode(' | ', $filtroTexto) : 'Todos los datos';

// --- Consultas fijas del dashboard ---
$productos = obtenerDatos($pdo, "
    SELECT p.descripcion, SUM(dv.cantidad) AS total
    FROM detalle_venta dv
    JOIN productos p ON dv.clave_producto = p.clave_producto
    JOIN venta v ON dv.folio_venta = v.folio
    WHERE 1=1" . $filterSql . "
    GROUP BY p.descripcion
    ORDER BY total DESC LIMIT 5
", $filterParams);

$empleados = obtenerDatos($pdo, "
    SELECT CONCAT(e.nombre,' ',e.apellido_p) AS nombre, SUM(v.total) AS total
    FROM venta v
    JOIN empleados e ON v.rfc_empleado = e.rfc
    WHERE 1=1" . $filterSql . "
    GROUP BY e.rfc
    ORDER BY total DESC LIMIT 5
", $filterParams);

$sucursales = obtenerDatos($pdo, "
    SELECT s.nombre, SUM(v.total) AS total
    FROM venta v
    JOIN sucursal s ON v.sucursal = s.nombre
    WHERE 1=1" . $filterSql . "
    GROUP BY s.nombre
    ORDER BY total DESC
", $filterParams);

$categorias = obtenerDatos($pdo, "
    SELECT p.tipo_producto AS categoria,
           SUM((dv.precio * dv.cantidad) - dv.descuento) AS ingreso
    FROM detalle_venta dv
    JOIN productos p ON dv.clave_producto = p.clave_producto
    JOIN venta v ON dv.folio_venta = v.folio
    WHERE 1=1" . $filterSql . "
    GROUP BY p.tipo_producto
    ORDER BY ingreso DESC
", $filterParams);

$topClientes = obtenerDatos($pdo, "
    SELECT c.rfc, CONCAT(c.nombre,' ',c.apellido_p) AS nombre,
           SUM(v.total) AS total
    FROM venta v
    JOIN clientes c ON v.rfc_cliente = c.rfc
    WHERE 1=1" . $filterSql . "
    GROUP BY c.rfc, nombre
    ORDER BY total DESC LIMIT 5
", $filterParams);

// --- Totales y “estrellas” ---
$totalSucursales = array_sum(array_column($sucursales, 'total'));
$totalCategorias = array_sum(array_column($categorias, 'ingreso'));
$totalProductosTop5 = array_sum(array_column($productos, 'total'));
$productoEstrella = !empty($productos) ? $productos[0]['descripcion'] : '—';
$totalEmpleadosTop5 = array_sum(array_column($empleados, 'total'));
$vendedorEstrella = !empty($empleados) ? $empleados[0]['nombre'] : '—';

// --- Lista de productos y años para filtros locales ---
$productosLista = obtenerDatos($pdo, "SELECT clave_producto, descripcion FROM productos ORDER BY descripcion");
$aniosLista = obtenerDatos($pdo, "SELECT DISTINCT YEAR(fecha) AS anio FROM venta ORDER BY anio");

// --- Opciones de sucursal para filtro global ---
$sucursalesDropdown = obtenerDatos($pdo, "SELECT nombre FROM sucursal ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Llantera</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">🏁 Llantera Hriam</div>
            <div class="logo-imagen">
                <img src="ferrari.png" alt="Logo de la empresa">
            </div>
            <nav>
                <a href="#" class="active">📊 Dashboard</a>
                <a href="#">📦 Inventario</a>
                <a href="#">💵 Ventas</a>
                <a href="#">🔄 Traspasos</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <h1>Panel de Análisis</h1>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span>Actualizado: <?= date('d/m/Y H:i') ?></span>
                    <button type="button" id="btn-imprimir" class="btn-imprimir">📄 Imprimir Reporte</button>
                </div>
            </header>

            <!-- Filtros globales -->
            <div class="card full-width">
                <h3>Filtros de Análisis</h3>
                <form method="get" class="filters">
                    <label>Desde: <input type="date" name="fecha_ini"
                            value="<?= htmlspecialchars($fechaIni) ?>"></label>
                    <label>Hasta: <input type="date" name="fecha_fin"
                            value="<?= htmlspecialchars($fechaFin) ?>"></label>
                    <label>Sucursal:
                        <select id="filtro-sucursal" name="sucursal">
                            <option value="">Todas</option>
                            <?php foreach ($sucursalesDropdown as $s): ?>
                                <option value="<?= htmlspecialchars($s['nombre']) ?>" <?= $filtroSucursal === $s['nombre'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit">Aplicar</button>
                </form>
            </div>

            <div class="cards-grid">
                <!-- Top 5 Productos -->
                <div class="card full-width">
                    <h3>🏆 Top 5 Productos (cantidad)</h3>
                    <div class="total-card">
                        Total unidades (top 5): <strong><?= number_format($totalProductosTop5) ?></strong>
                        | Producto estrella: <strong><?= htmlspecialchars($productoEstrella) ?></strong>
                    </div>
                    <div class="chart-container"><canvas id="chartProductos"></canvas></div>
                </div>

                <!-- Top 5 Empleados -->
                <div class="card full-width">
                    <h3>👤 Top 5 Vendedores</h3>
                    <div class="total-card">
                        Total ventas (top 5): <strong>$<?= number_format($totalEmpleadosTop5, 2) ?></strong>
                        | Vendedor estrella: <strong><?= htmlspecialchars($vendedorEstrella) ?></strong>
                    </div>
                    <div class="chart-container"><canvas id="chartEmpleados"></canvas></div>
                </div>

                <!-- Ventas por Sucursal -->
                <div class="card full-width">
                    <h3>🏢 Ventas por Sucursal</h3>
                    <div class="total-card">
                        Total general: <strong>$<?= number_format($totalSucursales, 2) ?></strong>
                    </div>
                    <div class="detalle-lista">
                        <?php foreach ($sucursales as $s): ?>
                            <span class="item-detalle">
                                <?= htmlspecialchars($s['nombre']) ?>:
                                <strong>$<?= number_format($s['total'], 2) ?></strong>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="chart-container"><canvas id="chartSucursales"></canvas></div>
                </div>

                <!-- Ingresos por Categoría -->
                <div class="card full-width">
                    <h3>📦 Ingresos por Categoría</h3>
                    <div class="total-card">
                        Total: <strong>$<?= number_format($totalCategorias, 2) ?></strong>
                    </div>
                    <div class="chart-container"><canvas id="chartCategorias"></canvas></div>
                </div>

                <!-- ==================== EVOLUCIÓN DE VENTAS MENSUALES ==================== -->
                <div class="card full-width">
                    <h3>📅 Evolución de Ventas Mensuales</h3>
                    <div id="evol-total" class="total-card" style="margin-bottom:12px;">
                        Total del período: <strong>$0.00</strong>
                    </div>
                    <div class="filters">
                        <label>Año:
                            <select id="evol-anio">
                                <option value="">Todos los años</option>
                                <?php foreach ($aniosLista as $a): ?>
                                    <option value="<?= $a['anio'] ?>" <?= (date('Y') == $a['anio']) ? 'selected' : '' ?>>
                                        <?= $a['anio'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Producto/Servicio:
                            <select id="evol-producto">
                                <option value="">Todos</option>
                                <?php foreach ($productosLista as $prod): ?>
                                    <option value="<?= htmlspecialchars($prod['clave_producto']) ?>">
                                        <?= htmlspecialchars($prod['descripcion']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div class="chart-container" style="min-height:400px;">
                        <canvas id="chartMesesVentas"></canvas>
                    </div>
                </div>

                <!-- Clientes que más compran -->
                <div class="card full-width">
                    <h3>👥 Clientes que más compran <span
                            class="filtro-titulo">(<?= htmlspecialchars($filtroTitulo) ?>)</span></h3>
                    <div class="chart-container"><canvas id="chartClientes"></canvas></div>
                </div>

                <!-- Consulta personalizada -->
                <div class="card full-width">
                    <h3>🧩 Consulta Personalizada</h3>
                    <div class="consulta-description">
                        <p>Compara resultados por <strong>dimensión</strong> y <strong>métrica</strong>.</p>
                        <p>Selecciona una dimensión y una métrica. El gráfico muestra el total en el rango de fechas.
                        </p>
                        <div id="consulta-estado">Presiona "Consultar" para generar el gráfico.</div>
                    </div>
                    <div class="filters">
                        <label>Dimensión:
                            <select id="dimension">
                                <option value="producto">Producto</option>
                                <option value="empleado">Empleado</option>
                                <option value="sucursal">Sucursal</option>
                                <option value="categoria">Categoría</option>
                            </select>
                        </label>
                        <label>Métrica:
                            <select id="metrica">
                                <option value="cantidad">Cantidad vendida</option>
                                <option value="monto">Monto total ($)</option>
                            </select>
                        </label>
                        <label>Desde: <input type="date" id="fecha-ini"></label>
                        <label>Hasta: <input type="date" id="fecha-fin"></label>
                        <button type="button" id="btn-consultar">🔍 Consultar</button>
                    </div>
                    <div class="chart-container"><canvas id="chartPersonalizado"></canvas></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Datos incrustados para los gráficos fijos -->
    <script id="data-productos" type="application/json"><?= json_encode($productos) ?></script>
    <script id="data-empleados" type="application/json"><?= json_encode($empleados) ?></script>
    <script id="data-sucursales" type="application/json"><?= json_encode($sucursales) ?></script>
    <script id="data-categorias" type="application/json"><?= json_encode($categorias) ?></script>
    <script id="data-clientes" type="application/json"><?= json_encode($topClientes) ?></script>
    <script id="data-sucursal-seleccionada" type="application/json"><?= json_encode($filtroSucursal) ?></script>

    <script src="../js/dashboard.js"></script>
    <script>
        // Botón de impresión: abre el reporte en una nueva ventana
        document.getElementById('btn-imprimir').addEventListener('click', () => {
            const params = new URLSearchParams(window.location.search);
            window.open('reporte.php?' + params.toString(), '_blank');
        });
    </script>
</body>

</html>