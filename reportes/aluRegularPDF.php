<?php
session_start();
require_once '../vendor/autoload.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Validación de parámetros obligatorios
if (!isset($_GET['idAlumno']) || !isset($_GET['nombre']) || !isset($_GET['apellido']) || !isset($_GET['dni'])) {
    die("Faltan parámetros para generar el certificado.");
}

$idAlumno = intval($_GET['idAlumno']);
$nombre   = htmlspecialchars($_GET['nombre']);
$apellido = htmlspecialchars($_GET['apellido']);
$dni      = htmlspecialchars($_GET['dni']);

// Buscar carrera (plan/es) asociada al alumno
$planes = buscarPlanes($conn, $idAlumno);
$carrera = !empty($planes) ? $planes[0]['Plan'] : 'Carrera no registrada';

// Obtener institución y localidad
$institucion = $datosColegio[0]['nombreColegio'] ?? 'Institución Educativa';
$localidad   = $datosColegio[0]['localidad'] ?? 'Localidad';

// Fecha actual con IntlDateFormatter
$fmt = new IntlDateFormatter(
    'es_ES',
    IntlDateFormatter::LONG,
    IntlDateFormatter::NONE,
    'America/Argentina/Buenos_Aires', // Ajusta a tu zona horaria
    IntlDateFormatter::GREGORIAN,
    'MMMM' // Solo el nombre del mes
);

$dia  = date('j');
$mes  = $fmt->format(new DateTime()); // Saldrá "febrero"
$anio = date('Y');

// Membrete (logo)
$membrete = $_SESSION['membrete'] ?? 'default_logo.jpg';
$img_path = __DIR__ . '/' . $membrete;
$img_base64 = '';
if (file_exists($img_path)) {
    $img_base64 = base64_encode(file_get_contents($img_path));
}

// Crear instancia de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Texto formal del certificado
$textoCertificado = "
    <p style='text-align: justify; line-height: 1.6;'>
        Se certifica por la presente que <strong>{$apellido}, {$nombre}</strong>, DNI N° <strong>{$dni}</strong>,
        cursa regularmente la carrera de <strong>{$carrera}</strong> en la institución <strong>{$institucion}</strong>.
    </p>
    <p style='text-align: justify; line-height: 1.6; margin-top: 20px;'>
        Este certificado se emite a los <strong>{$dia}</strong> días del mes de <strong>{$mes}</strong> del año <strong>{$anio}</strong>
        en la localidad de <strong>{$localidad}</strong>, a solicitud del interesado para ser presentado ante quien corresponda.
    </p>
";

// HTML del documento
$html = "
<html>
<head>
    <meta charset='utf-8'>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12pt; margin: 40px; }
        .header { text-align: center; margin-bottom: 40px; }
        .header img { height: 80px; }
        .title { text-align: center; margin-bottom: 30px; }
        .footer { margin-top: 80px; text-align: center; font-size: 10pt; }
        .firma { margin-top: 60px; text-align: center; }
        .firma-linea { border-top: 1px solid #000; width: 250px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class='header'>
        " . (!empty($img_base64) ? "<img src='data:image/jpeg;base64,{$img_base64}'>" : '') . "
    </div>
    <div class='title'>
        <h2>CERTIFICADO DE ALUMNO REGULAR</h2>
    </div>
    {$textoCertificado}
    <div class='firma'>
        <div class='firma-linea'></div>
        <p>Firma y Sello de la Autoridad</p>
    </div>
    <div class='footer'>
        <p>{$institucion} - {$localidad}</p>
    </div>
</body>
</html>
";

// Generar PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("certificado_regular_{$apellido}_{$nombre}.pdf", ["Attachment" => 0]);
?>
