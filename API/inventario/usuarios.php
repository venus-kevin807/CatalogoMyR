    <?php
    require_once 'config_1.php';

    // Aplicar configuración CORS y encabezados de respuesta
    configurarCORS();

    $metodo = $_SERVER['REQUEST_METHOD'];
    $conexion = conectarDB();

    switch ($metodo) {
        case 'GET':
            // Obtener todos los usuarios
            $sql = "SELECT id, nombre, usuario, rol, estado FROM usuarios WHERE 1 ORDER BY id";
            $resultado = $conexion->query($sql);
            
            if (!$resultado) {
                responderJSON(['error' => 'Error en la consulta: ' . $conexion->error], 500);
            }
            
            $usuarios = [];
            while ($fila = $resultado->fetch_assoc()) {
                // No incluir la contraseña en la respuesta
                $usuarios[] = $fila;
            }
            
            responderJSON(['usuarios' => $usuarios]);
            break;
            
        case 'POST':
            // Crear un nuevo usuario
            $datos = json_decode(file_get_contents('php://input'), true);
            
            if (
                !isset($datos['nombre']) || 
                !isset($datos['usuario']) ||
                !isset($datos['contrasena']) || 
                !isset($datos['rol'])
            ) {
                responderJSON(['error' => 'Faltan campos obligatorios'], 400);
            }
            
            $nombre = $conexion->real_escape_string($datos['nombre']);
            $usuario = $conexion->real_escape_string($datos['usuario']);
            $contrasena = password_hash($datos['contrasena'], PASSWORD_DEFAULT); // Encriptar contraseña
            $rol = $conexion->real_escape_string($datos['rol']);
            $estado = isset($datos['estado']) ? ($datos['estado'] ? 1 : 0) : 1;
            
            // Verificar si el usuario ya existe
            $verificar = $conexion->query("SELECT id FROM usuarios WHERE usuario = '$usuario'");
            if ($verificar->num_rows > 0) {
                responderJSON(['error' => 'El nombre de usuario ya está en uso'], 409);
                break;
            }
            
            $sql = "INSERT INTO usuarios (nombre, usuario, contrasena, rol, estado) 
                    VALUES ('$nombre', '$usuario', '$contrasena', '$rol', $estado)";
            
            if ($conexion->query($sql)) {
                $id = $conexion->insert_id;
                responderJSON(['mensaje' => 'Usuario creado con éxito', 'id' => $id], 201);
            } else {
                responderJSON(['error' => 'Error al crear el usuario: ' . $conexion->error], 500);
            }
            break;
        
        case 'PUT':
            // Actualizar un usuario existente
            $datos = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($datos['id'])) {
                responderJSON(['error' => 'El ID del usuario es obligatorio'], 400);
            }
            
            $id = $conexion->real_escape_string($datos['id']);
            $actualizaciones = [];
            
            if (isset($datos['nombre'])) {
                $nombre = $conexion->real_escape_string($datos['nombre']);
                $actualizaciones[] = "nombre = '$nombre'";
            }
            
            if (isset($datos['usuario'])) {
                $usuario = $conexion->real_escape_string($datos['usuario']);
                
                // Verificar si el nombre de usuario ya está en uso por otro usuario
                $verificar = $conexion->query("SELECT id FROM usuarios WHERE usuario = '$usuario' AND id != $id");
                if ($verificar->num_rows > 0) {
                    responderJSON(['error' => 'El nombre de usuario ya está en uso'], 409);
                    break;
                }
                
                $actualizaciones[] = "usuario = '$usuario'";
            }
            
            if (isset($datos['contrasena']) && !empty($datos['contrasena'])) {
                $contrasena = password_hash($datos['contrasena'], PASSWORD_DEFAULT);
                $actualizaciones[] = "contrasena = '$contrasena'";
            }
            
            if (isset($datos['rol'])) {
                $rol = $conexion->real_escape_string($datos['rol']);
                $actualizaciones[] = "rol = '$rol'";
            }
            
            if (isset($datos['estado'])) {
                $estado = $datos['estado'] ? 1 : 0;
                $actualizaciones[] = "estado = $estado";
            }
            
            if (empty($actualizaciones)) {
                responderJSON(['error' => 'No hay datos para actualizar'], 400);
            }
            
            $actualizacionesStr = implode(', ', $actualizaciones);
            $sql = "UPDATE usuarios SET $actualizacionesStr WHERE id = $id";
            
            if ($conexion->query($sql)) {
                if ($conexion->affected_rows > 0) {
                    responderJSON(['mensaje' => 'Usuario actualizado con éxito']);
                } else {
                    responderJSON(['mensaje' => 'No se encontró el usuario o no hubo cambios'], 404);
                }
            } else {
                responderJSON(['error' => 'Error al actualizar el usuario: ' . $conexion->error], 500);
            }
            break;
            
            case 'DELETE':
                // Eliminación física con validaciones mejoradas
                $id = $_GET['id'] ?? null;
                
                // Validación 1: ID presente y numérico
                if (!$id || !ctype_digit($id)) {
                    responderJSON(['error' => 'ID de usuario inválido o faltante'], 400);
                    break;
                }
                
                $id = (int)$id;
                
                // Validación 2: Existencia del usuario
                $stmt_check = $conexion->prepare("SELECT id FROM usuarios WHERE id = ?");
                $stmt_check->bind_param("i", $id);
                $stmt_check->execute();
                
                if ($stmt_check->get_result()->num_rows === 0) {
                    responderJSON(['error' => 'Usuario no encontrado'], 404);
                    break;
                }
                                
                // Eliminación física con prepared statement
                $sql = "DELETE FROM usuarios WHERE id = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        responderJSON(['mensaje' => 'Usuario eliminado permanentemente']);
                    } else {
                        responderJSON(['error' => 'No se pudo completar la eliminación'], 500);
                    }
                } else {
                    responderJSON(['error' => 'Error en la base de datos: ' . $conexion->error], 500);
                }
                break;
        
        default:
            responderJSON(['error' => 'Método no permitido'], 405);
    }

    $conexion->close();
    ?>
