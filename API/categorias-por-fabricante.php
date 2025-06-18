<?php
// categorias-por-fabricante.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir archivo de configuración de base de datos
require_once 'config.php';

// Verificar la conexión a la base de datos
try {
    // Usar la función de conexión desde config.php
    $conexion = conectarDB();

    // Obtener el ID del fabricante desde la solicitud GET
    $manufacturer_id = isset($_GET['manufacturer_id']) ? intval($_GET['manufacturer_id']) : null;

    if (!$manufacturer_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Manufacturer ID is required']);
        exit;
    }

    // Preparar la consulta
    $query = "SELECT c.id, c.nombre, c.descripcion 
              FROM categorias c
              JOIN fabricante_categoria fc ON c.id = fc.id_categoria
              WHERE fc.id_fabricante = ?";

    // Preparar y ejecutar la sentencia
    $stmt = $conexion->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $conexion->error);
    }

    $stmt->bind_param("i", $manufacturer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Recoger categorías
    $categorias = [];
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }

    // Cerrar statement
    $stmt->close();

    // Devolver resultado
    echo json_encode(['categorias' => $categorias]);

} catch (Exception $e) {
    // Manejar cualquier error
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
    exit;
}