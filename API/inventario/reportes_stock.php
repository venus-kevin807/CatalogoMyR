<?php
// reportes_stock.php
require_once 'config_1.php';


configurarCORS();

$conexion = conectarDB();
if (!$conexion) {
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión a la base de datos"]);
    exit;
}

// Se puede enviar un parámetro "threshold" por GET; si no se envía se usa 5 por defecto.
$threshold = isset($_GET['threshold']) ? intval($_GET['threshold']) : 5;

$sql = "SELECT id_repuesto, nombre, stock FROM repuestos WHERE stock < ?";
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Error en la preparación de la consulta"]);
    exit;
}
$stmt->bind_param("i", $threshold);
$stmt->execute();
$result = $stmt->get_result();

$productos = [];
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

echo json_encode(["productos" => $productos]);

$stmt->close();
$conexion->close();
?>
