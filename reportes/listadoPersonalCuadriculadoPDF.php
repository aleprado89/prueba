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
        @page { margin: 20px; }
        body { font-family: Arial, sans-serif; font-size: 8pt; }
        .header { text-align: center; margin-bottom: 15px; }
        .header img { max-width: 100%; height: 123px; }
        .title { text-align: center; }
        h4, h5, h2 { margin: 2px 0; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        .nombre-personal { width: 25%; text-align: left; }
        .cuadro { width: 2.3%; height: 22px; }
    </style>
</head>
<body>
    <div class="header"><img src="data:image/jpeg;base64,' . $img_base64 . '"></div>
    <div class="title">
        <h2>Listado de Personal</h2>
        <h5>' . htmlspecialchars($alcance) . '</h5>
    </div>
    <table border="1" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th class="nombre-personal" style="border: 1.2px solid #000;">Apellido y Nombre</th>';
for ($i = 1; $i <= 31; $i++) {
    $html .= '<th class="cuadro" style="border: 1.2px solid #000;">' . $i . '</th>';
}
$html .= '
            </tr>
        </thead>
        <tbody>';

foreach ($personal as $persona) {
    $html .= '
            <tr>
                <td class="nombre-personal" style="border: 1.2px solid #000;">' . htmlspecialchars($persona['apellido'] . ', ' . $persona['nombre']) . '</td>';
    for ($i = 1; $i <= 31; $i++) {
        $html .= '<td class="cuadro" style="border: 1.2px solid #000;">&nbsp;</td>';
    }
    $html .= '
            </tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
if (ob_get_length()) { ob_end_clean(); }
$dompdf->stream('listado_personal_cuadriculado.pdf', array('Attachment' => 0));
?>




