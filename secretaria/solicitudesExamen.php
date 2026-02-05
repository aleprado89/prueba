<?php
/**
 * solicitudesExamen.php
 * Módulo de Administración para aceptar/rechazar inscripciones a examen.
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

            case 'buscar_solicitudes':
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                $idTurno = filter_input(INPUT_POST, 'idTurno', FILTER_VALIDATE_INT);
                $idPlan = filter_input(INPUT_POST, 'idPlan', FILTER_VALIDATE_INT);
                $idCurso = filter_input(INPUT_POST, 'idCurso', FILTER_VALIDATE_INT); 
                $idMateria = filter_input(INPUT_POST, 'idMateria', FILTER_VALIDATE_INT);
                $verHistorico = filter_input(INPUT_POST, 'verHistorico', FILTER_VALIDATE_INT);

                if (!$idCiclo || !$idTurno || !$idPlan) {
                    echo json_encode(['success'=>false, 'message'=>'Faltan parámetros Ciclo/Turno/Plan.']);
                    exit;
                }

                $estadosBuscar = ($verHistorico == 1) ? [2, 3, 4] : [1];

                if (!function_exists('buscarSolicitudesExamenWeb')) {
                    throw new Exception("La función buscarSolicitudesExamenWeb no existe en consultas.php");
                }

                $solicitudes = buscarSolicitudesExamenWeb($conn, $idCiclo, $idTurno, $idPlan, $idCurso, $idMateria, $estadosBuscar);
                $dataProcessed = [];

                foreach ($solicitudes as $sol) {
                    $fechasAlt = [];
                    if ($verHistorico != 1) {
                         if(function_exists('obtenerFechasAlternativas')) {
                            $fechasAlt = obtenerFechasAlternativas($conn, $sol['idMateria'], $idCiclo, $idTurno);
                         }
                    }
                    
                    $idInscripcion = $sol['id_Inscripcion_web'] ?? $sol['idInscripcionWeb'] ?? $sol['id'] ?? 0;

                    $estadoTexto = "";
                    if ($verHistorico == 1) {
                        switch($sol['estado']) {
                            case 2: $estadoTexto = "INSCRIPCIÓN CORRECTA"; break;
                            case 3: $estadoTexto = "INSCRIPCIÓN RECHAZADA"; break;
                            case 4: $estadoTexto = "CANCELADA POR ALUMNO"; break;
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
                        'curso' => $sol['nombreCurso'],
                        'fechaActualId' => $sol['idFechaExamen'],
                        'fechaActualTexto' => date('d/m/Y', strtotime($sol['fechaExamen'])),
                        'horaExamen' => substr($sol['horaExamen'], 0, 5),
                        'fechasAlternativas' => $fechasAlt,
                        'fechaSolicitud' => date('d/m/Y H:i', strtotime($sol['fechhora_inscri'])),
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
                    $esValidaCondicion = false;
                    $observacion = "Verificando...";
                    $condicionTexto = "S/D";
                    $idCondicionBD = null; 
                    
                    global $materiasAdeuda;
                    $materiasAdeuda = ''; 

                    if (function_exists('controlCorrelatividades')) {
                        $esValidaCorrelativas = controlCorrelatividades((int)$idUnicoMateria, (int)$idAlumno, 1);
                    } else {
                        $observacion = "Error: Fn correlatividades no existe";
                    }

                    if (function_exists('obtenerCondicionExamen')) {
                        $infoCondicion = obtenerCondicionExamen($conn, (int)$idAlumno, (int)$idUnicoMateria);
                        
                        if ($infoCondicion['status'] === 'ok') {
                            $condicionTexto = $infoCondicion['text']; 
                            $idCondicionBD = $infoCondicion['idCondicion'];
                            
                            if (stripos($condicionTexto, 'Promoc') !== false) {
                                $esValidaCondicion = false;
                                $observacion = "Los promocionales se deben inscribir desde Insc. Examen Masiva";
                            } else {
                                $esValidaCondicion = true;
                            }
                        } else {
                            $esValidaCondicion = false;
                            $condicionTexto = $infoCondicion['text']; 
                            $observacion = $infoCondicion['observacion']; 
                        }
                    } else {
                         $condicionTexto = "Error Fn Condicion";
                    }

                    $esAptaTotal = ($esValidaCorrelativas && $esValidaCondicion);
                    $accionSugerida = "ACEPTAR";

                    if ($esAptaTotal) {
                        $observacion = ""; 
                        $accionSugerida = "ACEPTAR";
                    } else {
                        $accionSugerida = "RECHAZAR";
                        
                        if (stripos($condicionTexto, 'Promoc') !== false) {
                             // Obs seteada
                        } elseif (!$esValidaCondicion) {
                             // Obs seteada
                        } elseif (!$esValidaCorrelativas) {
                            $observacion = !empty($materiasAdeuda) ? $materiasAdeuda : "No cumple correlativas.";
                        }
                    }

                    $response = [
                        'success' => true, 
                        'esValida' => $esAptaTotal, 
                        'observacion' => $observacion,
                        'condicionTexto' => $condicionTexto,
                        'idCondicionBD' => $idCondicionBD,
                        'accionSugerida' => $accionSugerida
                    ];
                }
                break;

            case 'procesar_lote_final':
                $lista = json_decode($_POST['lista'], true); 
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                
                if (!$lista || !is_array($lista) || !$idCiclo) {
                    echo json_encode(['success'=>false, 'message'=>'Datos de inscripción inválidos.']);
                    exit;
                }

                $procesadosExito = 0;
                $detallesError = []; 
                $conn->autocommit(FALSE); 

                try {
                    foreach ($lista as $item) {
                        $idWeb = $item['idWeb'];
                        $accion = $item['accion']; // ACEPTAR o RECHAZAR
                        $idCondicion = $item['idCondicion']; 
                        $idFechaExamen = $item['idFechaExamen'];
                        $observacionUI = $item['observacionUI']; 
                        $nombreAlumno = $item['alumno'] ?? 'Alumno';

                        // Obtenemos los datos originales de la solicitud
                        $datosOrig = obtenerDatosSolicitudWeb($conn, $idWeb);

                        if (!$datosOrig) { continue; } 

                        // --- LÓGICA DE AUTORIDAD ---
                        if ($accion === 'ACEPTAR') {
                            // IMPORTANTE: Aquí NO validamos controlCorrelatividades.
                            // Si llegó "ACEPTAR", es porque el usuario (Secretaría) decidió inscribirlo
                            // ignorando la sugerencia del sistema (Rojo/Verde).
                            
                            if (!$idFechaExamen || !$idCondicion) {
                                $idCondicion = ($idCondicion) ? $idCondicion : 0; 
                            }

                            // Llamamos a la función que compartiste (que solo chequea duplicados)
                            $resultadoInscripcion = inscribirAlumnoExamen(
                                $conn, 
                                $datosOrig['idAlumno'], 
                                $datosOrig['idMateria'], 
                                $idCiclo, 
                                $idFechaExamen, 
                                $idCondicion, 
                                $datosOrig['idTurno']
                            );

                            if ($resultadoInscripcion['success']) {
                                // Éxito: marcamos solicitud como Aceptada (2)
                                actualizarEstadoSolicitudWeb($conn, $idWeb, 2, $idCondicion, "Inscripción Correcta (Manual)");
                                $procesadosExito++;
                            } else {
                                // Error (ej: ya estaba inscripto en el turno)
                                $motivoError = "Error BD: " . $resultadoInscripcion['message'];
                                actualizarEstadoSolicitudWeb($conn, $idWeb, 3, 0, $motivoError);
                                $detallesError[] = "<b>$nombreAlumno:</b> " . $resultadoInscripcion['message'];
                            }

                        } else {
                            // ACCIÓN: RECHAZAR
                            // Rechazamos directamente, aunque el sistema hubiera sugerido verde.
                            $motivoRechazo = "Rechazo manual: " . $observacionUI;
                            actualizarEstadoSolicitudWeb($conn, $idWeb, 3, 0, $motivoRechazo);
                            $procesadosExito++; 
                        }
                    }

                    $conn->commit();
                    
                    $response = [
                        'success' => true, 
                        'procesados' => $procesadosExito,
                        'errores' => $detallesError
                    ];

                } catch (Exception $e) {
                    $conn->rollback();
                    $response = ['success' => false, 'message' => 'Error en transacción: ' . $e->getMessage()];
                }
                break;
        }

    } catch (Exception $e) {
        $response['message'] = 'Error servidor: ' . $e->getMessage();
    } catch (Error $e) {
        $response['message'] = 'Error fatal PHP: ' . $e->getMessage();
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
    <title>Sistemas Escolares - Solicitudes de examen</title>
    
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/material/bootstrap.min.css"> 
    <link rel="stylesheet" href="../css/estilos.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body { position: relative; min-height: 100vh; }
        .fondo {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; 
            background-size: cover; background-position: center;
        }
        .content-wrapper { position: relative; z-index: 1; padding-bottom: 50px; }
        
        .custom-card { 
            background-color: rgba(255, 255, 255, 0.98); 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            border: 1px solid #ddd;
        }
        
        .card-header-custom {
            background-color: #fff;
            color: #333;
            border-bottom: 1px solid #eee;
            padding: 1rem 1.25rem;
        }

        tr.table-success-custom, tr.table-success-custom > td { 
            background-color: #d1e7dd !important; color: #0f5132 !important;
        }
        tr.table-danger-custom, tr.table-danger-custom > td { 
            background-color: #f8d7da !important; color: #842029 !important;
        }

        .table-loading { background-color: #f8f9fa; color: #6c757d; }
        .select-fecha-tabla { font-size: 0.9rem; padding: 0.25rem 0.5rem; min-width: 160px; }
        
        .filtro-rapido-container { position: relative; }
        .filtro-rapido-icon { position: absolute; top: 10px; left: 10px; color: #aaa; }
        .filtro-rapido-input { padding-left: 35px; }

        .nav-tabs .nav-link { color: #666; font-weight: 500; }
        
        /* Grupo de Acciones */
        .btn-accion-group .btn-outline-success, .btn-accion-group .btn-outline-danger {
            padding: 0.2rem 0.5rem;
            font-size: 0.9rem;
        }
        .btn-accion-group .btn-check:checked + .btn-outline-success {
            background-color: #198754; color: white;
        }
        .btn-accion-group .btn-check:checked + .btn-outline-danger {
            background-color: #dc3545; color: white;
        }
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
                <li class="breadcrumb-item active">Solicitudes de examen web</li>
            </ol>
        </nav>

        <div class="card custom-card">
            <div class="card-header card-header-custom">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>Administrar solicitudes de examen
                </h5>
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

                    <div class="col-md-2">
                        <label class="form-label">Turno de examen</label>
                        <select class="form-select" id="selectTurno" required>
                            <option value="">Seleccione...</option>
                            <?php
                            $turnos = obtenerTodosTurnos($conn);
                            foreach ($turnos as $turno) {
                                $idT = $turno['idturno'] ?? $turno['idTurno'];
                                echo "<option value='{$idT}'>{$turno['nombre']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Plan de estudio</label>
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

                    <div class="col-md-2">
                        <label class="form-label">Curso</label>
                        <select class="form-select" id="selectCurso" disabled>
                            <option value="0">Todos</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Materia</label>
                        <select class="form-select" id="selectMateria" disabled>
                            <option value="0">Todas</option>
                        </select>
                    </div>

                    <div class="col-md-1">
                        <button type="submit" class="w-100 btn btn-primary"><i class="bi bi-search"></i></button>
                    </div>
                </form>

                <hr>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="filtro-rapido-container">
                            <i class="bi bi-search filtro-rapido-icon"></i>
                            <input type="text" class="form-control filtro-rapido-input" id="inputBusquedaRapida" placeholder="Filtrar por nombre en la tabla...">
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-3" id="tabSolicitudes" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#pendientes-pane" type="button" role="tab" aria-controls="pendientes-pane" aria-selected="true">
                            <i class="bi bi-clock-history me-2"></i>Pendientes
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="resueltas-tab" data-bs-toggle="tab" data-bs-target="#resueltas-pane" type="button" role="tab" aria-controls="resueltas-pane" aria-selected="false">
                            <i class="bi bi-archive me-2"></i>Resueltas (Histórico)
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="tabSolicitudesContent">
                    
                    <div class="tab-pane fade show active" id="pendientes-pane" role="tabpanel" aria-labelledby="pendientes-tab">
                        
                        <div class="bg-light p-2 mb-2 border rounded d-flex flex-wrap align-items-center gap-2">
                            <span class="text-muted small fw-bold me-2"><i class="bi bi-check2-square"></i> Selección Rápida:</span>
                            
                            <button type="button" class="btn btn-sm btn-outline-primary px-3" id="btnSelCorrectos">
                                <i class="bi bi-check-all"></i> Tildar correctos
                            </button>
                            
                            <button type="button" class="btn btn-sm btn-outline-primary px-3" id="btnSelIncorrectos">
                                <i class="bi bi-x-square"></i> Tildar incorrectos
                            </button>
                            
                            <div class="vr mx-2"></div>
                            
                            <div class="d-flex align-items-center">
                                <span>Condición</span>
                                <select class="form-select form-select-sm mx-2" id="selFiltroCondicion" style="max-width: 150px;">
                                    <option value="">(Esperando datos...)</option>
                                </select>
                                <button class="btn btn-sm btn-outline-primary px-3 rounded" type="button" id="btnSelCondicion">Tildar</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle" id="tablaPendientes">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center"><input type="checkbox" id="checkAll"></th>
                                        <th>Alumno</th>
                                        <th class="text-center" style="width: 50px;">Calif.</th>
                                        <th>Materia</th>
                                        <th>Fecha de mesa</th>
                                        <th>Hora</th>
                                        <th>Fecha de solicitud</th>
                                        <th>Condición</th>
                                        <th>Observación</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="10" class="text-center text-muted">Use los filtros y presione buscar.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button class="btn btn-primary" id="btnEjecutar" disabled>
                                <i class="bi bi-check-circle me-2"></i>Ejecutar inscripción seleccionados
                            </button>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="resueltas-pane" role="tabpanel" aria-labelledby="resueltas-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle" id="tablaResueltas">
                                <thead class="table-light">
                                    <tr>
                                        <th>Alumno</th>
                                        <th class="text-center" style="width: 50px;">Calif.</th>
                                        <th>Materia</th>
                                        <th>Fecha mesa</th>
                                        <th>Hora</th>
                                        <th>Fecha solicitud</th>
                                        <th>Estado final</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="7" class="text-center text-muted">Seleccione los filtros para cargar el historial.</td></tr>
                                </tbody>
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
                    <h5 class="modal-title">Procesando solicitudes</h5>
                </div>
                <div class="modal-body text-center">
                    <p id="progresoTexto" class="mb-2">Iniciando...</p>
                    <div class="progress" style="height: 25px;">
                        <div id="barraProgreso" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;">0%</div>
                    </div>
                    <small class="text-muted mt-2 d-block" id="progresoDetalle">Consultando base de datos...</small>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-labelledby="modalConfirmacionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="modalConfirmacionLabel"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Confirmar Inscripción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Está a punto de procesar las inscripciones seleccionadas.</p>
                    <div class="alert alert-info py-2 mb-0">
                        <i class="bi bi-info-circle me-1"></i> Se procesarán: <strong id="lblCantidadConfirmar">0</strong> solicitudes.
                    </div>
                    <p class="mt-3 small text-muted">Esta acción registrará las inscripciones aceptadas en la base de datos oficial y marcará las rechazadas en el sistema web.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarEjecucion">
                        <i class="bi bi-save me-1"></i> Confirmar y ejecutar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAlerta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAlertaTitulo">Mensaje</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modalAlertaMensaje" class="mb-0"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Variables y Selectores
            const $selCiclo = $('#selectCiclo');
            const $selTurno = $('#selectTurno');
            const $selPlan = $('#selectPlan');
            const $selCurso = $('#selectCurso');
            const $selMateria = $('#selectMateria');
            
            const modalProgreso = new bootstrap.Modal(document.getElementById('modalProgreso'));
            const modalConfirmacion = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
            const modalAlerta = new bootstrap.Modal(document.getElementById('modalAlerta'));
            
            const $barra = $('#barraProgreso');
            const $textoProgreso = $('#progresoTexto');
            const $detalleProgreso = $('#progresoDetalle');
            
            // Helper para mostrar alertas HTML
            function mostrarAlerta(mensajeHTML, titulo = "Atención") {
                $('#modalAlertaTitulo').text(titulo);
                $('#modalAlertaMensaje').html(mensajeHTML); // Usamos HTML para permitir listas
                modalAlerta.show();
            }

            // Variable global para almacenar datos a enviar
            let listaParaEnviar = [];

            // --- COMBOS ---
            function checkAndLoadCursos() {
                const idPlan = $selPlan.val();
                const idCiclo = $selCiclo.val();
                $selCurso.html('<option value="0">Todos</option>').prop('disabled', true);
                $selMateria.html('<option value="0">Todas</option>').prop('disabled', true);

                if (idPlan && idCiclo) {
                    $.post('solicitudesExamen.php', { action: 'load_cursos', idPlan: idPlan, idCiclo: idCiclo }, function(res) {
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
                const idCurso = $(this).val();
                const idPlan = $selPlan.val();
                $selMateria.html('<option value="0">Todas</option>').prop('disabled', true);
                if (idPlan) {
                    $.post('solicitudesExamen.php', { action: 'load_materias', idPlan: idPlan, idCurso: idCurso }, function(res) {
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

            // --- FILTRO RÁPIDO ---
            $('#inputBusquedaRapida').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $(".tab-pane.active table tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // --- BÚSQUEDA ---
            $('#formFiltros').on('submit', function(e) {
                e.preventDefault();
                const esHistorico = $('#resueltas-tab').hasClass('active');
                if(!$selCiclo.val() || !$selTurno.val() || !$selPlan.val()){
                    mostrarAlerta("Complete los filtros requeridos (Ciclo, Turno, Plan).", "Faltan Datos");
                    return;
                }
                
                $barra.css('width', '0%').text('0%');
                $textoProgreso.text('Buscando solicitudes...');
                modalProgreso.show();

                const data = {
                    action: 'buscar_solicitudes',
                    idCiclo: $selCiclo.val(),
                    idTurno: $selTurno.val(),
                    idPlan: $selPlan.val(),
                    idCurso: $selCurso.val(),
                    idMateria: $selMateria.val(),
                    verHistorico: esHistorico ? 1 : 0
                };

                $.post('solicitudesExamen.php', data, function(res) {
                    const $tbody = esHistorico ? $('#tablaResueltas tbody') : $('#tablaPendientes tbody');
                    $tbody.empty();
                    const colCount = esHistorico ? 7 : 10;
                    $('#selFiltroCondicion').html('<option value="">Seleccione...</option>');

                    if (!res.success || !res.data || res.data.length === 0) {
                        $barra.css('width', '100%');
                        setTimeout(() => { modalProgreso.hide(); }, 500); 
                        $tbody.html(`<tr><td colspan="${colCount}" class="text-center text-muted fw-bold py-3">No se encontraron solicitudes.</td></tr>`);
                        return;
                    }

                    const solicitudes = res.data;
                    $textoProgreso.text(`Se encontraron ${solicitudes.length} solicitudes.`);
                    
                    solicitudes.forEach((row) => {
                        let tr = '';
                        const trId = `fila-${row.idInscripcionWeb}`;
                        
                        const celdaCalif = `
                            <td class="text-center">
                                <a href="carga_califxalumno_secretaria.php?idAlumno=${row.idAlumno}" target="_blank" class="btn btn-primary btn-sm text-white" title="Ver Calificaciones">
                                    <i class="bi bi-journal-text"></i>
                                </a>
                            </td>
                        `;

                        if (esHistorico) {
                            tr = `
                                <tr class="bg-white">
                                    <td>${row.alumno}</td>
                                    ${celdaCalif}
                                    <td>${row.materia}<br><small class="text-muted">${row.curso}</small></td>
                                    <td>${row.fechaActualTexto}</td>
                                    <td>${row.horaExamen}</td>
                                    <td>${row.fechaSolicitud}</td>
                                    <td class="fw-bold text-secondary">${row.estadoTexto}</td>
                                </tr>`;
                        } else {
                            let fechaDisplay = row.fechaActualTexto;
                            if (row.fechasAlternativas && row.fechasAlternativas.length > 0) {
                                fechaDisplay = `<select class="form-select select-fecha-tabla" name="fecha_${row.idInscripcionWeb}">`;
                                row.fechasAlternativas.forEach(f => {
                                    let horaCorta = f.hora.substring(0,5);
                                    let selected = (f.idFechaExamen == row.fechaActualId) ? 'selected' : '';
                                    fechaDisplay += `<option value="${f.idFechaExamen}" ${selected}>${f.fecha} (${horaCorta})</option>`;
                                });
                                fechaDisplay += `</select>`;
                            } else {
                                fechaDisplay += `<input type="hidden" name="fecha_${row.idInscripcionWeb}" value="${row.fechaActualId}">`;
                            }

                            const radioGroup = `
                                <div class="btn-group btn-group-sm btn-accion-group" role="group">
                                    <input type="radio" class="btn-check btn-accion-radio" name="radio_accion_${row.idInscripcionWeb}" id="btnAceptar_${row.idInscripcionWeb}" value="ACEPTAR" autocomplete="off">
                                    <label class="btn btn-outline-success" for="btnAceptar_${row.idInscripcionWeb}" title="Aceptar"><i class="bi bi-check-lg"></i></label>

                                    <input type="radio" class="btn-check btn-accion-radio" name="radio_accion_${row.idInscripcionWeb}" id="btnRechazar_${row.idInscripcionWeb}" value="RECHAZAR" autocomplete="off">
                                    <label class="btn btn-outline-danger" for="btnRechazar_${row.idInscripcionWeb}" title="Rechazar"><i class="bi bi-x-lg"></i></label>
                                </div>
                                <input type="hidden" name="idCondicionBD_${row.idInscripcionWeb}" class="id-condicion-bd">
                            `;

                            tr = `
                                <tr id="${trId}" class="table-loading" data-id="${row.idInscripcionWeb}">
                                    <td class="text-center"><input type="checkbox" class="solicitud-check" value="${row.idInscripcionWeb}"></td>
                                    <td class="td-alumno">${row.alumno}</td>
                                    ${celdaCalif}
                                    <td>${row.materia}<br><small class="text-muted">${row.curso}</small></td>
                                    <td>${fechaDisplay}</td>
                                    <td>${row.horaExamen}</td>
                                    <td>${row.fechaSolicitud}</td>
                                    <td class="celda-condicion"><i class="fas fa-spinner fa-spin"></i></td>
                                    <td class="celda-observacion">Verificando...</td>
                                    <td class="text-center">${radioGroup}</td>
                                </tr>`;
                        }
                        $tbody.append(tr);
                    });

                    if (esHistorico) {
                        $barra.css('width', '100%');
                        setTimeout(() => { modalProgreso.hide(); }, 500);
                    } else {
                        procesarLote(solicitudes, 0);
                    }

                }, 'json').fail(() => {
                    setTimeout(() => { modalProgreso.hide(); }, 500);
                    mostrarAlerta("Error de conexión al buscar.", "Error");
                });
            });

            // --- PROCESAMIENTO RECURSIVO ---
            function procesarLote(lista, index) {
                const total = lista.length;
                if (index >= total) {
                    setTimeout(() => {
                        modalProgreso.hide();
                        $('#btnEjecutar').prop('disabled', false); 
                        actualizarFiltroCondiciones();
                    }, 500);
                    return;
                }

                const porcentaje = Math.round((index / total) * 100);
                $barra.css('width', porcentaje + '%').text(porcentaje + '%');
                $textoProgreso.text(`Procesando solicitud ${index + 1} de ${total}`);

                const item = lista[index];
                $detalleProgreso.text(`Validando: ${item.alumno}`);

                $.post('solicitudesExamen.php', {
                    action: 'validar_individual',
                    idUnicoMateria: item.idUnicoMateria,
                    idAlumno: item.idAlumno
                }, function(res) {
                    const $fila = $(`#fila-${item.idInscripcionWeb}`);
                    const esValida = (res.success && (res.esValida === true || res.esValida === "true" || res.esValida === 1));
                    
                    $fila.removeClass('table-loading');
                    
                    if (esValida) {
                        $fila.addClass('table-success-custom').css('background-color', '#d1e7dd');
                        $fila.find(`input[value="ACEPTAR"]`).prop('checked', true);
                    } else {
                        $fila.addClass('table-danger-custom').css('background-color', '#f8d7da');
                        $fila.find(`input[value="RECHAZAR"]`).prop('checked', true);
                    }
                    
                    $fila.find('.celda-condicion').html(res.condicionTexto || 'S/D');
                    $fila.find('.celda-observacion').html(`<small>${res.observacion}</small>`);
                    
                    if (res.idCondicionBD) {
                        $fila.find(`input[name="idCondicionBD_${item.idInscripcionWeb}"]`).val(res.idCondicionBD);
                    }

                    procesarLote(lista, index + 1);

                }, 'json').fail(function() {
                    $(`#fila-${item.idInscripcionWeb}`).addClass('table-warning');
                    procesarLote(lista, index + 1);
                });
            }

            function actualizarFiltroCondiciones() {
                let condiciones = new Set();
                $('#tablaPendientes tbody tr').each(function() {
                    let cond = $(this).find('.celda-condicion').text().trim();
                    if (cond && cond !== 'S/D') condiciones.add(cond);
                });
                let $sel = $('#selFiltroCondicion');
                $sel.empty().append('<option value="">Seleccione...</option>');
                condiciones.forEach(c => $sel.append(`<option value="${c}">${c}</option>`));
            }

            // --- BOTONES SELECCIÓN RÁPIDA ---
            $('#btnSelCorrectos').click(function() {
                $('.solicitud-check').prop('checked', false);
                $('#tablaPendientes tbody tr.table-success-custom .solicitud-check').prop('checked', true);
            });
            $('#btnSelIncorrectos').click(function() {
                 $('.solicitud-check').prop('checked', false);
                $('#tablaPendientes tbody tr.table-danger-custom .solicitud-check').prop('checked', true);
            });
            $('#btnSelCondicion').click(function() {
                let val = $('#selFiltroCondicion').val();
                if(!val) return;
                $('.solicitud-check').prop('checked', false);
                $('#tablaPendientes tbody tr').each(function() {
                    if($(this).find('.celda-condicion').text().trim() === val) {
                        $(this).find('.solicitud-check').prop('checked', true);
                    }
                });
            });

            // --- CAMBIO DE COLOR DINÁMICO ---
            $(document).on('change', '.btn-accion-radio', function() {
                const idWeb = $(this).attr('name').split('_')[2];
                const $fila = $(`#fila-${idWeb}`);
                const valor = $(this).val();

                $fila.removeClass('table-success-custom table-danger-custom').css('background-color', '');
                if (valor === 'ACEPTAR') {
                    $fila.addClass('table-success-custom').css('background-color', '#d1e7dd');
                } else {
                    $fila.addClass('table-danger-custom').css('background-color', '#f8d7da');
                }
            });

            // --- PREPARAR EJECUCIÓN (MODAL) ---
            $('#btnEjecutar').on('click', function() {
                const checked = $('.solicitud-check:checked');
                if (checked.length === 0) {
                    mostrarAlerta("Seleccione al menos una solicitud.");
                    return;
                }

                listaParaEnviar = [];
                checked.each(function() {
                    const idWeb = $(this).val();
                    const $fila = $(`#fila-${idWeb}`);
                    const accion = $fila.find(`input[name="radio_accion_${idWeb}"]:checked`).val();
                    
                    let idFecha = $fila.find(`select[name="fecha_${idWeb}"]`).val() || $fila.find(`input[name="fecha_${idWeb}"]`).val();
                    const idCondicion = $fila.find(`input[name="idCondicionBD_${idWeb}"]`).val();
                    const obsTexto = $fila.find('.celda-observacion').text().trim();
                    const nombreAlumno = $fila.find('.td-alumno').text().trim(); // Capturamos nombre

                    if(accion) {
                        listaParaEnviar.push({
                            idWeb: idWeb, accion: accion, idCondicion: idCondicion,
                            idFechaExamen: idFecha, observacionUI: obsTexto, alumno: nombreAlumno
                        });
                    }
                });

                if (listaParaEnviar.length === 0) {
                    mostrarAlerta("No hay acciones definidas (Aceptar/Rechazar) para las filas seleccionadas.", "Error");
                    return;
                }

                $('#lblCantidadConfirmar').text(listaParaEnviar.length);
                modalConfirmacion.show();
            });

            // --- CONFIRMAR EJECUCIÓN (Lógica modificada para reporte detallado) ---
            $('#btnConfirmarEjecucion').on('click', function() {
                modalConfirmacion.hide();
                $barra.css('width', '100%').addClass('progress-bar-striped progress-bar-animated');
                $textoProgreso.text('Guardando inscripciones...');
                $detalleProgreso.text('Esto puede tardar unos segundos...');
                modalProgreso.show();

                $.post('solicitudesExamen.php', {
                    action: 'procesar_lote_final',
                    lista: JSON.stringify(listaParaEnviar),
                    idCiclo: $selCiclo.val()
                }, function(res) {
                    setTimeout(() => { modalProgreso.hide(); }, 500);
                    
                    if (res.success) {
                        // Construir mensaje detallado
                        let mensajeFinal = `<p class="text-success fw-bold"><i class="bi bi-check-circle"></i> Se procesaron ${res.procesados} solicitudes correctamente.</p>`;
                        
                        // Si hubo excepciones (alumnos aceptados pero rebotados por la BD)
                        if (res.errores && res.errores.length > 0) {
                            mensajeFinal += `<hr><p class="text-danger fw-bold mb-2"><i class="bi bi-exclamation-triangle"></i> Hubo ${res.errores.length} excepciones:</p>`;
                            mensajeFinal += `<ul class="list-group list-group-flush small">`;
                            res.errores.forEach(err => {
                                mensajeFinal += `<li class="list-group-item list-group-item-danger">${err}</li>`;
                            });
                            mensajeFinal += `</ul>`;
                            
                            mostrarAlerta(mensajeFinal, "Proceso Finalizado con Excepciones");
                        } else {
                            mostrarAlerta(mensajeFinal, "Operación Exitosa");
                        }

                        // Eliminar filas visualmente
                        listaParaEnviar.forEach(function(item) {
                            $(`#fila-${item.idWeb}`).fadeOut(300, function() { $(this).remove(); });
                        });
                        
                        $('.solicitud-check').prop('checked', false);
                        $('#checkAll').prop('checked', false);
                        
                    } else {
                        mostrarAlerta("Error: " + res.message, "Hubo un problema");
                    }
                }, 'json').fail(function() {
                    setTimeout(() => { modalProgreso.hide(); }, 500);
                    mostrarAlerta("Error crítico de servidor al procesar.", "Error Fatal");
                });
            });

            $(document).on('change', '#checkAll', function() {
                $('.solicitud-check').prop('checked', $(this).is(':checked'));
            });
        });
    </script>
</body>
</html>