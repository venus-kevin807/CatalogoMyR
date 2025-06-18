<?php
require_once 'config_1.php';

// Aplicar configuración CORS y encabezados de respuesta
configurarCORS();

// Verificar el token de autenticación. Si el token es inválido,
// la función autenticar() enviará la respuesta de error y terminará la ejecución.

// CONFIGURACIÓN DE ERRORES
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
error_reporting(E_ALL);

// CONFIGURAR CORS SOLO UNA VEZ

try {
    $conexion = conectarDB();
    if (!$conexion) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    $metodo = $_SERVER['REQUEST_METHOD'];
    
    switch ($metodo) {
        case 'GET':
            // Obtener ingresos con filtros y paginación
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 8;
            $offset = ($page - 1) * $per_page;
            
            $sqlWhere = " WHERE 1";
            if (isset($_GET['id_proveedor']) && intval($_GET['id_proveedor']) > 0) {
                $id_proveedor = intval($_GET['id_proveedor']);
                $sqlWhere .= " AND i.id_proveedor = $id_proveedor";
            }
            if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
                $search = $conexion->real_escape_string(trim($_GET['search']));
                $sqlWhere .= " AND (i.id_ingreso LIKE '%$search%' OR p.nombre LIKE '%$search%')";
            }
            if (isset($_GET['fecha']) && !empty(trim($_GET['fecha']))) {
                $fecha = $conexion->real_escape_string(trim($_GET['fecha']));
                $sqlWhere .= " AND DATE(i.fecha) = '$fecha'";
            }
            
            $sql = "SELECT i.id_ingreso, i.fecha, i.total, i.id_proveedor, i.id_usuario, 
                           p.nombre AS proveedor_nombre, u.nombre AS usuario_nombre
                    FROM ingresos i
                    JOIN proveedores p ON i.id_proveedor = p.id
                    JOIN usuarios u ON i.id_usuario = u.id
                    " . $sqlWhere;
                    
            $sqlCount = "SELECT COUNT(*) as total 
                         FROM ingresos i
                         JOIN proveedores p ON i.id_proveedor = p.id
                         JOIN usuarios u ON i.id_usuario = u.id
                         " . $sqlWhere;
                         
            $resultCount = $conexion->query($sqlCount);
            $total = ($resultCount && $row = $resultCount->fetch_assoc()) ? $row['total'] : 0;
            
            $sql .= " LIMIT $offset, $per_page";
            $resultado = $conexion->query($sql);
            if (!$resultado) {
                throw new Exception("Error en la consulta: " . $conexion->error);
            }
            
            $ingresos = [];
            while ($fila = $resultado->fetch_assoc()) {
                $ingresos[] = $fila;
            }
            
            echo json_encode([
                'ingresos' => $ingresos,
                'total'  => $total
            ]);
            break;
            
        case 'POST':
            // Crear ingreso: se espera recibir id_proveedor, id_usuario, total y un arreglo "detalle"
            $datos = json_decode(file_get_contents("php://input"), true);
            if (!isset($datos['id_proveedor'], $datos['id_usuario'], $datos['total'], $datos['detalle']) || !is_array($datos['detalle'])) {
                responderJSON(['error' => 'Faltan campos obligatorios o detalle incorrecto'], 400);
            }
            $id_proveedor = intval($datos['id_proveedor']);
            $id_usuario = intval($datos['id_usuario']);
            $total = floatval($datos['total']);
            
            // Iniciar transacción
            $conexion->begin_transaction();
            
            // Insertar ingreso
            $sqlIngreso = "INSERT INTO ingresos (id_proveedor, id_usuario, total) VALUES (?, ?, ?)";
            $stmtIngreso = $conexion->prepare($sqlIngreso);
            if (!$stmtIngreso) {
                throw new Exception("Error preparando la consulta de ingreso: " . $conexion->error);
            }
            $stmtIngreso->bind_param("iid", $id_proveedor, $id_usuario, $total);
            if (!$stmtIngreso->execute()) {
                $conexion->rollback();
                responderJSON(['error' => 'Error al crear el ingreso: ' . $stmtIngreso->error], 500);
            }
            $id_ingreso = $conexion->insert_id;
            $stmtIngreso->close();
            
            // Insertar cada detalle y actualizar stock (sumarlo)
            // Se asume que se han agregado 2 columnas: precio_unitario y precio_sugerido en la tabla detalle_ingreso
            $sqlDetalle = "INSERT INTO detalle_ingreso (id_ingreso, id_repuesto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
            $stmtDetalle = $conexion->prepare($sqlDetalle);
            if (!$stmtDetalle) {
                $conexion->rollback();
                throw new Exception("Error preparando la consulta de detalle: " . $conexion->error);
            }
            
            foreach ($datos['detalle'] as $item) {
                if (!isset($item['id_repuesto'], $item['cantidad'], $item['precio_compra'])) {
                    $conexion->rollback();
                    responderJSON(['error' => 'Cada detalle debe incluir id_repuesto, cantidad y precio_compra'], 400);
                }
                $id_repuesto = intval($item['id_repuesto']);
                $cantidad = intval($item['cantidad']);
                $precio_compra = floatval($item['precio_compra']);               
                $stmtDetalle->bind_param("iiid", $id_ingreso, $id_repuesto, $cantidad, $precio_compra);
                if (!$stmtDetalle->execute()) {
                    $conexion->rollback();
                    responderJSON(['error' => 'Error al insertar detalle: ' . $stmtDetalle->error], 500);
                }
                
                // Actualizar stock: sumar la cantidad ingresada
                $sqlUpdateStock = "UPDATE repuestos SET stock = stock + ? WHERE id_repuesto = ?";
                $stmtStock = $conexion->prepare($sqlUpdateStock);
                if (!$stmtStock) {
                    $conexion->rollback();
                    throw new Exception("Error preparando UPDATE stock: " . $conexion->error);
                }
                $stmtStock->bind_param("ii", $cantidad, $id_repuesto);
                if (!$stmtStock->execute()) {
                    $stmtStock->close();
                    $conexion->rollback();
                    responderJSON(['error' => 'Error al actualizar stock: ' . $conexion->error], 500);
                }
                $stmtStock->close();
            }
            $stmtDetalle->close();
            
            // Confirmar la transacción
            $conexion->commit();
            
            responderJSON(['mensaje' => 'Ingreso creado con éxito', 'id_ingreso' => $id_ingreso], 201);
            break;
            
        case 'PUT':
            // Actualizar ingreso (por ejemplo, solo se actualizará el total)
            $datos = json_decode(file_get_contents("php://input"), true);
            if (!isset($datos['id_ingreso'])) {
                responderJSON(['error' => 'El ID del ingreso es obligatorio'], 400);
            }
            $id_ingreso = intval($datos['id_ingreso']);
            $total = isset($datos['total']) ? floatval($datos['total']) : null;
            
            $sql = "UPDATE ingresos SET total = ? WHERE id_ingreso = ?";
            $stmt = $conexion->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar UPDATE: " . $conexion->error);
            }
            $stmt->bind_param("di", $total, $id_ingreso);
            
            if ($stmt->execute()) {
                responderJSON(['mensaje' => 'Ingreso actualizado con éxito']);
            } else {
                responderJSON(['error' => 'Error al actualizar el ingreso: ' . $stmt->error], 500);
            }
            $stmt->close();
            break;
            
        case 'DELETE':
            if (!isset($_GET['id_ingreso']) || intval($_GET['id_ingreso']) <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'El ID del ingreso es obligatorio']);
                exit;
            }
            $id_ingreso = intval($_GET['id_ingreso']);
            $conexion->begin_transaction();
            try {
                // Recuperar los detalles para revertir el stock
                $sqlSelectDetalle = "SELECT id_repuesto, cantidad FROM detalle_ingreso WHERE id_ingreso = ?";
                $stmtSelect = $conexion->prepare($sqlSelectDetalle);
                if (!$stmtSelect) {
                    throw new Exception("Error al preparar SELECT detalle: " . $conexion->error);
                }
                $stmtSelect->bind_param("i", $id_ingreso);
                if (!$stmtSelect->execute()) {
                    throw new Exception("Error al ejecutar SELECT detalle: " . $stmtSelect->error);
                }
                $resultado = $stmtSelect->get_result();
                $detalles = $resultado->fetch_all(MYSQLI_ASSOC);
                $stmtSelect->close();
                
                // Revertir stock: restar la cantidad ingresada en cada detalle
                foreach ($detalles as $item) {
                    $sqlRestoreStock = "UPDATE repuestos SET stock = stock - ? WHERE id_repuesto = ?";
                    $stmtStock = $conexion->prepare($sqlRestoreStock);
                    if (!$stmtStock) {
                        throw new Exception("Error al preparar UPDATE stock: " . $conexion->error);
                    }
                    $stmtStock->bind_param("ii", $item['cantidad'], $item['id_repuesto']);
                    if (!$stmtStock->execute()) {
                        $stmtStock->close();
                        throw new Exception("Error al restaurar stock: " . $stmtStock->error);
                    }
                    $stmtStock->close();
                }
                
                // Eliminar los detalles
                $sqlDeleteDetalle = "DELETE FROM detalle_ingreso WHERE id_ingreso = ?";
                $stmtDetalle = $conexion->prepare($sqlDeleteDetalle);
                if (!$stmtDetalle) {
                    throw new Exception("Error al preparar DELETE de detalle: " . $conexion->error);
                }
                $stmtDetalle->bind_param("i", $id_ingreso);
                if (!$stmtDetalle->execute()) {
                    throw new Exception("Error al eliminar detalle: " . $stmtDetalle->error);
                }
                $stmtDetalle->close();
                
                // Eliminar el ingreso
                $sqlDeleteIngreso = "DELETE FROM ingresos WHERE id_ingreso = ?";
                $stmtIngreso = $conexion->prepare($sqlDeleteIngreso);
                if (!$stmtIngreso) {
                    throw new Exception("Error al preparar DELETE ingreso: " . $conexion->error);
                }
                $stmtIngreso->bind_param("i", $id_ingreso);
                if (!$stmtIngreso->execute()) {
                    throw new Exception("Error al eliminar ingreso: " . $stmtIngreso->error);
                }
                $stmtIngreso->close();
                
                $conexion->commit();
                echo json_encode(['mensaje' => 'Ingreso eliminado con éxito']);
            } catch (Exception $e) {
                $conexion->rollback();
                http_response_code(500);
                echo json_encode(['error' => 'Error al eliminar el ingreso: ' . $e->getMessage()]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error'   => 'Error en el servidor',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conexion)) { $conexion->close(); }
}
?>
