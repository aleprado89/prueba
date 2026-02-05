<?php
/**
 * solicitudesCursado.php
 * Módulo de Administración para aceptar/rechazar inscripciones a cursado.
 * Lógica: Búsqueda rápida + Validación asíncrona (Barra de Progreso) + Autoridad de decisión manual.
 * Autor: Programador de SistemasEscolares
 */

// --- LIMPIEZA DE SALIDA ---
ob_start();

include_once '../funciones/verificarSesion.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

include_once '../inicio/conexion.php';
include_once '../funciones/consultas.php';

// Inicialización para controlCorrelatividad
$materiasAdeuda = ''; 
include_once '../funciones/controlCorrelatividad.php';

// --- AJAX HANDLER ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    ob_clean(); 
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'data' => [], 'message' => 'Acción no válida.'];
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'load_cursos':
                $idPlan = filter_input(INPUT_POST, 'idPlan', FILTER_VALIDATE_INT);
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                if ($idPlan && $idCiclo) {
                    $response = ['success' => true, 'data' => buscarCursosPlanCiclo($conn, $idPlan, $idCiclo)];
                }
                break;

            case 'load_materias':
                $idPlan = filter_input(INPUT_POST, 'idPlan', FILTER_VALIDATE_INT);
                $idCurso = filter_input(INPUT_POST, 'idCurso', FILTER_VALIDATE_INT);
                if ($idPlan) {
                     $response = ['success' => true, 'data' => materiasPlanCurso($conn, $idPlan, $idCurso)]; 
                }
                break;
            
            case 'get_condiciones':
                $condiciones = obtenerCondicionesCursado($conn);
                $response = ['success' => true, 'data' => $condiciones];
                break;

            // 1. BÚSQUEDA RÁPIDA (Solo devuelve datos, no valida)
            case 'buscar_solicitudes':
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                $idPlan = filter_input(INPUT_POST, 'idPlan', FILTER_VALIDATE_INT);
                $idCurso = filter_input(INPUT_POST, 'idCurso', FILTER_VALIDATE_INT); 
                $idMateria = filter_input(INPUT_POST, 'idMateria'); 
                $verHistorico = filter_input(INPUT_POST, 'verHistorico', FILTER_VALIDATE_INT);

                if (!$idCiclo || !$idPlan) {
                    echo json_encode(['success'=>false, 'message'=>'Faltan parámetros Ciclo/Plan.']);
                    exit;
                }

                $estadoBuscar = ($verHistorico == 1) ? 2 : 1; 

                if (!function_exists('buscarSolicitudesCursadoWeb')) {
                    throw new Exception("Error: Función buscarSolicitudesCursadoWeb no encontrada.");
                }

                $solicitudes = buscarSolicitudesCursadoWeb($conn, $idCiclo, $idMateria, $estadoBuscar);
                
                if ($verHistorico == 1) {
                     // Si es histórico, traemos también las rechazadas (estado 3)
                     $solicitudesRechazadas = buscarSolicitudesCursadoWeb($conn, $idCiclo, $idMateria, 3);
                     $solicitudes = array_merge($solicitudes, $solicitudesRechazadas);
                }

                $dataProcessed = [];

                foreach ($solicitudes as $sol) {
                    $idInscripcion = $sol['id_matriculacion_web'];
                    $estadoTexto = "";
                    if ($verHistorico == 1) {
                        switch($sol['estado']) {
                            case 2: $estadoTexto = "INSCRIPCIÓN CORRECTA"; break;
                            case 3: $estadoTexto = "INSCRIPCIÓN RECHAZADA"; break;
                            default: $estadoTexto = "ESTADO " . $sol['estado'];
                        }
                    }

                    $dataProcessed[] = [
                        'idInscripcionWeb' => $idInscripcion,
                        'idAlumno' => $sol['idAlumno'],
                        'idMateria' => $sol['idMateria'],
                        'idUnicoMateria' => $sol['idUnicoMateria'], // Fundamental para validar después
                        'alumno' => $sol['apellido'] . ', ' . $sol['nombre'] . ' (' . $sol['dni'] . ')',
                        'materia' => $sol['nombreMateria'],
                        'curso' => $sol['nombreCurso'] ?? '-',
                        'condicionSolicitada' => $sol['condicion'],
                        'fechaSolicitud' => date('d/m/Y H:i', strtotime($sol['fechhora_inscri'])),
                        'observaciones' => $sol['observaciones'],
                        'estadoNumero' => $sol['estado'],
                        'estadoTexto' => $estadoTexto
                    ];
                }

                $response = ['success' => true, 'data' => $dataProcessed];
                break;

            // 2. VALIDACIÓN INDIVIDUAL (Llamada por la barra de progreso)
            case 'validar_individual':
                $idUnicoMateria = filter_input(INPUT_POST, 'idUnicoMateria'); 
                $idAlumno = filter_input(INPUT_POST, 'idAlumno', FILTER_VALIDATE_INT);

                if(!$idUnicoMateria || !$idAlumno){
                     $response = ['success' => false, 'message' => 'Datos insuficientes.'];
                } else {
                    $esValidaCorrelativas = false;
                    $observacion = "Verificando...";
                    
                    global $materiasAdeuda;
                    $materiasAdeuda = ''; 

                    // 0 = Tipo Inscripción Cursado
                    if (function_exists('controlCorrelatividades')) {
                        $esValidaCorrelativas = controlCorrelatividades((int)$idUnicoMateria, (int)$idAlumno, 0);
                    }

                    if ($esValidaCorrelativas) {
                        $observacion = ""; 
                        $accionSugerida = "ACEPTAR";
                    } else {
                        $accionSugerida = "RECHAZAR";
                        $observacion = !empty($materiasAdeuda) ? $materiasAdeuda : "No cumple correlativas.";
                    }

                    $response = [
                        'success' => true, 
                        'esValida' => $esValidaCorrelativas, 
                        'observacion' => $observacion,
                        'accionSugerida' => $accionSugerida
                    ];
                }
                break;

            // 3. PROCESAMIENTO FINAL (Obedece decisión manual)
          case 'procesar_lote_final':
                $lista = json_decode($_POST['lista'], true); 
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                
                if (!$lista || !is_array($lista) || !$idCiclo) {
                    echo json_encode(['success'=>false, 'message'=>'Datos inválidos o Ciclo no seleccionado.']);
                    exit;
                }

                $procesadosExito = 0;
                $detallesError = []; 

                foreach ($lista as $item) {
                    // Aseguramos que sea entero
                    $idWeb = intval($item['idWeb']);
                    $accion = $item['accion']; 
                    $idCondicion = intval($item['idCondicion']); 
                    $observacionUI = $item['observacionUI']; 
                    $nombreAlumno = $item['alumno'] ?? 'Alumno';

                    // 1. Buscamos los datos originales
                    $datosOrig = obtenerSolicitudCursadoWebPorId($conn, $idWeb);

                    if (!$datosOrig) { 
                        // MENSAJE DE DEPURACIÓN: Mostramos el ID que falló
                        $detallesError[] = "<b>$nombreAlumno:</b> Solicitud ID ($idWeb) no encontrada en BD.";
                        continue; 
                    } 

                    if ($accion === 'ACEPTAR') {
                        if (!$idCondicion) {
                            $detallesError[] = "<b>$nombreAlumno:</b> No seleccionó condición.";
                            continue;
                        }

                        // 2. Traducir ID Condición a Texto (Para tabla matriculacionmateria)
                        $textoCondicion = "Regular"; 
                        $sqlCond = "SELECT condicion FROM condicionescursado WHERE idCondicion = ?";
                        $stmtC = $conn->prepare($sqlCond);
                        if ($stmtC) {
                            $stmtC->bind_param("i", $idCondicion);
                            $stmtC->execute();
                            $resC = $stmtC->get_result();
                            if ($rowC = $resC->fetch_assoc()) {
                                $textoCondicion = $rowC['condicion'];
                            }
                            $stmtC->close();
                        }
                        
                        // 3. Insertar Matriculación Real
                        $datosMatricula = [
                            'idAlumno' => $datosOrig['idAlumno'],
                            'idMateria' => $datosOrig['idMateria'],
                            'fechaMatriculacion' => date('Y-m-d'),
                            'fechaBajaMatriculacion' => null,
                            'estado' => $textoCondicion, // Texto (ej: Regular, Promocional)
                            'idCicloLectivo' => $idCiclo
                        ];

                        if (insertarMatriculacionMateria($conn, $datosMatricula)) {
                            // Inicializar datos académicos
                            inicializarAsistenciaMateria($conn, $datosOrig['idAlumno'], $datosOrig['idMateria'], $idCiclo);
                            inicializarCalificacionMateria($conn, $datosOrig['idAlumno'], $datosOrig['idMateria']);

                            // Actualizar Web a ACEPTADA (2)
                            if (actualizarEstadoSolicitudCursadoWeb($conn, $idWeb, 2, "Inscripción Correcta")) {
                                $procesadosExito++;
                            } else {
                                $detallesError[] = "<b>$nombreAlumno:</b> Se inscribió, pero falló actualizar la solicitud web.";
                            }
                        } else {
                            $errorDB = $conn->error;
                            actualizarEstadoSolicitudCursadoWeb($conn, $idWeb, 3, "Error BD: " . $errorDB);
                            $detallesError[] = "<b>$nombreAlumno:</b> Error BD al inscribir (posible duplicado).";
                        }

                    } else {
                        // RECHAZAR
                        $motivoRechazo = "Rechazo manual: " . $observacionUI;
                        if (actualizarEstadoSolicitudCursadoWeb($conn, $idWeb, 3, $motivoRechazo)) {
                            $procesadosExito++;
                        } else {
                            $errorDB = $conn->error;
                            $detallesError[] = "<b>$nombreAlumno:</b> Error al rechazar: " . $errorDB;
                        }
                    }
                }
                
                $response = ['success' => true, 'procesados' => $procesadosExito, 'errores' => $detallesError];
                echo json_encode($response);
                exit;
                break;
        }

    } catch (Exception $e) {
        $response['message'] = 'Error servidor: ' . $e->getMessage();
    }
    echo json_encode($response);
    exit;
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistemas Escolares - Solicitudes de cursado</title>
    
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/material/bootstrap.min.css"> 
    <link rel="stylesheet" href="../css/estilos.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body { position: relative; min-height: 100vh; }
        .fondo { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; background-size: cover; background-position: center; }
        .content-wrapper { position: relative; z-index: 1; padding-bottom: 50px; }
        .custom-card { background-color: rgba(255, 255, 255, 0.98); box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 1px solid #ddd; }
        .card-header-custom { background-color: #fff; color: #333; border-bottom: 1px solid #eee; padding: 1rem 1.25rem; }
        
        tr.table-success-custom, tr.table-success-custom > td { background-color: #d1e7dd !important; color: #0f5132 !important; }
        tr.table-danger-custom, tr.table-danger-custom > td { background-color: #f8d7da !important; color: #842029 !important; }
        .table-loading { background-color: #f8f9fa; color: #6c757d; }
        
        .select-condicion-tabla { font-size: 0.9rem; padding: 0.25rem 0.5rem; min-width: 150px; }
        .btn-accion-group .btn-outline-success, .btn-accion-group .btn-outline-danger { padding: 0.2rem 0.5rem; font-size: 0.9rem; }
        .btn-accion-group .btn-check:checked + .btn-outline-success { background-color: #198754; color: white; }
        .btn-accion-group .btn-check:checked + .btn-outline-danger { background-color: #dc3545; color: white; }
    </style>
</head>
<body>

    <?php include '../funciones/menu_secretaria.php'; ?>
    <div class="container-fluid fondo"></div>
    <br>
    
    <div class="content-wrapper container mt-4">
        
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../inicio/inicio.php">Inicio</a></li>
                <li class="breadcrumb-item active">Solicitudes de cursado</li>
            </ol>
        </nav>

        <div class="card custom-card">
            <div class="card-header card-header-custom">
                <h5 class="card-title mb-0"><i class="fas fa-book-reader me-2"></i>Administrar solicitudes de cursado</h5>
            </div>
            
            <div class="card-body">
                <form id="formFiltros" class="row g-3 align-items-end mb-4">
                    <div class="col-md-2">
                        <label class="form-label">Ciclo lectivo</label>
                        <select class="form-select" id="selectCiclo" required>
                            <option value="">Seleccione...</option>
                            <?php
                            $ciclos = levantarCiclosLectivos($conn);
                            foreach ($ciclos as $ciclo) {
                                $idC = $ciclo['idciclolectivo'] ?? $ciclo['idCicloLectivo'] ?? $ciclo['id'];
                                echo "<option value='{$idC}'>{$ciclo['anio']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Plan de Estudio</label>
                        <select class="form-select" id="selectPlan" required>
                            <option value="">Seleccione...</option>
                            <?php
                            $planes = obtenerPlanesDeEstudio($conn);
                            foreach ($planes as $plan) {
                                $idP = $plan['idPlan'] ?? $plan['id'];
                                echo "<option value='{$idP}'>{$plan['nombre']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Curso</label>
                        <select class="form-select" id="selectCurso" disabled><option value="0">Todos</option></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Materia</label>
                        <select class="form-select" id="selectMateria" disabled><option value="0">Todas</option></select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="w-100 btn btn-primary"><i class="bi bi-search"></i></button>
                    </div>
                </form>

                <hr>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="inputBusquedaRapida" placeholder="Filtrar por nombre...">
                    </div>
                </div>

                <ul class="nav nav-tabs mb-3" id="tabSolicitudes" role="tablist">
                    <li class="nav-item"><button class="nav-link active" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#pendientes-pane" type="button">Pendientes</button></li>
                    <li class="nav-item"><button class="nav-link" id="resueltas-tab" data-bs-toggle="tab" data-bs-target="#resueltas-pane" type="button">Resueltas</button></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pendientes-pane">
                        <div class="bg-light p-2 mb-2 border rounded d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary px-3" id="btnSelCorrectos">Tildar Correctos</button>
                            <button type="button" class="btn btn-sm btn-outline-primary px-3" id="btnSelIncorrectos">Tildar Incorrectos</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle" id="tablaPendientes">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center"><input type="checkbox" id="checkAll"></th>
                                        <th>Alumno</th>
                                        <th>Materia</th>
                                        <th>Curso</th>
                                        <th>Fecha solicitud</th>
                                        <th>Condición</th>
                                        <th>Observación</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="8" class="text-center text-muted">Use los filtros y presione buscar.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button class="btn btn-primary" id="btnEjecutar" disabled>Ejecutar inscripción seleccionados</button>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="resueltas-pane">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle" id="tablaResueltas">
                                <thead class="table-light">
                                    <tr><th>Alumno</th><th>Materia</th><th>Curso</th><th>Fecha</th><th>Condición</th><th>Observación</th><th>Estado</th></tr>
                                </thead>
                                <tbody><tr><td colspan="7" class="text-center text-muted">Seleccione filtros.</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalProgreso" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Procesando</h5>
                </div>
                <div class="modal-body text-center">
                    <p id="progresoTexto" class="mb-2">Iniciando...</p>
                    <div class="progress" style="height: 25px;">
                        <div id="barraProgreso" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;">0%</div>
                    </div>
                    <small class="text-muted mt-2 d-block" id="progresoDetalle">Consultando...</small>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAlerta" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="modalAlertaTitulo">Mensaje</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div id="modalAlertaMensaje"></div></div><div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button></div></div></div></div>

    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            const $selCiclo = $('#selectCiclo');
            const $selPlan = $('#selectPlan');
            const $selCurso = $('#selectCurso');
            const $selMateria = $('#selectMateria');
            const modalProgreso = new bootstrap.Modal(document.getElementById('modalProgreso'));
            const modalAlerta = new bootstrap.Modal(document.getElementById('modalAlerta'));
            
            const $barra = $('#barraProgreso');
            const $textoProgreso = $('#progresoTexto');
            const $detalleProgreso = $('#progresoDetalle');
            
            let listaCondiciones = [];

            // Carga inicial
            $.post('solicitudesCursado.php', { action: 'get_condiciones' }, function(res){ if(res.success) listaCondiciones = res.data; }, 'json');

            // Combos
            function checkAndLoadCursos() {
                const idPlan = $selPlan.val(); const idCiclo = $selCiclo.val();
                $selCurso.html('<option value="0">Todos</option>').prop('disabled', true);
                $selMateria.html('<option value="0">Todas</option>').prop('disabled', true);
                if (idPlan && idCiclo) {
                    $.post('solicitudesCursado.php', { action: 'load_cursos', idPlan: idPlan, idCiclo: idCiclo }, function(res) {
                        if(res.success){
                            let opts = '<option value="0">Todos</option>';
                            res.data.forEach(c => opts += `<option value="${c.idCurso}">${c.nombre}</option>`);
                            $selCurso.html(opts).prop('disabled', false);
                        }
                    }, 'json');
                }
            }
            $selPlan.on('change', checkAndLoadCursos);
            $selCiclo.on('change', checkAndLoadCursos);

            $selCurso.on('change', function() {
                const idCurso = $(this).val(); const idPlan = $selPlan.val();
                $selMateria.html('<option value="0">Todas</option>').prop('disabled', true);
                if (idPlan) {
                    $.post('solicitudesCursado.php', { action: 'load_materias', idPlan: idPlan, idCurso: idCurso }, function(res) {
                        if(res.success){
                            let opts = '<option value="0">Todas</option>';
                            res.data.forEach(m => {
                                const nom = m.nombreMateria || m.nombre || 'Materia';
                                opts += `<option value="${m.idMateria}">${nom}</option>`;
                            });
                            $selMateria.html(opts).prop('disabled', false);
                        }
                    }, 'json');
                }
            });

            $('#inputBusquedaRapida').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $(".tab-pane.active table tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // --- BÚSQUEDA Y PROCESO ASÍNCRONO ---
            $('#formFiltros').on('submit', function(e) {
                e.preventDefault();
                const esHistorico = $('#resueltas-tab').hasClass('active');
                if(!$selCiclo.val() || !$selPlan.val()){
                    $('#modalAlertaTitulo').text("Faltan Datos"); 
                    $('#modalAlertaMensaje').text("Complete Ciclo y Plan."); 
                    modalAlerta.show(); 
                    return;
                }
                
                $barra.css('width', '100%').addClass('progress-bar-striped progress-bar-animated');
                $textoProgreso.text('Buscando solicitudes...'); 
                $detalleProgreso.text('');
                modalProgreso.show();

                $.post('solicitudesCursado.php', {
                    action: 'buscar_solicitudes',
                    idCiclo: $selCiclo.val(), idPlan: $selPlan.val(), idCurso: $selCurso.val(), idMateria: $selMateria.val(),
                    verHistorico: esHistorico ? 1 : 0
                }, function(res) {
                    const $tbody = esHistorico ? $('#tablaResueltas tbody') : $('#tablaPendientes tbody');
                    $tbody.empty();
                    const colCount = esHistorico ? 7 : 8;

                    if (!res.success || !res.data || res.data.length === 0) {
                        setTimeout(() => { modalProgreso.hide(); }, 300); 
                        $tbody.html(`<tr><td colspan="${colCount}" class="text-center text-muted">No se encontraron solicitudes.</td></tr>`);
                        return;
                    }

                    const solicitudes = res.data;
                    solicitudes.forEach((row) => {
                        let tr = '';
                        if (esHistorico) {
                            tr = `<tr class="bg-white"><td>${row.alumno}</td><td>${row.materia}</td><td>${row.curso}</td><td>${row.fechaSolicitud}</td><td>${row.condicionSolicitada||'-'}</td><td>${row.observaciones||''}</td><td>${row.estadoTexto}</td></tr>`;
                        } else {
                            // Select Condición
                            let selCond = `<select class="form-select select-condicion-tabla" name="condicion_${row.idInscripcionWeb}">`;
                            if(listaCondiciones.length > 0) {
                                listaCondiciones.forEach(c => {
                                    let selected = (c.condicion.toLowerCase() === (row.condicionSolicitada || '').toLowerCase()) ? 'selected' : '';
                                    selCond += `<option value="${c.idCondicion}" ${selected}>${c.condicion}</option>`;
                                });
                            } else {
                                selCond += `<option value="">Sin datos</option>`;
                            }
                            selCond += `</select>`;

                            // Radio Buttons
                            const radioGroup = `
                                <div class="btn-group btn-group-sm btn-accion-group">
                                    <input type="radio" class="btn-check btn-accion-radio" name="radio_accion_${row.idInscripcionWeb}" id="btnAceptar_${row.idInscripcionWeb}" value="ACEPTAR">
                                    <label class="btn btn-outline-success" for="btnAceptar_${row.idInscripcionWeb}"><i class="bi bi-check-lg"></i></label>

                                    <input type="radio" class="btn-check btn-accion-radio" name="radio_accion_${row.idInscripcionWeb}" id="btnRechazar_${row.idInscripcionWeb}" value="RECHAZAR">
                                    <label class="btn btn-outline-danger" for="btnRechazar_${row.idInscripcionWeb}"><i class="bi bi-x-lg"></i></label>
                                </div>`;

                            tr = `<tr id="fila-${row.idInscripcionWeb}" class="table-loading" data-id="${row.idInscripcionWeb}">
                                    <td class="text-center"><input type="checkbox" class="solicitud-check" value="${row.idInscripcionWeb}"></td>
                                    <td class="td-alumno">${row.alumno}</td>
                                    <td>${row.materia}</td>
                                    <td>${row.curso}</td>
                                    <td>${row.fechaSolicitud}</td>
                                    <td>${selCond}</td>
                                    <td class="celda-observacion">Verificando...</td>
                                    <td class="text-center">${radioGroup}</td>
                                </tr>`;
                        }
                        $tbody.append(tr);
                    });

                    // Si NO es histórico, iniciamos la validación recursiva
                    if (!esHistorico) {
                        $barra.removeClass('progress-bar-animated').css('width', '0%');
                        $textoProgreso.text('Analizando correlatividades...');
                        // IMPORTANTE: Llamamos a la función recursiva
                        procesarLote(solicitudes, 0);
                    } else {
                        setTimeout(() => { modalProgreso.hide(); }, 300);
                    }

                }, 'json').fail(() => { setTimeout(() => { modalProgreso.hide(); }, 300); alert("Error de conexión."); });
            });

            // --- FUNCIÓN RECURSIVA PARA VALIDAR UNO POR UNO ---
            function procesarLote(lista, index) {
                const total = lista.length;
                
                // Fin del proceso
                if (index >= total) {
                    setTimeout(() => { 
                        modalProgreso.hide(); 
                        $('#btnEjecutar').prop('disabled', false); 
                    }, 500); 
                    return; 
                }

                const item = lista[index];
                
                // Actualizar barra y textos
                const porcentaje = Math.round((index / total) * 100);
                $barra.css('width', porcentaje + '%').text(porcentaje + '%');
                $textoProgreso.text(`Procesando solicitud ${index + 1} de ${total}`);
                $detalleProgreso.text(`Validando: ${item.alumno}`);
                
                // Llamada AJAX individual
                $.post('solicitudesCursado.php', { 
                    action: 'validar_individual', 
                    idUnicoMateria: item.idUnicoMateria, 
                    idAlumno: item.idAlumno 
                }, function(res) {
                    const $fila = $(`#fila-${item.idInscripcionWeb}`);
                    const esValida = (res.success && res.esValida);
                    $fila.removeClass('table-loading');
                    
                    if (esValida) {
                        $fila.addClass('table-success-custom');
                        $fila.find(`input[value="ACEPTAR"]`).prop('checked', true); // Sugerir Aceptar
                    } else {
                        $fila.addClass('table-danger-custom');
                        $fila.find(`input[value="RECHAZAR"]`).prop('checked', true); // Sugerir Rechazar
                    }
                    $fila.find('.celda-observacion').html(`<small>${res.observacion}</small>`);
                    
                    // Siguiente item
                    procesarLote(lista, index + 1);

                }, 'json').fail(function() {
                    // Si falla uno, marcamos error visual y seguimos con el siguiente
                    $(`#fila-${item.idInscripcionWeb}`).addClass('table-warning').find('.celda-observacion').text("Error al validar");
                    procesarLote(lista, index + 1);
                });
            }

            // Click manual cambia color
            $(document).on('change', '.btn-accion-radio', function() {
                const idWeb = $(this).attr('name').split('_')[2];
                const val = $(this).val();
                $(`#fila-${idWeb}`).removeClass('table-success-custom table-danger-custom');
                if(val === 'ACEPTAR') $(`#fila-${idWeb}`).addClass('table-success-custom');
                else $(`#fila-${idWeb}`).addClass('table-danger-custom');
            });

            // Botones masivos
            $('#btnSelCorrectos').click(function() { $('.solicitud-check').prop('checked', false); $('.table-success-custom .solicitud-check').prop('checked', true); });
            $('#btnSelIncorrectos').click(function() { $('.solicitud-check').prop('checked', false); $('.table-danger-custom .solicitud-check').prop('checked', true); });
            $(document).on('change', '#checkAll', function() { $('.solicitud-check').prop('checked', $(this).is(':checked')); });

            // EJECUTAR
            $('#btnEjecutar').click(function() {
                const checked = $('.solicitud-check:checked');
                if (checked.length === 0) return;
                
                let lista = [];
                checked.each(function() {
                    const idWeb = $(this).val();
                    const $fila = $(`#fila-${idWeb}`);
                    const accion = $fila.find(`input[name="radio_accion_${idWeb}"]:checked`).val();
                    const cond = $fila.find(`select[name="condicion_${idWeb}"]`).val();
                    const obs = $fila.find('.celda-observacion').text();
                    const alumno = $fila.find('.td-alumno').text();
                    if(accion) lista.push({ idWeb: idWeb, accion: accion, idCondicion: cond, observacionUI: obs, alumno: alumno });
                });

                if(lista.length === 0) { alert("No hay acciones seleccionadas."); return; }
                if(!confirm(`¿Procesar ${lista.length} solicitudes?`)) return;

                $barra.addClass('progress-bar-animated').css('width', '100%');
                $textoProgreso.text('Guardando...'); 
                $detalleProgreso.text('');
                modalProgreso.show();

                $.post('solicitudesCursado.php', {
                    action: 'procesar_lote_final', lista: JSON.stringify(lista), idCiclo: $selCiclo.val()
                }, function(res) {
                    setTimeout(() => { modalProgreso.hide(); }, 500);
                    if (res.success) {
                        let msg = `<div class="text-success fw-bold">Se procesaron ${res.procesados} solicitudes.</div>`;
                        if (res.errores.length > 0) {
                            msg += `<div class="text-danger mt-2 small">Errores:<ul>${res.errores.map(e=>`<li>${e}</li>`).join('')}</ul></div>`;
                        }
                        $('#modalAlertaTitulo').text("Resultado");
                        $('#modalAlertaMensaje').html(msg);
                        modalAlerta.show();
                        // Remover filas
                        lista.forEach(i => $(`#fila-${i.idWeb}`).remove());
                        $('.solicitud-check').prop('checked', false);
                    } else {
                        alert(res.message);
                    }
                }, 'json');
            });
        });
    </script>
</body>
</html>