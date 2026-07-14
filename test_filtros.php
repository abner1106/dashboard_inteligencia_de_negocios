<?php
require_once 'php/conexion.php';

echo "=== VERIFICACIÓN DE FILTROS ===\n\n";

// Simular los mismos filtros del usuario
$_GET['mes'] = 6; // Junio
$_GET['anio'] = 2026;
$_GET['sucursal'] = 'Ciudad Universitaria';

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

echo "Filtros aplicados:\n";
echo "- Mes: " . $mesSeleccionado . "\n";
echo "- Año: " . $anioSeleccionado . "\n";
echo "- Sucursal: " . $filtroSucursal . "\n\n";

// Consulta de prueba
$sql = "SELECT s.nombre, SUM(v.total) AS total
        FROM venta v
        JOIN sucursal s ON v.sucursal = s.nombre
        WHERE 1=1" . $filterSql . "
        GROUP BY s.nombre
        ORDER BY total DESC";

echo "SQL: " . $sql . "\n";
echo "Parámetros: " . json_encode($filterParams) . "\n\n";

$stmt = $pdo->prepare($sql);
$stmt->execute($filterParams);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Resultado:\n";
foreach ($result as $row) {
    echo "- " . $row['nombre'] . ": $" . number_format($row['total'], 2) . "\n";
}
?>