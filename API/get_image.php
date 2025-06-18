<?php
require_once 'config.php';

// Aplicar configuración CORS
configurarCORS();

$metodo = $_SERVER['REQUEST_METHOD'];

$id = $_GET['id'] ?? 0;

$conexion = conectarDB();
$stmt = $conexion->prepare("SELECT imagen FROM repuestos WHERE id_repuesto = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($imagen);
$stmt->fetch();

// Forzamos el tipo JPEG sin verificar (solución rápida)
header("Content-Type: image/jpeg");
echo $imagen;