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

// Incluir control de correlatividad e inicializar variable global de reporte
$materiasAdeuda = ''; 
include_once '../funciones/controlCorrelatividad.php';


// --- MANEJO DE SOLICITUDES AJAX (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json'); 
    $response = ['success' => false, 'data' => [], 'message' => 'Acción no válida.'];
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            
            // 1. CARGA DE COMBOS (Igual a inscripcionExamenAlumno)
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
                    $response['message'] = 'Faltan datos para buscar mesas.'; 
                }
                break;

            // 2. BÚSQUEDA DE CANDIDATOS (Lógica de filtrado)
            case 'buscar_candidatos':
                $idMateria = filter_input(INPUT_POST, 'idMateria', FILTER_VALIDATE_INT);
                $idCurso = filter_input(INPUT_POST, 'idCurso', FILTER_VALIDATE_INT);
                $condicionTexto = $_POST['condicionTexto'] ?? ''; // 'Regular', 'Libre'

                if (!$idMateria || !$idCurso || !$condicionTexto) {
                    $response['message'] = 'Faltan datos para buscar alumnos.';
                    break;
                }

                // A. Obtenemos idUnicoMateria
                $stmtUnico = $conn->prepare("SELECT idUnicoMateria FROM materiaterciario WHERE idMateria = ?");
                $stmtUnico->bind_param("i", $idMateria);
                $stmtUnico->execute();
                $resUnico = $stmtUnico->get_result();
                if ($rowU = $resUnico->fetch_assoc()) {
                    $idUnicoMateria = $rowU['idUnicoMateria'];
                } else {
                    $response['message'] = 'Materia no encontrada.';
                    break;
                }
                $stmtUnico->close();

                // B. Obtenemos TODOS los alumnos del curso (Necesitamos esta función en consultas.php o usamos una query directa aquí solo por lectura)
                // Nota: Usaré una query directa aquí para no pedirte modificar consultas.php ahora, pero idealmente va allá.
                $sqlAlumnos = "SELECT a.idAlumno, p.dni, p.apellido, p.nombre 
                               FROM alumnosterciario a 
                               JOIN persona p ON a.idPersona = p.idPersona 
                               WHERE a.idCurso = ? ORDER BY p.apellido ASC";
                $stmtAlu = $conn->prepare($sqlAlumnos);
                $stmtAlu->bind_param("i", $idCurso);
                $stmtAlu->execute();
                $resAlumnos = $stmtAlu->get_result();
                
                $candidatos = [];

                // C. FILTRADO: Verificamos uno por uno si cumplen la condición "inscripcionExamenEstado"
                while ($alu = $resAlumnos->fetch_assoc()) {
                    // Esta función viene de controlCorrelatividad.php
                    // Devuelve TRUE si el estado numérico del alumno coincide con la condición solicitada (ej: Regular)
                    $esApto = inscripcionExamenEstado($alu['idAlumno'], $idUnicoMateria, $condicionTexto);
                    
                    if ($esApto === true) {
                        // Buscamos el estado numérico solo para mostrarlo en la tabla (informativo)
                        // Reutilizo lógica interna, idealmente esto debería venir optimizado
                        $estadoNum = obtenerIdEstadoCursada($conn, $alu['idAlumno'], $idUnicoMateria); // Función auxiliar definida abajo o en consultas
                        
                        $candidatos[] = [
                            'idAlumno' => $alu['idAlumno'],
                            'dni' => $alu['dni'],
                            'nombreCompleto' => $alu['apellido'] . ', ' . $alu['nombre'],
                            'estado' => $estadoNum ?? 'S/D'
                        ];
                    }
                }
                
                $response = ['success' => true, 'data' => $candidatos, 'idUnicoMateria' => $idUnicoMateria];
                break;

            // 3. EJECUCIÓN MASIVA
            case 'inscribir_masivo':
                $listaAlumnos = json_decode($_POST['listaAlumnos'], true); // Array de IDs
                $idMateria = filter_input(INPUT_POST, 'idMateria', FILTER_VALIDATE_INT);
                $idUnicoMateria = $_POST['idUnicoMateria']; // Viene del hidden
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                $idTurno = filter_input(INPUT_POST, 'idTurno', FILTER_VALIDATE_INT);
                $idFechaExamen = filter_input(INPUT_POST, 'idFechaExamen', FILTER_VALIDATE_INT);
                $idCondicion = filter_input(INPUT_POST, 'idCondicion', FILTER_VALIDATE_INT);
                $condicionTexto = $_POST['condicionTexto'];

                if (empty($listaAlumnos) || !$idFechaExamen) {
                    $response['message'] = 'Datos incompletos para la inscripción.';
                    break;
                }

                $resultados = [];

                foreach ($listaAlumnos as $idAlumno) {
                    // Resetear variable global de errores de correlatividad
                    $materiasAdeuda = ''; 

                    // A. CONTROL DE SEGURIDAD (Estado + Correlativas)
                    // Esta función es el "Director de Orquesta" que definimos anteriormente
                    $control = inscripcionExamenControl($conn, $idAlumno, $idUnicoMateria, $condicionTexto);

                    if ($control === true) {
                        // B. INSCRIPCIÓN EFECTIVA
                        // Usamos la misma función de consultas.php que usas en la inscripción individual
                        $resInscripcion = inscribirAlumnoExamen($conn, $idAlumno, $idMateria, $idCiclo, $idFechaExamen, $idCondicion, $idTurno);
                        
                        if ($resInscripcion['success']) {
                            $resultados[] = ['id' => $idAlumno, 'status' => 'OK', 'msg' => 'Inscripto correctamente'];
                        } else {
                            $resultados[] = ['id' => $idAlumno, 'status' => 'ERROR', 'msg' => $resInscripcion['message']];
                        }
                    } else {
                        // Falló Correlatividades o Estado
                        $resultados[] = ['id' => $idAlumno, 'status' => 'RECHAZADO', 'msg' => $control];
                    }
                }

                $response = ['success' => true, 'resultados' => $resultados];
                break;
        }
    } catch (Throwable $e) {
        error_log("Error FATAL en inscripcionExamenMasivo.php: " . $e->getMessage());
        $response = ['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()];
    }

    echo json_encode($response);
    exit;
}

