<?php
require_once 'config_1.php';

// Aplicar configuración CORS y encabezados de respuesta
configurarCORS();

// Verificar el token de autenticación. Si el token es inválido,
// la función autenticar() enviará la respuesta de error y terminará la ejecución.

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

if (!isset($_GET['id_venta']) || intval($_GET['id_venta']) <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de la venta no proporcionado']);
    exit;
}

$id_venta = intval($_GET['id_venta']);

try {
    $conexion = conectarDB();
    if (!$conexion) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Consulta para traer el detalle de la venta, uniendo con la tabla de repuestos para obtener el nombre del producto.
    $sql = "SELECT dv.id_detalle, dv.id_repuesto, dv.cantidad, dv.precio_unitario, r.nombre AS producto_nombre
            FROM detalle_venta dv
            JOIN repuestos r ON dv.id_repuesto = r.id_repuesto
            WHERE dv.id_venta = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_venta);
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
    echo json_encode(['error' => 'Error en el servidor', 'message' => $e->getMessage()]);
} finally {
    if (isset($conexion)) {
        $conexion->close();
    }
}
?>
