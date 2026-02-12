<?php
session_start();
require_once '../vendor/autoload.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// (Validación y obtención de parámetros igual que el primero)
if (!isset($_GET['idCurso']) || !isset($_GET['idPlan']) || !isset($_GET['idCicloLectivo'])) {
    die("Faltan parámetros para generar el reporte.");
}
$idCurso = $_GET['idCurso'];
$idPlan = $_GET['idPlan'];
$idCicloLectivo = $_GET['idCicloLectivo'];
$nombrePlan = buscarNombrePlan($conn, $idPlan);
$nombreCiclo = buscarnombreCiclo($conn, $idCicloLectivo);
$stmtCurso = $conn->prepare("SELECT nombre FROM curso WHERE idCurso = ?");
$stmtCurso->bind_param("i", $idCurso);
$stmtCurso->execute();
$nombreCurso = $stmtCurso->get_result()->fetch_assoc()['nombre'] ?? 'Desconocido';
$alumnos = obtenerAlumnosPorCurso($conn, $idCurso, $idCicloLectivo);
$membrete = $_SESSION['membrete'] ?? 'default_logo.jpg';
$img_base64 = base64_encode(file_get_contents(__DIR__ . '/' . $membrete));

// Crear instancia de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
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
        h4, h5 { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        td { border-bottom: 1px solid #000; padding: 3px 3px; }
        .nombre-alumno { width: 50%; } /* Un poco más ancho para el DNI */
        .renglon { width: 50%; }
    </style>
<link rel="icon" type="image/png" href="../img/icon.png">

</head>
<body>
    <div class="header"><img src="data:image/jpeg;base64,' . $img_base64 . '"></div>
    <div class="title">
        <h2>Listado de Alumnos por Curso con DNI</h2>
        <h3>Plan: ' . htmlspecialchars($nombrePlan) . '</h3>
        <h4>Curso: ' . htmlspecialchars($nombreCurso) . ' - Ciclo Lectivo: ' . htmlspecialchars($nombreCiclo) . '</h4>
    </div>
    <table>';

foreach ($alumnos as $alumno) {
    // La única diferencia está aquí
    $html .= '
        <tr>
            <td class="nombre-alumno">' . htmlspecialchars($alumno['apellido'] . ', ' . $alumno['nombre']) . ' - DNI: ' . htmlspecialchars($alumno['dni']) . '</td>
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
$dompdf->stream("listado_curso_dni.pdf", array("Attachment" => 0));
?>