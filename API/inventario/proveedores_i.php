<?php
// proveedores_i.php

require_once 'config_1.php';

// Aplicar configuración CORS y encabezados de respuesta
configurarCORS();

// Verificar el token de autenticación. Si el token es inválido,
// la función autenticar() enviará la respuesta de error y terminará la ejecución.
// Configuración de errores y encabezados
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


try {
    $conexion = conectarDB();
    if (!$conexion) {
        throw new Exception("Error de conexión a la base de datos");
    }

    $metodo = $_SERVER['REQUEST_METHOD'];

    switch ($metodo) {
        case 'GET':
            // Parámetros: page, limit, search
            $page   = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit  = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
            $search = isset($_GET['search']) ? trim($_GET['search']) : "";
            $offset = ($page - 1) * $limit;
            
            $whereClause = " WHERE 1=1";
            if ($search !== "") {
                $searchEsc = $conexion->real_escape_string($search);
                $whereClause .= " AND (nombre LIKE '%$searchEsc%' OR direccion LIKE '%$searchEsc%' OR telefono LIKE '%$searchEsc%' OR email LIKE '%$searchEsc%')";
            }
            
            $sql = "SELECT * FROM proveedores" . $whereClause . " ORDER BY id LIMIT $limit OFFSET $offset";
            $resultado = $conexion->query($sql);
            if (!$resultado) {
                throw new Exception("Error en la consulta: " . $conexion->error);
            }
            
            $proveedores = [];
            while ($fila = $resultado->fetch_assoc()) {
                $proveedores[] = $fila;
            }
            
            // Consulta para contar total de proveedores
            $sqlCount = "SELECT COUNT(*) as total FROM proveedores" . $whereClause;
            $resultCount = $conexion->query($sqlCount);
            $total = $resultCount->fetch_assoc()['total'];
            
            echo json_encode([
                "success" => true,
                "count"   => count($proveedores),
                "total"   => $total,
                "data"    => $proveedores
            ]);
            break;
            
            case 'POST':
                // Si $_POST está vacío, se asume que los datos vienen en formato JSON
                if (empty($_POST)) {
                    $data = json_decode(file_get_contents('php://input'), true);
                    if ($data) {
                        $_POST = $data;
                    }
                }
            
                // Extraer los datos del proveedor
                $nombre    = isset($_POST['nombre']) ? trim($_POST['nombre']) : null;
                $direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : null;
                $telefono  = isset($_POST['telefono']) ? trim($_POST['telefono']) : null;
                $email     = isset($_POST['email']) ? trim($_POST['email']) : null;
            
                // Verificar que todos los campos requeridos existen
                if (!$nombre || !$direccion || !$telefono || !$email) {
                    throw new Exception("Faltan campos obligatorios");
                }
            
                $stmt = $conexion->prepare("INSERT INTO proveedores (nombre, direccion, telefono, email) VALUES (?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Error preparando consulta: " . $conexion->error);
                }
                $stmt->bind_param("ssss", $nombre, $direccion, $telefono, $email);
                if (!$stmt->execute()) {
                    throw new Exception("Error al ejecutar consulta: " . $stmt->error);
                }
                $id = $conexion->insert_id;
                $stmt->close();
            
                echo json_encode([
                    "success" => true,
                    "message" => "Proveedor creado correctamente",
                    "id"      => $id
                ]);
                break;
            
        case 'PUT':
            // Actualizar proveedor
            // Se acepta JSON o multipart/form-data
            if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    throw new Exception("Error al decodificar JSON");
                }
            } else {
                parse_str(file_get_contents("php://input"), $data);
            }
            
            if (!isset($data['id']) || intval($data['id']) <= 0) {
                throw new Exception("ID inválido para actualizar");
            }
            $id = intval($data['id']);
            $nombre    = $data['nombre'] ?? null;
            $direccion = $data['direccion'] ?? null;
            $telefono  = $data['telefono'] ?? null;
            $email     = $data['email'] ?? null;
            
            if (!$nombre || !$direccion || !$telefono || !$email) {
                throw new Exception("Faltan campos obligatorios para actualizar");
            }
            
            $stmt = $conexion->prepare("UPDATE proveedores SET nombre = ?, direccion = ?, telefono = ?, email = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Error preparando UPDATE: " . $conexion->error);
            }
            $stmt->bind_param("ssssi", $nombre, $direccion, $telefono, $email, $id);
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar: " . $stmt->error);
            }
            $stmt->close();
            
            echo json_encode([
                "success" => true,
                "message" => "Proveedor actualizado correctamente"
            ]);
            break;
            
        case 'DELETE':
            if (!isset($_GET['id']) || intval($_GET['id']) <= 0) {
                throw new Exception("Se requiere un ID válido para eliminar");
            }
            $id = intval($_GET['id']);
            $stmt = $conexion->prepare("DELETE FROM proveedores WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Error preparando DELETE: " . $conexion->error);
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    "success" => true,
                    "message" => "Proveedor eliminado correctamente"
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "No se encontró el proveedor"
                ]);
            }
            $stmt->close();
            break;
            
        case 'OPTIONS':
            http_response_code(200);
            echo json_encode(["success" => true]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["success" => false, "error" => "Método no permitido"]);
            break;
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error"   => "Error en el servidor",
        "message" => $e->getMessage()
    ]);
} finally {
    if (isset($conexion)) $conexion->close();
}
?>
