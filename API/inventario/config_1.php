<?php
// config.php - Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');  // Usuario por defecto en XAMPP
define('DB_PASS', '');      // Contraseña por defecto en XAMPP (vacía)
define('DB_NAME', 'catalogo_repuestos');

// Función para conectar a la base de datos
function conectarDB() {
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
   
    if ($conexion->connect_error) {
        throw new Exception('Error de conexión: ' . $conexion->connect_error);
    }
   
    $conexion->set_charset("utf8");
    return $conexion;
}

// Función para manejar CORS
function configurarCORS() {
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
   
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Función para responder con JSON
function responderJSON($datos, $codigo = 200) {
    http_response_code($codigo);
    header('Content-Type: application/json');
    echo json_encode($datos);
    exit;
}