<?php
session_start();
// Ajusta la ruta al autoload de Composer si es necesario
include '../vendor/autoload.php';
include '../inicio/conexion.php';

// Buffering para evitar errores de salida antes de generar el PDF
ob_start();
include '../funciones/consultas.php';
ob_end_clean();

use Dompdf\Dompdf;
use Dompdf\Options;

// =============================================================================
// 1. VALIDACIÓN Y RECEPCIÓN DE PARÁMETROS
// =============================================================================

if (!isset($_GET['idFechaExamen']) || empty($_GET['idFechaExamen'])) {
    die("Error: No se ha especificado el ID de la mesa de examen.");
}

$idFechaExamen = (int)$_GET['idFechaExamen'];
$condicionFiltro = $_GET['condicion'] ?? ''; // Recibimos 'Regular', 'Libre', etc.

// Validación estricta: No permitimos imprimir sin filtrar por condición
if (empty($condicionFiltro) || $condicionFiltro == 'todos') {
    die("Error: Debe especificar una condición válida (Regular, Libre, etc.) para generar el Acta Volante.");
}

$nombreColegio = $_SESSION['nombreColegio'] ?? 'Instituto Superior';
$membrete = $_SESSION['membrete'] ?? '';

// =============================================================================
// 2. OBTENCIÓN DE DATOS (USANDO LA FUNCIÓN CENTRALIZADA)
// =============================================================================

// Esta función devuelve un array con ['cabecera'] y ['alumnos']
$datosCompletos = obtenerDetalleActaCompleto($conn, $idFechaExamen);
$cabecera = $datosCompletos['cabecera'];
$todosAlumnos = $datosCompletos['alumnos'];

if (!$cabecera) {
    die("Error: No se encontraron datos para la mesa seleccionada.");
}

// =============================================================================
// 3. FILTRADO DE ALUMNOS SEGÚN CONDICIÓN
// =============================================================================

$alumnosFiltrados = [];
foreach ($todosAlumnos as $alu) {
    // La función devuelve 'condicion' (ej: Regular, Libre)
    $condAlumno = $alu['condicion'] ?? ''; 
    
    // Comparamos lo que seleccionó el usuario con lo que tiene el alumno
    if ($condAlumno === $condicionFiltro) {
        $alumnosFiltrados[] = $alu;
    }
}

// =============================================================================
// 4. PREPARACIÓN DE UTILIDADES
// =============================================================================

function calificacionALetras($nota) {
    if ($nota === null || $nota === '') return '-';
    $nota = (int)$nota;
    $mapa = [
        1 => 'UNO', 2 => 'DOS', 3 => 'TRES', 4 => 'CUATRO', 5 => 'CINCO',
        6 => 'SEIS', 7 => 'SIETE', 8 => 'OCHO', 9 => 'NUEVE', 10 => 'DIEZ'
    ];
    return $mapa[$nota] ?? '-';
}

$fechaExamen = date("d/m/Y", strtotime($cabecera['fecha']));

// Configuración Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);

