<?php
require_once 'conexion.php';

// Recibe los mismos filtros globales
$filtroSucursal = $_GET['sucursal'] ?? '';
$fechaIni = $_GET['fecha_ini'] ?? '';
$fechaFin = $_GET['fecha_fin'] ?? '';

$where = [];
$params = [];

if ($fechaIni) {
    $where[] = "v.fecha >= :fecha_ini";
    $params['fecha_ini'] = $fechaIni . ' 00:00:00';
}
if ($fechaFin) {
    $where[] = "v.fecha <= :fecha_fin";
    $params['fecha_fin'] = $fechaFin . ' 23:59:59';
}
if ($filtroSucursal) {
    $where[] = "v.sucursal = :sucursal";
    $params['sucursal'] = $filtroSucursal;
}

$sqlWhere = $where ? " WHERE " . implode(" AND ", $where) : "";

// Consulta de ventas con detalles (incluye productos y cliente)
$sql = "SELECT v.folio, v.fecha, v.sucursal, 
               CONCAT(e.nombre,' ',e.apellido_p) AS empleado,
               CONCAT(c.nombre,' ',c.apellido_p) AS cliente,
               p.descripcion AS producto, dv.cantidad, dv.precio, 
               (dv.cantidad * dv.precio) AS importe,
               v.forma_pago, v.total
        FROM venta v
        JOIN empleados e ON v.rfc_empleado = e.rfc
        JOIN clientes c ON v.rfc_cliente = c.rfc
        JOIN detalle_venta dv ON v.folio = dv.folio_venta
        JOIN productos p ON dv.clave_producto = p.clave_producto
        $sqlWhere
        ORDER BY v.fecha DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Resumen
$totalVentas = array_sum(array_column($ventas, 'total'));

// Título dinámico
$tituloReporte = 'Historial de Ventas';
if ($fechaIni)
    $tituloReporte .= " desde $fechaIni";
if ($fechaFin)
    $tituloReporte .= " hasta $fechaFin";
if ($filtroSucursal)
    $tituloReporte .= " - Sucursal: $filtroSucursal";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Ventas</title>
    <style>
        @media print {
            body {
                background: white;
                color: black;
            }

            .no-print {
                display: none;
            }
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            padding: 20px;
            background: #f4f4f4;
        }

        .reporte-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .resumen {
            text-align: center;
            font-size: 1.2em;
            margin-bottom: 20px;
            background: #ecf0f1;
            padding: 10px;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #bdc3c7;
            padding: 8px;
            text-align: left;
            font-size: 0.9em;
        }

        th {
            background-color: #2c3e50;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .acciones {
            text-align: right;
            margin-bottom: 15px;
        }

        button {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>

<body onload="window.print()">
    <div class="reporte-container">
        <div class="acciones no-print">
            <button onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
        </div>
        <h1><?= htmlspecialchars($tituloReporte) ?></h1>
        <div class="resumen">
            <strong>Total de ventas:</strong> $<?= number_format($totalVentas, 2) ?>
            &nbsp;|&nbsp;
            <strong>Registros encontrados:</strong> <?= count($ventas) ?>
        </div>

        <?php if (!empty($ventas)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Sucursal</th>
                        <th>Empleado</th>
                        <th>Cliente</th>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Precio</th>
                        <th>Importe</th>
                        <th>Forma de pago</th>
                        <th>Total Venta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventas as $v): ?>
                        <tr>
                            <td><?= htmlspecialchars($v['folio']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                            <td><?= htmlspecialchars($v['sucursal']) ?></td>
                            <td><?= htmlspecialchars($v['empleado']) ?></td>
                            <td><?= htmlspecialchars($v['cliente']) ?></td>
                            <td><?= htmlspecialchars($v['producto']) ?></td>
                            <td><?= $v['cantidad'] ?></td>
                            <td>$<?= number_format($v['precio'], 2) ?></td>
                            <td>$<?= number_format($v['importe'], 2) ?></td>
                            <td><?= htmlspecialchars($v['forma_pago']) ?></td>
                            <td><strong>$<?= number_format($v['total'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center;">No se encontraron ventas con los filtros aplicados.</p>
        <?php endif; ?>
    </div>
</body>

</html>