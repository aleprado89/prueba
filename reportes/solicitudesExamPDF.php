<?php
session_start();

// Inclusión de dependencias
include '../vendor/autoload.php';
include '../inicio/conexion.php';

// Buffering para evitar salida previa que corrompa la generación del PDF
ob_start();
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
ob_end_clean();

use Dompdf\Dompdf;
use Dompdf\Options;

// =============================================================================
// 1. VALIDACIÓN, RECEPCIÓN Y SANEAMIENTO DE DATOS
// =============================================================================
// Sanitizamos fuertemente las variables de sesión antes de interactuar o imprimir
$idAlumno     = isset($_SESSION['alu_idAlumno']) ? (int)$_SESSION['alu_idAlumno'] : 0;
$nombreAlumno = htmlspecialchars($_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'], ENT_QUOTES, 'UTF-8');
$dni          = htmlspecialchars($_SESSION['alu_dni'], ENT_QUOTES, 'UTF-8');
$idPlan       = isset($_SESSION['idP']) ? (int)$_SESSION['idP'] : 0;
$nombrePlan   = htmlspecialchars($_SESSION['nombreP'], ENT_QUOTES, 'UTF-8');
$membrete     = $_SESSION['membrete'] ?? '';
$nombreColegio= $_SESSION['nombreColegio'] ?? 'Instituto Superior';

if ($idAlumno === 0) {
    die("Error crítico: Sesión de alumno no válida. Accesos no autorizados serán registrados.");
}

$cicloLectivo   = $datosColegio[0]['anioautoweb'] ?? date('Y');
$idCicloLectivo = buscarIdCiclo($conn, $cicloLectivo);
$idTurno        = isset($datosColegio[0]['idTurno']) ? (int)$datosColegio[0]['idTurno'] : null;

// =============================================================================
// 2. OBTENCIÓN DE DATOS CENTRALIZADA (consultas.php)
// =============================================================================

// Buscamos las solicitudes de examen del alumno
$solicitudes = buscarSolicitudesExamen($conn, $idAlumno, $idPlan, $idCicloLectivo, $idTurno);

// Reutilizamos la función para buscar el curso actual del alumno (índice 0 es la última matriculación)
$matriculaciones = obtenerMatriculacionesPlanAlumno($conn, $idAlumno);
$nombreCurso = 'Sin asignar';
if (!empty($matriculaciones)) {
    $nombreCurso = htmlspecialchars($matriculaciones[0]['nombreCurso'], ENT_QUOTES, 'UTF-8');
}

// =============================================================================
// 3. PREPARACIÓN DE DOMPDF Y MEMBRETE
// =============================================================================

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);

