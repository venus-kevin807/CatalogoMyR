<?php

require_once 'config_1.php';

// Aplicar configuración CORS y encabezados de respuesta
configurarCORS();

// Verificar el token de autenticación. Si el token es inválido,
// la función autenticar() enviará la respuesta de error y terminará la ejecución.
try {
    $conexion = conectarDB();
    if (!$conexion) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    $metodo = $_SERVER['REQUEST_METHOD'];
    
    switch ($metodo) {
        case 'GET':
            // (Código GET similar al anterior, con filtros y paginación)
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 8;
            $offset = ($page - 1) * $per_page;
    
            $sqlWhere = " WHERE 1";
            if (isset($_GET['id_usuario']) && intval($_GET['id_usuario']) > 0) {
                $id_usuario = intval($_GET['id_usuario']);
                $sqlWhere .= " AND v.id_usuario = $id_usuario";
            }
            if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
                $search = $conexion->real_escape_string(trim($_GET['search']));
                $sqlWhere .= " AND (v.id_venta LIKE '%$search%' OR u.nombre LIKE '%$search%')";
            }

            // Filtro por fecha
            if (isset($_GET['fecha']) && !empty(trim($_GET['fecha']))) {
                $fecha = $conexion->real_escape_string(trim($_GET['fecha']));
                // Se asume que el campo 'v.fecha' es de DATETIME o DATE; si es DATETIME se extrae la parte de la fecha.
                $sqlWhere .= " AND DATE(v.fecha) = '$fecha'";
            }
    
            $sql = "SELECT v.id_venta, v.fecha, v.total, v.id_usuario, u.nombre AS usuario_nombre
                    FROM ventas v
                    JOIN usuarios u ON v.id_usuario = u.id
                    " . $sqlWhere;
    
            $sqlCount = "SELECT COUNT(*) as total 
                         FROM ventas v
                         JOIN usuarios u ON v.id_usuario = u.id
                         " . $sqlWhere;
                         
            $resultCount = $conexion->query($sqlCount);
            $total = ($resultCount && $row = $resultCount->fetch_assoc()) ? $row['total'] : 0;
    
            $sql .= " LIMIT $offset, $per_page";
            $resultado = $conexion->query($sql);
            if (!$resultado) {
                throw new Exception("Error en la consulta: " . $conexion->error);
            }
    
            $ventas = [];
            while ($fila = $resultado->fetch_assoc()) {
                $ventas[] = $fila;
            }
    
            echo json_encode([
                'ventas' => $ventas,
                'total'  => $total
            ]);
            break;
    
        case 'POST':
            // Se espera recibir id_usuario, total y un arreglo "detalle"
            $datos = json_decode(file_get_contents("php://input"), true);
            if (!isset($datos['id_usuario'], $datos['total'], $datos['detalle']) || !is_array($datos['detalle'])) {
                responderJSON(['error' => 'Faltan campos obligatorios o detalle incorrecto'], 400);
            }
    
            $id_usuario = intval($datos['id_usuario']);
            $total = floatval($datos['total']);
    
            // Iniciar transacción
            $conexion->begin_transaction();
    
            // Insertar venta
            $sqlVenta = "INSERT INTO ventas (id_usuario, total) VALUES (?, ?)";
            $stmtVenta = $conexion->prepare($sqlVenta);
            if (!$stmtVenta) {
                throw new Exception("Error preparando la consulta de venta: " . $conexion->error);
            }
            $stmtVenta->bind_param("id", $id_usuario, $total);
            if (!$stmtVenta->execute()) {
                $conexion->rollback();
                responderJSON(['error' => 'Error al crear la venta: ' . $stmtVenta->error], 500);
            }
            $id_venta = $conexion->insert_id;
            $stmtVenta->close();
    
            // Procesar cada detalle
            $sqlDetalle = "INSERT INTO detalle_venta (id_venta, id_repuesto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
            $stmtDetalle = $conexion->prepare($sqlDetalle);
            if (!$stmtDetalle) {
                $conexion->rollback();
                throw new Exception("Error preparando la consulta de detalle: " . $conexion->error);
            }
    
            foreach ($datos['detalle'] as $item) {
                if (!isset($item['id_repuesto'], $item['cantidad'], $item['precio_unitario'])) {
                    $conexion->rollback();
                    responderJSON(['error' => 'Cada detalle debe incluir id_repuesto, cantidad y precio_unitario'], 400);
                }
                $id_repuesto = intval($item['id_repuesto']);
                $cantidad = intval($item['cantidad']);
                $precio_unitario = floatval($item['precio_unitario']);
    
                // Insertar detalle
                $stmtDetalle->bind_param("iiid", $id_venta, $id_repuesto, $cantidad, $precio_unitario);
                if (!$stmtDetalle->execute()) {
                    $conexion->rollback();
                    responderJSON(['error' => 'Error al insertar detalle: ' . $stmtDetalle->error], 500);
                }
    
                // Actualizar stock del repuesto: se resta la cantidad vendida
                $sqlUpdateStock = "UPDATE repuestos SET stock = stock - ? WHERE id_repuesto = ?";
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
    
            responderJSON(['mensaje' => 'Venta creada con éxito', 'id_venta' => $id_venta], 201);
            break;
    
        case 'PUT':
            // Para actualizar una venta (generalmente solo se modificará el total)
            $datos = json_decode(file_get_contents("php://input"), true);
            if (!isset($datos['id_venta'])) {
                responderJSON(['error' => 'El ID de la venta es obligatorio'], 400);
            }
            $id_venta = intval($datos['id_venta']);
            $total = isset($datos['total']) ? floatval($datos['total']) : null;
    
            $sql = "UPDATE ventas SET total = ? WHERE id_venta = ?";
            $stmt = $conexion->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar UPDATE: " . $conexion->error);
            }
            $stmt->bind_param("di", $total, $id_venta);
    
            if ($stmt->execute()) {
                responderJSON(['mensaje' => 'Venta actualizada con éxito']);
            } else {
                responderJSON(['error' => 'Error al actualizar la venta: ' . $stmt->error], 500);
            }
            $stmt->close();
            break;
        
            case 'DELETE':
                if (!isset($_GET['id_venta']) || intval($_GET['id_venta']) <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'El ID de la venta es obligatorio']);
                    exit;
                }
            
                $id_venta = intval($_GET['id_venta']);
            
                // Iniciar transacción
                $conexion->begin_transaction();
            
                try {
                    // Recuperar el detalle de la venta para restaurar stock
                    $sqlSelectDetalle = "SELECT id_repuesto, cantidad FROM detalle_venta WHERE id_venta = ?";
                    $stmtSelect = $conexion->prepare($sqlSelectDetalle);
                    if (!$stmtSelect) {
                        throw new Exception("Error al preparar SELECT detalle: " . $conexion->error);
                    }
                    $stmtSelect->bind_param("i", $id_venta);
                    if (!$stmtSelect->execute()) {
                        throw new Exception("Error al ejecutar SELECT detalle: " . $stmtSelect->error);
                    }
                    $resultado = $stmtSelect->get_result();
                    $detalles = $resultado->fetch_all(MYSQLI_ASSOC);
                    $stmtSelect->close();
            
                    // Restaurar stock de cada repuesto
                    foreach ($detalles as $item) {
                        $sqlRestoreStock = "UPDATE repuestos SET stock = stock + ? WHERE id_repuesto = ?";
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
            
                    // Eliminar los detalles primero
                    $sqlDeleteDetalle = "DELETE FROM detalle_venta WHERE id_venta = ?";
                    $stmtDetalle = $conexion->prepare($sqlDeleteDetalle);
                    if (!$stmtDetalle) {
                        throw new Exception("Error al preparar DELETE de detalle: " . $conexion->error);
                    }
                    $stmtDetalle->bind_param("i", $id_venta);
                    if (!$stmtDetalle->execute()) {
                        throw new Exception("Error al eliminar detalle: " . $stmtDetalle->error);
                    }
                    $stmtDetalle->close();
            
                    // Finalmente eliminar la venta
                    $sqlDeleteVenta = "DELETE FROM ventas WHERE id_venta = ?";
                    $stmtVenta = $conexion->prepare($sqlDeleteVenta);
                    if (!$stmtVenta) {
                        throw new Exception("Error al preparar DELETE venta: " . $conexion->error);
                    }
                    $stmtVenta->bind_param("i", $id_venta);
                    if (!$stmtVenta->execute()) {
                        throw new Exception("Error al eliminar venta: " . $stmtVenta->error);
                    }
                    $stmtVenta->close();
            
                    // Confirmar transacción
                    $conexion->commit();
                    echo json_encode(['mensaje' => 'Venta eliminada con éxito']);
                } catch (Exception $e) {
                    $conexion->rollback();
                    http_response_code(500);
                    echo json_encode(['error' => 'Error al eliminar la venta: ' . $e->getMessage()]);
                }
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
