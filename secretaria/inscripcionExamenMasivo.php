<?php
// Incluir el script de verificación de sesión
include_once '../funciones/verificarSesion.php';

// Habilitar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED); 

// Incluir la conexión a la base de datos y consultas
include_once '../inicio/conexion.php';
include_once '../funciones/consultas.php';

// Inicializar variable global e incluir control de correlatividad
$materiasAdeuda = ''; 
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
                } else { $response['message'] = 'ID de Plan o Ciclo no válido.'; }
                break;

            case 'load_materias':
                $idPlan = filter_input(INPUT_POST, 'idPlan', FILTER_VALIDATE_INT);
                $idCurso = filter_input(INPUT_POST, 'idCurso', FILTER_VALIDATE_INT);
                if ($idPlan && $idCurso) {
                    $response = ['success' => true, 'data' => materiasPlanCurso($conn, $idPlan, $idCurso)];
                } else { $response['message'] = 'ID de Plan o Curso no válido.'; }
                break;

            case 'load_mesas':
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                $idTurno = filter_input(INPUT_POST, 'idTurno', FILTER_VALIDATE_INT);
                $idMateria = filter_input(INPUT_POST, 'idMateria', FILTER_VALIDATE_INT);
                if ($idCiclo && $idTurno && $idMateria) {
                    $response = ['success' => true, 'data' => filtrarMesasExamen($conn, $idCiclo, $idTurno, null, null, $idMateria)];
                } else { $response['message'] = 'Ciclo, Turno o Materia no seleccionados.'; }
                break;
            
            case 'buscar_alumnos':
                $idMateria = filter_input(INPUT_POST, 'idMateria', FILTER_VALIDATE_INT);
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                // *** CORREGIDO: Lee de 'idCondicionCursado' ***
                $idCondicionCursado = filter_input(INPUT_POST, 'idCondicionCursado', FILTER_VALIDATE_INT); 

                if ($idMateria && $idCiclo && $idCondicionCursado) {
                    // La función usa el ID para comparar con 'estadoCursadoNumero'
                    $alumnos = buscarAlumnosParaInscripcionMasiva($conn, $idMateria, $idCiclo, $idCondicionCursado);
                    $response = ['success' => true, 'data' => $alumnos];
                } else {
                    $response['message'] = 'Faltan datos (Materia, Ciclo o Condición de Cursado).';
                }
                break;
            
            case 'inscribir':
                $idAlumno = filter_input(INPUT_POST, 'idAlumno', FILTER_VALIDATE_INT);
                $idMateria = filter_input(INPUT_POST, 'idMateria', FILTER_VALIDATE_INT);
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                $idFechaExamen = filter_input(INPUT_POST, 'idFechaExamen', FILTER_VALIDATE_INT);
                // *** CORREGIDO: Lee 'idCondicionExamen' y 'inscripcionTexto' del formulario ***
                $idCondicion = filter_input(INPUT_POST, 'idCondicionExamen', FILTER_VALIDATE_INT);
                $inscripcionTexto = filter_input(INPUT_POST, 'inscripcionTexto', FILTER_UNSAFE_RAW); 

                if (!$idAlumno || !$idMateria || !$idCiclo || !$idFechaExamen || !$idCondicion || !$inscripcionTexto) {
                    $response['message'] = 'Datos incompletos (Falta ID Alumno, Materia, Ciclo, Mesa o Condición de Examen).';
                    break;
                }

                // 1. Obtener idUnicoMateria
                $stmtUnico = $conn->prepare("SELECT idUnicoMateria FROM materiaterciario WHERE idMateria = ?");
                $stmtUnico->bind_param("i", $idMateria);
                $stmtUnico->execute();
                $resultUnico = $stmtUnico->get_result();
                
                if ($resultUnico->num_rows == 0) {
                    $response['message'] = 'Error: No se pudo encontrar el idUnicoMateria.';
                    $stmtUnico->close();
                    break;
                }
                $idUnicoMateria = $resultUnico->fetch_assoc()['idUnicoMateria'];
                $stmtUnico->close();

                // 2. Ejecutar control (usa el texto de la condición de EXAMEN)
                $control_result = inscripcionExamenControl($conn, $idAlumno, $idUnicoMateria, $inscripcionTexto);

                // 3. Evaluar resultado
                if ($control_result === true) {
                    // Inscribir (usa el ID de la condición de EXAMEN)
                    $response = inscribirAlumnoExamen($conn, $idAlumno, $idMateria, $idCiclo, $idFechaExamen, $idCondicion);
                } else {
                    $response = ['success' => false, 'message' => "Error: " . htmlspecialchars($control_result)];
                }
                break;
        }
    } catch (Throwable $e) {
        $error_message = 'Error fatal: ' . htmlspecialchars($e->getMessage()) . ' en ' . htmlspecialchars($e->getFile()) . ' L:' . htmlspecialchars($e->getLine());
        error_log("Error FATAL en AJAX (inscripcionExamenMasivo.php): " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
        $response = ['success' => false, 'message' => $error_message];
    }

    echo json_encode($response);
    exit;
}

