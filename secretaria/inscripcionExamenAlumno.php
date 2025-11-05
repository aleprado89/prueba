<?php
// Incluir el script de verificación de sesión
include '../funciones/verificarSesion.php';

// Habilitar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir la conexión a la base de datos y consultas
include '../inicio/conexion.php';
include '../funciones/consultas.php';

// --- MANEJO DE SOLICITUDES AJAX (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json'); // Aseguramos que la respuesta sea JSON
    $response = ['success' => false, 'data' => [], 'message' => 'Acción no válida.'];

    // Sanitizamos la acción
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            
            // Cargar Cursos
            case 'load_cursos':
                $idPlan = filter_input(INPUT_POST, 'idPlan', FILTER_VALIDATE_INT);
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                
                if ($idPlan && $idCiclo) {
                    $cursos = buscarCursosPlanCiclo($conn, $idPlan, $idCiclo);
                    $response = ['success' => true, 'data' => $cursos];
                } else {
                    $response['message'] = 'ID de Plan o Ciclo no válido.';
                }
                break;

            // Cargar Materias
            case 'load_materias':
                $idPlan = filter_input(INPUT_POST, 'idPlan', FILTER_VALIDATE_INT);
                $idCurso = filter_input(INPUT_POST, 'idCurso', FILTER_VALIDATE_INT);
                
                if ($idPlan && $idCurso) {
                    $materias = materiasPlanCurso($conn, $idPlan, $idCurso);
                    $response = ['success' => true, 'data' => $materias];
                } else {
                    $response['message'] = 'ID de Plan o Curso no válido.';
                }
                break;

            // Cargar Mesas de Examen
            case 'load_mesas':
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                $idTurno = filter_input(INPUT_POST, 'idTurno', FILTER_VALIDATE_INT);
                $idMateria = filter_input(INPUT_POST, 'idMateria', FILTER_VALIDATE_INT);
                
                if ($idCiclo && $idTurno && $idMateria) {
                    // Usamos filtrarMesasExamen, pasando null a plan y curso ya que tenemos la materia
                    $mesas = filtrarMesasExamen($conn, $idCiclo, $idTurno, null, null, $idMateria);
                    $response = ['success' => true, 'data' => $mesas];
                } else {
                    $response['message'] = 'Ciclo, Turno o Materia no seleccionados.';
                }
                break;
            
            // Inscribir Alumno
            case 'inscribir':
                $idAlumno = filter_input(INPUT_POST, 'idAlumno', FILTER_VALIDATE_INT);
                $idMateria = filter_input(INPUT_POST, 'idMateria', FILTER_VALIDATE_INT);
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                $idFechaExamen = filter_input(INPUT_POST, 'idFechaExamen', FILTER_VALIDATE_INT);
                $idCondicion = filter_input(INPUT_POST, 'idCondicion', FILTER_VALIDATE_INT);

                if ($idAlumno && $idMateria && $idCiclo && $idFechaExamen && $idCondicion) {
                    $response = inscribirAlumnoExamen($conn, $idAlumno, $idMateria, $idCiclo, $idFechaExamen, $idCondicion);
                } else {
                    $response['message'] = 'Datos incompletos. Faltan IDs necesarios para la inscripción.';
                }
                break;
        }
    } catch (Exception $e) {
        error_log("Error en AJAX (inscripcionExamenAlumno.php): " . $e->getMessage());
        $response = ['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()];
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
$condiciones = [];

if (!$idAlumno) {
    die("Error: ID de Alumno no proporcionado o no válido.");
}

// Cargar datos iniciales
try {
    $alumno = obtenerDatosBasicosAlumno($conn, $idAlumno);
    if (!$alumno) {
        die("Error: No se encontró al alumno con ID " . htmlspecialchars($idAlumno));
    }
    
    // Usamos las funciones existentes de consultas.php
    $resultCiclos = obtenerCiclosLectivos($conn);
    while($fila = $resultCiclos->fetch_assoc()) { $ciclos[] = $fila; }
    
    $resultTurnos = obtenerTurnosExamen($conn);
    while($fila = $resultTurnos->fetch_assoc()) { $turnos[] = $fila; }
    
    $planes = buscarPlanes($conn, $idAlumno); // Planes del alumno
    
    $condiciones = obtenerCondicionesCursado($conn); // Condiciones (Regular, Libre, etc.)

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
        #loader {
            display: none; /* Oculto por defecto, se muestra con JS */
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1050;
        }
        .table-hover tbody tr:hover {
            cursor: pointer;
        }
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
                                    <option value="<?php echo htmlspecialchars($ciclo['idciclolectivo']); ?>">
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
                                <?php foreach ($condiciones as $condicion): ?>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarInscripcion">Confirmar Inscripción</button>
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
            const spinnerMesas = $('#spinnerMesas');
            const containerMesas = $('#containerMesas');
            const noMesas = $('#noMesas');
            const tablaMesasBody = $('#tablaMesasBody');
            const btnInscribir = $('#btnInscribir');
            const messageContainer = $('#messageContainer');

            // *** NUEVO: Cachear instancia del Modal y botón de confirmar ***
            const modalConfirm = new bootstrap.Modal(document.getElementById('confirmInscripcionModal'));
            const btnConfirmarInscripcion = $('#btnConfirmarInscripcion');

            // --- Funciones auxiliares ---

            function showLoader() { loader.fadeIn('fast'); }
            function hideLoader() { loader.fadeOut('fast'); }
            function showMessage(type, message) {
                messageContainer
                    .html(`<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                             ${message}
                             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                           </div>`)
                    .fadeIn();
            }

            // Función para resetear selectores
            function resetSelect(selector, message) {
                $(selector).html(`<option value="">${message}</option>`).prop('disabled', true);
            }
            
            // Función para poblar selectores
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

            // Función para buscar mesas (el filtro principal)
            function buscarMesas() {
                const idCiclo = $('#idCiclo').val();
                const idTurno = $('#idTurno').val();
                const idMateria = $('#idMateria').val();

                // Resetear tabla y botón
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

            // --- Control de Filtros Dependientes ---

            // 1. (Plan y Ciclo) -> Cursos
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

            // 2. (Curso) -> Materias
            $('#idCurso').on('change', function() {
                const idPlan = $('#idPlan').val(); // El plan ya está seleccionado
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
                            // Asumimos que la función materiasPlanCurso devuelve idMateria y nombreMateria
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

            // 3. (Ciclo, Turno, Materia) -> Mesas (Tabla)
            $('#idCiclo, #idTurno, #idMateria').on('change', buscarMesas);

            // 4. Habilitar botón al seleccionar mesa
            // Usamos delegación de eventos por si la tabla se recarga
            $(document).on('change', 'input[name="idFechaExamen"]', function() {
                if ($(this).is(':checked')) {
                    btnInscribir.prop('disabled', false);
                }
            });

            // --- *** LÓGICA DE INSCRIPCIÓN MODIFICADA *** ---

            // 1. Click en el botón principal "Inscribir Alumno"
            $('#btnInscribir').on('click', function() {
                // Validar campos *antes* de mostrar el modal
                const idCondicion = $('#idCondicion').val();
                const idFechaExamen = $('input[name="idFechaExamen"]:checked').val();

                let isValid = true;
                let errorMsg = 'Faltan datos. Asegúrese de seleccionar ';
                let errors = [];

                if (!idFechaExamen) {
                    errors.push('una mesa');
                    isValid = false;
                }
                if (!idCondicion) {
                    errors.push('la condición');
                    isValid = false;
                }

                if (!isValid) {
                    errorMsg += errors.join(' y ') + '.';
                    showMessage('warning', errorMsg);
                    return; // No mostrar el modal si falta algo
                }
                
                // Si la validación pasa, mostrar el modal
                modalConfirm.show();
            });

            // 2. Click en el botón "Confirmar Inscripción" DENTRO del modal
            btnConfirmarInscripcion.on('click', function() {
                // Ocultar el modal
                modalConfirm.hide();

                // Recolectar datos (ya sabemos que idCondicion y idFechaExamen existen por la validación anterior)
                const idAlumno = $('#idAlumno').val();
                const idCiclo = $('#idCiclo').val();
                const idMateria = $('#idMateria').val();
                const idCondicion = $('#idCondicion').val();
                const idFechaExamen = $('input[name="idFechaExamen"]:checked').val();

                // Mostrar spinner y deshabilitar botones
                showLoader();
                btnInscribir.prop('disabled', true); // Deshabilitar el botón principal
                btnConfirmarInscripcion.prop('disabled', true); // Deshabilitar el botón del modal

                // Ejecutar el AJAX (esta es la lógica del antiguo handler 'submit')
                $.post(ajaxUrl, {
                    action: 'inscribir',
                    idAlumno: idAlumno,
                    idMateria: idMateria,
                    idCiclo: idCiclo,
                    idFechaExamen: idFechaExamen,
                    idCondicion: idCondicion
                }, function(response) {
                    hideLoader();
                    btnConfirmarInscripcion.prop('disabled', false); // Reactivar botón del modal

                    if (response.success) {
                        showMessage('success', response.message || 'Inscripción exitosa.');
                        // Reseteamos la tabla para evitar doble inscripción
                        tablaMesasBody.empty();
                        containerMesas.hide();
                        noMesas.hide();
                        // El botón 'btnInscribir' permanece deshabilitado hasta que se seleccione una nueva mesa
                    } else {
                        showMessage('danger', response.message || 'Error al inscribir.');
                        // Habilitamos el botón principal de nuevo si falló (ya que la selección sigue activa)
                        btnInscribir.prop('disabled', false);
                    }
                }, 'json').fail(function() {
                    hideLoader();
                    btnInscribir.prop('disabled', false); // Reactivar botón principal
                    btnConfirmarInscripcion.prop('disabled', false); // Reactivar botón del modal
                    showMessage('danger', 'Error de conexión. No se pudo completar la inscripción.');
                });
            });

        });
    </script>
</body>

</html>