<?php
require_once '../conexion.php';

$dimension = $_GET['dimension'] ?? 'producto';
$metrica = $_GET['metrica'] ?? 'cantidad';
$fechaIni = $_GET['fecha_ini'] ?? null;
$fechaFin = $_GET['fecha_fin'] ?? null;

// Validar que la dimensión y métrica sean permitidos (evitar inyección)
$permitidos = [
    'dimension' => ['producto', 'empleado', 'sucursal', 'categoria', 'mes'],
    'metrica' => ['cantidad', 'monto', 'ingreso']
];
if (!in_array($dimension, $permitidos['dimension']))
    die("Dimensión inválida");
if (!in_array($metrica, $permitidos['metrica']))
    die("Métrica inválida");

// Construir SELECT dinámico
$sql = "";
$groupBy = "";
$orderBy = "";

switch ($metrica) {
    case 'cantidad':
        $select = "SUM(dv.cantidad) AS valor";
        break;
    case 'monto':
        $select = "SUM(v.total) AS valor";
        break;
    case 'ingreso':
        $select = "SUM((dv.precio * dv.cantidad) - dv.descuento) AS valor";
        break;
}

switch ($dimension) {
    case 'producto':
        $sql = "SELECT p.descripcion AS etiqueta, $select
                FROM detalle_venta dv
                JOIN productos p ON dv.clave_producto = p.clave_producto
                JOIN venta v ON dv.folio_venta = v.folio
                WHERE 1=1";
        $groupBy = " GROUP BY p.descripcion";
        break;
    case 'empleado':
        $sql = "SELECT CONCAT(e.nombre,' ',e.apellido_p) AS etiqueta, $select
                FROM venta v
                JOIN empleados e ON v.rfc_empleado = e.rfc";
        // si la métrica requiere detalle_venta, hay que agregar el JOIN
        if ($metrica === 'cantidad' || $metrica === 'ingreso') {
            $sql = "SELECT CONCAT(e.nombre,' ',e.apellido_p) AS etiqueta, $select
                    FROM venta v
                    JOIN empleados e ON v.rfc_empleado = e.rfc
                    JOIN detalle_venta dv ON v.folio = dv.folio_venta
                    WHERE 1=1";
        }
        $groupBy = " GROUP BY e.rfc";
        break;
    case 'sucursal':
        $sql = "SELECT s.nombre AS etiqueta, $select
                FROM venta v
                JOIN sucursal s ON v.sucursal = s.nombre";
        if ($metrica === 'cantidad' || $metrica === 'ingreso') {
            $sql .= " JOIN detalle_venta dv ON v.folio = dv.folio_venta";
        }
        $groupBy = " GROUP BY s.nombre";
        break;
    case 'categoria':
        $sql = "SELECT p.tipo_producto AS etiqueta, $select
                FROM detalle_venta dv
                JOIN productos p ON dv.clave_producto = p.clave_producto
                JOIN venta v ON dv.folio_venta = v.folio
                WHERE 1=1";
        $groupBy = " GROUP BY p.tipo_producto";
        break;
    case 'mes':
        $sql = "SELECT DATE_FORMAT(v.fecha, '%Y-%m') AS etiqueta, $select
                FROM venta v";
        if ($metrica === 'cantidad' || $metrica === 'ingreso') {
            $sql .= " JOIN detalle_venta dv ON v.folio = dv.folio_venta";
        }
        $groupBy = " GROUP BY DATE_FORMAT(v.fecha, '%Y-%m')";
        break;
}

// Filtros de fecha
$params = [];
if ($fechaIni) {
    $sql .= " AND v.fecha >= :fecha_ini";
    $params['fecha_ini'] = $fechaIni . ' 00:00:00';
}
if ($fechaFin) {
    $sql .= " AND v.fecha <= :fecha_fin";
    $params['fecha_fin'] = $fechaFin . ' 23:59:59';
}

$sql .= $groupBy;
$sql .= " ORDER BY valor DESC LIMIT 10";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($resultados);