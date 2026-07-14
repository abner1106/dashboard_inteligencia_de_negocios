<?php
require_once 'conexion.php';

function obtenerDatos($pdo, $sql, $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- Filtros globales (mes/año y sucursal) ---
$filtroSucursal = $_GET['sucursal'] ?? '';
$mesSeleccionado = $_GET['mes'] ?? date('m');
$anioSeleccionado = $_GET['anio'] ?? date('Y');
$filterSql = '';
$filterParams = [];

$mesSeleccionado = (int) $mesSeleccionado;
$anioSeleccionado = (int) $anioSeleccionado;

if ($mesSeleccionado >= 1 && $mesSeleccionado <= 12) {
    $filterSql .= " AND MONTH(v.fecha) = :mes";
    $filterParams['mes'] = $mesSeleccionado;
}
if ($anioSeleccionado >= 2000) {
    $filterSql .= " AND YEAR(v.fecha) = :anio";
    $filterParams['anio'] = $anioSeleccionado;
}
if ($filtroSucursal) {
    $filterSql .= " AND v.sucursal = :sucursal";
    $filterParams['sucursal'] = $filtroSucursal;
}

// Filtro SIN sucursal para la gráfica de sucursales (siempre muestra las 4)
$filterSqlSinSucursal = '';
$filterParamsSinSucursal = [];
if ($mesSeleccionado >= 1 && $mesSeleccionado <= 12) {
    $filterSqlSinSucursal .= " AND MONTH(v.fecha) = :mes";
    $filterParamsSinSucursal['mes'] = $mesSeleccionado;
}
if ($anioSeleccionado >= 2000) {
    $filterSqlSinSucursal .= " AND YEAR(v.fecha) = :anio";
    $filterParamsSinSucursal['anio'] = $anioSeleccionado;
}

$mesesTexto = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$filtroTexto = [];
if ($filtroSucursal)
    $filtroTexto[] = "Sucursal: $filtroSucursal";
if ($mesSeleccionado >= 1 && $mesSeleccionado <= 12)
    $filtroTexto[] = "Periodo: {$mesesTexto[$mesSeleccionado - 1]} {$anioSeleccionado}";
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

// Sucursales filtradas (para el total monetario y KPI)
$sucursalesFiltradas = obtenerDatos($pdo, "
    SELECT s.nombre, SUM(v.total) AS total
    FROM venta v
    JOIN sucursal s ON v.sucursal = s.nombre
    WHERE 1=1" . $filterSql . "
    GROUP BY s.nombre
    ORDER BY total DESC
", $filterParams);

// Sucursales sin filtro de sucursal (para la gráfica, siempre las 4)
$sucursalesAll = obtenerDatos($pdo, "
    SELECT s.nombre, SUM(v.total) AS total
    FROM venta v
    JOIN sucursal s ON v.sucursal = s.nombre
    WHERE 1=1" . $filterSqlSinSucursal . "
    GROUP BY s.nombre
    ORDER BY total DESC
", $filterParamsSinSucursal);

$categorias = obtenerDatos($pdo, "
    SELECT p.tipo_producto AS categoria,
           SUM(dv.precio * dv.cantidad - dv.descuento) AS ingreso
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
$totalSucursales = array_sum(array_column($sucursalesFiltradas, 'total'));
$totalCategoriasQuery = obtenerDatos($pdo, "SELECT SUM(v.subtotal) AS total FROM venta v WHERE 1=1" . $filterSql, $filterParams);
$totalCategorias = $totalCategoriasQuery[0]['total'] ?? 0;
$totalProductosTop5 = array_sum(array_column($productos, 'total'));
$productoEstrella = !empty($productos) ? $productos[0]['descripcion'] : '—';
$totalEmpleadosTop5 = array_sum(array_column($empleados, 'total'));
$vendedorEstrella = !empty($empleados) ? $empleados[0]['nombre'] : '—';
$montoVendedorEstrella = !empty($empleados) ? $empleados[0]['total'] : 0;

// --- Lista de productos y años para filtros locales ---
$productosLista = obtenerDatos($pdo, "SELECT clave_producto, descripcion FROM productos ORDER BY descripcion");
$aniosLista = obtenerDatos($pdo, "SELECT DISTINCT YEAR(fecha) AS anio FROM venta ORDER BY anio");
$aniosDisponibles = array_map(fn($item) => (int) $item['anio'], $aniosLista);
if (!in_array(date('Y'), $aniosDisponibles, true)) {
    $aniosDisponibles[] = (int) date('Y');
}
sort($aniosDisponibles);

// --- Opciones de sucursal para filtro global ---
$sucursalesDropdown = obtenerDatos($pdo, "SELECT nombre FROM sucursal ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard BI — Llantera Hiram</title>
    <meta name="description"
        content="Dashboard de Inteligencia de Negocios para la gestión y análisis de ventas de Llantera Hiram">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <link rel="stylesheet" href="../css/dashboard.css">
</head>

<body>
    <header class="header">
        <div class="header-content">
            <a href="#" class="logo">
                <div class="logo-text">
                    <h1>Llantera Hiram</h1>
                    <span>Dashboard de Inteligencia de Negocios</span>
                </div>
            </a>

            <div class="filters">
                <form method="get" class="filters-form">
                    <div class="filter-group">
                        <label for="mes">Mes</label>
                        <select id="mes" name="mes">
                            <option value="">Seleccionar</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $mesSeleccionado === $i ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mesesTexto[$i - 1]) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="anio">Año</label>
                        <select id="anio" name="anio">
                            <?php foreach ($aniosDisponibles as $anio): ?>
                                <option value="<?= $anio ?>" <?= $anioSeleccionado === $anio ? 'selected' : '' ?>><?= $anio ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="sucursal">Sucursal</label>
                        <select id="filtro-sucursal" name="sucursal">
                            <option value="">Todas</option>
                            <?php foreach ($sucursalesDropdown as $s): ?>
                                <option value="<?= htmlspecialchars($s['nombre']) ?>" <?= $filtroSucursal === $s['nombre'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn-filter" type="submit">🔍 Filtrar</button>
                    <button type="button" id="btn-imprimir" class="btn-filter btn-print">📄 PDF</button>
                </form>
            </div>
        </div>
    </header>

    <main class="main">
        <section class="kpi-wrapper">
            <div class="logo-sidebar">
                <img src="../img/ferrari.png" alt="Logo Llantera Hiram" class="logo-sidebar-img">
            </div>
            <section class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-label">Monto de Ventas</span>
                        <div class="kpi-icon">🧾</div>
                    </div>
                    <div class="kpi-value" id="kpiVentas">$<?= number_format($totalSucursales, 2) ?></div>
                    <div class="kpi-sub">Total facturado del período</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-label">Producto Más Vendido</span>
                        <div class="kpi-icon">📦</div>
                    </div>
                    <div class="kpi-value" id="kpiTicket"><?= number_format($totalProductosTop5) ?></div>
                    <div class="kpi-sub"><?= htmlspecialchars($productoEstrella) ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-label">Vendedor Estrella</span>
                        <div class="kpi-icon">👤</div>
                    </div>
                    <div class="kpi-value" id="kpiClientes">$<?= number_format($montoVendedorEstrella, 2) ?></div>
                    <div class="kpi-sub"><?= htmlspecialchars($vendedorEstrella) ?></div>
                </div>
            </section>
        </section>

        <section class="charts-grid">
            <div class="chart-card full-width">
                <div class="chart-header">
                    <div class="chart-title">
                        <span class="emoji">📈</span> Evolución de ventas mensuales
                    </div>
                    <span class="chart-badge"><?= htmlspecialchars($filtroTitulo) ?></span>
                </div>
                <div class="chart-container">
                    <canvas id="chartMesesVentas"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <span class="emoji">🏆</span> Top 5 Productos
                    </div>
                    <span class="chart-badge">Cantidad vendida</span>
                </div>
                <div class="chart-container"><canvas id="chartProductos"></canvas></div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <span class="emoji">📦</span> Ingresos por Categoría
                    </div>
                    <span class="chart-badge">Distribución</span>
                </div>
                <div class="chart-container"><canvas id="chartCategorias"></canvas></div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <span class="emoji">👤</span> Top 5 Vendedores
                    </div>
                    <span class="chart-badge">Monto</span>
                </div>
                <div class="chart-container"><canvas id="chartEmpleados"></canvas></div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <span class="emoji">🏪</span> Ventas por Sucursal
                    </div>
                    <span class="chart-badge">Comparativo</span>
                </div>
                <div class="chart-container"><canvas id="chartSucursales"></canvas></div>
            </div>

            <div class="chart-card full-width">
                <div class="chart-header">
                    <div class="chart-title">
                        <span class="emoji">⭐</span> Clientes que más compran
                    </div>
                    <span class="chart-badge">Top 5</span>
                </div>
                <div class="chart-container"><canvas id="chartClientes"></canvas></div>
            </div>
        </section>
    </main>

    <footer class="footer">
        Dashboard de Inteligencia de Negocios — Llantera Hiram &copy; <?= date('Y') ?>
    </footer>

    <!-- Se pasa la data de sucursales (todas) para la gráfica -->
    <script id="data-productos" type="application/json"><?= json_encode($productos) ?></script>
    <script id="data-empleados" type="application/json"><?= json_encode($empleados) ?></script>
    <script id="data-sucursales" type="application/json"><?= json_encode($sucursalesAll) ?></script>
    <script id="data-categorias" type="application/json"><?= json_encode($categorias) ?></script>
    <script id="data-clientes" type="application/json"><?= json_encode($topClientes) ?></script>
    <script id="data-sucursal-seleccionada" type="application/json"><?= json_encode($filtroSucursal) ?></script>

    <script src="../js/dashboard.js"></script>
    <script>
        document.getElementById('btn-imprimir').addEventListener('click', () => {
            const params = new URLSearchParams(window.location.search);
            window.open('reporte.php?' + params.toString(), '_blank');
        });
    </script>
</body>

</html>