<?php
require_once 'php/conexion.php';

// Totales sin filtros
$sql1 = 'SELECT SUM(v.total) AS total FROM venta v';
$sql2 = 'SELECT SUM((dv.precio * dv.cantidad) - dv.descuento) AS total FROM detalle_venta dv';
$sqlIva = 'SELECT SUM(iva) AS total_iva FROM venta';

$result1 = $pdo->query($sql1)->fetch(PDO::FETCH_ASSOC);
$result2 = $pdo->query($sql2)->fetch(PDO::FETCH_ASSOC);
$resultIva = $pdo->query($sqlIva)->fetch(PDO::FETCH_ASSOC);

$total1 = $result1['total'] ?? 0;
$total2 = $result2['total'] ?? 0;
$totalIva = $resultIva['total_iva'] ?? 0;
$diferencia = $total2 - $total1;

echo "=== ANÁLISIS DE DIFERENCIA ===\n";
echo "Ventas Totales (v.total): $" . number_format($total1, 2) . "\n";
echo "Ingresos Netos (detalle): $" . number_format($total2, 2) . "\n";
echo "Diferencia: $" . number_format($diferencia, 2) . "\n\n";

echo "Total IVA registrado: $" . number_format($totalIva, 2) . "\n\n";

// Ver si la diferencia coincide con IVA
if (abs($diferencia - $totalIva) < 1) {
    echo "✓ LA DIFERENCIA ES EL IVA\n";
    echo "v.total = detalle + IVA\n";
} else {
    echo "✗ La diferencia NO es solo el IVA\n";
    echo "Diferencia de diferencia: $" . number_format($diferencia - $totalIva, 2) . "\n";
}
?>