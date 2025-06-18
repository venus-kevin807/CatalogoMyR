<?php


require_once 'config_1.php';
require_once __DIR__ . '/../vendor/autoload.php';
  // Incluir el autoloader de Composer

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

configurarCORS();

$metodo = $_SERVER['REQUEST_METHOD'];
$conexion = conectarDB();

switch ($metodo) {
    case 'POST':
        // Login
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($datos['usuario']) || !isset($datos['contrasena'])) {
            responderJSON(['error' => 'Usuario y contraseña son obligatorios'], 400);
        }
        
        // Sanitizamos el input
        $usuario = $conexion->real_escape_string($datos['usuario']);
        $contrasena = $datos['contrasena'];
        
        $sql = "SELECT id, nombre, usuario, contrasena, rol, estado FROM usuarios WHERE usuario = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 0) {
            responderJSON(['error' => 'Usuario no encontrado'], 401);
            break;
        }
        
        $usuario_db = $resultado->fetch_assoc();
        
        // Validar el estado del usuario (asegurarse de que está activo)
        $estado = $usuario_db['estado'];
        $inactivo = ($estado === 'Inactivo' || $estado === 0 || $estado === false);
        if ($inactivo) {
            responderJSON(['error' => 'Usuario desactivado'], 401);
            break;
        }
        
        // Verificar la contraseña usando password_verify
        $hashValido = password_verify($contrasena, $usuario_db['contrasena']);
        error_log("Resultado de verificación: ".($hashValido ? 'VÁLIDO' : 'INVÁLIDO'));
        if (!$hashValido) {
            responderJSON(['error' => 'Contraseña incorrecta'], 401);
            break;
        }
        
        // Generar el token JWT utilizando firebase/php-jwt
        $tiempo_actual = time();
        $payload = [
            'iat' => $tiempo_actual,
            'exp' => $tiempo_actual + (60 * 60 * 24), // El token tendrá una validez de 24 horas
            'data' => [
                'id'      => $usuario_db['id'],
                'usuario' => $usuario_db['usuario'],
                'rol'     => $usuario_db['rol']
            ]
        ];
        
        // Define tu clave secreta (usa una clave robusta y mantenla segura. Idealmente, usar una variable de entorno)
        $clave_secreta = 'clave_secreta_para_firmar_token';
        
        // Codificar el token
        $token = JWT::encode($payload, $clave_secreta, 'HS256');
        
        // Eliminar la contraseña del usuario antes de enviar la respuesta
        unset($usuario_db['contrasena']);
        
        responderJSON([
            'token' => $token,
            'user'  => $usuario_db
        ]);
        break;
        
    default:
        responderJSON(['error' => 'Método no permitido'], 405);
}

$conexion->close();
?>
