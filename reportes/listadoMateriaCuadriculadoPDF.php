<?php
session_start();
require_once '../vendor/autoload.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// (Validación y obtención de parámetros igual que el anterior)
if (!isset($_GET['idMateria']) || !isset($_GET['idPlan']) || !isset($_GET['idCicloLectivo'])) { die("Faltan parámetros."); }
$idMateria = $_GET['idMateria'];
$idPlan = $_GET['idPlan'];
$idCicloLectivo = $_GET['idCicloLectivo'];
$nombrePlan = buscarNombrePlan($conn, $idPlan);
$nombreCiclo = buscarnombreCiclo($conn, $idCicloLectivo);
$stmtInfo = $conn->prepare("SELECT m.nombre as nombreMateria, c.nombre as nombreCurso FROM materiaterciario m JOIN curso c ON m.idCurso = c.idCurso WHERE m.idMateria = ?");
$stmtInfo->bind_param("i", $idMateria);
$stmtInfo->execute();
$info = $stmtInfo->get_result()->fetch_assoc();
$nombreMateria = $info['nombreMateria'] ?? 'Desconocida';
$nombreCurso = $info['nombreCurso'] ?? 'Desconocido';
$alumnos = obtenerAlumnosPorMateria($conn, $idMateria);
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
        h4, h5 { margin: 2px 0; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        .nombre-alumno { width: 25%; text-align: left; }
        .cuadro { width: 2.3%; height: 20px; }
    </style>
<link rel="icon" type="image/png" href="../img/icon.png">

</head>
<body>
    <div class="header"><img src="data:image/jpeg;base64,' . $img_base64 . '"></div>
    <div class="title">
        <h4>Listado de Alumnos por Materia</h4>
        <h5>Plan: ' . htmlspecialchars($nombrePlan) . ' - Ciclo Lectivo: ' . htmlspecialchars($nombreCiclo) . '</h5>
        <h5>Curso: ' . htmlspecialchars($nombreCurso) . '</h5>
        <h5>Materia: ' . htmlspecialchars($nombreMateria) . '</h5>
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
        $html .= '<td class="cuadro">&nbsp;</td>';
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
$dompdf->stream("listado_materia_cuadriculado.pdf", array("Attachment" => 0));
?>