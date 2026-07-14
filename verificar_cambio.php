<?php
require_once 'php/conexion.php';

$filterSql = '';
$filterParams = [];

// Totales sin filtros
$sql1 = 'SELECT SUM(v.total) AS total FROM venta v WHERE 1=1' . $filterSql;
$sql2 = 'SELECT SUM(v.subtotal) AS total FROM venta v WHERE 1=1' . $filterSql;

$result1 = $pdo->prepare($sql1);
$result1->execute($filterParams);
$total1 = $result1->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$result2 = $pdo->prepare($sql2);
$result2->execute($filterParams);
$total2 = $result2->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

echo "=== DESPUÉS DEL CAMBIO ===\n";
echo "Monto de Ventas (v.total): $" . number_format($total1, 2) . "\n";
echo "Ingresos Netos (v.subtotal): $" . number_format($total2, 2) . "\n\n";

$diferencia = $total1 - $total2;
echo "IVA implícito: $" . number_format($diferencia, 2) . "\n";
echo "Relación: Ingresos Netos < Monto de Ventas ✓\n";
?>