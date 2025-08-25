<?php
session_start();
require_once '../vendor/autoload.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// (Validación y obtención de parámetros igual que el primero)
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
        body { font-family: Arial, sans-serif; font-size: 8pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .header img { max-width: 100%; height: 80px; }
        .title { text-align: center; }
        h4, h5,h3 { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        td { border-bottom: 1px solid #000; padding: 6px 3px; }
        .nombre-alumno { width: 50%; }
        .renglon { width: 50%; }
    </style>
</head>
<body>
    <div class="header"><img src="data:image/jpeg;base64,' . $img_base64 . '"></div>
    <div class="title">
        <h3>Listado de Alumnos por Materia con DNI</h3>
        <h3>Plan: ' . htmlspecialchars($nombrePlan) . ' - Ciclo Lectivo: ' . htmlspecialchars($nombreCiclo) . '</h3>
        <h3>Curso: ' . htmlspecialchars($nombreCurso) . '</h3>
        <h4>Materia: ' . htmlspecialchars($nombreMateria) . '</h4>
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
$dompdf->stream("listado_materia_dni.pdf", array("Attachment" => 0));
?>