// --- LÓGICA DE CARGA DE PÁGINA (GET) ---
$ciclos = [];
$turnos = [];
$planes = [];
$condicionesCursado = []; // <-- Para el FILTRO
$condicionesExamen = []; // <-- Para la INSCRIPCIÓN

try {
    // Cargar Planes
    $stmtPlanes = $conn->query("SELECT idPlan, CONCAT(nombre, ' (Res: ', IFNULL(resolucion, 'N/A'), ')') as Plan 
                                FROM plandeestudio 
                                ORDER BY nombre ASC");
    if (!$stmtPlanes) { throw new Exception("Error al consultar 'plandeestudio': " . $conn->error); }
    while($fila = $stmtPlanes->fetch_assoc()) { $planes[] = $fila; }
    
    // Cargar Ciclos
    $resultCiclos = obtenerCiclosLectivos($conn);
    while($fila = $resultCiclos->fetch_assoc()) { $ciclos[] = $fila; }
    
    // Cargar Turnos
    $resultTurnos = obtenerTurnosExamen($conn);
    while($fila = $resultTurnos->fetch_assoc()) { $turnos[] = $fila; }
    
    // *** CORREGIDO: Cargar AMBAS condiciones ***
    $condicionesCursado = obtenerCondicionesCursado($conn); // Asumimos que esta lee de 'condicionescursado'
    $condicionesExamen = obtenerCondicionesExamen($conn); // La nueva función que lee de 'condicion'

} catch (Exception $e) {
    $error_db = ($conn) ? $conn->error : 'Error de conexión no disponible.';
    $error_php = $e->getMessage();
    die("Error al cargar datos iniciales: " . htmlspecialchars($error_php) . " (Detalle DB: " . htmlspecialchars($error_db) . ")");
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripción Masiva a Examen - Secretaría</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/material/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        #loader { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1060; }
        .list-group-item-success { background-color: #d1e7dd; }
        .list-group-item-danger { background-color: #f8d7da; }
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
                <li class="breadcrumb-item active">Inscripción Masiva a Examen</li>
            </ol>

            <div id="messageContainer" class="mb-3" style="display: none;"></div>

            <div class="card p-4 mb-4">
                <h5 class="mb-3"><i class="bi bi-people-fill"></i> Inscripción Masiva a Mesas de Examen</h5>
                <hr>

                <div id="formFiltros">
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="idCiclo" class="form-label">Ciclo Lectivo <span class="text-danger">*</span></label>
                            <select class="form-select" id="idCiclo" name="idCiclo" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($ciclos as $ciclo): ?>
                                    <option value="<?php echo htmlspecialchars($ciclo['idciclolectivo']); ?>">
                                        <?php echo htmlspecialchars($ciclo['anio']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="idTurno" class="form-label">Turno de Examen <span class="text-danger">*</span></label>
                            <select class="form-select" id="idTurno" name="idTurno" required>
                                <option value="">Seleccione...</option>
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
                                <option value="">Seleccione...</option>
                                <?php foreach ($planes as $plan): ?>
                                    <option value="<?php echo htmlspecialchars($plan['idPlan']); ?>">
                                        <?php echo htmlspecialchars($plan['Plan']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3 align-items-end">
                        <div class="col-md-3">
                            <label for="idCurso" class="form-label">Curso <span class="text-danger">*</span></label>
                            <select class="form-select" id="idCurso" name="idCurso" required disabled>
                                <option value="">(Seleccione Plan y Ciclo)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="idMateria" class="form-label">Materia <span class="text-danger">*</span></label>
                            <select class="form-select" id="idMateria" name="idMateria" required disabled>
                                <option value="">(Seleccione Curso)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="idCondicionCursado" class="form-label">Condición de Cursado <span class="text-danger">*</span></label>
                            <select class="form-select" id="idCondicionCursado" name="idCondicionCursado" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($condicionesCursado as $condicion): ?>
                                    <option value="<?php echo htmlspecialchars($condicion['idCondicion']); ?>">
                                        <?php echo htmlspecialchars($condicion['condicion']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 text-end">
                            <button type="button" class="btn btn-primary w-100" id="btnBuscarAlumnos">
                                <i class="bi bi-search"></i> Buscar Alumnos
                            </button>
                        </div>
                    </div>
                </div> <hr>

                <div id="containerResultados" style="display: none;">
                    
                    <h5 class="mt-3">Alumnos a Inscribir</h5>
                    <p>Se encontraron <strong id="contadorAlumnos">0</strong> alumnos. Puede eliminar alumnos de la lista antes de inscribir.</p>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Apellido</th>
                                    <th>Nombre</th>
                                    <th>DNI</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tablaAlumnosBody">
                                </tbody>
                        </table>
                    </div>

                    <div class="row mt-4 align-items-end">
                        <div class="col-md-5">
                            <label for="idFechaExamen" class="form-label">Seleccionar Mesa de Examen <span class="text-danger">*</span></label>
                            <select class="form-select" id="idFechaExamen" name="idFechaExamen" required disabled>
                                <option value="">(Cargando mesas...)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="idCondicionExamen" class="form-label">Inscribir como: <span class="text-danger">*</span></label>
                            <select class="form-select" id="idCondicionExamen" name="idCondicionExamen" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($condicionesExamen as $condicion): ?>
                                    <option value="<?php echo htmlspecialchars($condicion['idCondicion']); ?>">
                                        <?php echo htmlspecialchars($condicion['condicion']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-success w-100" id="btnInscribirMasivo" disabled>
                                <i class="bi bi-check-circle-fill"></i> Inscribir <span id="contadorAlumnosBtn">0</span> Alumnos
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="progressModal" tabindex="-1" aria-labelledby="progressModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="progressModalLabel">Procesando Inscripciones</h5>
                </div>
                <div class="modal-body">
                    <p id="progressText">Iniciando proceso...</p>
                    <div class="progress" style="height: 25px;">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="mt-2 text-muted small">Esto puede tardar varios minutos. Por favor, no cierre esta ventana.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="resultsModal" tabindex="-1" aria-labelledby="resultsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resultsModalLabel">Resultados de la Inscripción Masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="resultsModalBody">
                    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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
            const ajaxUrl = 'inscripcionExamenMasivo.php';
            const loader = $('#loader');

            // --- Selectores de Filtros ---
            const idCiclo = $('#idCiclo');
            const idTurno = $('#idTurno');
            const idPlan = $('#idPlan');
            const idCurso = $('#idCurso');
            const idMateria = $('#idMateria');
            // *** CORREGIDO: Selector de filtro ***
            const idCondicionCursado = $('#idCondicionCursado'); 
            
            // --- Contenedores y Botones ---
            const btnBuscarAlumnos = $('#btnBuscarAlumnos');
            const containerResultados = $('#containerResultados');
            const tablaAlumnosBody = $('#tablaAlumnosBody');
            const idFechaExamen = $('#idFechaExamen');
            // *** NUEVO: Selector de inscripción ***
            const idCondicionExamen = $('#idCondicionExamen'); 
            const btnInscribirMasivo = $('#btnInscribirMasivo');

            // --- Contadores ---
            const contadorAlumnos = $('#contadorAlumnos');
            const contadorAlumnosBtn = $('#contadorAlumnosBtn');

            // --- Modales ---
            const progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
            const $progressText = $('#progressText');
            const $progressBar = $('#progressBar');
            const resultsModal = new bootstrap.Modal(document.getElementById('resultsModal'));
            const $resultsModalBody = $('#resultsModalBody');

            // --- Funciones Auxiliares ---
            function showLoader() { loader.fadeIn('fast'); }
            function hideLoader() { loader.fadeOut('fast'); }

            function showMessage(type, message) {
                $('#messageContainer')
                    .html(`<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                             ${message}
                             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                           </div>`)
                    .fadeIn();
            }

            function resetSelect(selector, message) {
                $(selector).html(`<option value="">${message}</option>`).prop('disabled', true);
            }

            function updateContadores() {
                const count = tablaAlumnosBody.find('tr').length;
                contadorAlumnos.text(count);
                contadorAlumnosBtn.text(count);
                
                // *** CORREGIDO: Validar ambos selects ***
                const mesaSeleccionada = idFechaExamen.val();
                const condicionExamenSeleccionada = idCondicionExamen.val();
                
                if (count > 0 && mesaSeleccionada && condicionExamenSeleccionada) {
                    btnInscribirMasivo.prop('disabled', false);
                } else {
                    btnInscribirMasivo.prop('disabled', true);
                }
            }

            // --- Lógica de Filtros Dependientes ---
            // (Plan y Ciclo) -> Cursos
            function loadCursos() {
                const plan = idPlan.val();
                const ciclo = idCiclo.val();
                resetSelect(idCurso, '(Seleccione Plan y Ciclo)');
                resetSelect(idMateria, '(Seleccione Curso)');
                
                if (plan && ciclo) {
                    showLoader();
                    $.post(ajaxUrl, { action: 'load_cursos', idPlan: plan, idCiclo: ciclo }, 'json')
                     .done(function(response) {
                        if (response.success && response.data.length > 0) {
                            idCurso.html('<option value="">Seleccione un curso...</option>');
                            response.data.forEach(item => {
                                idCurso.append(`<option value="${item.idCurso}">${item.nombre}</option>`);
                            });
                            idCurso.prop('disabled', false);
                        } else {
                            resetSelect(idCurso, '(No hay cursos)');
                        }
                    }).fail(() => showMessage('danger', 'Error al cargar cursos.'))
                      .always(() => hideLoader());
                }
            }
            idPlan.on('change', loadCursos);
            idCiclo.on('change', loadCursos);

            // (Curso) -> Materias
            idCurso.on('change', function() {
                const plan = idPlan.val();
                const curso = $(this).val();
                resetSelect(idMateria, '(Seleccione Curso)');
                
                if (plan && curso) {
                    showLoader();
                    $.post(ajaxUrl, { action: 'load_materias', idPlan: plan, idCurso: curso }, 'json')
                     .done(function(response) {
                        if (response.success && response.data.length > 0) {
                            idMateria.html('<option value="">Seleccione una materia...</option>');
                            response.data.forEach(item => {
                                idMateria.append(`<option value="${item.idMateria}">${item.nombreMateria}</option>`);
                            });
                            idMateria.prop('disabled', false);
                        } else {
                            resetSelect(idMateria, '(No hay materias)');
                        }
                    }).fail(() => showMessage('danger', 'Error al cargar materias.'))
                      .always(() => hideLoader());
                }
            });

            // (Ciclo, Turno, Materia) -> Mesas
            function loadMesas() {
                const ciclo = idCiclo.val();
                const turno = idTurno.val();
                const materia = idMateria.val();
                
                resetSelect(idFechaExamen, '(Seleccione Ciclo, Turno y Materia)');
                updateContadores(); // Deshabilitará el botón

                if (ciclo && turno && materia) {
                    resetSelect(idFechaExamen, '(Buscando mesas...)');
                    $.post(ajaxUrl, { action: 'load_mesas', idCiclo: ciclo, idTurno: turno, idMateria: materia }, 'json')
                     .done(function(response) {
                        if (response.success && response.data.length > 0) {
                            idFechaExamen.html('<option value="">Seleccione una mesa...</option>');
                            response.data.forEach(mesa => {
                                idFechaExamen.append(`<option value="${mesa.idFechaExamen}">${mesa.fecha} ${mesa.hora} (${mesa.nombreMateria})</option>`);
                            });
                            idFechaExamen.prop('disabled', false);
                        } else {
                            resetSelect(idFechaExamen, '(No se encontraron mesas)');
                        }
                    }).fail(() => { resetSelect(idFechaExamen, '(Error al cargar mesas)'); });
                }
            }
            idCiclo.on('change', loadMesas);
            idTurno.on('change', loadMesas);
            idMateria.on('change', loadMesas);

            // Habilitar botón de inscribir al seleccionar mesa O condición de examen
            idFechaExamen.on('change', updateContadores);
            idCondicionExamen.on('change', updateContadores); // <-- NUEVO

            // --- Lógica Principal ---

            // 1. Botón "Buscar Alumnos"
            btnBuscarAlumnos.on('click', function() {
                const data = {
                    action: 'buscar_alumnos',
                    idMateria: idMateria.val(),
                    idCiclo: idCiclo.val(),
                    // *** CORREGIDO: Lee del select de Cursado ***
                    idCondicionCursado: idCondicionCursado.val() 
                };

                if (!data.idMateria || !data.idCiclo || !data.idCondicionCursado) {
                    showMessage('warning', 'Debe seleccionar Ciclo, Plan, Curso, Materia y Condición de Cursado.');
                    return;
                }

                showLoader();
                containerResultados.slideUp();
                
                $.post(ajaxUrl, data, 'json')
                 .done(function(response) {
                    tablaAlumnosBody.empty();
                    if (response.success) {
                        if(response.data.length > 0) {
                            response.data.forEach(alu => {
                                const row = `
                                    <tr data-id="${alu.idAlumno}" data-nombre="${alu.apellido}, ${alu.nombre}">
                                        <td>${alu.apellido}</td>
                                        <td>${alu.nombre}</td>
                                        <td>${alu.dni}</td>
                                        <td>
                                            <button class="btn btn-danger btn-sm btn-remover-alu" data-bs-toggle="tooltip" title="Quitar de la lista">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `;
                                tablaAlumnosBody.append(row);
                            });
                            containerResultados.slideDown();
                            loadMesas(); // Cargar mesas
                        } else {
                            showMessage('info', 'No se encontraron alumnos que cumplan con esa condición para la materia y ciclo seleccionados.');
                        }
                    } else {
                        showMessage('danger', response.message || 'Error al buscar alumnos.');
                    }
                    updateContadores();
                }).fail(() => {
                    showMessage('danger', 'Error de conexión al buscar alumnos.');
                }).always(() => hideLoader());
            });

            // 2. Botón "Eliminar"
            tablaAlumnosBody.on('click', '.btn-remover-alu', function() {
                $(this).closest('tr').fadeOut(300, function() { 
                    $(this).remove(); 
                    updateContadores();
                });
            });

            // 3. Botón "Inscribir Alumnos" (El bucle masivo)
            btnInscribirMasivo.on('click', async function() {
                // Recolectar datos
                // *** CORREGIDO: Lee de los selects correctos ***
                const commonData = {
                    idMateria: idMateria.val(),
                    idCiclo: idCiclo.val(),
                    idFechaExamen: idFechaExamen.val(),
                    idCondicionExamen: idCondicionExamen.val(),
                    inscripcionTexto: idCondicionExamen.find('option:selected').text()
                };

                // Recolectar alumnos
                let alumnos = [];
                tablaAlumnosBody.find('tr').each(function() {
                    alumnos.push({
                        id: $(this).data('id'),
                        nombre: $(this).data('nombre')
                    });
                });

                if (alumnos.length === 0 || !commonData.idFechaExamen || !commonData.idCondicionExamen) {
                    showMessage('warning', 'No hay alumnos en la lista, o no se seleccionó una mesa, o no se seleccionó la condición de examen.');
                    return;
                }

                // Iniciar proceso
                progressModal.show();
                let results = [];
                const total = alumnos.length;

                for (let i = 0; i < total; i++) {
                    const alumno = alumnos[i];
                    
                    $progressText.text(`Procesando ${i + 1} de ${total}: ${alumno.nombre}`);
                    const percent = ((i + 1) / total) * 100;
                    $progressBar.css('width', percent + '%').attr('aria-valuenow', percent);

                    try {
                        const response = await $.post(ajaxUrl, {
                            action: 'inscribir',
                            idAlumno: alumno.id,
                            idMateria: commonData.idMateria,
                            idCiclo: commonData.idCiclo,
                            idFechaExamen: commonData.idFechaExamen,
                            idCondicionExamen: commonData.idCondicionExamen, // Envía el ID de la cond. examen
                            inscripcionTexto: commonData.inscripcionTexto // Envía el texto de la cond. examen
                        }, 'json');
                        
                        results.push({ nombre: alumno.nombre, message: response.message, success: response.success });

                    } catch (error) {
                        let errorMsg = 'Error de Conexión/Script. El servidor no respondió correctamente.';
                        if (error.responseJSON && error.responseJSON.message) {
                            errorMsg = error.responseJSON.message;
                        } else if (error.responseText) {
                            errorMsg = "Error de Servidor: " + error.responseText.substring(0, 100) + "...";
                        }
                        results.push({ nombre: alumno.nombre, message: errorMsg, success: false });
                    }
                }

                // Proceso terminado
                progressModal.hide();
                
                // Mostrar resultados
                let listHtml = '<ul class="list-group">';
                let successCount = 0;
                let errorCount = 0;
                
                results.forEach(res => {
                    if (res.success) {
                        successCount++;
                        listHtml += `<li class="list-group-item list-group-item-success">
                                        <i class="bi bi-check-circle-fill me-2"></i><strong>${res.nombre}:</strong> ${res.message}
                                     </li>`;
                    } else {
                        errorCount++;
                        listHtml += `<li class="list-group-item list-group-item-danger">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>${res.nombre}:</strong> ${res.message}
                                     </li>`;
                    }
                });
                listHtml += '</ul>';
                
                $resultsModalBody.html(`
                    <p><strong>Proceso finalizado.</strong></p>
                    <p>Inscripciones Exitosas: <span class="badge bg-success">${successCount}</span></p>
                    <p>Inscripciones Fallidas: <span class="badge bg-danger">${errorCount}</span></p>
                    <hr>
                    ${listHtml}
                `);
                resultsModal.show();

                // Resetear UI
                containerResultados.slideUp();
                tablaAlumnosBody.empty();
                updateContadores();
            });

        });
    </script>
</body>

</html>