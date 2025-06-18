<?php
// categorias.php
require_once 'config.php';

// Aplicar configuración CORS
configurarCORS();

$metodo = $_SERVER['REQUEST_METHOD'];
$conexion = conectarDB();

switch ($metodo) {
    case 'GET':
        // Obtener todas las categorías
        $sql = "SELECT * FROM categorias WHERE activo = 1 ORDER BY id";
        $resultado = $conexion->query($sql);
        
        if (!$resultado) {
            responderJSON(['error' => 'Error en la consulta: ' . $conexion->error], 500);
        }
        
        $categorias = [];
        while ($fila = $resultado->fetch_assoc()) {
            $categorias[] = $fila;
        }
        
        responderJSON(['categorias' => $categorias]);
        break;
        
    case 'POST':
        // Crear una nueva categoría
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($datos['nombre']) || empty($datos['nombre'])) {
            responderJSON(['error' => 'El nombre de la categoría es obligatorio'], 400);
        }
        
        $nombre = $conexion->real_escape_string($datos['nombre']);
        $descripcion = isset($datos['descripcion']) ? $conexion->real_escape_string($datos['descripcion']) : '';
        
        $sql = "INSERT INTO categorias (nombre, descripcion) VALUES ('$nombre', '$descripcion')";
        
        if ($conexion->query($sql)) {
            $id = $conexion->insert_id;
            responderJSON(['mensaje' => 'Categoría creada con éxito', 'id' => $id], 201);
        } else {
            responderJSON(['error' => 'Error al crear la categoría: ' . $conexion->error], 500);
        }
        break;
    
    case 'PUT':
        // Actualizar una categoría existente
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($datos['id']) || empty($datos['id'])) {
            responderJSON(['error' => 'El ID de la categoría es obligatorio'], 400);
        }
        
        $id = $conexion->real_escape_string($datos['id']);
        $actualizaciones = [];
        
        if (isset($datos['nombre']) && !empty($datos['nombre'])) {
            $nombre = $conexion->real_escape_string($datos['nombre']);
            $actualizaciones[] = "nombre = '$nombre'";
        }
        
        if (isset($datos['descripcion'])) {
            $descripcion = $conexion->real_escape_string($datos['descripcion']);
            $actualizaciones[] = "descripcion = '$descripcion'";
        }
        
        if (isset($datos['activo'])) {
            $activo = $datos['activo'] ? 1 : 0;
            $actualizaciones[] = "activo = $activo";
        }
        
        if (empty($actualizaciones)) {
            responderJSON(['error' => 'No hay datos para actualizar'], 400);
        }
        
        $actualizacionesStr = implode(', ', $actualizaciones);
        $sql = "UPDATE categorias SET $actualizacionesStr WHERE id = $id";
        
        if ($conexion->query($sql)) {
            if ($conexion->affected_rows > 0) {
                responderJSON(['mensaje' => 'Categoría actualizada con éxito']);
            } else {
                responderJSON(['mensaje' => 'No se encontró la categoría o no hubo cambios'], 404);
            }
        } else {
            responderJSON(['error' => 'Error al actualizar la categoría: ' . $conexion->error], 500);
        }
        break;
        
    case 'DELETE':
        // Eliminar una categoría
        $id = isset($_GET['id']) ? $conexion->real_escape_string($_GET['id']) : null;
        
        if (!$id) {
            responderJSON(['error' => 'El ID de la categoría es obligatorio'], 400);
        }
        
        // Opción 2: Eliminación lógica (recomendada)
        $sql = "UPDATE categorias SET activo = 0 WHERE id = $id";
        
        if ($conexion->query($sql)) {
            if ($conexion->affected_rows > 0) {
                responderJSON(['mensaje' => 'Categoría eliminada con éxito']);
            } else {
                responderJSON(['mensaje' => 'No se encontró la categoría'], 404);
            }
        } else {
            responderJSON(['error' => 'Error al eliminar la categoría: ' . $conexion->error], 500);
        }
        break;
        
    default:
        responderJSON(['error' => 'Método no permitido'], 405);
}

$conexion->close();