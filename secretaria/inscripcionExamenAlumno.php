<?php
// Incluir el script de verificación de sesión
include_once '../funciones/verificarSesion.php';

// Habilitar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED); // Ignorar deprecated para JSON limpio

// Incluir la conexión a la base de datos y consultas
include_once '../inicio/conexion.php';
include_once '../funciones/consultas.php';

// Incluir control de correlatividad
$materiasAdeuda = ''; // Es crucial inicializarla ANTES de incluir el archivo
include_once '../funciones/controlCorrelatividad.php';


// --- MANEJO DE SOLICITUDES AJAX (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
                } else { 
                    $response['message'] = 'ID de Plan o Ciclo no válido.'; 
                }
                break;

            case 'load_materias':
                $idPlan = filter_input(INPUT_POST, 'idPlan', FILTER_VALIDATE_INT);
                $idCurso = filter_input(INPUT_POST, 'idCurso', FILTER_VALIDATE_INT);
                if ($idPlan && $idCurso) {
                    $response = ['success' => true, 'data' => materiasPlanCurso($conn, $idPlan, $idCurso)];
                } else { 
                    $response['message'] = 'ID de Plan o Curso no válido.'; 
                }
                break;

            case 'load_mesas':
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                $idTurno = filter_input(INPUT_POST, 'idTurno', FILTER_VALIDATE_INT);
                $idMateria = filter_input(INPUT_POST, 'idMateria', FILTER_VALIDATE_INT);
                if ($idCiclo && $idTurno && $idMateria) {
                    $response = ['success' => true, 'data' => filtrarMesasExamen($conn, $idCiclo, $idTurno, null, null, $idMateria)];
                } else { 
                    $response['message'] = 'Ciclo, Turno o Materia no seleccionados.'; 
                }
                break;
            
            case 'load_inscripciones_turno':
                $idAlumno = filter_input(INPUT_POST, 'idAlumno', FILTER_VALIDATE_INT);
                $idTurno = filter_input(INPUT_POST, 'idTurno', FILTER_VALIDATE_INT);
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                if ($idAlumno && $idTurno && $idCiclo) {
                    $inscripciones = obtenerInscripcionesTurno($conn, $idAlumno, $idTurno, $idCiclo);
                    $response = ['success' => true, 'data' => $inscripciones];
                } else {
                    $response['message'] = 'Faltan datos (Alumno, Turno o Ciclo) para buscar inscripciones.';
                }
                break;

            case 'inscribir':
                $idAlumno = filter_input(INPUT_POST, 'idAlumno', FILTER_VALIDATE_INT);
                $idMateria = filter_input(INPUT_POST, 'idMateria', FILTER_VALIDATE_INT);
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                $idFechaExamen = filter_input(INPUT_POST, 'idFechaExamen', FILTER_VALIDATE_INT);
                $idCondicion = filter_input(INPUT_POST, 'idCondicion', FILTER_VALIDATE_INT);
                $inscripcionTexto = filter_input(INPUT_POST, 'inscripcionTexto', FILTER_UNSAFE_RAW);
                $idTurno = filter_input(INPUT_POST, 'idTurno', FILTER_VALIDATE_INT);

                if (!$idAlumno || !$idMateria || !$idCiclo || !$idFechaExamen || !$idCondicion || !$inscripcionTexto || !$idTurno) {
                    $response['message'] = 'Datos incompletos. Faltan IDs o el texto de la condición o el turno.';
                    break;
                }

                // 1. Obtener idUnicoMateria
                $stmtUnico = $conn->prepare("SELECT idUnicoMateria FROM materiaterciario WHERE idMateria = ?");
                $stmtUnico->bind_param("i", $idMateria);
                $stmtUnico->execute();
                $resultUnico = $stmtUnico->get_result();
                
                if ($resultUnico->num_rows == 0) {
                    $response['message'] = 'Error: No se pudo encontrar el identificador único (idUnicoMateria) de la materia.';
                    $stmtUnico->close();
                    break;
                }
                $rowUnico = $resultUnico->fetch_assoc();
                $idUnicoMateria = $rowUnico['idUnicoMateria'];
                $stmtUnico->close();

                // 2. Ejecutar el control de correlatividad y estado
                $control_result = inscripcionExamenControl($conn, $idAlumno, $idUnicoMateria, $inscripcionTexto,false);

                // 3. Evaluar el resultado del control
                if ($control_result === true) {
                    // Pasar $idTurno a la función de inscripción para la nueva validación
                    $response = inscribirAlumnoExamen($conn, $idAlumno, $idMateria, $idCiclo, $idFechaExamen, $idCondicion, $idTurno);
                    
                    if ($response['success']) {
                        $response['message'] = 'Inscripción realizada con éxito. Se cumplieron todas las validaciones.';
                    }
                } else {
                    // Si el control falla, devuelve el string de error
                    $response = [
                        'success' => false, 
                        'message' => "Error en la inscripción: \n" . htmlspecialchars($control_result) 
                    ];
                }
                break;

            case 'eliminar_inscripcion':
                $idInscripcion = filter_input(INPUT_POST, 'idInscripcion', FILTER_VALIDATE_INT);
                
                if ($idInscripcion) {
                    // Llamamos a la nueva función de consultas.php
                    $response = eliminarInscripcionExamen($conn, $idInscripcion);
                } else {
                    $response['message'] = 'ID de Inscripción no válido o no proporcionado.';
                }
                break;
        }
    } catch (Throwable $e) {
        $error_message = 'Error fatal del servidor: ' . htmlspecialchars($e->getMessage()) . 
                         '<br><br><b>Archivo:</b> ' . htmlspecialchars($e->getFile()) . 
                         '<br><b>Línea:</b> ' . htmlspecialchars($e->getLine());
        error_log("Error FATAL en AJAX (inscripcionExamenAlumno.php): " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
        $response = ['success' => false, 'message' => $error_message];
    }

    echo json_encode($response);
    exit; // Terminamos la ejecución después de la respuesta AJAX
}