// --- FUNCIONES AUXILIARES PHP (Para no romper si faltan en consultas.php) ---
// Si ya la tienes en consultas.php, puedes borrarla de aquí.
if (!function_exists('obtenerIdEstadoCursada')) {
    function obtenerIdEstadoCursada($conn, $idAlumno, $idUnicoMateria) {
        $sql = "SELECT c.estadoCursadoNumero 
                FROM calificacionesterciario c
                INNER JOIN materiaterciario m ON c.idMateria = m.idMateria
                WHERE c.idAlumno = ? AND m.idUnicoMateria = ? 
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $idAlumno, $idUnicoMateria);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) return $row['estadoCursadoNumero'];
        return null;
    }
}


// --- LÓGICA DE CARGA DE PÁGINA (GET) ---
// Cargar selectores iniciales
$ciclos = []; $turnos = []; $planes = []; $condicionesExamen = [];
try {
    $ciclos = levantarCiclosLectivos($conn);
    $turnos = obtenerTodosTurnos($conn);
    // Para planes, traemos todos los planes activos (ya que no filtramos por alumno aun)
    $planes = buscarTodosPlanes($conn); // O usar funcion buscarPlanes generica si existe
    
    $condicionesExamen = obtenerCondicionesExamen($conn); 
} catch (Exception $e) {
    die("Error al cargar datos iniciales.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripción Masiva - Secretaría</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/material/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        #loader { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1050; }
        .fila-alumno-borrada { text-decoration: line-through; color: #dc3545; background-color: #f8d7da; }
        .step-container { border-left: 4px solid #007bff; padding-left: 15px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div id="loader">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Procesando...</span>
        </div>
    </div>

    <?php include '../funciones/menu_secretaria.php'; ?>

    <div class="container-fluid fondo">
        <br>
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
                <li class="breadcrumb-item active">Inscripción Masiva</li>
            </ol>

            <div id="messageContainer" class="mb-3" style="display: none;"></div>

            <div class="card p-4 mb-4">
                <h4 class="mb-3 text-primary"><i class="bi bi-people-fill"></i> Inscripción Masiva a Exámenes</h4>
                <p class="text-muted">Seleccione los parámetros para filtrar alumnos y la mesa de destino.</p>
                <hr>

                <div class="step-container">
                    <h5>1. Configuración de Búsqueda y Mesa</h5>
                    <form id="formFiltros">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Ciclo Lectivo <span class="text-danger">*</span></label>
                                <select class="form-select" id="idCiclo" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($ciclos as $c): ?>
                                        <option value="<?= $c['idCicloLectivo'] ?>"><?= $c['anio'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Turno Examen <span class="text-danger">*</span></label>
                                <select class="form-select" id="idTurno" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($turnos as $t): ?>
                                        <option value="<?= $t['idTurno'] ?>"><?= $t['nombre'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Plan de Estudio <span class="text-danger">*</span></label>
                                <select class="form-select" id="idPlan" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($planes as $p): ?>
                                        <option value="<?= $p['idPlan'] ?>"><?= $p['nombre'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                             <div class="col-md-3">
                                <label class="form-label">Condición Inscripción <span class="text-danger">*</span></label>
                                <select class="form-select" id="idCondicion" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($condicionesExamen as $ce): ?>
                                        <option value="<?= $ce['idCondicion'] ?>"><?= $ce['condicion'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-5">
                                <label class="form-label">Curso <span class="text-danger">*</span></label>
                                <select class="form-select" id="idCurso" disabled>
                                    <option value="">(Seleccione Plan y Ciclo)</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Materia <span class="text-danger">*</span></label>
                                <select class="form-select" id="idMateria" disabled>
                                    <option value="">(Seleccione Curso)</option>
                                </select>
                                <input type="hidden" id="idUnicoMateria">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-info text-white w-100" id="btnBuscarAlumnos" disabled>
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div id="contenedorMesas" style="display:none;" class="mt-3 bg-light p-3 border rounded">
                        <h6><i class="bi bi-calendar-event"></i> Seleccione la Mesa de Examen:</h6>
                        <div id="listaMesas"></div>
                    </div>
                    <div id="noMesas" style="display:none;" class="alert alert-warning mt-2">
                        No hay mesas configuradas para estos filtros.
                    </div>
                </div>

                <div id="paso2Container" style="display:none;">
                    <div class="step-container">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5>2. Alumnos Candidatos</h5>
                            <span class="badge bg-secondary" id="contadorAlumnos">0 alumnos</span>
                        </div>
                        <p class="small text-muted">A continuación se muestran los alumnos que cumplen la condición de cursado. Puede eliminar los que no desee inscribir.</p>
                        
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-striped table-hover table-sm" id="tablaCandidatos">
                                <thead class="table-dark">
                                    <tr>
                                        <th>DNI</th>
                                        <th>Apellido y Nombre</th>
                                        <th>Estado (Cód)</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button class="btn btn-success btn-lg" id="btnEjecutarMasivo" disabled>
                            <i class="bi bi-check-circle-fill"></i> EJECUTAR INSCRIPCIÓN MASIVA
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalResultados" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Resultados de la Inscripción</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="progress mb-3" style="height: 25px;">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%">Procesando...</div>
                    </div>
                    
                    <table class="table table-bordered table-sm" id="tablaResultados">
                        <thead>
                            <tr>
                                <th>Alumno</th>
                                <th>Resultado</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
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
        
        // --- HELPERS ---
        function showLoader() { $('#loader').fadeIn('fast'); }
        function hideLoader() { $('#loader').fadeOut('fast'); }
        function showMessage(type, msg) {
            $('#messageContainer').html(`<div class="alert alert-${type}">${msg}</div>`).show();
            setTimeout(() => $('#messageContainer').fadeOut(), 5000);
        }

        // --- COMBOS ENCADENADOS ---
        $('#idPlan, #idCiclo').change(function() {
            const plan = $('#idPlan').val();
            const ciclo = $('#idCiclo').val();
            $('#idCurso').html('<option value="">Cargando...</option>').prop('disabled', true);
            
            if(plan && ciclo) {
                $.post(ajaxUrl, { action: 'load_cursos', idPlan: plan, idCiclo: ciclo }, function(res){
                    if(res.success) {
                        let opts = '<option value="">Seleccione Curso...</option>';
                        res.data.forEach(c => opts += `<option value="${c.idCurso}">${c.nombre}</option>`);
                        $('#idCurso').html(opts).prop('disabled', false);
                    }
                }, 'json');
            }
        });

        $('#idCurso').change(function() {
            const plan = $('#idPlan').val();
            const curso = $(this).val();
            $('#idMateria').html('<option value="">Cargando...</option>').prop('disabled', true);

            if(plan && curso) {
                $.post(ajaxUrl, { action: 'load_materias', idPlan: plan, idCurso: curso }, function(res){
                    if(res.success) {
                        let opts = '<option value="">Seleccione Materia...</option>';
                        res.data.forEach(m => opts += `<option value="${m.idMateria}">${m.nombreMateria}</option>`);
                        $('#idMateria').html(opts).prop('disabled', false);
                    }
                }, 'json');
            }
        });

        // Detectar cambios para habilitar botón Buscar
        $('#idMateria, #idCondicion, #idTurno').change(function() {
            const ok = $('#idMateria').val() && $('#idCondicion').val() && $('#idTurno').val();
            $('#btnBuscarAlumnos').prop('disabled', !ok);
            // Si cambian la materia o el turno, resetear la tabla
            $('#paso2Container').hide();
        });

        // --- BUSCAR ALUMNOS Y MESAS ---
        $('#btnBuscarAlumnos').click(function() {
            const idMateria = $('#idMateria').val();
            const idCurso = $('#idCurso').val();
            const idCiclo = $('#idCiclo').val();
            const idTurno = $('#idTurno').val();
            const condicionTexto = $('#idCondicion option:selected').text(); // "Regular" o "Libre"

            if(!idMateria || !idCurso) return;

            showLoader();
            $('#paso2Container').hide();
            $('#contenedorMesas').hide();
            $('#listaMesas').empty();
            $('#tablaCandidatos tbody').empty();

            // 1. Buscar Mesas primero
            $.post(ajaxUrl, { 
                action: 'load_mesas', idCiclo: idCiclo, idTurno: idTurno, idMateria: idMateria 
            }, function(resMesas) {
                
                if(resMesas.success && resMesas.data.length > 0) {
                    let htmlMesas = '';
                    resMesas.data.forEach(m => {
                        htmlMesas += `
                        <div class="form-check">
                            <input class="form-check-input mesa-radio" type="radio" name="mesaSel" value="${m.idFechaExamen}" id="mesa${m.idFechaExamen}">
                            <label class="form-check-label" for="mesa${m.idFechaExamen}">
                                <strong>Fecha:</strong> ${m.fecha} - <strong>Hora:</strong> ${m.hora}
                            </label>
                        </div>`;
                    });
                    $('#listaMesas').html(htmlMesas);
                    $('#contenedorMesas').show();

                    // 2. Buscar Alumnos
                    $.post(ajaxUrl, {
                        action: 'buscar_candidatos',
                        idMateria: idMateria,
                        idCurso: idCurso,
                        condicionTexto: condicionTexto
                    }, function(resAlu) {
                        hideLoader();
                        if(resAlu.success) {
                            $('#idUnicoMateria').val(resAlu.idUnicoMateria); // Guardar para uso posterior
                            const lista = resAlu.data;
                            $('#contadorAlumnos').text(lista.length + ' alumnos');
                            
                            if(lista.length === 0) {
                                showMessage('warning', 'No se encontraron alumnos en condiciones de inscribirse en este estado.');
                            } else {
                                let html = '';
                                lista.forEach(a => {
                                    html += `
                                    <tr data-id="${a.idAlumno}">
                                        <td>${a.dni}</td>
                                        <td>${a.nombreCompleto}</td>
                                        <td>${a.estado}</td>
                                        <td class="text-center">
                                            <button class="btn btn-outline-danger btn-sm btn-borrar-fila" title="Quitar de la lista">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>`;
                                });
                                $('#tablaCandidatos tbody').html(html);
                                $('#paso2Container').fadeIn();
                            }
                        } else {
                            showMessage('danger', resAlu.message);
                        }
                    }, 'json');

                } else {
                    hideLoader();
                    $('#noMesas').show();
                }
            }, 'json');
        });

        // --- MANEJO DE LA TABLA (QUITAR FILAS) ---
        $(document).on('click', '.btn-borrar-fila', function() {
            $(this).closest('tr').remove();
            // Actualizar contador
            const count = $('#tablaCandidatos tbody tr').length;
            $('#contadorAlumnos').text(count + ' alumnos');
        });

        // Habilitar botón Ejecutar solo si hay mesa seleccionada
        $(document).on('change', '.mesa-radio', function() {
            $('#btnEjecutarMasivo').prop('disabled', false);
        });

        // --- EJECUCIÓN MASIVA ---
        $('#btnEjecutarMasivo').click(function() {
            const idMesa = $('input[name="mesaSel"]:checked').val();
            const idCondicion = $('#idCondicion').val();
            const condicionTexto = $('#idCondicion option:selected').text();
            
            // Recolectar IDs
            let ids = [];
            $('#tablaCandidatos tbody tr').each(function() {
                ids.push($(this).data('id'));
            });

            if(ids.length === 0) { alert('No hay alumnos para inscribir.'); return; }
            if(!idMesa) { alert('Seleccione una mesa.'); return; }

            // Configurar Modal
            $('#modalResultados').modal('show');
            $('#tablaResultados tbody').empty();
            $('#progressBar').css('width', '100%').addClass('progress-bar-animated').removeClass('bg-success bg-danger').text('Procesando inscripciones...');

            // AJAX Enviar Lote
            $.post(ajaxUrl, {
                action: 'inscribir_masivo',
                listaAlumnos: JSON.stringify(ids),
                idMateria: $('#idMateria').val(),
                idUnicoMateria: $('#idUnicoMateria').val(),
                idCiclo: $('#idCiclo').val(),
                idTurno: $('#idTurno').val(),
                idFechaExamen: idMesa,
                idCondicion: idCondicion,
                condicionTexto: condicionTexto
            }, function(res) {
                $('#progressBar').removeClass('progress-bar-animated').addClass('bg-success').text('Proceso Finalizado');
                
                if(res.success) {
                    let html = '';
                    res.resultados.forEach(r => {
                        let clase = r.status === 'OK' ? 'table-success' : 'table-danger';
                        let icono = r.status === 'OK' ? '<i class="bi bi-check text-success"></i>' : '<i class="bi bi-x text-danger"></i>';
                        
                        // Buscar nombre en la tabla visual para mostrarlo en el modal
                        let nombre = $(`tr[data-id="${r.id}"] td:nth-child(2)`).text() || 'ID: ' + r.id;

                        html += `
                        <tr class="${clase}">
                            <td>${nombre}</td>
                            <td>${icono} ${r.status}</td>
                            <td><small>${r.msg}</small></td>
                        </tr>`;
                    });
                    $('#tablaResultados tbody').html(html);
                } else {
                    $('#tablaResultados tbody').html(`<tr><td colspan="3" class="text-danger">${res.message}</td></tr>`);
                }

            }, 'json').fail(function() {
                $('#progressBar').removeClass('bg-success').addClass('bg-danger').text('Error de conexión');
                alert('Error crítico de conexión.');
            });
        });

    });
    </script>
</body>
</html>