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
        table { font-size: 8px; width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-center { text-align: center; }
        .fecha { text-align: right; font-size: 14px; margin-bottom: 20px; }
        .aprobada {  font-weight: bold; } /* Estilo para materia aprobada */
    </style>
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
                <th>Curso</th>
                <th>Materia</th>
                <th class="text-center">IE1</th>
                <th class="text-center">IE2</th>
                <th class="text-center">IE3</th>
                <th class="text-center">IE4</th>
                <th class="text-center">IE5</th>
                <th class="text-center">IE6</th>
                <th class="text-center">IE7</th>
                <th class="text-center">IEFI</th>
                <th class="text-center">RIE1</th>
                <th class="text-center">RIE2</th>
                <th class="text-center">RIE3</th>
                <th class="text-center">RIE4</th>
                <th class="text-center">RIE5</th>
                <th class="text-center">RIE6</th>
                <th class="text-center">RIE7</th>
                <th class="text-center">RIEFI</th>
                <th>Estado</th>
                <th class="text-center">Final</th>
            </tr>';

            //RECORRER TABLA DE CALIFICACIONES
        
        $a = 0;
        $html2="";
        while ($a < $cantidad) {
            if (!$mostrarEstadoParcial) {
                $listadoCalificaciones[$a]['Estado'] = '';
            }
            $html2=$html2.' <tr>
                <td>'.htmlspecialchars($listadoCalificaciones[$a]['Curso'] ?? '').'</td>
                <td>'.htmlspecialchars($listadoCalificaciones[$a]['Materia'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['n1'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['n2'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['n3'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['n4'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['n5'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['n6'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['n7'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['n8'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['r1'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['r2'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['r3'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['r4'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['r5'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['r6'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['r7'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['r8'] ?? '').'</td>
                <td>'.htmlspecialchars($listadoCalificaciones[$a]['Estado'] ?? '').'</td>
                <td class="text-center">'.htmlspecialchars($listadoCalificaciones[$a]['CalificacionFinal'] ?? '').'</td>
            </tr>';
            $a++;
        }
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