// --- LÓGICA DE CARGA DE PÁGINA (GET) ---

$idAlumno = filter_input(INPUT_GET, 'idAlumno', FILTER_VALIDATE_INT);
$alumno = null;
$ciclos = [];
$turnos = [];
$planes = [];
$condicionesExamen = []; // Usaremos esta variable

if (!$idAlumno) {
    die("Error: ID de Alumno no proporcionado o no válido.");
}

// Cargar datos iniciales
try {
    $alumno = obtenerDatosBasicosAlumno($conn, $idAlumno);
    if (!$alumno) {
        die("Error: No se encontró al alumno con ID " . htmlspecialchars($idAlumno));
    }
    
    $ciclos = levantarCiclosLectivos($conn);
    //while($fila = $resultCiclos->fetch_assoc()) { $ciclos[] = $fila; }
    
    $turnos = obtenerTodosTurnos($conn);
    //while($fila = $resultTurnos->fetch_assoc()) { $turnos[] = $fila; }
    
    $planes = buscarPlanes($conn, $idAlumno);
    
    // Usamos la función correcta para leer la tabla 'condicion'
    $condicionesExamen = obtenerCondicionesExamen($conn); 

} catch (Exception $e) {
    die("Error al cargar datos iniciales: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscribir Alumno a Examen - Secretaría</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/material/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        #loader { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1050; }
        .table-hover tbody tr:hover { cursor: pointer; }
        .btn-eliminar-inscripcion { cursor: pointer; }
    </style>
</head>
<body>
    <div id="loader">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>

    <?php include '../funciones/menu_secretaria.php'; ?>

    <div class="container-fluid fondo">
        <br>
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
                <li class="breadcrumb-item"><a href="buscarAlumno.php?origin=inscribirExamen">Buscar Alumno</a></li>
                <li class="breadcrumb-item active">Inscripción a Examen</li>
            </ol>

            <div id="messageContainer" class="mb-3" style="display: none;"></div>

            <div class="card p-4 mb-4">
                <h5 class="mb-3">
                    <i class="bi bi-person-check-fill"></i> Inscribir a Examen al Alumno:
                    <strong><?php echo htmlspecialchars($alumno['apellido'] . ', ' . $alumno['nombre']); ?></strong>
                    (DNI: <?php echo htmlspecialchars($alumno['dni']); ?>)
                </h5>
                <hr>

                <div id="formInscripcion">
                    <input type="hidden" name="idAlumno" id="idAlumno" value="<?php echo htmlspecialchars($idAlumno); ?>">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="idCiclo" class="form-label">Ciclo Lectivo <span class="text-danger">*</span></label>
                            <select class="form-select" id="idCiclo" name="idCiclo" required>
                                <option value="">Seleccione un ciclo...</option>
                                <?php foreach ($ciclos as $ciclo): ?>
                                    <option value="<?php echo htmlspecialchars($ciclo['idCicloLectivo']); ?>">
                                        <?php echo htmlspecialchars($ciclo['anio']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="idTurno" class="form-label">Turno de Examen <span class="text-danger">*</span></label>
                            <select class="form-select" id="idTurno" name="idTurno" required>
                                <option value="">Seleccione un turno...</option>
                                <?php foreach ($turnos as $turno): ?>
                                    <option value="<?php echo htmlspecialchars($turno['idTurno']); ?>">
                                        <?php echo htmlspecialchars($turno['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="idPlan" class="form-label">Plan de Estudio <span class="text-danger">*</span></label>
                            <select class="form-select" id="idPlan" name="idPlan" required>
                                <option value="">Seleccione un plan...</option>
                                <?php foreach ($planes as $plan): ?>
                                    <option value="<?php echo htmlspecialchars($plan['idPlan']); ?>">
                                        <?php echo htmlspecialchars($plan['Plan']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="idCurso" class="form-label">Curso <span class="text-danger">*</span></label>
                            <select class="form-select" id="idCurso" name="idCurso" required disabled>
                                <option value="">(Seleccione Plan y Ciclo)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="idMateria" class="form-label">Materia <span class="text-danger">*</span></label>
                            <select class="form-select" id="idMateria" name="idMateria" required disabled>
                                <option value="">(Seleccione Curso)</option>
                            </select>
                        </div>
                    </div>
                    <hr>

                    <div class="row mb-3">
                        <div class="col-12">
                            <h6>Mesas de Examen Disponibles</h6>
                            <p class="text-muted small">(Seleccione Ciclo, Turno y Materia para buscar mesas)</p>
                            <div id="spinnerMesas" style="display: none;" class="text-center">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Buscando...</span>
                                </div>
                                <span class="ms-2">Buscando mesas...</span>
                            </div>
                            <div class="table-responsive" id="containerMesas" style="display: none;">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Sel.</th>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Materia</th>
                                            <th>Curso</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaMesasBody">
                                        </tbody>
                                </table>
                            </div>
                            <div id="noMesas" style="display: none;" class="alert alert-warning">
                                No se encontraron mesas de examen para los filtros seleccionados.
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3 align-items-end">
                        <div class="col-md-6">
                            <label for="idCondicion" class="form-label">Condición de Inscripción <span class="text-danger">*</span></label>
                            <select class="form-select" id="idCondicion" name="idCondicion" required>
                                <option value="">Seleccione condición...</option>
                                <?php foreach ($condicionesExamen as $condicion): ?>
                                    <option value="<?php echo htmlspecialchars($condicion['idCondicion']); ?>">
                                        <?php echo htmlspecialchars($condicion['condicion']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-primary" id="btnInscribir" disabled>
                                <i class="bi bi-check-circle-fill"></i> Inscribir Alumno
                            </button>
                        </div>
                    </div>
                    
                    <hr>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6><i class="bi bi-list-task"></i> Inscripciones Previas en este Turno</h6>
                            <p class="text-muted small">(Se actualiza al seleccionar Ciclo Lectivo y Turno)</p>
                            <div id="spinnerInscripcionesTurno" style="display: none;" class="text-center">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Buscando...</span>
                                </div>
                                <span class="ms-2">Buscando inscripciones...</span>
                            </div>
                            <div class="table-responsive" id="containerInscripcionesTurno" style="display: none;">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Materia</th>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Condición</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaInscripcionesTurnoBody">
                                        </tbody>
                                </table>
                            </div>
                            <div id="noInscripcionesTurno" style="display: none;" class="alert alert-secondary">
                                No se encontraron inscripciones para este alumno en el turno y ciclo seleccionados.
                            </div>
                        </div>
                    </div>

                </div> </div>
        </div>
    </div>

    <div class="modal fade" id="confirmInscripcionModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel"><i class="bi bi-question-circle-fill text-primary"></i> Confirmar Inscripción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea inscribir al alumno 
                       <strong><?php echo htmlspecialchars($alumno['apellido'] . ', ' . $alumno['nombre']); ?></strong> 
                       a la mesa de examen seleccionada?
                    </p>
                    <p class="text-muted small">Se realizarán los controles de correlatividad y de inscripciones duplicadas en el turno.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarInscripcion">Confirmar Inscripción</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="resultModal" tabindex="-1" aria-labelledby="resultModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resultModalLabel">Resultado de la Inscripción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="resultModalBody">
                    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteLabel"><i class="bi bi-exclamation-triangle-fill text-danger"></i> Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar esta inscripción?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminacion">Eliminar Inscripción</button>
                </div>
            </div>
        </div>
    </div>


    <?php include '../funciones/footer.html'; ?>

    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../funciones/sessionControl.js"></script>

    <script>
        $(document).ready(function() {
            const ajaxUrl = 'inscripcionExamenAlumno.php';
            const loader = $('#loader');
            const messageContainer = $('#messageContainer'); // Para mensajes de borrado

            // Selectores de Mesas
            const spinnerMesas = $('#spinnerMesas');
            const containerMesas = $('#containerMesas');
            const noMesas = $('#noMesas');
            const tablaMesasBody = $('#tablaMesasBody');

            // Selectores de Inscripciones Previas
            const spinnerInscripcionesTurno = $('#spinnerInscripcionesTurno');
            const containerInscripcionesTurno = $('#containerInscripcionesTurno');
            const noInscripcionesTurno = $('#noInscripcionesTurno');
            const tablaInscripcionesTurnoBody = $('#tablaInscripcionesTurnoBody');

            // Modales y Botones
            const btnInscribir = $('#btnInscribir');
            const modalConfirm = new bootstrap.Modal(document.getElementById('confirmInscripcionModal'));
            const btnConfirmarInscripcion = $('#btnConfirmarInscripcion');
            const modalResult = new bootstrap.Modal(document.getElementById('resultModal'));
            const resultModalLabel = $('#resultModalLabel');
            const resultModalBody = $('#resultModalBody');

            // Modal de Eliminación
            const modalDelete = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            const btnConfirmarEliminacion = $('#btnConfirmarEliminacion');


            // --- Funciones Auxiliares ---
            function showLoader() { loader.fadeIn('fast'); }
            function hideLoader() { loader.fadeOut('fast'); }
            
            function showMessage(type, message) {
                messageContainer
                    .html(`<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                             ${message}
                             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                           </div>`)
                    .fadeIn();
                // Auto-ocultar después de 5 segundos
                setTimeout(() => { messageContainer.fadeOut(); }, 5000);
            }
            
            function resetSelect(selector, message) { 
                $(selector).html(`<option value="">${message}</option>`).prop('disabled', true); 
            }
            
            function populateSelect(selector, data, keyId, keyName, firstMessage) {
                const select = $(selector);
                select.html(`<option value="">${firstMessage}</option>`);
                if (data.length > 0) {
                    data.forEach(item => {
                        select.append(`<option value="${item[keyId]}">${item[keyName]}</option>`);
                    });
                    select.prop('disabled', false);
                } else {
                    select.html(`<option value="">(No hay opciones)</option>`).prop('disabled', true);
                }
            }

            // --- Función para buscar MESAS ---
            function buscarMesas() {
                const idCiclo = $('#idCiclo').val();
                const idTurno = $('#idTurno').val();
                const idMateria = $('#idMateria').val();

                tablaMesasBody.empty();
                containerMesas.hide();
                noMesas.hide();
                btnInscribir.prop('disabled', true);

                if (idCiclo && idTurno && idMateria) {
                    spinnerMesas.show();
                    $.post(ajaxUrl, {
                        action: 'load_mesas',
                        idCiclo: idCiclo,
                        idTurno: idTurno,
                        idMateria: idMateria
                    }, function(response) {
                        spinnerMesas.hide();
                        if (response.success && response.data.length > 0) {
                            response.data.forEach(mesa => {
                                const row = `
                                    <tr>
                                        <td>
                                            <input class="form-check-input" type="radio" name="idFechaExamen" value="${mesa.idFechaExamen}" required>
                                        </td>
                                        <td>${mesa.fecha}</td>
                                        <td>${mesa.hora}</td>
                                        <td>${mesa.nombreMateria}</td>
                                        <td>${mesa.nombreCurso}</td>
                                    </tr>
                                `;
                                tablaMesasBody.append(row);
                            });
                            containerMesas.show();
                        } else if (response.success) {
                            noMesas.show();
                        } else {
                            showMessage('danger', response.message || 'Error al cargar las mesas.');
                        }
                    }, 'json').fail(function() {
                        spinnerMesas.hide();
                        showMessage('danger', 'Error de conexión al buscar mesas.');
                    });
                }
            }
            
            // --- Función: Buscar Inscripciones Previas ---
            function buscarInscripcionesTurno() {
                const idAlumno = $('#idAlumno').val();
                const idTurno = $('#idTurno').val();
                const idCiclo = $('#idCiclo').val();

                tablaInscripcionesTurnoBody.empty();
                containerInscripcionesTurno.hide();
                noInscripcionesTurno.hide();

                if (idAlumno && idTurno && idCiclo) {
                    spinnerInscripcionesTurno.show();
                    $.post(ajaxUrl, {
                        action: 'load_inscripciones_turno',
                        idAlumno: idAlumno,
                        idTurno: idTurno,
                        idCiclo: idCiclo
                    }, function(response) {
                        spinnerInscripcionesTurno.hide();
                        if (response.success && response.data.length > 0) {
                            response.data.forEach(inscripcion => {
                                const row = `
                                    <tr id="inscripcion-row-${inscripcion.idInscripcion}">
                                        <td>${inscripcion.nombreMateria}</td>
                                        <td>${inscripcion.fecha}</td>
                                        <td>${inscripcion.hora}</td>
                                        <td>${inscripcion.condicion}</td>
                                        <td>
                                            <button class="btn btn-danger btn-sm btn-eliminar-inscripcion" 
                                                    data-id-inscripcion="${inscripcion.idInscripcion}"
                                                    data-bs-toggle="tooltip" 
                                                    title="Eliminar esta inscripción">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `;
                                tablaInscripcionesTurnoBody.append(row);
                            });
                            containerInscripcionesTurno.show();
                            // Inicializar tooltips (si usas Bootstrap 5)
                            $('[data-bs-toggle="tooltip"]').tooltip();
                        } else if (response.success) {
                            noInscripcionesTurno.show();
                        } else {
                            showMessage('danger', response.message || 'Error al cargar inscripciones previas.');
                        }
                    }, 'json').fail(function() {
                        spinnerInscripcionesTurno.hide();
                        showMessage('danger', 'Error de conexión al buscar inscripciones previas.');
                    });
                }
            }


            // --- Control de Filtros Dependientes (Lógica de Cursos y Materias) ---
            $('#idPlan, #idCiclo').on('change', function() {
                const idPlan = $('#idPlan').val();
                const idCiclo = $('#idCiclo').val();
                
                resetSelect('#idCurso', '(Seleccione Plan y Ciclo)');
                resetSelect('#idMateria', '(Seleccione Curso)');
                tablaMesasBody.empty(); containerMesas.hide(); noMesas.hide(); btnInscribir.prop('disabled', true);

                if (idPlan && idCiclo) {
                    showLoader();
                    $.post(ajaxUrl, {
                        action: 'load_cursos',
                        idPlan: idPlan,
                        idCiclo: idCiclo
                    }, function(response) {
                        hideLoader();
                        if (response.success) {
                            populateSelect('#idCurso', response.data, 'idCurso', 'nombre', 'Seleccione un curso...');
                        } else {
                            showMessage('danger', response.message || 'Error al cargar cursos.');
                        }
                    }, 'json').fail(function() {
                        hideLoader();
                        showMessage('danger', 'Error de conexión al cargar cursos.');
                    });
                }
            });

            $('#idCurso').on('change', function() {
                const idPlan = $('#idPlan').val(); 
                const idCurso = $(this).val();
                resetSelect('#idMateria', '(Seleccione Curso)');
                tablaMesasBody.empty(); containerMesas.hide(); noMesas.hide(); btnInscribir.prop('disabled', true);

                if (idPlan && idCurso) {
                    showLoader();
                    $.post(ajaxUrl, {
                        action: 'load_materias',
                        idPlan: idPlan,
                        idCurso: idCurso
                    }, function(response) {
                        hideLoader();
                        if (response.success) {
                            populateSelect('#idMateria', response.data, 'idMateria', 'nombreMateria', 'Seleccione una materia...');
                        } else {
                            showMessage('danger', response.message || 'Error al cargar materias.');
                        }
                    }, 'json').fail(function() {
                        hideLoader();
                        showMessage('danger', 'Error de conexión al cargar materias.');
                    });
                }
            });
            
            // --- Triggers ---
            
            // 1. Cuando cambian los filtros de mesa, buscar mesas
            $('#idCiclo, #idTurno, #idMateria').on('change', buscarMesas);
            
            // 2. Cuando cambian los filtros de turno, buscar inscripciones previas
            $('#idCiclo, #idTurno').on('change', buscarInscripcionesTurno);

            // 3. Habilitar botón al seleccionar mesa
            $(document).on('change', 'input[name="idFechaExamen"]', function() {
                if ($(this).is(':checked')) {
                    btnInscribir.prop('disabled', false);
                }
            });

            // --- Lógica de Inscripción (Modal y Botón) ---

            // 1. Botón "Inscribir" (Validación)
            $('#btnInscribir').on('click', function() {
                const idCondicion = $('#idCondicion').val();
                const idFechaExamen = $('input[name="idFechaExamen"]:checked').val();
                let isValid = true;
                let errorMsg = 'Faltan datos. Asegúrese de seleccionar ';
                let errors = [];
                if (!idFechaExamen) { errors.push('una mesa'); isValid = false; }
                if (!idCondicion) { errors.push('la condición'); isValid = false; }
                if (!isValid) {
                    errorMsg += errors.join(' y ') + '.';
                    showMessage('warning', errorMsg);
                    return;
                }
                modalConfirm.show();
            });

            // 2. Botón "Confirmar Inscripción" (AJAX)
            btnConfirmarInscripcion.on('click', function() {
                modalConfirm.hide();

                const idAlumno = $('#idAlumno').val();
                const idCiclo = $('#idCiclo').val();
                const idMateria = $('#idMateria').val();
                const idCondicion = $('#idCondicion').val();
                const idFechaExamen = $('input[name="idFechaExamen"]:checked').val();
                const inscripcionTexto = $('#idCondicion option:selected').text();
                const idTurno = $('#idTurno').val(); // <-- Se envía el idTurno

                showLoader();
                btnInscribir.prop('disabled', true); 
                btnConfirmarInscripcion.prop('disabled', true);

                $.post(ajaxUrl, {
                    action: 'inscribir',
                    idAlumno: idAlumno,
                    idMateria: idMateria,
                    idCiclo: idCiclo,
                    idFechaExamen: idFechaExamen,
                    idCondicion: idCondicion,
                    inscripcionTexto: inscripcionTexto,
                    idTurno: idTurno 
                }, function(response) {
                    hideLoader();
                    btnConfirmarInscripcion.prop('disabled', false);

                    if (response.success) {
                        resultModalLabel.text('Inscripción Exitosa');
                        resultModalBody.html('<i class="bi bi-check-circle-fill text-success me-2"></i>' + response.message);
                        modalResult.show();
                        
                        // Actualizar tabla de inscripciones
                        buscarInscripcionesTurno(); 
                        // Resetear tabla de mesas
                        tablaMesasBody.empty();
                        containerMesas.hide();
                        noMesas.hide();
                        btnInscribir.prop('disabled', true);
                        
                    } else {
                        resultModalLabel.text('Error en la Inscripción');
                        const formattedMessage = (response.message || 'Error desconocido.').replace(/\n/g, '<br>');
                        resultModalBody.html('<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>' + formattedMessage);
                        modalResult.show();
                        
                        // Habilitar botón si el error no es "ya inscripto"
                        if (!response.message.includes('ya se encuentra inscripto')) {
                             btnInscribir.prop('disabled', false);
                        }
                    }
                }, 'json').fail(function(jqXHR) {
                    hideLoader();
                    btnInscribir.prop('disabled', false); 
                    btnConfirmarInscripcion.prop('disabled', false);
                    resultModalLabel.text('Error de Conexión');
                    let errorMsg = 'Error de conexión. No se pudo completar la inscripción.';
                    if (jqXHR.responseText) {
                        errorMsg += '<br><br><code>' + jqXHR.responseText.substring(0, 200) + '...</code>';
                    }
                    resultModalBody.html('<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>' + errorMsg);
                    modalResult.show();
                });
            });

            // --- Lógica de Eliminación ---

            // 1. Click en el botón de basura
            $('#tablaInscripcionesTurnoBody').on('click', '.btn-eliminar-inscripcion', function() {
                const idInscripcion = $(this).data('id-inscripcion');
                
                if (idInscripcion) {
                    // Guardamos el ID en el botón del modal
                    btnConfirmarEliminacion.data('id-a-eliminar', idInscripcion);
                    // Abrimos el modal
                    modalDelete.show();
                }
            });

            // 2. Click en el botón "Eliminar Inscripción" del modal
            btnConfirmarEliminacion.on('click', function() {
                const idInscripcion = $(this).data('id-a-eliminar');
                
                if (!idInscripcion) return;

                modalDelete.hide(); 
                showLoader(); 

                $.post(ajaxUrl, {
                    action: 'eliminar_inscripcion',
                    idInscripcion: idInscripcion
                }, function(response) {
                    hideLoader();
                    if (response.success) {
                        showMessage('success', response.message);
                        // Refrescar la tabla de inscripciones
                        buscarInscripcionesTurno();
                        // Refrescar la tabla de mesas (por si el borrado habilita una nueva inscripción)
                        buscarMesas(); 
                    } else {
                        showMessage('danger', response.message || 'Error al eliminar.');
                    }
                }, 'json').fail(function() {
                    hideLoader();
                    showMessage('danger', 'Error de conexión. No se pudo eliminar la inscripción.');
                });
            });

        });
    </script>
</body>
</html>