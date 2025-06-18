<?php
require_once 'config_1.php';

// Aplicar configuración CORS y encabezados de respuesta
configurarCORS();

// Verificar el token de autenticación. Si el token es inválido,
// la función autenticar() enviará la respuesta de error y terminará la ejecución.

$metodo = $_SERVER['REQUEST_METHOD'];
$conexion = conectarDB();


switch ($metodo) {
    case 'GET':
        // Parámetros de paginación
        $pagina     = isset($_GET['page'])  ? (int)$_GET['page'] : 1;
        $limite     = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $offset     = ($pagina - 1) * $limite;
        $busqueda   = isset($_GET['search']) ? $conexion->real_escape_string($_GET['search']) : '';
        $categoria_id = isset($_GET['categoria_id']) ? $conexion->real_escape_string($_GET['categoria_id']) : null;
        
        if ($categoria_id) {
            // Consulta para obtener subcategorías de una categoría específica (independientemente del estado)
            $sql = "SELECT * FROM subcategorias 
                    WHERE categoria_id = $categoria_id";
            if ($busqueda !== '') {
                $sql .= " AND (nombre LIKE '%$busqueda%' OR descripcion LIKE '%$busqueda%')";
            }
            $sql .= " ORDER BY nombre LIMIT $limite OFFSET $offset";
            
            $resultado = $conexion->query($sql);
            if (!$resultado) {
                responderJSON(['error' => 'Error en la consulta: ' . $conexion->error], 500);
            }
            
            $subcategorias = [];
            while ($fila = $resultado->fetch_assoc()) {
                $subcategorias[] = $fila;
            }
            
            // Contar total según el filtro aplicado
            $totalSql = "SELECT COUNT(*) as total FROM subcategorias WHERE categoria_id = $categoria_id";
            if ($busqueda !== '') {
                $totalSql .= " AND (nombre LIKE '%$busqueda%' OR descripcion LIKE '%$busqueda%')";
            }
            $totalResult     = $conexion->query($totalSql);
            $totalSubcategorias = $totalResult->fetch_assoc()['total'];
            
            responderJSON(['subcategorias' => $subcategorias, 'total' => $totalSubcategorias]);
        } else {
            // Consulta para obtener todas las subcategorías con join a categorías (no se filtra por estado)
            $sql = "SELECT s.*, c.nombre as categoria_nombre 
                    FROM subcategorias s
                    JOIN categorias c ON s.categoria_id = c.id
                    WHERE 1=1";
            if ($busqueda !== '') {
                $sql .= " AND (s.nombre LIKE '%$busqueda%' OR s.descripcion LIKE '%$busqueda%')";
            }
            $sql .= " ORDER BY c.nombre, s.nombre LIMIT $limite OFFSET $offset";
    
            $resultado = $conexion->query($sql);
            if (!$resultado) {
                responderJSON(['error' => 'Error en la consulta: ' . $conexion->error], 500);
            }
            
            $subcategorias = [];
            while ($fila = $resultado->fetch_assoc()) {
                $subcategorias[] = $fila;
            }
            
            // Total de registros (aplicando la búsqueda)
            $totalSql = "SELECT COUNT(*) as total FROM subcategorias WHERE 1=1";
            if ($busqueda !== '') {
                $totalSql .= " AND (nombre LIKE '%$busqueda%' OR descripcion LIKE '%$busqueda%')";
            }
            $totalResult = $conexion->query($totalSql);
            $totalSubcategorias = $totalResult->fetch_assoc()['total'];
            
            responderJSON(['subcategorias' => $subcategorias, 'total' => $totalSubcategorias]);
        }
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
        
        $nombre       = $conexion->real_escape_string($datos['nombre']);
        $categoria_id = $conexion->real_escape_string($datos['categoria_id']);
        $descripcion  = isset($datos['descripcion']) ? $conexion->real_escape_string($datos['descripcion']) : '';
        
        // Verificar que la categoría existe y está activa
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
            
            // Verificar que la categoría existe y está activa
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
            // Se espera que se envíe true o false (o 1/0) para actualizar el estado
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
        // Eliminar la subcategoría de forma física de la base de datos
        $id = isset($_GET['id']) ? $conexion->real_escape_string($_GET['id']) : null;
        
        if (!$id) {
            responderJSON(['error' => 'El ID de la subcategoría es obligatorio'], 400);
        }
        
        $sql = "DELETE FROM subcategorias WHERE id = $id";
        
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
?>