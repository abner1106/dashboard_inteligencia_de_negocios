<?php
require_once 'php/conexion.php';

function obtenerDatos($pdo, $sql, $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$mesSeleccionado = (int) ($_GET['mes'] ?? date('m'));
$anioSeleccionado = (int) ($_GET['anio'] ?? date('Y'));
$productoSeleccionado = $_GET['producto'] ?? '';
$sucursalSeleccionada = $_GET['sucursal'] ?? '';

$productosLista = obtenerDatos($pdo, "SELECT clave_producto, descripcion FROM productos ORDER BY descripcion");
$aniosLista = obtenerDatos($pdo, "SELECT DISTINCT YEAR(fecha) AS anio FROM venta ORDER BY anio");
$sucursalesDropdown = obtenerDatos($pdo, "SELECT nombre FROM sucursal ORDER BY nombre");

$where = [];
$params = [];

if ($mesSeleccionado >= 1 && $mesSeleccionado <= 12) {
    $where[] = "MONTH(v.fecha) = :mes";
    $params['mes'] = $mesSeleccionado;
}
if ($anioSeleccionado >= 2000) {
    $where[] = "YEAR(v.fecha) = :anio";
    $params['anio'] = $anioSeleccionado;
}
if ($productoSeleccionado) {
    $where[] = "dv.clave_producto = :producto";
    $params['producto'] = $productoSeleccionado;
}
if ($sucursalSeleccionada) {
    $where[] = "v.sucursal = :sucursal";
    $params['sucursal'] = $sucursalSeleccionada;
}

$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT DATE_FORMAT(v.fecha, '%Y-%m') AS mes, SUM(v.total) AS total
        FROM venta v
        JOIN detalle_venta dv ON v.folio = dv.folio_venta
        $sqlWhere
        GROUP BY mes
        ORDER BY mes ASC";

$datos = obtenerDatos($pdo, $sql, $params);
$totalPeriodo = array_sum(array_column($datos, 'total'));
$mesesTexto = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$nombreMes = $mesesTexto[$mesSeleccionado - 1] ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evolución de Ventas</title>
    <link rel="stylesheet" href="css/evolucion_ventas.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
</head>

<body>
    <div class="page-shell">
        <header class="hero-card">
            <div>
                <p class="eyebrow">Análisis ejecutivo</p>
                <h1>Evolución de ventas mensuales</h1>
                <p class="hero-text">Visualiza el comportamiento mensual con filtros claros de mes, año y
                    producto/servicio.</p>
            </div>
            <a href="dashboard.php" class="back-link">← Volver al dashboard</a>
        </header>

        <section class="filters-card">
            <form method="get" class="filters">
                <label>Mes
                    <select name="mes">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= $mesSeleccionado === $i ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mesesTexto[$i - 1]) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label>Año
                    <select name="anio">
                        <?php foreach ($aniosLista as $anio): ?>
                            <option value="<?= (int) $anio['anio'] ?>" <?= $anioSeleccionado === (int) $anio['anio'] ? 'selected' : '' ?>><?= (int) $anio['anio'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Producto/Servicio
                    <select name="producto">
                        <option value="">Todos</option>
                        <?php foreach ($productosLista as $prod): ?>
                            <option value="<?= htmlspecialchars($prod['clave_producto']) ?>"
                                <?= $productoSeleccionado === $prod['clave_producto'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prod['descripcion']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Sucursal
                    <select name="sucursal">
                        <option value="">Todas</option>
                        <?php foreach ($sucursalesDropdown as $s): ?>
                            <option value="<?= htmlspecialchars($s['nombre']) ?>" <?= $sucursalSeleccionada === $s['nombre'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit">Aplicar filtros</button>
            </form>
        </section>

        <section class="summary-grid">
            <article class="summary-card accent">
                <span>Total del periodo</span>
                <strong>$<?= number_format($totalPeriodo, 2) ?></strong>
            </article>
            <article class="summary-card">
                <span>Periodo</span>
                <strong><?= htmlspecialchars($nombreMes ?: 'Sin selección') ?> <?= $anioSeleccionado ?></strong>
            </article>
            <article class="summary-card">
                <span>Producto/Servicio</span>
                <strong><?= htmlspecialchars($productoSeleccionado ? $productosLista[array_search(array_column($productosLista, 'clave_producto'), $productoSeleccionado)]['descripcion'] ?? 'Seleccionado' : 'Todos') ?></strong>
            </article>
        </section>

        <section class="chart-card">
            <div class="chart-header">
                <h2>Comparativo mensual</h2>
                <p>Las etiquetas muestran el importe de cada mes en moneda y con valores visibles.</p>
            </div>
            <div class="chart-wrap">
                <canvas id="chartEvolucionVentas"></canvas>
            </div>
        </section>
    </div>

    <script id="datos-evolucion" type="application/json"><?= json_encode($datos) ?></script>
    <script src="js/evolucion_ventas.js"></script>
</body>

</html>