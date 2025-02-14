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
$idMateria = filter_var($_GET['idMateria'], FILTER_SANITIZE_NUMBER_INT);
$mes = filter_var($_GET['mes'], FILTER_SANITIZE_NUMBER_INT);
$mes = ltrim($mes, '0');
$ciclolectivo = $_SESSION['ciclolectivo'];
$plan = $_SESSION['plan'];
$materia = $_SESSION['materia'];
$membrete=$_SESSION['membrete'];

$nombreMes = array(
  1 => 'Enero',
  2 => 'Febrero',
  3 => 'Marzo',
  4 => 'Abril',
  5 => 'Mayo',
  6 => 'Junio',
  7 => 'Julio',
  8 => 'Agosto',
  9 => 'Septiembre',
  10 => 'Octubre',
  11 => 'Noviembre',
  12 => 'Diciembre'
);
$nombreMes = $nombreMes[$mes];

//preparo imagen para que dompdf la pueda leer
$img = file_get_contents(__DIR__ . '/'.$membrete);
$img_base64 = base64_encode($img);

$num_dias = cal_days_in_month(CAL_GREGORIAN, $mes, $ciclolectivo);
$columnasAsistencia="";
if ($num_dias == 31) {
    $columnasAsistencia="asis.d1,asis.d2,asis.d3,asis.d4,asis.d5,asis.d6,asis.d7,asis.d8,asis.d9,asis.d10,
    asis.d11,asis.d12,asis.d13,asis.d14,asis.d15,asis.d16,asis.d17,asis.d18,asis.d19,asis.d20,
    asis.d21,asis.d22,asis.d23,asis.d24,asis.d25,asis.d26,asis.d27,asis.d28,asis.d29,asis.d30,asis.d31";
} else if ($num_dias == 30) {
    $columnasAsistencia="asis.d1,asis.d2,asis.d3,asis.d4,asis.d5,asis.d6,asis.d7,asis.d8,asis.d9,asis.d10,
    asis.d11,asis.d12,asis.d13,asis.d14,asis.d15,asis.d16,asis.d17,asis.d18,asis.d19,asis.d20,
    asis.d21,asis.d22,asis.d23,asis.d24,asis.d25,asis.d26,asis.d27,asis.d28,asis.d29,asis.d30";
}
else if ($num_dias == 29) {
    $columnasAsistencia="asis.d1,asis.d2,asis.d3,asis.d4,asis.d5,asis.d6,asis.d7,asis.d8,asis.d9,asis.d10,
    asis.d11,asis.d12,asis.d13,asis.d14,asis.d15,asis.d16,asis.d17,asis.d18,asis.d19,asis.d20,
    asis.d21,asis.d22,asis.d23,asis.d24,asis.d25,asis.d26,asis.d27,asis.d28,asis.d29";
}
else if ($num_dias == 28) {
    $columnasAsistencia="asis.d1,asis.d2,asis.d3,asis.d4,asis.d5,asis.d6,asis.d7,asis.d8,asis.d9,asis.d10,
    asis.d11,asis.d12,asis.d13,asis.d14,asis.d15,asis.d16,asis.d17,asis.d18,asis.d19,asis.d20,
    asis.d21,asis.d22,asis.d23,asis.d24,asis.d25,asis.d26,asis.d27,asis.d28";
}


$idciclo=buscarIdCiclo($conn, $ciclolectivo);
$alumnosAsis=obtenerAsistenciaMateriaPDF($conn, $columnasAsistencia, $idMateria, $mes, $idciclo);

// Cargar el contenido HTML
$html = '

<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de asistencia</title>
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
  text-align: left;
  width: 20px; /* Establece un ancho fijo para las columnas */
}

th {
  background-color: #f0f0f0;
}

th:first-child { /* Establece un ancho mayor para la columna "Alumno" */
  width: 150px;
}  
  </style>
</head>
<body>
    <div class="header">
        <img src="data:image/jpeg;base64,' . $img_base64 . '" alt="Logo">
        <h1>Listado de asistencia por materia</h1>
        <h2>Plan: ' . $plan . '</h2>
        <h2>Materia: ' . $materia . '</h2>
        <h2>'.$nombreMes.' - ' . $ciclolectivo . '</h2>

    </div>
    <div class="container">
        <table>
    <thead>
      <tr>
        <th>Alumno</th>
        ' . implode('', array_map(function($i) { return '<th>' . $i . '</th>'; }, range(1, $num_dias))) . '
      </tr>
    </thead>
    <tbody>';

foreach ($alumnosAsis as $alumno) {
    $html .= '
            <tr>
                <td>' . $alumno['apellido'] . ' ' . $alumno['nombre'] . '</td>';
    for ($i = 1; $i <= $num_dias; $i++) {
        $html .= '
                    <td>' . $alumno['d' . $i] . '</td>';
    }
    $html .= '
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
     $dompdf->setPaper('A4', 'landscape'); // Establece el tamaño del papel en A4 y la orientación en horizontal
     $dompdf->render();
     $dompdf->stream('asist_'.$materia.'.pdf', array('Attachment' => 0));
 } catch (Exception $e) {
     echo 'Error al generar el PDF: ' . $e->getMessage();
 }