<?php
// manufacturers.php
require_once 'config.php';

// Aplicar configuración CORS
configurarCORS();

$metodo = $_SERVER['REQUEST_METHOD'];
$conexion = conectarDB();

switch ($metodo) {
    case 'GET':
        // Obtener todos los fabricantes activos
        $sql = "SELECT * FROM manufacturers WHERE is_active = 1 ORDER BY id";
        $resultado = $conexion->query($sql);
        
        if (!$resultado) {
            responderJSON(['error' => 'Error en la consulta: ' . $conexion->error], 500);
        }
        
        $fabricantes = [];
        while ($fila = $resultado->fetch_assoc()) {
            $fabricantes[] = $fila;
        }

        if (isset($_GET['incluir_categorias']) && $_GET['incluir_categorias'] == 'true') {
            foreach ($fabricantes as &$fabricante) {
                // Obtener categorías asociadas a este fabricante
                $sql_categorias = "SELECT c.id, c.nombre, c.descripcion 
                                  FROM categorias c
                                  JOIN fabricante_categoria fc ON c.id = fc.id_categoria
                                  WHERE fc.id_fabricante = ? AND c.activo = 1
                                  ORDER BY c.nombre";
                                  
                $stmt = $conexion->prepare($sql_categorias);
                $fabricante_id = $fabricante['id'];
                $stmt->bind_param("i", $fabricante_id);
                $stmt->execute();
                $resultado_cats = $stmt->get_result();
                
                $categorias = [];
                while ($cat = $resultado_cats->fetch_assoc()) {
                    $categorias[] = $cat;
                }
                
                $fabricante['categorias'] = $categorias;
                $stmt->close();
            }
        }
        
        responderJSON(['manufacturers' => $fabricantes]);
        break;
        
    case 'POST':
        // Crear un nuevo fabricante
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($datos['name']) || empty($datos['name'])) {
            responderJSON(['error' => 'El nombre del fabricante es obligatorio'], 400);
        }
        
        $name = $conexion->real_escape_string($datos['name']);
        $short_name = isset($datos['short_name']) ? $conexion->real_escape_string($datos['short_name']) : '';
        $description = isset($datos['description']) ? $conexion->real_escape_string($datos['description']) : '';
        $logo_path = isset($datos['logo_path']) ? $conexion->real_escape_string($datos['logo_path']) : '';
        
        $sql = "INSERT INTO manufacturers (name, short_name, description, logo_path) 
                VALUES ('$name', '$short_name', '$description', '$logo_path')";
        
        if ($conexion->query($sql)) {
            $id = $conexion->insert_id;
            responderJSON(['mensaje' => 'Fabricante creado con éxito', 'id' => $id], 201);
        } else {
            responderJSON(['error' => 'Error al crear el fabricante: ' . $conexion->error], 500);
        }
        break;
    
    case 'PUT':
        // Actualizar un fabricante existente
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($datos['id']) || empty($datos['id'])) {
            responderJSON(['error' => 'El ID del fabricante es obligatorio'], 400);
        }
        
        $id = $conexion->real_escape_string($datos['id']);
        $actualizaciones = [];
        
        if (isset($datos['name']) && !empty($datos['name'])) {
            $name = $conexion->real_escape_string($datos['name']);
            $actualizaciones[] = "name = '$name'";
        }
        
        if (isset($datos['short_name'])) {
            $short_name = $conexion->real_escape_string($datos['short_name']);
            $actualizaciones[] = "short_name = '$short_name'";
        }
        
        if (isset($datos['description'])) {
            $description = $conexion->real_escape_string($datos['description']);
            $actualizaciones[] = "description = '$description'";
        }
        
        if (isset($datos['logo_path'])) {
            $logo_path = $conexion->real_escape_string($datos['logo_path']);
            $actualizaciones[] = "logo_path = '$logo_path'";
        }
        
        if (isset($datos['is_active'])) {
            $is_active = $datos['is_active'] ? 1 : 0;
            $actualizaciones[] = "is_active = $is_active";
        }
        
        if (empty($actualizaciones)) {
            responderJSON(['error' => 'No hay datos para actualizar'], 400);
        }
        
        $actualizacionesStr = implode(', ', $actualizaciones);
        $sql = "UPDATE manufacturers SET $actualizacionesStr WHERE id = $id";
        
        if ($conexion->query($sql)) {
            if ($conexion->affected_rows > 0) {
                responderJSON(['mensaje' => 'Fabricante actualizado con éxito']);
            } else {
                responderJSON(['mensaje' => 'No se encontró el fabricante o no hubo cambios'], 404);
            }
        } else {
            responderJSON(['error' => 'Error al actualizar el fabricante: ' . $conexion->error], 500);
        }
        break;
        
    case 'DELETE':
        // Eliminar un fabricante (eliminación lógica)
        $id = isset($_GET['id']) ? $conexion->real_escape_string($_GET['id']) : null;
        
        if (!$id) {
            responderJSON(['error' => 'El ID del fabricante es obligatorio'], 400);
        }
        
        $sql = "UPDATE manufacturers SET is_active = 0 WHERE id = $id";
        
        if ($conexion->query($sql)) {
            if ($conexion->affected_rows > 0) {
                responderJSON(['mensaje' => 'Fabricante eliminado con éxito']);
            } else {
                responderJSON(['mensaje' => 'No se encontró el fabricante'], 404);
            }
        } else {
            responderJSON(['error' => 'Error al eliminar el fabricante: ' . $conexion->error], 500);
        }
        break;
        
    default:
        responderJSON(['error' => 'Método no permitido'], 405);
}

$conexion->close();