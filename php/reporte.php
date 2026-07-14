<?php
require_once 'conexion.php';

// Recibe los filtros de mes/año y sucursal
$filtroSucursal = $_GET['sucursal'] ?? '';
$mesSeleccionado = (int) ($_GET['mes'] ?? date('m'));
$anioSeleccionado = (int) ($_GET['anio'] ?? date('Y'));

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
$mesesTexto = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$nombreMes = $mesesTexto[$mesSeleccionado - 1] ?? '';
$tituloReporte = 'Reporte Ejecutivo de Ventas';
if ($nombreMes && $anioSeleccionado)
    $tituloReporte .= " - $nombreMes $anioSeleccionado";
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
                color: #1f2937;
            }

            .no-print {
                display: none;
            }
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            color: #1f2937;
        }

        .reporte-container {
            max-width: 1100px;
            margin: 0 auto;
            background: white;
            padding: 28px;
            border-radius: 16px;
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.12);
        }

        .reporte-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 20px;
            border-radius: 14px;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: white;
            margin-bottom: 20px;
        }

        .reporte-header h1 {
            margin: 0 0 6px;
            font-size: 1.6rem;
        }

        .reporte-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .badge {
            background: rgba(255, 255, 255, 0.18);
            padding: 10px 14px;
            border-radius: 999px;
            font-weight: 600;
            white-space: nowrap;
        }

        .resumen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .resumen-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 5px solid #2563eb;
            border-radius: 12px;
            padding: 12px 14px;
        }

        .resumen-card strong {
            display: block;
            font-size: 1.15rem;
            color: #0f172a;
            margin-top: 4px;
        }

        .resumen-card span {
            color: #64748b;
            font-size: 0.86rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 0.9rem;
        }

        th,
        td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            text-align: left;
        }

        th {
            background-color: #1d4ed8;
            color: white;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .acciones {
            text-align: right;
            margin-bottom: 15px;
        }

        button {
            padding: 10px 18px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 28px 12px;
            color: #64748b;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            margin-top: 16px;
        }
    </style>
</head>

<body onload="window.print()">
    <div class="reporte-container">
        <div class="acciones no-print">
            <button onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
        </div>
        <div class="reporte-header">
            <div>
                <h1><?= htmlspecialchars($tituloReporte) ?></h1>
                <p>Resumen claro y ejecutivo para análisis de ventas del periodo seleccionado.</p>
            </div>
            <div class="badge">Periodo: <?= htmlspecialchars($nombreMes) ?> <?= $anioSeleccionado ?></div>
        </div>
        <div class="resumen-grid">
            <div class="resumen-card">
                <span>Total de ventas</span>
                <strong>$<?= number_format($totalVentas, 2) ?></strong>
            </div>
            <div class="resumen-card">
                <span>Ventas</span>
                <strong><?= count($ventas) ?></strong>
            </div>
            <div class="resumen-card">
                <span>Sucursal</span>
                <strong><?= htmlspecialchars($filtroSucursal ?: 'Todas') ?></strong>
            </div>
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
                            <td><?= htmlspecialchars($v['forma_pago']) ?></td>
                            <td><strong>$<?= number_format($v['total'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">No se encontraron ventas con los filtros aplicados para este mes y año.</div>
        <?php endif; ?>
    </div>
</body>

</html>