// Procesamiento del Membrete
$rutaImagen = __DIR__ . '/' . $membrete;
$img_base64 = '';
if (file_exists($rutaImagen) && !empty($membrete)) {
    $type = pathinfo($rutaImagen, PATHINFO_EXTENSION);
    $data = file_get_contents($rutaImagen);
    $img_base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

// =============================================================================
// 5. GENERACIÓN DEL HTML
// =============================================================================

$html = '
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta Volante</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }
        
        /* Cabecera */
        .logo-container { text-align: center; margin-bottom: 5px; }
        .titulo-acta { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 12px; }
        .header-table td { padding: 3px; vertical-align: top; }
        .bold { font-weight: bold; }
        
        /* Tabla de Alumnos */
        .tabla-alumnos { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .tabla-alumnos th, .tabla-alumnos td { border: 1px solid #000; padding: 5px; font-size: 11px; }
        .tabla-alumnos th { background-color: #f0f0f0; text-align: center; font-weight: bold; }
        
        .text-center { text-align: center; }
        .text-left { text-align: left; padding-left: 5px; }
        
        /* Pie de página */
        .footer-container { margin-top: 30px; width: 100%; }
        .tabla-resumen { width: 45%; border-collapse: collapse; float: left; }
        .tabla-resumen td { border: 1px solid #000; padding: 5px; font-size: 10px; }
        
        .firmas-container { width: 45%; float: right; text-align: center; margin-top: 50px; }
        .firma-linea { border-top: 1px solid #000; width: 90%; margin: 0 auto 5px auto; }
        
        .clear { clear: both; }
    </style>
<link rel="icon" type="image/png" href="../img/icon.png">

</head>
<body>

    <div class="logo-container">
        ' . ($img_base64 ? '<img src="' . $img_base64 . '" style="max-height: 70px;">' : '') . '
        <h3>' . htmlspecialchars($nombreColegio) . '</h3>
    </div>

    <div class="titulo-acta">Acta Volante de Exámenes</div>

    <table class="header-table">
        <tr>
            <td class="bold" width="15%">Fecha:</td>
            <td width="35%">' . $fechaExamen . '</td>
            <td class="bold" width="15%">Ciclo Lectivo:</td>
            <td width="35%">' . htmlspecialchars($cabecera['cicloLectivo']) . '</td>
        </tr>
        <tr>
            <td class="bold">Turno:</td>
            <td>' . htmlspecialchars($cabecera['nombreTurno']) . '</td>
            <td class="bold">Condición:</td>
            <td style="text-transform: uppercase;">' . strtoupper($condicionFiltro) . '</td>
        </tr>
        <tr>
            <td class="bold">Plan:</td>
            <td colspan="3">' . htmlspecialchars($cabecera['nombrePlan']) . '</td>
        </tr>
        <tr>
            <td class="bold">Curso:</td>
            <td>' . htmlspecialchars($cabecera['nombreCurso']) . '</td>
            <td class="bold">Materia:</td>
            <td>' . htmlspecialchars($cabecera['nombreMateria']) . '</td>
        </tr>
    </table>

    <table class="tabla-alumnos">
        <thead>
            <tr>
                <th width="5%">Ord.</th>
                <th width="15%">DNI</th>
                <th width="50%">Apellido y Nombre</th>
                <th width="10%">Calif.</th>
                <th width="20%">En Letras</th>
            </tr>
        </thead>
        <tbody>';

if (count($alumnosFiltrados) > 0) {
    $orden = 1;
    foreach ($alumnosFiltrados as $alu) {
        $nota = $alu['calificacion'];
        $notaLetras = calificacionALetras($nota);
        $notaNum = ($nota !== null && $nota !== '') ? $nota : '-';
        
        $html .= '<tr>
                    <td class="text-center">' . $orden++ . '</td>
                    <td class="text-center">' . htmlspecialchars($alu['dni']) . '</td>
                    <td class="text-left">' . htmlspecialchars($alu['apellido'] . ', ' . $alu['nombre']) . '</td>
                    <td class="text-center bold" style="font-size:12px;">' . $notaNum . '</td>
                    <td class="text-center">' . $notaLetras . '</td>
                  </tr>';
    }
} else {
    $html .= '<tr><td colspan="5" class="text-center" style="padding: 20px;">
                No hay alumnos inscriptos con condición <strong>' . strtoupper($condicionFiltro) . '</strong> en esta mesa.
              </td></tr>';
}

$html .= '
        </tbody>
    </table>

    <div class="footer-container">
        
        <table class="tabla-resumen">
            <tr>
                <td width="80%">CANTIDAD DE APROBADOS</td>
                <td></td>
            </tr>
            <tr>
                <td>CANTIDAD DE APLAZADOS</td>
                <td></td>
            </tr>
            <tr>
                <td>CANTIDAD DE AUSENTES</td>
                <td></td>
            </tr>
            <tr>
                <td class="bold">TOTAL DE ALUMNOS</td>
                <td></td>
            </tr>
        </table>

        <div class="firmas-container">
            <div class="firma-linea"></div>
            <div class="bold">' . htmlspecialchars($cabecera['apellidoDocente'] . ' ' . $cabecera['nombreDocente']) . '</div>
            <div>Docente Titular</div>
        </div>

        <div class="clear"></div>
    </div>

</body>
</html>';

// =============================================================================
// 6. RENDERIZADO Y DESCARGA
// =============================================================================

try {
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Nombre de archivo limpio para la descarga
    $cleanMateria = preg_replace('/[^a-zA-Z0-9]/', '_', $cabecera['nombreMateria']);
    $filename = "Acta_" . $cleanMateria . "_" . $condicionFiltro . "_" . date("dmY") . ".pdf";
    
    // "Attachment" => false abre en el navegador, true descarga directa
    $dompdf->stream($filename, ["Attachment" => false]);

} catch (Exception $e) {
    echo 'Error al generar el PDF: ' . $e->getMessage();
}
?>