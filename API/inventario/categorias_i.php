<?php
// categorias.php

require_once 'config_1.php';
// Incluye el middleware de autenticación

// Aplicar configuración CORS y encabezados de respuesta
configurarCORS();

// Verificar el token de autenticación. Si el token es inválido,
// la función autenticar() enviará la respuesta de error y terminará la ejecución.


// Conectar a la base de datos
$conexion = conectarDB();
$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        // Parámetros para la paginación
        $pagina = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limite = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $offset = ($pagina - 1) * $limite;

        // Se elimina el filtro por activo para mostrar todas las categorías
        $sql = "SELECT * FROM categorias ORDER BY id LIMIT $limite OFFSET $offset";
        $resultado = $conexion->query($sql);

        if (!$resultado) {
            responderJSON(['error' => 'Error en la consulta: ' . $conexion->error], 500);
        }

        $categorias = [];
        while ($fila = $resultado->fetch_assoc()) {
            $categorias[] = $fila;
        }

        // Contar el total de categorías sin filtro por estado
        $totalSql = "SELECT COUNT(*) as total FROM categorias";
        $totalResult = $conexion->query($totalSql);
        $totalCategorias = $totalResult->fetch_assoc()['total'];

        responderJSON(['categorias' => $categorias, 'total' => $totalCategorias]);
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
            // Se espera un valor booleano (true/false) o 1/0 para actualizar el estado
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
        // Eliminar la categoría de forma física
        $id = isset($_GET['id']) ? $conexion->real_escape_string($_GET['id']) : null;
        
        if (!$id) {
            responderJSON(['error' => 'El ID de la categoría es obligatorio'], 400);
        }
        
        $sql = "DELETE FROM categorias WHERE id = $id";
        
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
?>
