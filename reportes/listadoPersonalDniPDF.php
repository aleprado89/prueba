<?php
ob_start();
define('VERIFICAR_SESION_SIN_SCRIPT', true);
include '../funciones/verificarSesion.php';
require_once '../vendor/autoload.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/verificarAccesoReporte.php';
assertReporteSecretaria();

use Dompdf\Dompdf;

$tipoListado = $_GET['tipoListado'] ?? 'activos';
$soloActivos = ($tipoListado !== 'todos');

$personal = obtenerPersonalParaListado($conn, $soloActivos);
$alcance = $soloActivos ? 'Solo personal activo' : 'Todo el personal';
$membrete = $_SESSION['membrete'] ?? 'default_logo.jpg';
$img_base64 = base64_encode(file_get_contents(__DIR__ . '/' . $membrete));

if (class_exists('Dompdf\\Options')) {
    $options = new \Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $dompdf = new Dompdf($options);
} else {
    $dompdf = new Dompdf();
}

$html = '
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 8pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .header img { max-width: 100%; height: 80px; }
        .title { text-align: center; }
        h3, h4 { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        td { border-bottom: 1px solid #000; padding: 3px 3px; }
        .nombre-personal { width: 50%; }
        .renglon { width: 50%; }
    </style>
</head>
<body>
    <div class="header"><img src="data:image/jpeg;base64,' . $img_base64 . '"></div>
    <div class="title">
        <h3>Listado de Personal con DNI</h3>
        <h4>' . htmlspecialchars($alcance) . '</h4>
    </div>
    <table cellspacing="0" cellpadding="0">';

foreach ($personal as $persona) {
    $html .= '
        <tr>
            <td class="nombre-personal" style="border-bottom: 1.5px solid #000; height: 20px;">' . htmlspecialchars($persona['apellido'] . ', ' . $persona['nombre']) . ' - DNI: ' . htmlspecialchars($persona['dni']) . '</td>
            <td class="renglon" style="border-bottom: 1.5px solid #000; height: 20px;">&nbsp;</td>
        </tr>';
}

$html .= '
    </table>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
if (ob_get_length()) { ob_end_clean(); }
$dompdf->stream('listado_personal_dni.pdf', array('Attachment' => 0));
?>



