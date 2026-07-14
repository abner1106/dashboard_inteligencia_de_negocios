<?php
require_once '../conexion.php';

$mes    = $_GET['mes'] ?? null;
$anio   = $_GET['anio'] ?? null;
$producto = $_GET['producto'] ?? null;
$fechaIni = $_GET['fecha_ini'] ?? null;
$fechaFin = $_GET['fecha_fin'] ?? null;
$sucursal = $_GET['sucursal'] ?? null;

$where = [];
$params = [];

if ($mes) {
    $where[] = "MONTH(v.fecha) = :mes";
    $params['mes'] = $mes;
}
if ($anio) {
    $where[] = "YEAR(v.fecha) = :anio";
    $params['anio'] = $anio;
}
if ($producto) {
    $where[] = "dv.clave_producto = :prod";
    $params['prod'] = $producto;
}
if ($fechaIni) {
    $where[] = "v.fecha >= :fecha_ini";
    $params['fecha_ini'] = $fechaIni . ' 00:00:00';
}
if ($fechaFin) {
    $where[] = "v.fecha <= :fecha_fin";
    $params['fecha_fin'] = $fechaFin . ' 23:59:59';
}
if ($sucursal) {
    $where[] = "v.sucursal = :sucursal";
    $params['sucursal'] = $sucursal;
}

$sql = "SELECT DATE_FORMAT(v.fecha, '%Y-%m') AS mes, SUM(v.total) AS total
        FROM venta v
        JOIN detalle_venta dv ON v.folio = dv.folio_venta
        WHERE 1=1";

if (!empty($where)) {
    $sql .= " AND " . implode(" AND ", $where);
}
$sql .= " GROUP BY mes ORDER BY mes ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($data);

