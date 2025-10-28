<?php
session_start();
require_once '../vendor/autoload.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['idCurso']) || !isset($_GET['idPlan']) || !isset($_GET['idCicloLectivo'])) {
    die("Faltan parámetros para generar el reporte.");
}

// Obtener parámetros
$idCurso = $_GET['idCurso'];
$idPlan = $_GET['idPlan'];
$idCicloLectivo = $_GET['idCicloLectivo'];

// Obtener nombres para el encabezado
$nombrePlan = buscarNombrePlan($conn, $idPlan);
$nombreCiclo = buscarnombreCiclo($conn, $idCicloLectivo);
$stmtCurso = $conn->prepare("SELECT nombre FROM curso WHERE idCurso = ?");
$stmtCurso->bind_param("i", $idCurso);
$stmtCurso->execute();
$nombreCurso = $stmtCurso->get_result()->fetch_assoc()['nombre'] ?? 'Desconocido';

// Obtener datos
$alumnos = obtenerAlumnosPorCurso($conn, $idCurso, $idCicloLectivo);
$membrete = $_SESSION['membrete'] ?? 'default_logo.jpg';
$img_base64 = base64_encode(file_get_contents(__DIR__ . '/' . $membrete));

// Crear instancia de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Generar HTML
$html = '
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 8pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .header img { max-width: 100%; height: 80px; }
        .title { text-align: center; }
        h4, h5,h2,h3 { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        td { border-bottom: 1px solid #000; padding: 3px 3px; }
        .nombre-alumno { width: 40%; }
        .renglon { width: 60%; }
    </style>
</head>
<body>
    <div class="header"><img src="data:image/jpeg;base64,' . $img_base64 . '"></div>
    <div class="title">
        <h3>Listado de Alumnos por Curso</h3>
        <h3>Plan: ' . htmlspecialchars($nombrePlan) . '</h3>
        <h4>Curso: ' . htmlspecialchars($nombreCurso) . ' - Ciclo Lectivo: ' . htmlspecialchars($nombreCiclo) . '</h4>
    </div>
    <table>';

foreach ($alumnos as $alumno) {
    $html .= '
        <tr>
            <td class="nombre-alumno">' . htmlspecialchars($alumno['apellido'] . ', ' . $alumno['nombre']) . '</td>
            <td class="renglon"></td>
        </tr>';
}

$html .= '
    </table>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("listado_curso_renglon.pdf", array("Attachment" => 0));
?>