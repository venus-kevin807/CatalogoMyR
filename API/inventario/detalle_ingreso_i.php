<?php
require_once 'config_1.php';
  // Incluye el middleware de autenticación

// Aplicar configuración CORS y encabezados de respuesta
configurarCORS();

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

if (!isset($_GET['id_ingreso']) || intval($_GET['id_ingreso']) <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID del ingreso no proporcionado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With");
    exit(0);
}
$id_ingreso = intval($_GET['id_ingreso']);

try {
    $conexion = conectarDB();
    if (!$conexion) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Consulta para traer el detalle del ingreso
    // Se unen las tablas detalle_ingreso y repuestos para obtener el nombre del producto
    $sql = "SELECT di.id_detalle, di.id_repuesto, di.cantidad, 
                   di.precio_unitario AS precio_compra, di.precio_sugerido, 
                   r.nombre AS producto_nombre
            FROM detalle_ingreso di
            JOIN repuestos r ON di.id_repuesto = r.id_repuesto
            WHERE di.id_ingreso = ?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparando la consulta: " . $conexion->error);
    }
    $stmt->bind_param("i", $id_ingreso);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $detalle = [];
    while ($item = $resultado->fetch_assoc()) {
        $detalle[] = $item;
    }
    echo json_encode(['detalle' => $detalle]);
    $stmt->close();
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error'   => 'Error en el servidor',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conexion)) {
        $conexion->close();
    }
}
?>
