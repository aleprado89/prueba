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
$idMateria = $_GET['idMateria'];
$materia = $_GET['materia'];
$curso = $_GET['curso'];
$idciclolectivo = $_GET['ciclolectivo'];
$ciclolectivo=buscarnombreCiclo($conn,$idciclolectivo);
$plan = $_GET['plan'];
//$materia = $_SESSION['materia'];
$membrete=$_SESSION['membrete'];

$alumnosCalif = obtenerCalificacionesMateriaPDF($conn, $idMateria);

//preparo imagen para que dompdf la pueda leer
$img = file_get_contents(__DIR__ . '/'.$membrete);
$img_base64 = base64_encode($img);


// Cargar el contenido HTML
$html = '

<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de calificaciones</title>
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
        }
        .columna4 {
            width: 5%;
        }
            h3, h4,h5 {
    line-height: 0.2; /* ajusta el valor según tus necesidades */
}
    </style>
</head>
<body>
    <div class="header">
        <img src="data:image/jpeg;base64,' . $img_base64 . '" alt="Logo">
        <h4>Listado de calificaciones</h4>
        <h5>Plan: ' . $plan . '</h5>
        <h5>Materia: ' . $materia . '</h5>
        <h5>Curso: ' . $curso . '</h5>
        <h5>Ciclo Lectivo: ' . $ciclolectivo . '</h5>
        
    </div>
    <div class="container">
        <table>
            <thead>
                <tr>
                    <th scope="col">Estudiante</th>
              <th scope="col">P1</th>
              <th scope="col">P2</th>
              <th scope="col">P3</th>
              <th scope="col">P4</th>
              <th scope="col">P5</th>
              <th scope="col">P6</th>
              <th scope="col">P7</th>
              <th scope="col">IEFI</th>
              <th scope="col">RP1</th>
              <th scope="col">RP2</th>
              <th scope="col">RP3</th>
              <th scope="col">RP4</th>
              <th scope="col">RP5</th>
              <th scope="col">RP6</th>
              <th scope="col">RP7</th>
              <th scope="col">RIEFI</th>
              <th scope="col">Asist</th>

                </tr>
            </thead>
            <tbody>';
foreach ($alumnosCalif as $alumno) {
    $html .= '
                <tr>
                    <td>' . $alumno['apellido'] . ', ' . $alumno['nombre'] . '</td>
                    <td>' . $alumno['n1'] . '</td>
                    <td>' . $alumno['n2'] . '</td>
                    <td>' . $alumno['n3'] . '</td>
                    <td>' . $alumno['n4'] . '</td>
                    <td>' . $alumno['n5'] . '</td>
                    <td>' . $alumno['n6'] . '</td>
                    <td>' . $alumno['n7'] . '</td>
                    <td>' . $alumno['n8'] . '</td>
                    <td>' . $alumno['r1'] . '</td>
                    <td>' . $alumno['r2'] . '</td>
                    <td>' . $alumno['r3'] . '</td>
                    <td>' . $alumno['r4'] . '</td>
                    <td>' . $alumno['r5'] . '</td>
                    <td>' . $alumno['r6'] . '</td>
                    <td>' . $alumno['r7'] . '</td>
                    <td>' . $alumno['r8'] . '</td>
                    <td>' . $alumno['asistencia'] . '</td>

                </tr>';
}
$html .= '
            </tbody>
        </table>
    </div>
</body>
</html>';
        
// Generar el PDF
 try {
     $dompdf->loadHtml($html);
     $dompdf->render();
 $dompdf->stream('calif_'.$nombreAlumno.'.pdf', array('Attachment' => 0));
 } catch (Exception $e) {
     echo 'Error al generar el PDF: ' . $e->getMessage();
 }