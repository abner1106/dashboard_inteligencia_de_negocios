<?php
require_once 'conexion.php';

$sql = "
    SELECT s.nombre AS sucursal, SUM(v.total) AS total_vendido
    FROM venta v
    JOIN sucursal s ON v.sucursal = s.nombre
    GROUP BY s.nombre
    ORDER BY total_vendido DESC
";

$datos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($datos, JSON_PRETTY_PRINT);