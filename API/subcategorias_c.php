<?php
// subcategorias.php
require_once 'config.php';

// Aplicar configuración CORS
configurarCORS();

$metodo = $_SERVER['REQUEST_METHOD'];
$conexion = conectarDB();

switch ($metodo) {
    case 'GET':
        // Obtener subcategorías
        $categoria_id = isset($_GET['categoria_id']) ? $conexion->real_escape_string($_GET['categoria_id']) : null;
        
        if ($categoria_id) {
            // Obtener subcategorías de una categoría específica
            $sql = "SELECT * FROM subcategorias WHERE categoria_id = $categoria_id AND activo = 1 ORDER BY nombre";
        } else {
            // Obtener todas las subcategorías
            $sql = "SELECT s.*, c.nombre as categoria_nombre 
                   FROM subcategorias s
                   JOIN categorias c ON s.categoria_id = c.id
                   WHERE s.activo = 1 
                   ORDER BY c.nombre, s.nombre";
        }
        
        $resultado = $conexion->query($sql);
        
        if (!$resultado) {
            responderJSON(['error' => 'Error en la consulta: ' . $conexion->error], 500);
        }
        
        $subcategorias = [];
        while ($fila = $resultado->fetch_assoc()) {
            $subcategorias[] = $fila;
        }
        
        responderJSON(['subcategorias' => $subcategorias]);
        break;
        
    case 'POST':
        // Crear una nueva subcategoría
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($datos['nombre']) || empty($datos['nombre'])) {
            responderJSON(['error' => 'El nombre de la subcategoría es obligatorio'], 400);
        }
        
        if (!isset($datos['categoria_id']) || empty($datos['categoria_id'])) {
            responderJSON(['error' => 'El ID de la categoría es obligatorio'], 400);
        }
        
        $nombre = $conexion->real_escape_string($datos['nombre']);
        $categoria_id = $conexion->real_escape_string($datos['categoria_id']);
        $descripcion = isset($datos['descripcion']) ? $conexion->real_escape_string($datos['descripcion']) : '';
        
        // Verificar que la categoría existe
        $verificar = $conexion->query("SELECT id FROM categorias WHERE id = $categoria_id AND activo = 1");
        if ($verificar->num_rows === 0) {
            responderJSON(['error' => 'La categoría especificada no existe o está inactiva'], 400);
        }
        
        $sql = "INSERT INTO subcategorias (categoria_id, nombre, descripcion) 
                VALUES ($categoria_id, '$nombre', '$descripcion')";
        
        if ($conexion->query($sql)) {
            $id = $conexion->insert_id;
            responderJSON(['mensaje' => 'Subcategoría creada con éxito', 'id' => $id], 201);
        } else {
            responderJSON(['error' => 'Error al crear la subcategoría: ' . $conexion->error], 500);
        }
        break;
    
    case 'PUT':
        // Actualizar una subcategoría existente
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($datos['id']) || empty($datos['id'])) {
            responderJSON(['error' => 'El ID de la subcategoría es obligatorio'], 400);
        }
        
        $id = $conexion->real_escape_string($datos['id']);
        $actualizaciones = [];
        
        if (isset($datos['nombre']) && !empty($datos['nombre'])) {
            $nombre = $conexion->real_escape_string($datos['nombre']);
            $actualizaciones[] = "nombre = '$nombre'";
        }
        
        if (isset($datos['categoria_id']) && !empty($datos['categoria_id'])) {
            $categoria_id = $conexion->real_escape_string($datos['categoria_id']);
            
            // Verificar que la categoría existe
            $verificar = $conexion->query("SELECT id FROM categorias WHERE id = $categoria_id AND activo = 1");
            if ($verificar->num_rows === 0) {
                responderJSON(['error' => 'La categoría especificada no existe o está inactiva'], 400);
            }
            
            $actualizaciones[] = "categoria_id = $categoria_id";
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
        $sql = "UPDATE subcategorias SET $actualizacionesStr WHERE id = $id";
        
        if ($conexion->query($sql)) {
            if ($conexion->affected_rows > 0) {
                responderJSON(['mensaje' => 'Subcategoría actualizada con éxito']);
            } else {
                responderJSON(['mensaje' => 'No se encontró la subcategoría o no hubo cambios'], 404);
            }
        } else {
            responderJSON(['error' => 'Error al actualizar la subcategoría: ' . $conexion->error], 500);
        }
        break;
        
    case 'DELETE':
        // Eliminar una subcategoría
        $id = isset($_GET['id']) ? $conexion->real_escape_string($_GET['id']) : null;
        
        if (!$id) {
            responderJSON(['error' => 'El ID de la subcategoría es obligatorio'], 400);
        }
        
        // Opción 2: Eliminación lógica (recomendada)
        $sql = "UPDATE subcategorias SET activo = 0 WHERE id = $id";
        
        if ($conexion->query($sql)) {
            if ($conexion->affected_rows > 0) {
                responderJSON(['mensaje' => 'Subcategoría eliminada con éxito']);
            } else {
                responderJSON(['mensaje' => 'No se encontró la subcategoría'], 404);
            }
        } else {
            responderJSON(['error' => 'Error al eliminar la subcategoría: ' . $conexion->error], 500);
        }
        break;
        
    default:
        responderJSON(['error' => 'Método no permitido'], 405);
}

$conexion->close();