// Procesamiento seguro de la imagen para el membrete
$rutaImagen = __DIR__ . '/' . $membrete;
$img_base64 = '';
if (file_exists($rutaImagen) && !empty($membrete)) {
    $type = pathinfo($rutaImagen, PATHINFO_EXTENSION);
    $data = file_get_contents($rutaImagen);
    $img_base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

// =============================================================================
// 4. GENERACIÓN DE LA ESTRUCTURA HTML Y CSS (Aesthetics)
// =============================================================================

$html = '
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitudes Examen</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        
        /* Encabezado y Logo */
        .logo-container { text-align: center; margin-bottom: 15px; }
        .titulo-comprobante { text-align: center; font-size: 20px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; color: #2c3e50; }
        .fecha-impresion { text-align: right; font-size: 10px; color: #7f8c8d; margin-bottom: 20px; }
        
        /* Caja de Información del Alumno (Diseño Cuadriculado Atractivo) */
        .info-box { 
            border: 2px solid #2980b9; 
            border-radius: 6px; 
            padding: 12px; 
            margin-bottom: 25px; 
            background-color: #f8f9fa; 
        }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px; vertical-align: middle; font-size: 13px; }
        .info-label { font-weight: bold; color: #2c3e50; width: 15%; text-align: right; padding-right: 10px; }
        .info-data { width: 35%; color: #34495e; font-weight: bold; }
        
        /* Tabla Principal de Datos */
        .tabla-solicitudes { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabla-solicitudes th, .tabla-solicitudes td { border: 1px solid #bdc3c7; padding: 10px; text-align: left; }
        .tabla-solicitudes th { background-color: #34495e; color: #ffffff; font-weight: bold; text-align: center; text-transform: uppercase; font-size: 11px;}
        .tabla-solicitudes td.text-center { text-align: center; }
        
        /* Estilos dinámicos para los estados */
        .estado-Aprobada { color: #27ae60; font-weight: bold; }
        .estado-Rechazada { color: #c0392b; font-weight: bold; }
        .estado-Cancelada { color: #c0392b; font-weight: bold; }
        .estado-Pendiente { color: #f39c12; font-weight: bold; }
    </style>
</head>
<body>

    <div class="logo-container">
        ' . ($img_base64 ? '<img src="' . $img_base64 . '" style="max-height: 140px; max-width: 100%; height: auto;">' : '<h2>' . $nombreColegio . '</h2>') . '
    </div>

    <div class="titulo-comprobante">Comprobante de Solicitudes de Examen</div>
    <div class="fecha-impresion">Fecha de impresión: ' . date('d/m/Y H:i') . '</div>

    <div class="info-box">
        <table class="info-table">
            <tr>
                <td class="info-label">Estudiante:</td>
                <td class="info-data">' . $nombreAlumno . '</td>
                <td class="info-label">DNI:</td>
                <td class="info-data">' . $dni . '</td>
            </tr>
            <tr>
                <td class="info-label">Carrera:</td>
                <td class="info-data">' . $nombrePlan . '</td>
                <td class="info-label">Curso:</td>
                <td class="info-data">' . $nombreCurso . '</td>
            </tr>
        </table>
    </div>

    <table class="tabla-solicitudes">
        <thead>
            <tr>
                <th width="40%">Materia</th>
                <th width="20%">Fecha / Turno</th>
                <th width="15%">Estado</th>
                <th width="25%">Observaciones</th>
            </tr>
        </thead>
        <tbody>';

// Recorrido de los resultados. Aseguramos saneamiento estricto al renderizar.
if (!empty($solicitudes)) {
    foreach ($solicitudes as $solicitud) {
        $materiaStr = htmlspecialchars($solicitud['Materia'], ENT_QUOTES, 'UTF-8');
        // Formatear la fecha. En el caso de exámenes puede ser útil tener la hora o al menos validarla correctamente.
        $fechaStr   = date('d/m/Y H:i', strtotime($solicitud['Fecha'])); 
        $estadoStr  = htmlspecialchars($solicitud['Estado'], ENT_QUOTES, 'UTF-8');
        $obsStr     = htmlspecialchars($solicitud['Observaciones'] ?? '-', ENT_QUOTES, 'UTF-8');
        
        // Asignamos una clase CSS dinámica basada en el estado
        $claseEstado = 'estado-' . str_replace(' ', '', $estadoStr);

        $html .= '
        <tr>
            <td>' . $materiaStr . '</td>
            <td class="text-center">' . $fechaStr . '</td>
            <td class="text-center ' . $claseEstado . '">' . $estadoStr . '</td>
            <td>' . $obsStr . '</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="4" class="text-center" style="padding: 20px;">No posee solicitudes de examen registradas para este turno.</td></tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';
        
// =============================================================================
// 5. RENDERIZADO DEL PDF
// =============================================================================
try {
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // 1. Limpiamos el nombre de manera segura (eliminando espacios y caracteres raros)
    $cleanNombre = preg_replace('/[^a-zA-Z0-9]/', '_', $_SESSION['alu_apellido']);
    
    // 2. Agregamos el timestamp único para romper la caché del celular
    $timestamp = date('Ymd_His');
    $filename = 'solicitudesExamen_' . $cleanNombre . '_' . $timestamp . '.pdf';
    
    // 3. Forzamos cabeceras HTTP para evitar que el navegador móvil guarde el PDF anterior
    header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
    header("Pragma: no-cache"); // HTTP 1.0
    header("Expires: 0"); // Proxies
    
    // 4. Cambiamos 'Attachment' a 1 para forzar la descarga y apertura del nuevo archivo
    $dompdf->stream($filename, array('Attachment' => 1));

} catch (Exception $e) {
    error_log("Error crítico generando PDF de solicitudes de examen: " . $e->getMessage());
    echo 'Error al generar el PDF. Contacte al administrador del sistema.';
}