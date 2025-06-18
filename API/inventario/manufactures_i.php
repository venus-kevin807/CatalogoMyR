<?php
// manufacturers_i.php

require_once 'config_1.php';

// Aplicar configuración CORS y encabezados de respuesta
configurarCORS();

// Verificar el token de autenticación. Si el token es inválido,
// la función autenticar() enviará la respuesta de error y terminará la ejecución.

// Conectar a la base de datos
$conexion = conectarDB();
$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        // Parámetros de paginación y búsqueda
        $pagina   = isset($_GET['page'])  ? (int)$_GET['page'] : 1;
        $limite   = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $offset   = ($pagina - 1) * $limite;
        $busqueda = isset($_GET['search']) ? $conexion->real_escape_string($_GET['search']) : '';

        // Se retornan todas las marcas (activas e inactivas)
        $sql = "SELECT * FROM manufacturers WHERE 1=1";
        if ($busqueda !== '') {
            $sql .= " AND (name LIKE '%$busqueda%' OR short_name LIKE '%$busqueda%' OR description LIKE '%$busqueda%')";
        }
        $sql .= " ORDER BY id LIMIT $limite OFFSET $offset";

        $resultado = $conexion->query($sql);
        if (!$resultado) {
            responderJSON(['error' => 'Error en la consulta: ' . $conexion->error], 500);
        }
        $fabricantes = [];
        while ($fila = $resultado->fetch_assoc()) {
            $fabricantes[] = $fila;
        }

        // Consulta para contar el total de registros (sin filtrar por activo)
        $totalSql = "SELECT COUNT(*) as total FROM manufacturers WHERE 1=1";
        if ($busqueda !== '') {
            $totalSql .= " AND (name LIKE '%$busqueda%' OR short_name LIKE '%$busqueda%' OR description LIKE '%$busqueda%')";
        }
        $totalResult = $conexion->query($totalSql);
        $totalRow = $totalResult->fetch_assoc();
        $totalFabricantes = $totalRow['total'];

        // Si se solicita incluir las categorías asociadas a cada fabricante
        if (isset($_GET['incluir_categorias']) && $_GET['incluir_categorias'] == 'true') {
            foreach ($fabricantes as &$fabricante) {
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
        responderJSON(['manufacturers' => $fabricantes, 'total' => $totalFabricantes]);
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
        // Se agrega el valor de is_active (checkbox); si no se envía, se asigna true (1)
        $is_active = isset($datos['is_active']) ? ($datos['is_active'] ? 1 : 0) : 1;
    
        $sql = "INSERT INTO manufacturers (name, short_name, description, is_active) 
                VALUES ('$name', '$short_name', '$description', $is_active)";
    
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
    
        if (isset($datos['is_active'])) {
            // Actualiza el valor basado en el checkbox (true/false)
            $is_active = $datos['is_active'] ? 1 : 0;
            $actualizaciones[] = "is_active = $is_active";
        }
    
        if (empty($actualizaciones)) {
            responderJSON(['error' => 'No hay datos para actualizar'], 400);
        }
    
        $actualizacionesStr = implode(', ', $actualizaciones);
        $sql = "UPDATE manufacturers SET $actualizacionesStr WHERE id = $id";
    
        if ($conexion->query($sql)) {
            responderJSON(['mensaje' => 'Fabricante actualizado con éxito']);
        } else {
            responderJSON(['error' => 'Error al actualizar el fabricante: ' . $conexion->error], 500);
        }
        break;
        
    case 'DELETE':
        // Eliminación física del fabricante para quitarlo de la base de datos
        $id = isset($_GET['id']) ? $conexion->real_escape_string($_GET['id']) : null;
        
        if (!$id) {
            responderJSON(['error' => 'El ID del fabricante es obligatorio'], 400);
        }
        
        $sql = "DELETE FROM manufacturers WHERE id = $id";
        
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
?>
