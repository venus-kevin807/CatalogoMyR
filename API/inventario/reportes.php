<?php
require_once 'config_1.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

configurarCORS();

try {
    $conexion = conectarDB();

    // Consultas para entradas
    $sql_entradas = "SELECT di.id_repuesto, r.nombre, SUM(di.cantidad) as cantidad, di.precio_unitario
                     FROM detalle_ingreso di
                     JOIN repuestos r ON di.id_repuesto = r.id_repuesto
                     GROUP BY di.id_repuesto, r.nombre, di.precio_unitario";
    $entradas = $conexion->query($sql_entradas)->fetch_all(MYSQLI_ASSOC);

    // Consultas para ventas
    $sql_ventas = "SELECT dv.id_repuesto, r.nombre, SUM(dv.cantidad) as cantidad, dv.precio_unitario
                   FROM detalle_venta dv
                   JOIN repuestos r ON dv.id_repuesto = r.id_repuesto
                   GROUP BY dv.id_repuesto, r.nombre, dv.precio_unitario";
    $ventas = $conexion->query($sql_ventas)->fetch_all(MYSQLI_ASSOC);

    // Generar HTML del reporte
    $html = generarHTML($entradas, $ventas);

    // Crear PDF con DomPDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Enviar PDF al navegador
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="reporte_inventario.pdf"');
    echo $dompdf->output();
    exit;

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}

// Función para construir el HTML del PDF
function generarHTML($entradas, $ventas) {
    $totalEntradas = calcularTotal($entradas);
    $totalSalidas = calcularTotal($ventas);
    $balance = $totalSalidas - $totalEntradas;

    $html = "
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #888; padding: 6px; text-align: center; }
        th { background-color: #3498db; color: white; }
        h2 { margin-top: 30px; }
    </style>
    <h1 style='text-align:center;'>Reporte de Entradas y Salidas</h1>
    <p style='text-align:right;'>Fecha: ".date('d/m/Y H:i:s')."</p>

    <h2>Entradas de Repuestos</h2>" . generarTabla($entradas) . "
    <h2>Salidas de Repuestos</h2>" . generarTabla($ventas) . "
    <h2>Resumen Financiero</h2>
    <table>
        <tr><td>Total Entradas:</td><td>$" . number_format($totalEntradas, 2) . "</td></tr>
        <tr><td>Total Salidas:</td><td>$" . number_format($totalSalidas, 2) . "</td></tr>
        <tr><td><strong>Balance:</strong></td><td><strong style='color:" . ($balance >= 0 ? "green" : "red") . ";'>$" . number_format($balance, 2) . "</strong></td></tr>
    </table>";

    return $html;
}

function generarTabla($datos) {
    $html = "<table><thead>
        <tr><th>ID</th><th>Repuesto</th><th>Cantidad</th><th>P. Unitario</th><th>Total</th></tr>
        </thead><tbody>";
    foreach ($datos as $item) {
        $total = $item['cantidad'] * $item['precio_unitario'];
        $html .= "<tr>
            <td>{$item['id_repuesto']}</td>
            <td>{$item['nombre']}</td>
            <td>{$item['cantidad']}</td>
            <td>$" . number_format($item['precio_unitario'], 2) . "</td>
            <td>$" . number_format($total, 2) . "</td>
        </tr>";
    }
    $html .= "</tbody></table>";
    return $html;
}

function calcularTotal($datos) {
    return array_reduce($datos, function ($carry, $item) {
        return $carry + ($item['cantidad'] * $item['precio_unitario']);
    }, 0);
}
