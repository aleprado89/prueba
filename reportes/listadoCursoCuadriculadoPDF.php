<?php
session_start();
require_once '../vendor/autoload.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// (Validación y obtención de parámetros igual que el anterior)
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
        @page { margin: 20px; }
        body { font-family: Arial, sans-serif; font-size: 8pt; }
        .header { text-align: center; margin-bottom: 15px; }
        .header img { max-width: 100%; height: 70px; }
        .title { text-align: center; }
        h4, h5, h2 { margin: 2px 0; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        .nombre-alumno { width: 25%; text-align: left; }
        .cuadro { width: 2.3%; height: 20px; } /* Ancho para que quepan 31 */
    </style>
</head>
<body>
    <div class="header"><img src="data:image/jpeg;base64,' . $img_base64 . '"></div>
    <div class="title">
        <h2>Listado de Alumnos por Curso</h2>
        <h5>Plan: ' . htmlspecialchars($nombrePlan) . '</h5>
        <h5>Curso: ' . htmlspecialchars($nombreCurso) . ' - Ciclo Lectivo: ' . htmlspecialchars($nombreCiclo) . '</h5>
    </div>
    <table>
        <thead>
            <tr>
                <th class="nombre-alumno">Apellido y Nombre</th>';
for ($i = 1; $i <= 31; $i++) {
    $html .= '<th class="cuadro">' . $i . '</th>';
}
$html .= '
            </tr>
        </thead>
        <tbody>';

foreach ($alumnos as $alumno) {
    $html .= '
            <tr>
                <td class="nombre-alumno">' . htmlspecialchars($alumno['apellido'] . ', ' . $alumno['nombre']) . '</td>';
    for ($i = 1; $i <= 31; $i++) {
        $html .= '<td class="cuadro">&nbsp;</td>'; // Celda vacía
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
$dompdf->setPaper('A4', 'landscape'); // Hoja apaisada para que quepan las columnas
$dompdf->render();
$dompdf->stream("listado_curso_cuadriculado.pdf", array("Attachment" => 0));
?>