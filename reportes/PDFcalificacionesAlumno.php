<?php
session_start();
include '../inicio/conexion.php';
// No es necesario ob_start/ob_end_clean si solo se incluyen archivos PHP sin salida
include '../funciones/consultas.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$hoy = new DateTime();
$anio = $hoy->format("Y");
$fechaLimite = new DateTime("$anio-11-15");

// Verificamos si mostrar columna
$mostrarEstadoParcial = ($hoy >= $fechaLimite);

// Crear una instancia de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);

// --- OBTENER DATOS (sin cambios) ---
if (isset($_GET['idAlumno'])) {
    $idAlumno = $_GET['idAlumno'];
} elseif (isset($_SESSION['alu_idAlumno'])) {
    $idAlumno = $_SESSION['alu_idAlumno'];
} else {
    die("Error: No se ha especificado un alumno.");
}
if (isset($_GET['nombreAlumno'])) {
    $nombreAlumno = $_GET['nombreAlumno'];
} elseif (isset($_SESSION['alu_apellido'], $_SESSION['alu_nombre'])) {
    $nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];
} else {
    $datosAlumnoTemp = obtenerDatosBasicosAlumno($conn, $idAlumno);
    $nombreAlumno = $datosAlumnoTemp ? ($datosAlumnoTemp['apellido'] . ", " . $datosAlumnoTemp['nombre']) : "Alumno Desconocido";
}
if (isset($_GET['idPlan'])) {
    $idPlan = $_GET['idPlan'];
} elseif (isset($_SESSION['idP'])) {
    $idPlan = $_SESSION['idP'];
} else {
    die("Error: No se ha especificado un plan de estudios.");
}
$membrete = $_SESSION['membrete'] ?? 'ruta/por/defecto/logo.png';
if (!file_exists(__DIR__ . '/' . $membrete)) {
    die("Error: No se encuentra el archivo de membrete.");
}
$img = file_get_contents(__DIR__ . '/' . $membrete);
$img_base64 = base64_encode($img);
$nombrePlan = buscarNombrePlan($conn, $idPlan);
$listadoCalificaciones = buscarMaterias($conn, $idAlumno, $idPlan);
$cantidad = count($listadoCalificaciones);

// --- CONSTRUIR HTML (con la lógica de estado modificada) ---
$html = '
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado Curricular del Alumno/a</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .header { text-align: center; }
        .header img { max-width: 500px; height: auto; }
        .container { width: 100%; }
        h3, h4 { text-align: center; margin: 5px 0; }
        table { font-size: 10px; width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
        th { background-color: #f2f2f2; }
        .columna1 { width: 20%; }
        .columna2 { width: 50%; }
        .columna3 { width: 20%; }
        .columna4 { width: 10%; text-align: center; }
        .fecha { text-align: right; font-size: 14px; margin-bottom: 20px; }
        .aprobada {  font-weight: bold; } /* Estilo para materia aprobada */
    </style>
<link rel="icon" type="image/png" href="../img/icon.png">

</head>
<body>
    <div class="header">
        <img src="data:image/jpeg;base64,' . $img_base64 . '" alt="Logo">
        <div class="fecha">Fecha de impresión: '.date('d/m/Y H:i').'</div>
        <h3>Estado Curricular del Alumno/a: '.htmlspecialchars($nombreAlumno).'</h3>
        <h4>'.htmlspecialchars($nombrePlan).'</h4>
    </div>
    <div class="container">
        <table>
            <tr>
                <th class="columna1">Curso</th>
                <th class="columna2">Materia</th>
                <th class="columna3">Estado</th>
                <th class="columna4">Final</th>
            </tr>';

            //RECORRER TABLA DE CALIFICACIONES
        
        $a = 0;
        $html2="";
        while ($a < $cantidad) {        
          $Materia = $listadoCalificaciones[$a]['Materia'];
          $Curso = $listadoCalificaciones[$a]['Curso'];
          $Estado = $listadoCalificaciones[$a]['Estado'];
          $CalificacionFinal = $listadoCalificaciones[$a]['CalificacionFinal'];
                        if (!$mostrarEstadoParcial)
                            $listadoCalificaciones[$a]['Estado'] = '';
          $html2=$html2.' <tr>
                <td>'.$listadoCalificaciones[$a]['Curso'].'  </td>
                <td>'.$listadoCalificaciones[$a]['Materia'].'</td>
                <td>'.$listadoCalificaciones[$a]['Estado'].'</td>
                <td>'.$listadoCalificaciones[$a]['CalificacionFinal'].'</td>
            </tr>';
            $a++;  }
            $html=$html.$html2.'
        </table>
    </div>
</body>
</html>';
        
// Generar el PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$nombreArchivo = 'calif_' . preg_replace('/[^A-Za-z0-9\-_]/', '', str_replace(' ', '_', $nombreAlumno)) . '.pdf';
$dompdf->stream($nombreArchivo, array('Attachment' => 0));
?>