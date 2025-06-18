<?php
// repuestos.php

// Configuración de errores y encabezados
ini_set('display_errors', 1); // Cambiado a 1 para ver errores
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config.php';

// Función para limpiar datos
function limpiarDatos($data) {
    if (is_array($data)) {
        return array_map('limpiarDatos', $data);
    }
    return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
}

try {
    $conexion = conectarDB();
    if (!$conexion) {
        throw new Exception("Error de conexión a la base de datos");
    }

    $metodo = $_SERVER['REQUEST_METHOD'];

    switch ($metodo) {
        case 'GET':
            // Recibir filtros vía GET
            $filtros = [
                'id_categoria'   => isset($_GET['id_categoria']) ? intval($_GET['id_categoria']) : null,
                'id_subcategoria'=> isset($_GET['id_subcategoria']) ? intval($_GET['id_subcategoria']) : null,
                'id_fabricante'  => isset($_GET['id_fabricante']) ? intval($_GET['id_fabricante']) : null,
                'id_repuesto'    => isset($_GET['id_repuesto']) ? intval($_GET['id_repuesto']) : null,
                'page'           => isset($_GET['page']) ? intval($_GET['page']) : 1,
                'per_page'       => isset($_GET['per_page']) ? intval($_GET['per_page']) : 8
            ];

            // Construir la cláusula WHERE para los filtros
            $whereClause = " WHERE 1=1";
            
            // Aplicar filtros
            if ($filtros['id_categoria'] !== null) {
                $whereClause .= " AND r.id_categoria = " . $filtros['id_categoria'];
            }
            if ($filtros['id_subcategoria'] !== null) {
                $whereClause .= " AND r.subcategoria = " . $filtros['id_subcategoria'];
            }
            if ($filtros['id_fabricante'] !== null) {
                $id_fab = intval($filtros['id_fabricante']);
                $whereClause .= " AND (
                    r.id_fabricante = $id_fab OR
                    EXISTS (
                        SELECT 1 FROM repuestos_marcas rm 
                        WHERE rm.id_repuesto = r.id_repuesto AND rm.id_marca = $id_fab
                    )
                )";
            }
            if ($filtros['id_repuesto'] !== null) {
                $whereClause .= " AND r.id_repuesto = " . $filtros['id_repuesto'];
            }

            // Consulta SIN el campo imagen para hacerla más ligera
            $sql = "SELECT r.id_repuesto, r.str_referencia, r.nombre, r.descripcion,
                           r.precio, r.stock, r.id_categoria, r.id_fabricante, r.subcategoria,
                           c.nombre as categoria_nombre,
                           m.name as fabricante_nombre,
                           s.nombre as subcategoria_nombre
                    FROM repuestos r
                    LEFT JOIN categorias c ON r.id_categoria = c.id
                    LEFT JOIN manufacturers m ON r.id_fabricante = m.id
                    LEFT JOIN subcategorias s ON r.subcategoria = s.id"
                    . $whereClause;
            
            // Consulta para contar el total de registros con filtros aplicados
            $sqlCount = "SELECT COUNT(*) as total FROM repuestos r" . $whereClause;
            
            // Paginación
            $offset = ($filtros['page'] - 1) * $filtros['per_page'];
            $sqlFinal = $sql . " LIMIT " . $offset . ", " . $filtros['per_page'];

            $resultado = $conexion->query($sqlFinal);
            if (!$resultado) {
                throw new Exception("Error en consulta: " . $conexion->error);
            }

            $repuestos = [];
            while ($fila = $resultado->fetch_assoc()) {
                $fila = limpiarDatos($fila);
                // URL directa al endpoint de imágenes
                $fila['imagen_url'] = "http://localhost:8080/repuestos-api/get_image.php?id=" . $fila['id_repuesto'];

                if (isset($fila['id_repuesto'])) {
                    $sql_marcas = "SELECT m.id, m.name, m.short_name, m.logo_path 
                                 FROM manufacturers m
                                 JOIN repuestos_marcas rm ON m.id = rm.id_marca
                                 WHERE rm.id_repuesto = ? AND m.is_active = 1";
                    
                    $stmt_marcas = $conexion->prepare($sql_marcas);
                    $repuesto_id = $fila['id_repuesto'];
                    $stmt_marcas->bind_param("i", $repuesto_id);
                    $stmt_marcas->execute();
                    $resultado_marcas = $stmt_marcas->get_result();
                    
                    $marcas_compatibles = [];
                    while ($marca = $resultado_marcas->fetch_assoc()) {
                        $marcas_compatibles[] = $marca;
                    }
                    
                    $fila['marcas_compatibles'] = $marcas_compatibles;
                    $stmt_marcas->close();
                }

                $repuestos[] = $fila;
            }

            // Obtener total filtrado para paginación
            $totalQuery = $conexion->query($sqlCount);
            $total = $totalQuery->fetch_assoc()['total'];

            echo json_encode([
                'success' => true,
                'count'   => count($repuestos),
                'total'   => $total,
                'data'    => $repuestos
            ]);
            break;

        case 'POST':
            // Para insertar un nuevo repuesto con su imagen
            if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== 0) {
                throw new Exception("No se proporcionó una imagen válida");
            }

            // Validación básica de imagen
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $mimeType = mime_content_type($_FILES['imagen']['tmp_name']);

            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception("Tipo de archivo no permitido. Solo se aceptan JPEG, PNG y GIF.");
            }

            if ($_FILES['imagen']['size'] > 5242880) {
                throw new Exception("El tamaño de la imagen no debe exceder 5MB.");
            }

            $imagenBlob = file_get_contents($_FILES['imagen']['tmp_name']);

            // Obtener demás datos del formulario
            $referencia   = isset($_POST['str_referencia']) ? trim($_POST['str_referencia']) : null;
            $nombre       = isset($_POST['nombre']) ? trim($_POST['nombre']) : null;
            $descripcion  = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : null;
            $precio       = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
            $stock        = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
            $id_categoria = isset($_POST['id_categoria']) ? intval($_POST['id_categoria']) : null;
            $id_fabricante= isset($_POST['id_fabricante']) ? intval($_POST['id_fabricante']) : null;
            $subcategoria = isset($_POST['subcategoria']) ? intval($_POST['subcategoria']) : null;

            if (!$referencia || !$nombre || $precio <= 0 || !$id_categoria || !$id_fabricante) {
                throw new Exception("Faltan campos obligatorios");
            }

            $stmt = $conexion->prepare("INSERT INTO repuestos 
                (str_referencia, nombre, imagen, descripcion, precio, stock, id_categoria, id_fabricante, subcategoria)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta: " . $conexion->error);
            }

            $stmt->bind_param(
                "ssssdiiii", 
                $referencia, 
                $nombre, 
                $imagenBlob, 
                $descripcion, 
                $precio, 
                $stock, 
                $id_categoria, 
                $id_fabricante, 
                $subcategoria
            );

            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }

            $id_repuesto = $conexion->insert_id;
            $stmt->close();

            if (isset($_POST['marcas_compatibles']) && !empty($_POST['marcas_compatibles'])) {
                $marcas_ids = json_decode($_POST['marcas_compatibles'], true);
                
                if (is_array($marcas_ids) && !empty($marcas_ids)) {
                    $stmt_marca = $conexion->prepare("INSERT INTO repuestos_marcas (id_repuesto, id_marca) VALUES (?, ?)");
                    
                    foreach ($marcas_ids as $marca_id) {
                        $stmt_marca->bind_param("ii", $id_repuesto, $marca_id);
                        $stmt_marca->execute();
                    }
                    
                    $stmt_marca->close();
                }
            }

            echo json_encode([
                'success'   => true,
                'message'   => 'Repuesto creado correctamente',
                'id_repuesto' => $id_repuesto
            ]);
            break;

        case 'OPTIONS':
            http_response_code(200);
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Error en el servidor',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conexion)) $conexion->close();
}
?>