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
        // Dos opciones de consulta:
        // 1. Obtener marcas por repuesto
        // 2. Obtener repuestos por marca
        
        if (isset($_GET['repuesto_id'])) {
            // Opción 1: Obtener marcas de un repuesto específico
            $repuesto_id = intval($_GET['repuesto_id']);
            
            $sql = "SELECT m.* 
                   FROM manufacturers m
                   JOIN repuestos_marcas rm ON m.id = rm.id_marca
                   WHERE rm.id_repuesto = ? AND m.is_active = 1
                   ORDER BY m.name";
                   
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $repuesto_id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            $marcas = [];
            while ($fila = $resultado->fetch_assoc()) {
                $marcas[] = $fila;
            }
            
            responderJSON(['marcas' => $marcas]);
            
        } elseif (isset($_GET['marca_id'])) {
            // Opción 2: Obtener repuestos de una marca específica
            $marca_id = intval($_GET['marca_id']);
            
            $sql = "SELECT r.id_repuesto, r.str_referencia, r.nombre, r.descripcion,
                           r.precio, r.stock, r.id_categoria, r.id_fabricante, r.subcategoria,
                           c.nombre as categoria_nombre,
                           m.name as fabricante_nombre,
                           s.nombre as subcategoria_nombre
                    FROM repuestos r
                    JOIN repuestos_marcas rm ON r.id_repuesto = rm.id_repuesto
                    LEFT JOIN categorias c ON r.id_categoria = c.id
                    LEFT JOIN manufacturers m ON r.id_fabricante = m.id
                    LEFT JOIN subcategorias s ON r.subcategoria = s.id
                    WHERE rm.id_marca = ?";
                   
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $marca_id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            $repuestos = [];
            while ($fila = $resultado->fetch_assoc()) {
                // URL directa al endpoint de imágenes
                $fila['imagen_url'] = "http://localhost:8080/repuestos-api/get_image.php?id=" . $fila['id_repuesto'];
                $repuestos[] = $fila;
            }
            
            responderJSON(['repuestos' => $repuestos]);
            
        } else {
            // Si no se proporciona ID, mostrar todas las relaciones
            $sql = "SELECT rm.id, rm.id_repuesto, rm.id_marca, 
                          r.nombre as repuesto_nombre, 
                          m.name as marca_nombre
                   FROM repuestos_marcas rm
                   JOIN repuestos r ON rm.id_repuesto = r.id_repuesto
                   JOIN manufacturers m ON rm.id_marca = m.id
                   ORDER BY r.nombre, m.name";
                   
            $resultado = $conexion->query($sql);
            
            $relaciones = [];
            while ($fila = $resultado->fetch_assoc()) {
                $relaciones[] = $fila;
            }
            
            responderJSON(['relaciones' => $relaciones]);
        }
        break;
        
    case 'POST':
        // Crear una nueva relación entre repuesto y marca
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($datos['id_repuesto']) || !isset($datos['id_marca'])) {
            responderJSON(['error' => 'Se requieren id_repuesto e id_marca'], 400);
        }
        
        $id_repuesto = intval($datos['id_repuesto']);
        $id_marca = intval($datos['id_marca']);
        
        // Verificar que tanto el repuesto como la marca existen
        $sql_verificar = "SELECT 
                         (SELECT COUNT(*) FROM repuestos WHERE id_repuesto = ?) as repuesto_existe,
                         (SELECT COUNT(*) FROM manufacturers WHERE id = ?) as marca_existe";
                         
        $stmt = $conexion->prepare($sql_verificar);
        $stmt->bind_param("ii", $id_repuesto, $id_marca);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $existencia = $resultado->fetch_assoc();
        
        if ($existencia['repuesto_existe'] == 0) {
            responderJSON(['error' => 'El repuesto especificado no existe'], 400);
        }
        
        if ($existencia['marca_existe'] == 0) {
            responderJSON(['error' => 'La marca especificada no existe'], 400);
        }
        
        // Verificar si la relación ya existe
        $sql_duplicado = "SELECT COUNT(*) as existe FROM repuestos_marcas 
                         WHERE id_repuesto = ? AND id_marca = ?";
                         
        $stmt = $conexion->prepare($sql_duplicado);
        $stmt->bind_param("ii", $id_repuesto, $id_marca);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $duplicado = $resultado->fetch_assoc();
        
        if ($duplicado['existe'] > 0) {
            responderJSON(['error' => 'La relación entre este repuesto y marca ya existe'], 409);
            break;
        }
        
        // Insertar la nueva relación
        $sql_insertar = "INSERT INTO repuestos_marcas (id_repuesto, id_marca) VALUES (?, ?)";
        $stmt = $conexion->prepare($sql_insertar);
        $stmt->bind_param("ii", $id_repuesto, $id_marca);
        
        if ($stmt->execute()) {
            $id = $conexion->insert_id;
            responderJSON(['mensaje' => 'Relación creada con éxito', 'id' => $id], 201);
        } else {
            responderJSON(['error' => 'Error al crear la relación: ' . $stmt->error], 500);
        }
        break;
        
    case 'DELETE':
        // Eliminar una relación
        // Se puede eliminar por ID específico o por la combinación de repuesto y marca
        
        if (isset($_GET['id'])) {
            // Eliminar por ID específico
            $id = intval($_GET['id']);
            $sql = "DELETE FROM repuestos_marcas WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id);
        } elseif (isset($_GET['repuesto_id']) && isset($_GET['marca_id'])) {
            // Eliminar por la combinación de repuesto y marca
            $repuesto_id = intval($_GET['repuesto_id']);
            $marca_id = intval($_GET['marca_id']);
            $sql = "DELETE FROM repuestos_marcas WHERE id_repuesto = ? AND id_marca = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $repuesto_id, $marca_id);
        } else {
            responderJSON(['error' => 'Se requiere id o la combinación de repuesto_id y marca_id'], 400);
            break;
        }
        
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            responderJSON(['mensaje' => 'Relación eliminada con éxito']);
        } else {
            responderJSON(['mensaje' => 'No se encontró la relación'], 404);
        }
        break;
        
    default:
        responderJSON(['error' => 'Método no permitido'], 405);
}

$conexion->close();