<?php
session_start();
include '../inicio/conexion.php';
ob_start();
include '../funciones/consultas.php';
ob_end_clean();require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Crear una instancia de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);

//PREPARO CONSULTAS PARA LOS DATOS DEL REPORTE HTML
// Obtener idAlumno
if (isset($_GET['idAlumno'])) {
    $idAlumno = $_GET['idAlumno'];
} elseif (isset($_SESSION['alu_idAlumno'])) {
    $idAlumno = $_SESSION['alu_idAlumno'];
} else {
    die("Error: No se ha especificado un alumno.");
}

// Obtener nombreAlumno
if (isset($_GET['nombreAlumno'])) {
    $nombreAlumno = $_GET['nombreAlumno'];
} elseif (isset($_SESSION['alu_apellido'], $_SESSION['alu_nombre'])) {
    $nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];
} else {
    // Si no tenemos el nombre, lo buscamos en la BD
    $datosAlumnoTemp = obtenerDatosBasicosAlumno($conn, $idAlumno);
    $nombreAlumno = $datosAlumnoTemp ? ($datosAlumnoTemp['apellido'] . ", " . $datosAlumnoTemp['nombre']) : "Alumno Desconocido";
}

// Obtener idPlan
if (isset($_GET['idPlan'])) {
    $idPlan = $_GET['idPlan'];
} elseif (isset($_SESSION['idP'])) {
    $idPlan = $_SESSION['idP'];
} else {
    die("Error: No se ha especificado un plan de estudios.");
}

$membrete=$_SESSION['membrete'];
//preparo imagen para que dompdf la pueda leer
$img = file_get_contents(__DIR__ . '/'.$membrete);
$img_base64 = base64_encode($img);

$nombrePlan=buscarNombrePlan($conn,$idPlan);
$listadoCalificaciones = array();
$listadoCalificaciones = buscarMaterias($conn, $idAlumno, $idPlan);
$cantidad = count($listadoCalificaciones);

// Cargar el contenido HTML
$html = '

<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado Curricular del Alumno/a</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
                }
        .header {
            background-color: #fff;
            color: black;
            text-align: center;
        }
        .header img {
            max-width: 500px;
            height: auto;
        }
        .container {
            width: 100%;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            text-align: center;
        }
        table {
            font-size: 10;
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 3px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
            .columna1 {
        width: 20%;
    }
    .columna2 {
        width: 50%;
    }
        .columna3 {
        width: 25%;
    }.columna4 {
        width: 5%;
    }
        .fecha {
        text-align: right;
        font-size: 14px;
        margin-bottom: 20px;
    }
    </style>
</head>
<body>
    <div class="header">
        <img src="data:image/jpeg;base64,' . $img_base64 . '" alt="Logo">
        <div class="fecha">Fecha de impresión: '.date('d/m/Y H:i').'</div>

<h3>Estado Curricular del Alumno/a: '.$nombreAlumno.'</h3>
        <h4>'.$nombrePlan.'</h4>
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
$dompdf->render();
$dompdf->stream('calif_'.$nombreAlumno.'.pdf', array('Attachment' => 0));