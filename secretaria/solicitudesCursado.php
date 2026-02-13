<?php
/**
 * solicitudesCursado.php
 * Versión Final: 
 * 1. Lógica de UI (concatenación de observaciones) de la primera versión.
 * 2. Lógica de búsqueda (SQL) adaptada de solicitudesExamen (filtro por Plan/Curso).
 */

ob_start();
include_once '../funciones/verificarSesion.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

include_once '../inicio/conexion.php';
include_once '../funciones/consultas.php';

$materiasAdeuda = '';
include_once '../funciones/controlCorrelatividad.php';

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
                if (function_exists('obtenerCondicionesCursado')) {
                    $condiciones = obtenerCondicionesCursado($conn);
                    $response = ['success' => true, 'data' => $condiciones];
                }
                break;

            // --- CASO MODIFICADO PARA USAR LA NUEVA LOGICA DE FILTRADO ---
            case 'buscar_solicitudes':
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                $idPlan = filter_input(INPUT_POST, 'idPlan', FILTER_VALIDATE_INT); // AHORA ES OBLIGATORIO
                $idCurso = filter_input(INPUT_POST, 'idCurso', FILTER_VALIDATE_INT);
                $idMateria = filter_input(INPUT_POST, 'idMateria', FILTER_VALIDATE_INT);
                $verHistorico = filter_input(INPUT_POST, 'verHistorico', FILTER_VALIDATE_INT);

                if (!$idCiclo || !$idPlan) {
                    echo json_encode(['success' => false, 'message' => 'Faltan parámetros Ciclo o Plan.']);
                    exit;
                }

                $estadoBuscar = ($verHistorico == 1) ? 2 : 1;

                // Llamamos a la función corregida enviando el Plan
                $solicitudes = buscarSolicitudesCursadoWeb($conn, $idCiclo, $idPlan, $idMateria, $estadoBuscar, $idCurso);

                if ($verHistorico == 1) {
                    // Buscar también rechazadas (estado 3) con la misma lógica
                    $solicitudesRechazadas = buscarSolicitudesCursadoWeb($conn, $idCiclo, $idPlan, $idMateria, 3, $idCurso);
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
                        'idUnicoMateria' => $sol['idUnicoMateria'], 
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

            case 'validar_individual':
                $idUnicoMateria = filter_input(INPUT_POST, 'idUnicoMateria');
                $idAlumno = filter_input(INPUT_POST, 'idAlumno', FILTER_VALIDATE_INT);

                if(!$idUnicoMateria || !$idAlumno){
                    $response = ['success' => false, 'message' => 'Datos insuficientes.'];
                } else {
                    $esValidaCorrelativas = false;
                    $observacion = "Verificando...";
                    $accionSugerida = "RECHAZAR";
                    global $materiasAdeuda;
                    $materiasAdeuda = '';

                    if (function_exists('controlCorrelatividades')) {
                        $esValidaCorrelativas = controlCorrelatividades((int)$idUnicoMateria, (int)$idAlumno, 0);
                    }

                    if ($esValidaCorrelativas) {
                        $observacion = ""; 
                        $accionSugerida = "ACEPTAR";
                    } else {
                        $accionSugerida = "RECHAZAR";
                        $observacion = !empty($materiasAdeuda) ? "Debe: " . $materiasAdeuda : "No cumple correlativas.";                    }

                    $response = [
                        'success' => true,
                        'esValida' => $esValidaCorrelativas,
                        'observacion' => $observacion,
                        'accionSugerida' => $accionSugerida
                    ];
                }
                break;

            case 'procesar_lote_final':
                $lista = json_decode($_POST['lista'], true);
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);

                if (!$lista || !is_array($lista) || !$idCiclo) {
                    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
                    exit;
                }

                $procesadosExito = 0;
                $detallesError = [];
                $conn->autocommit(FALSE);

                try {
                    foreach ($lista as $item) {
                        $idWeb = intval($item['idWeb']);
                        $accion = $item['accion']; 
                        $idCondicion = intval($item['idCondicion']);
                        
                        // Capturamos la observación que el usuario veía en pantalla (ROJO o VERDE)
                        $observacionUI = isset($item['observacionUI']) ? trim($item['observacionUI']) : '';
                        $nombreAlumno = $item['alumno'] ?? 'Alumno';

                        $datosOrig = obtenerSolicitudCursadoWebPorId($conn, $idWeb);
                        if (!$datosOrig) {
                            $detallesError[] = "<b>$nombreAlumno:</b> Solicitud no encontrada.";
                            continue;
                        }

                        if ($accion === 'ACEPTAR') {
                            if (checkMatriculacionMateriaExiste($conn, $datosOrig['idAlumno'], $datosOrig['idMateria'])) {
                                actualizarEstadoSolicitudCursadoWeb($conn, $idWeb, 2, "Ya estaba inscripto previamente.");
                                $detallesError[] = "<b>$nombreAlumno:</b> Ya inscripto. Solicitud marcada procesada.";
                                $procesadosExito++;
                                continue;
                            }

                            if (!$idCondicion) {
                                $detallesError[] = "<b>$nombreAlumno:</b> Condición no seleccionada.";
                                continue;
                            }

                            $datosMatricula = [
                                'idAlumno' => $datosOrig['idAlumno'],
                                'idMateria' => $datosOrig['idMateria'],
                                'fechaMatriculacionMateria' => date('Y-m-d'),
                                'estadoMatriculacionMateria' => $idCondicion,
                                'anio' => null
                            ];

                            if (insertarMatriculacionMateria($conn, $datosMatricula)) {
                                inicializarAsistenciaMateria($conn, $datosOrig['idAlumno'], $datosOrig['idMateria'], $idCiclo);
                                inicializarCalificacionMateria($conn, $datosOrig['idAlumno'], $datosOrig['idMateria']);

                                // LOGICA DE CONCATENACION PEDIDA
                                $obsFinal = "Inscripción Correcta";
                                // Si venía con un mensaje de error (diferente a "Verificando..." o vacio), lo concatenamos
                                if (!empty($observacionUI) && $observacionUI !== 'Verificando...' && $observacionUI !== 'Correlativas OK') {
                                    $obsFinal = $observacionUI . " (Inscripción Correcta)";
                                }

                                if (actualizarEstadoSolicitudCursadoWeb($conn, $idWeb, 2, $obsFinal)) {
                                    $procesadosExito++;
                                } else {
                                    $detallesError[] = "<b>$nombreAlumno:</b> Falló actualización estado web.";
                                }
                            } else {
                                $errorDB = $conn->error;
                                actualizarEstadoSolicitudCursadoWeb($conn, $idWeb, 3, "Error BD: " . $errorDB);
                                $detallesError[] = "<b>$nombreAlumno:</b> Error BD al inscribir.";
                            }

                        } else {
                            // RECHAZAR
                            $motivoRechazo = "Rechazo manual: " . $observacionUI;
                            if (actualizarEstadoSolicitudCursadoWeb($conn, $idWeb, 3, $motivoRechazo)) {
                                $procesadosExito++;
                            } else {
                                $detallesError[] = "<b>$nombreAlumno:</b> Error al rechazar.";
                            }
                        }
                    }
                    $conn->commit();
                    $response = ['success' => true, 'procesados' => $procesadosExito, 'errores' => $detallesError];

                } catch (Exception $e) {
                    $conn->rollback();
                    $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
                }
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
                            echo "<option value='{$ciclo['idCicloLectivo']}'>{$ciclo['anio']}</option>";
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
                    <input type="text" class="form-control" id="inputBusquedaRapida" placeholder="Filtrar en pantalla...">
                </div>
            </div>

            <ul class="nav nav-tabs mb-3" id="tabSolicitudes" role="tablist">
                <li class="nav-item"><button class="nav-link active" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#pendientes-pane">Pendientes</button></li>
                <li class="nav-item"><button class="nav-link" id="resueltas-tab" data-bs-toggle="tab" data-bs-target="#resueltas-pane">Resueltas (Histórico)</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="pendientes-pane">
                    <div class="bg-light p-2 mb-2 border rounded d-flex flex-wrap align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary px-3" id="btnSelCorrectos"><i class="bi bi-check-all"></i> Tildar Sugeridos Aceptar</button>
                        <button type="button" class="btn btn-sm btn-outline-primary px-3" id="btnSelIncorrectos"><i class="bi bi-x-square"></i> Tildar Sugeridos Rechazar</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle" id="tablaPendientes">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center"><input type="checkbox" id="checkAll"></th>
                                    <th>Alumno</th>
                                    <th>Materia</th>
                                    <th>Curso</th>
                                    <th>Fecha</th>
                                    <th>Condición</th>
                                    <th>Observación</th>
                                    <th class="text-center">Acción Manual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="8" class="text-center text-muted">Use los filtros y presione buscar.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button class="btn btn-primary" id="btnEjecutar" disabled><i class="bi bi-save"></i> Ejecutar inscripción</button>
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

