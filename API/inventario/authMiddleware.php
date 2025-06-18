<?php
require_once 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function autenticar() {
    // Obtener el token del encabezado
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        responderJSON(['error' => 'Token no proporcionado'], 401);
        exit();
    }
    
    $parts = explode(" ", $headers['Authorization']);
    if (count($parts) !== 2 || strtolower($parts[0]) !== 'bearer') {
        responderJSON(['error' => 'Formato de token inválido'], 401);
        exit();
    }
    
    $token = $parts[1];
    
    // Validar el token
    return verificarToken($token);  // Esta función ya debe estar definida en algún lugar
}
?>
