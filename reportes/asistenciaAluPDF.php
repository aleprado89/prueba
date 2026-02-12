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

// Get parameters
$idAlumno = filter_var($_GET['idAlumno'], FILTER_SANITIZE_NUMBER_INT);
$idCiclo = filter_var($_GET['idCiclo'], FILTER_SANITIZE_NUMBER_INT);
$mes = filter_var($_GET['mes'], FILTER_SANITIZE_NUMBER_INT);
$plan_nombre = htmlspecialchars($_GET['plan_nombre'], ENT_QUOTES, 'UTF-8');
$nombre_alumno = htmlspecialchars($_GET['nombre_alumno'], ENT_QUOTES, 'UTF-8'); // Passed from calificaciones.php

// Buscamos el año del ciclo lectivo para el PDF
$ciclolectivo_anio = buscarnombreCiclo($conn, $idCiclo);

$membrete = $_SESSION['membrete'];

// Name of the month
$nombreMes = array(
  1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
  5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
  9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
);
$nombreMes = $nombreMes[$mes];

// Prepare image for Dompdf
$img = file_get_contents(__DIR__ . '/' . $membrete);
$img_base64 = base64_encode($img);

// Determine number of days in the month
$num_dias = cal_days_in_month(CAL_GREGORIAN, $mes, $ciclolectivo_anio);

// Construct columns for SQL query dynamically
$columnasAsistencia = [];
for ($i = 1; $i <= $num_dias; $i++) {
    $columnasAsistencia[] = "asis.d" . $i;
}
$columnasAsistencia_sql = implode(',', $columnasAsistencia);

// Get attendance data for the student
$materiasAsis = obtenerAsistenciaAlumnoPDF($conn, $columnasAsistencia_sql, $idAlumno, $mes, $idCiclo);

// Cargar el contenido HTML
$html = '
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistencia de Alumno</title>
    <style>
    /* Estilos de Bootstrap */
    body {
      font-family: Arial, sans-serif;
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
    table {
      border-collapse: collapse;
      width: 100%;
      font-size: 10px; /* Reduce el tamaño de la letra */
    }

    th, td {
      border: 1px solid #ddd;
      padding: 2px; /* Reduce el padding */
      text-align: center; /* Center align for attendance codes */
      width: 20px; /* Establece un ancho fijo para las columnas */
    }

    th {
      background-color: #f0f0f0;
    }

    th:first-child, td:first-child { /* Establece un ancho mayor para la columna "Materia" */
      width: 150px;
      text-align: left; /* Left align for subject name */
    }
    </style>
<link rel="icon" type="image/png" href="../img/icon.png">

</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td style="width: 20%; text-align: center;">
                    <img src="data:image/jpeg;base64,' . $img_base64 . '" alt="Logo" style="max-width: 100px;">
                </td>
                <td style="width: 80%; font-size: 12px; padding-left: 3px; box-sizing: border-box;">
                    <div style="padding-left: 10px;">
                        <h2 style="font-size: 14px;">Asistencia Mensual por Alumno</h2>
                        <h3>Alumno: ' . $nombre_alumno . '</h3>
                        <h3>' . $plan_nombre . ' - ' . $nombreMes . ' ' . $ciclolectivo_anio . '</h3>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="container">
        <table>
            <thead>
                <tr>
                    <th>Materia</th>
                    ' . implode('', array_map(function($i) { return '<th>' . $i . '</th>'; }, range(1, $num_dias))) . '
                </tr>
            </thead>
            <tbody>';

if (empty($materiasAsis)) {
    $html .= '<tr><td colspan="' . ($num_dias + 1) . '">No hay registros de asistencia para este alumno en el mes y año seleccionados.</td></tr>';
} else {
    foreach ($materiasAsis as $materia) {
        $html .= '<tr>';
        $html .= '<td>' . $materia['nombreMateria'] . '</td>';
        for ($i = 1; $i <= $num_dias; $i++) {
            $html .= '<td>' . ($materia['d' . $i] ?? '') . '</td>'; // Use null coalescing to handle potentially missing days (e.g., if columnsAsistencia had less days than num_dias)
        }
        $html .= '</tr>';
    }
}

$html .= '
            </tbody>
        </table>
        <br>
        <p style="font-size: 9px;">
            En esta tabla se muestra la asistencia del alumno en cada día del mes para las materias que estuvo matriculado en este ciclo lectivo y mes.
        </p>
    </div>
</body>
</html>';
        
// Generar el PDF
 try {
     $dompdf->loadHtml($html);
     $dompdf->setPaper('A4', 'landscape'); // Establece el tamaño del papel en A4 y la orientación en horizontal
     $dompdf->render();
     $dompdf->stream('asistencia_' . str_replace(' ', '_', $nombre_alumno) . '_' . $nombreMes . '.pdf', array('Attachment' => 0));
 } catch (Exception $e) {
     echo 'Error al generar el PDF: ' . $e->getMessage();
 }

?>