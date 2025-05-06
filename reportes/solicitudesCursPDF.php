<?php
session_start();
include '../vendor/autoload.php';
include '../inicio/conexion.php';
ob_start();
include '../funciones/consultas.php';
ob_end_clean();
use Dompdf\Dompdf;
use Dompdf\Options;

// Crear una instancia de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);

//PREPARO CONSULTAS PARA LOS DATOS DEL REPORTE HTML
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'].", ".$_SESSION['alu_nombre'];
$dni=$_SESSION['alu_dni'];
$idPlan=$_SESSION['idP'];
$nombrePlan=$_SESSION['nombreP'];
$membrete=$_SESSION['membrete'];
$idCicloLectivo = $_SESSION['idCiclo'];

//busco ciclo lectivo
//$anio=buscarnombreCiclo($conn,$idCicloLectivo);

//preparo imagen para que dompdf la pueda leer
$img = file_get_contents(__DIR__ . '/'.$membrete);
$img_base64 = base64_encode($img);

//BUSCO SOLICITUDES
$solicitudes = buscarSolicitudesMateria($conn, $idAlumno, $idPlan, $idCicloLectivo);

// Cargar el contenido HTML
$html = '<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitudes Cursado</title>
<style>
    body {
        font-family: Arial;
    }
    .titulo {
        text-align: center;
        font-size: 24px;
        font-weight: bold;
    }
    .subtitulo {
        font-size: 18px;
        margin-bottom: 10px;
    }
    .subtitulo label {
        font-weight: bold;
    }
    .fecha {
        text-align: right;
        font-size: 14px;
        margin-bottom: 20px;
    }
    table {
        border-collapse: collapse;
        width: 100%;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f0f0f0;
    }
</style>

<div class="titulo">Comprobante de Solicitudes para Cursado</div>
<br>
<div class="fecha">Fecha de impresión: '.date('d/m/Y H:i').'</div>

<div class="subtitulo">
    <label>Estudiante:</label> '.$nombreAlumno.' <br>
    <label>DNI:</label> '.$dni.' <br>
    <label>Carrera:</label> '.$nombrePlan.'
</div>


<table>
    <tr>
        <th>Materia</th>
        <th>Fecha</th>
        <th>Estado</th>
        <th>Observaciones</th>
    </tr>
';

foreach ($solicitudes as $solicitud) {
    $html .= '
    <tr>
        <td>'.$solicitud['Materia'].'</td>
        <td>'.$solicitud['Fecha'].'</td>
        <td>'.$solicitud['Estado'].'</td>
        <td>'.$solicitud['Observaciones'].'</td>
    </tr>
    ';
}

$html .= '
</table>
';
        
   
// Generar el PDF
try {
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream('solicitudesExam_'.$nombreAlumno.'.pdf', array('Attachment' => 0));
} catch (Exception $e) {
    echo 'Error al generar el PDF: ' . $e->getMessage();
}