<div class="modal fade" id="modalProgreso" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Procesando</h5></div>
            <div class="modal-body text-center">
                <p id="progresoTexto">Iniciando...</p>
                <div class="progress" style="height: 25px;">
                    <div id="barraProgreso" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%;">0%</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAlerta" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="modalAlertaTitulo">Mensaje</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="modalAlertaMensaje"></div>
            <div class="modal-footer"><button class="btn btn-primary" data-bs-dismiss="modal">Entendido</button></div>
        </div>
    </div>
</div>

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
    let listaCondiciones = [];

    // Cargar condiciones
    $.post('solicitudesCursado.php', { action: 'get_condiciones' }, function(res){ if(res.success) listaCondiciones = res.data; }, 'json');

    // Cargar cursos
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
                    res.data.forEach(m => opts += `<option value="${m.idMateria}">${m.nombreMateria}</option>`);
                    $selMateria.html(opts).prop('disabled', false);
                }
            }, 'json');
        }
    });

    $('#inputBusquedaRapida').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $(".tab-pane.active table tbody tr").filter(function() { $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1) });
    });

    $('#formFiltros').on('submit', function(e) {
        e.preventDefault();
        const esHistorico = $('#resueltas-tab').hasClass('active');
        if(!$selCiclo.val() || !$selPlan.val()) { mostrarAlerta("Faltan datos (Ciclo/Plan)."); return; }

        $barra.css('width', '100%'); $textoProgreso.text('Buscando...'); modalProgreso.show();

        $.post('solicitudesCursado.php', {
            action: 'buscar_solicitudes',
            idCiclo: $selCiclo.val(), idPlan: $selPlan.val(), idCurso: $selCurso.val(), idMateria: $selMateria.val(),
            verHistorico: esHistorico ? 1 : 0
        }, function(res) {
            const $tbody = esHistorico ? $('#tablaResueltas tbody') : $('#tablaPendientes tbody');
            $tbody.empty();
            const colCount = esHistorico ? 7 : 8;

            if (!res.success || !res.data || res.data.length === 0) {
                setTimeout(() => modalProgreso.hide(), 500);
                $tbody.html(`<tr><td colspan="${colCount}" class="text-center text-muted">No se encontraron solicitudes.</td></tr>`);
                return;
            }

            const solicitudes = res.data;
            solicitudes.forEach((row) => {
                let tr = '';
                if (esHistorico) {
                    tr = `<tr class="bg-white"><td>${row.alumno}</td><td>${row.materia}</td><td>${row.curso}</td><td>${row.fechaSolicitud}</td><td>${row.condicionSolicitada||'-'}</td><td>${row.observaciones||''}</td><td>${row.estadoTexto}</td></tr>`;
                } else {
                    let selCond = `<select class="form-select select-condicion-tabla" name="condicion_${row.idInscripcionWeb}">`;
                    let found = false;
                    listaCondiciones.forEach(c => {
                        let s = (c.condicion.toLowerCase() === (row.condicionSolicitada||'').toLowerCase());
                        if(s) found=true;
                        selCond += `<option value="${c.idCondicion}" ${s?'selected':''}>${c.condicion}</option>`;
                    });
                    if(!found) selCond += `<option value="" disabled selected>Seleccione...</option>`;
                    selCond += `</select>`;

                    const btns = `<div class="btn-group btn-group-sm btn-accion-group">
                        <input type="radio" class="btn-check btn-accion-radio" name="radio_accion_${row.idInscripcionWeb}" id="btnOk_${row.idInscripcionWeb}" value="ACEPTAR">
                        <label class="btn btn-outline-success" for="btnOk_${row.idInscripcionWeb}"><i class="bi bi-check-lg"></i></label>
                        <input type="radio" class="btn-check btn-accion-radio" name="radio_accion_${row.idInscripcionWeb}" id="btnNo_${row.idInscripcionWeb}" value="RECHAZAR">
                        <label class="btn btn-outline-danger" for="btnNo_${row.idInscripcionWeb}"><i class="bi bi-x-lg"></i></label>
                    </div>`;

                    tr = `<tr id="fila-${row.idInscripcionWeb}" class="table-loading" data-id="${row.idInscripcionWeb}">
                        <td class="text-center"><input type="checkbox" class="solicitud-check" value="${row.idInscripcionWeb}"></td>
                        <td class="td-alumno">${row.alumno}</td>
                        <td>${row.materia}</td>
                        <td>${row.curso}</td>
                        <td>${row.fechaSolicitud}</td>
                        <td>${selCond}</td>
                        <td class="celda-observacion"><small>Esperando...</small></td>
                        <td class="text-center">${btns}</td>
                    </tr>`;
                }
                $tbody.append(tr);
            });

            if (esHistorico) {
                setTimeout(() => modalProgreso.hide(), 500);
            } else {
                $barra.css('width', '0%').removeClass('progress-bar-animated');
                $textoProgreso.text('Validando correlatividades...');
                procesarLote(solicitudes, 0);
            }

        }, 'json').fail(() => { setTimeout(() => modalProgreso.hide(), 500); mostrarAlerta("Error de conexión."); });
    });

    function procesarLote(lista, index) {
        // Si terminamos la lista
        if (index >= lista.length) {
            setTimeout(() => { 
                modalProgreso.hide(); 
                $('#btnEjecutar').prop('disabled', false); 
            }, 500);
            return;
        }

        const item = lista[index];
        const pct = Math.round((index / lista.length) * 100);
        
        // Actualizamos la barra de porcentaje
        $barra.css('width', pct + '%').text(pct + '%');
        
        // AQUI ESTÁ EL CAMBIO: Mostramos "Validando (1/50): Juan Perez..."
        // Usamos .html() para poder poner negritas
        $textoProgreso.html(`Validando correlatividades (${index + 1}/${lista.length})<br><b>${item.alumno}</b>`);
        
        $.post('solicitudesCursado.php', { 
            action: 'validar_individual', 
            idUnicoMateria: item.idUnicoMateria, 
            idAlumno: item.idAlumno 
        }, function(res) {
            const $fila = $(`#fila-${item.idInscripcionWeb}`);
            $fila.removeClass('table-loading');
            
            if (res.success && res.esValida) {
                $fila.addClass('table-success-custom');
                $fila.find(`input[value="ACEPTAR"]`).prop('checked', true);
            } else {
                $fila.addClass('table-danger-custom');
                $fila.find(`input[value="RECHAZAR"]`).prop('checked', true);
            }
            
            // Insertamos el texto de la observación (que ahora vendrá con "Debe: ...")
            $fila.find('.celda-observacion').text(res.observacion || "Correlativas OK");

            // Llamada recursiva al siguiente
            procesarLote(lista, index + 1);

        }, 'json').fail(() => {
            // Si falla la conexión, intentamos con el siguiente de todas formas
            procesarLote(lista, index + 1);
        });
    }

    $(document).on('change', '.btn-accion-radio', function() {
        const id = $(this).attr('name').split('_')[2];
        const val = $(this).val();
        $(`#fila-${id}`).removeClass('table-success-custom table-danger-custom').addClass(val === 'ACEPTAR' ? 'table-success-custom' : 'table-danger-custom');
    });

    $('#btnSelCorrectos').click(() => { $('.solicitud-check').prop('checked', false); $('.table-success-custom .solicitud-check').prop('checked', true); });
    $('#btnSelIncorrectos').click(() => { $('.solicitud-check').prop('checked', false); $('.table-danger-custom .solicitud-check').prop('checked', true); });
    $(document).on('change', '#checkAll', function() { $('.solicitud-check').prop('checked', $(this).is(':checked')); });

  $('#btnEjecutar').click(function() {
        const checked = $('.solicitud-check:checked');
        if (checked.length === 0) { mostrarAlerta("Seleccione solicitudes."); return; }
        
        let lista = [];
        let faltaCondicion = false;

        checked.each(function() {
            const id = $(this).val();
            const $fila = $(`#fila-${id}`);
            const accion = $fila.find(`input[name="radio_accion_${id}"]:checked`).val();
            const cond = $fila.find(`select[name="condicion_${id}"]`).val();
            const obs = $fila.find('.celda-observacion').text().trim();
            const alumno = $fila.find('.td-alumno').text();

            if (accion === 'ACEPTAR' && !cond) faltaCondicion = true;
            if (accion) lista.push({ idWeb: id, accion: accion, idCondicion: cond, observacionUI: obs, alumno: alumno });
        });

        if (faltaCondicion) { mostrarAlerta("Hay aceptados sin condición."); return; }
        if (lista.length === 0) return;

        if(!confirm(`¿Procesar ${lista.length} solicitudes?`)) return;

        // Configuración visual del modal
        $barra.removeClass('bg-success bg-danger').addClass('progress-bar-striped progress-bar-animated');
        $barra.css('width', '100%').text('Procesando...');
        $textoProgreso.text('Guardando cambios en base de datos...');
        
        // 1. Mostramos el modal
        modalProgreso.show();

        // 2. Realizamos la petición AJAX
        $.post('solicitudesCursado.php', { 
            action: 'procesar_lote_final', 
            lista: JSON.stringify(lista), 
            idCiclo: $selCiclo.val() 
        }, function(res) {
            
            // 3. Función auxiliar para manejar la respuesta después de cerrar el modal
            function procesarRespuesta() {
                if(res.success) {
                    let msg = `<div class="text-success fw-bold">Procesados: ${res.procesados}</div>`;
                    if(res.errores.length > 0) {
                        msg += `<div class="text-danger mt-2 small"><ul>${res.errores.map(e=>`<li>${e}</li>`).join('')}</ul></div>`;
                    }
                    mostrarAlerta(msg, "Resultado");
                    
                    // Limpieza de tabla
                    lista.forEach(i => $(`#fila-${i.idWeb}`).remove());
                    $('.solicitud-check').prop('checked', false);
                    $('#checkAll').prop('checked', false);
                } else {
                    mostrarAlerta(res.message, "Error");
                }
            }

            // 4. Verificamos si el modal de progreso ya está completamente visible.
            // Si Bootstrap aún lo está animando (abriendo), ocultarlo de golpe lo rompe.
            if ($('#modalProgreso').hasClass('show')) {
                modalProgreso.hide();
                setTimeout(procesarRespuesta, 400); // Esperamos a que termine la animación de cierre
            } else {
                // Si la respuesta fue muy rápida y el modal aún se está abriendo,
                // esperamos a que termine de abrirse para cerrarlo inmediatamente.
                $('#modalProgreso').one('shown.bs.modal', function () {
                    modalProgreso.hide();
                    setTimeout(procesarRespuesta, 400);
                });
            }

        }, 'json')
        .fail(function(xhr, status, error) {
            console.error("Error en respuesta:", xhr.responseText);
            
            function procesarError() {
                mostrarAlerta("Ocurrió un error en el servidor. Revise la consola (F12) para más detalles.", "Error Crítico");
            }

            if ($('#modalProgreso').hasClass('show')) {
                modalProgreso.hide();
                setTimeout(procesarError, 400);
            } else {
                $('#modalProgreso').one('shown.bs.modal', function () {
                    modalProgreso.hide();
                    setTimeout(procesarError, 400);
                });
            }
        });
    });

    function mostrarAlerta(msg, tit="Atención") { $('#modalAlertaTitulo').text(tit); $('#modalAlertaMensaje').html(msg); modalAlerta.show(); }
});
</script>
</body>
</html>