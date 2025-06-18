<?php
// repuestos_i.php

// CONFIGURACIÓN DE ERRORES Y ENCABEZADOS
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config_1.php';

// Aplicar configuración CORS y encabezados de respuesta
configurarCORS();

// Verificar el token de autenticación. Si el token es inválido,
// la función autenticar() enviará la respuesta de error y terminará la ejecución.

/**
 * Función que limpia los datos asegurando codificación UTF-8.
 */
function limpiarDatos($data) {
    if (is_array($data)) {
        return array_map('limpiarDatos', $data);
    }
    return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
}

// Aplica la configuración CORS (esta función debe estar definida en tu config_1.php)
configurarCORS();

try {
    $conexion = conectarDB();
    if (!$conexion) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    $metodo = $_SERVER['REQUEST_METHOD'];
    
    switch ($metodo) {
        case 'GET':
            // --- OBTENER REPUESTOS CON FILTROS Y PAGINACIÓN ---
            // Se reciben parámetros opcionales: id_categoria, id_subcategoria, id_fabricante, id_repuesto, search, page, per_page
            $filtros = [
                'id_categoria'    => isset($_GET['id_categoria']) ? intval($_GET['id_categoria']) : null,
                'id_subcategoria' => isset($_GET['id_subcategoria']) ? intval($_GET['id_subcategoria']) : null,
                'id_fabricante'   => isset($_GET['id_fabricante']) ? intval($_GET['id_fabricante']) : null,
                'id_repuesto'     => isset($_GET['id_repuesto']) ? intval($_GET['id_repuesto']) : null,
                'search'          => isset($_GET['search']) ? trim($_GET['search']) : null,
                'page'            => isset($_GET['page']) ? intval($_GET['page']) : 1,
                'per_page'        => isset($_GET['per_page']) ? intval($_GET['per_page']) : 8
            ];
            
            $whereClause = " WHERE 1=1";
            if ($filtros['id_categoria'] !== null) {
                $whereClause .= " AND r.id_categoria = " . $filtros['id_categoria'];
            }
            if ($filtros['id_subcategoria'] !== null) {
                $whereClause .= " AND r.subcategoria = " . $filtros['id_subcategoria'];
            }
            if ($filtros['id_fabricante'] !== null) {
                $fab = intval($filtros['id_fabricante']);
                // Se incluye la condición directa o mediante relación en repuestos_marcas
                $whereClause .= " AND (
                    r.id_fabricante = $fab OR
                    EXISTS (
                        SELECT 1 FROM repuestos_marcas rm 
                        WHERE rm.id_repuesto = r.id_repuesto AND rm.id_marca = $fab
                    )
                )";
            }
            if ($filtros['id_repuesto'] !== null) {
                $whereClause .= " AND r.id_repuesto = " . $filtros['id_repuesto'];
            }
            if ($filtros['search']) {
                $search = $conexion->real_escape_string($filtros['search']);
                $whereClause .= " AND (r.nombre LIKE '%$search%' OR r.str_referencia LIKE '%$search%')";
            }
            
            $sql = "SELECT r.id_repuesto, r.str_referencia, r.nombre, r.descripcion,
            r.precio, r.stock, r.id_categoria, r.id_fabricante, r.subcategoria,
            c.nombre as categoria_nombre,
            m.name as fabricante_nombre,
            s.nombre as subcategoria_nombre,
            p.nombre as proveedor_nombre
     FROM repuestos r
     LEFT JOIN categorias c ON r.id_categoria = c.id
     LEFT JOIN manufacturers m ON r.id_fabricante = m.id
     LEFT JOIN subcategorias s ON r.subcategoria = s.id
     LEFT JOIN proveedores p ON r.id_proveedor = p.id"
     . $whereClause;
                    
            
            // Consulta para contar el total de registros (para paginación)
            $sqlCount = "SELECT COUNT(*) as total FROM repuestos r" . $whereClause;
            
            // Aplicar paginación
            $offset = ($filtros['page'] - 1) * $filtros['per_page'];
            $sqlFinal = $sql . " LIMIT " . $offset . ", " . $filtros['per_page'];
            
            $resultado = $conexion->query($sqlFinal);
            if (!$resultado) {
                throw new Exception("Error en consulta: " . $conexion->error);
            }
            
            $repuestos = [];
            while ($fila = $resultado->fetch_assoc()) {
                $fila = limpiarDatos($fila);
                // Se asigna la URL de la imagen con otro endpoint
                $fila['imagen_url'] = "http://localhost:8080/repuestos-api/get_image.php?id=" . $fila['id_repuesto'];
                // Calcular is_active: si el stock es 0 el repuesto se considera inactivo
                $fila['is_active'] = (intval($fila['stock']) > 0) ? 1 : 0;
                
                // Extraer las marcas compatibles (si tuviera)
                if (isset($fila['id_repuesto'])) {
                    $sql_marcas = "SELECT m.id, m.name, m.short_name, m.logo_path 
                                   FROM manufacturers m
                                   JOIN repuestos_marcas rm ON m.id = rm.id_marca
                                   WHERE rm.id_repuesto = ? AND m.is_active = 1
                                   ORDER BY m.name";
                    
                    $stmt = $conexion->prepare($sql_marcas);
                    $stmt->bind_param("i", $fila['id_repuesto']);
                    $stmt->execute();
                    $resultado_marcas = $stmt->get_result();
                    
                    $marcas_compatibles = [];
                    while ($marca = $resultado_marcas->fetch_assoc()) {
                        $marcas_compatibles[] = $marca;
                    }
                    $fila['marcas_compatibles'] = $marcas_compatibles;
                    $stmt->close();
                }
                $repuestos[] = $fila;
            }
            
            $totalResult = $conexion->query($sqlCount);
            $total = $totalResult->fetch_assoc()['total'];
            
            echo json_encode([
                'success' => true,
                'count'   => count($repuestos),
                'total'   => $total,
                'data'    => $repuestos
            ]);
            break;
            
        case 'POST':
            // --- CREAR REPUESTO ---
            // Se espera que la imagen se envíe mediante POST (multipart/form-data)
            if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== 0) {
                throw new Exception("No se proporcionó una imagen válida");
            }
            
            // Validar imagen:
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $mimeType = mime_content_type($_FILES['imagen']['tmp_name']);
            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception("Tipo de archivo no permitido. Solo se aceptan JPEG, PNG y GIF.");
            }
            if ($_FILES['imagen']['size'] > 5242880) { // 5MB máximo
                throw new Exception("El tamaño de la imagen no debe exceder 5MB.");
            }
            
            $imagenBlob = file_get_contents($_FILES['imagen']['tmp_name']);
            
            // Recoger el resto de los datos mediante POST
            $referencia   = isset($_POST['str_referencia']) ? trim($_POST['str_referencia']) : null;
            $nombre       = isset($_POST['nombre']) ? trim($_POST['nombre']) : null;
            $descripcion  = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : "";
            $precio       = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
            $stock        = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
            $id_categoria = isset($_POST['id_categoria']) ? intval($_POST['id_categoria']) : null;
            $id_fabricante= isset($_POST['id_fabricante']) ? intval($_POST['id_fabricante']) : null;
            $subcategoria = isset($_POST['subcategoria']) ? intval($_POST['subcategoria']) : null;
            
            // Validar campos obligatorios
            if (!$referencia || !$nombre || $precio <= 0 || !$id_categoria || !$id_fabricante) {
                throw new Exception("Faltan campos obligatorios");
            }
            
            // Inserción en la tabla repuestos
            $stmt = $conexion->prepare("INSERT INTO repuestos (str_referencia, nombre, imagen, descripcion, precio, stock, id_categoria, id_fabricante, subcategoria)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta: " . $conexion->error);
            }
            $stmt->bind_param("ssssdiiii", $referencia, $nombre, $imagenBlob, $descripcion, $precio, $stock, $id_categoria, $id_fabricante, $subcategoria);
            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }
            $id_repuesto = $conexion->insert_id;
            $stmt->close();
            
            // Insertar marcas compatibles si se enviaron (se espera un JSON)
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
                'success'     => true,
                'message'     => 'Repuesto creado correctamente',
                'id_repuesto' => $id_repuesto
            ]);
            break;
            
            case 'PUT':
                // Determinar la fuente de datos: JSON o multipart/form-data
                if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
                    // Los datos vienen en formato JSON
                    $data = json_decode(file_get_contents('php://input'), true);
                    if (!$data) {
                        throw new Exception("Error al decodificar datos JSON");
                    }
                } elseif (strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
                    // Los datos vienen como multipart/form-data
                    $data = $_POST;
                } else {
                    throw new Exception("Formato de datos no soportado");
                }
            
                // Validación del ID del repuesto
                if (!isset($data['id']) || intval($data['id']) <= 0) {
                    throw new Exception("ID del repuesto no proporcionado");
                }
            
                $id_repuesto  = intval($data['id']);
                $referencia   = $data['str_referencia'] ?? null;
                $nombre       = $data['nombre'] ?? null;
                $descripcion  = $data['descripcion'] ?? '';
                $precio       = isset($data['precio']) ? floatval($data['precio']) : 0;
                $stock        = isset($data['stock']) ? intval($data['stock']) : 0;
                $id_categoria = isset($data['id_categoria']) ? intval($data['id_categoria']) : null;
                $id_fabricante= isset($data['id_fabricante']) ? intval($data['id_fabricante']) : null;
                $subcategoria = isset($data['subcategoria']) ? intval($data['subcategoria']) : null;
                if (isset($data['marcas_compatibles'])) {
                    if (is_string($data['marcas_compatibles'])) {
                        $marcasCompatibles = json_decode($data['marcas_compatibles'], true);
                    } elseif (is_array($data['marcas_compatibles'])) {
                        $marcasCompatibles = $data['marcas_compatibles'];
                    } else {
                        $marcasCompatibles = [];
                    }
                } else {
                    $marcasCompatibles = [];
                }            
                // Validar campos obligatorios
                if (!$referencia || !$nombre || $precio <= 0 || !$id_categoria || !$id_fabricante) {
                    throw new Exception("Faltan campos obligatorios para actualizar");
                }
            
                //////// Actualizar el repuesto
                $id_proveedor = isset($data['id_proveedor']) ? intval($data['id_proveedor']) : 0;

                $sqlUpdate = "UPDATE repuestos SET 
                                str_referencia = ?, 
                                nombre = ?, 
                                descripcion = ?, 
                                precio = ?, 
                                stock = ?, 
                                id_categoria = ?, 
                                id_fabricante = ?, 
                                subcategoria = ?,
                                id_proveedor = ?
                              WHERE id_repuesto = ?";
                $stmt = $conexion->prepare($sqlUpdate);
                if (!$stmt) {
                    throw new Exception("Error al preparar UPDATE: " . $conexion->error);
                }
                $stmt->bind_param("sssdiiiiii",
                    $referencia,
                    $nombre,
                    $descripcion,
                    $precio,
                    $stock,
                    $id_categoria,
                    $id_fabricante,
                    $subcategoria,
                    $id_proveedor,
                    $id_repuesto
                );
                if (!$stmt->execute()) {
                    throw new Exception("Error al ejecutar consulta: " . $stmt->error);
                }
                $stmt->close();
            
                // Actualizar marcas compatibles (si aplica)
                $conexion->query("DELETE FROM repuestos_marcas WHERE id_repuesto = $id_repuesto");
                if (is_array($marcasCompatibles) && !empty($marcasCompatibles)) {
                    $stmt_marca = $conexion->prepare("INSERT INTO repuestos_marcas (id_repuesto, id_marca) VALUES (?, ?)");
                    foreach ($marcasCompatibles as $marca_id) {
                        $stmt_marca->bind_param("ii", $id_repuesto, $marca_id);
                        $stmt_marca->execute();
                    }
                    $stmt_marca->close();
                }
            
                echo json_encode([
                    'success' => true,
                    'message' => 'Repuesto actualizado correctamente'
                ]);
                break;
        case 'DELETE':
            // --- BORRAR REPUESTO ---
            if (!isset($_GET['id']) || intval($_GET['id']) <= 0) {
                throw new Exception("Se requiere un ID válido para eliminar");
            }
            $id_repuesto = intval($_GET['id']);
            $stmt = $conexion->prepare("DELETE FROM repuestos WHERE id_repuesto = ?");
            if (!$stmt) {
                throw new Exception("Error al preparar DELETE: " . $conexion->error);
            }
            $stmt->bind_param("i", $id_repuesto);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Repuesto eliminado con éxito']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se encontró el repuesto']);
            }
            $stmt->close();
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
