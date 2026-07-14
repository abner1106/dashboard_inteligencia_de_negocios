<?php
require_once '../conexion.php';

$mes = $_GET['mes'] ?? null;
$anio = $_GET['anio'] ?? null;
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

$granularidad = $mes ? 'dias' : 'meses';

if ($granularidad === 'dias') {
    $sql = "SELECT DATE_FORMAT(v.fecha, '%Y-%m-%d') AS periodo,
                   DATE_FORMAT(v.fecha, '%d') AS etiqueta,
                   SUM(v.total) AS total
            FROM venta v
            WHERE 1=1";
} else {
    $sql = "SELECT DATE_FORMAT(v.fecha, '%Y-%m') AS periodo,
                   DATE_FORMAT(v.fecha, '%Y-%m') AS etiqueta,
                   SUM(v.total) AS total
            FROM venta v
            WHERE 1=1";
}

if (!empty($where)) {
    $sql .= " AND " . implode(" AND ", $where);
}

if ($granularidad === 'dias') {
    $sql .= " GROUP BY DATE(v.fecha) ORDER BY DATE(v.fecha) ASC";
} else {
    $sql .= " GROUP BY DATE_FORMAT(v.fecha, '%Y-%m') ORDER BY DATE_FORMAT(v.fecha, '%Y-%m') ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'granularidad' => $granularidad,
    'data' => $data
]);

