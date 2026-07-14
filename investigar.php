<?php
require_once 'php/conexion.php';

echo "=== ESTRUCTURA TABLA VENTA ===\n";
$stmt = $pdo->query('DESCRIBE venta');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    echo $col['Field'] . ' (' . $col['Type'] . ')\n';
}

echo "\n=== MUESTRA DE DATOS VENTA ===\n";
$stmt = $pdo->query('SELECT * FROM venta LIMIT 2');
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data, JSON_PRETTY_PRINT);

echo "\n\n=== MUESTRA DE DATOS DETALLE ===\n";
$stmt = $pdo->query('SELECT * FROM detalle_venta LIMIT 2');
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data, JSON_PRETTY_PRINT);
?>