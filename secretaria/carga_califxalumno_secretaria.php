<?php
// Archivo: carga_califxalumno_secretaria.php
ob_start();
// --- INCLUSIONES Y CONFIGURACIÓN INICIAL ---
include '../funciones/verificarSesion.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- MANEJO DE PETICIONES AJAX (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    ob_end_clean(); 
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_calif':
            if (isset($_POST['idCalificacion'], $_POST['columna'], $_POST['nuevoValor'])) {
                $idCalificacion = $_POST['idCalificacion'];
                $columna = $_POST['columna'];
                $nuevoValor = trim($_POST['nuevoValor']);
                $valoresPermitidos = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'A', 'AP', 'NA'];
                $valorParaGuardar = ($nuevoValor === '') ? null : strtoupper($nuevoValor);
                if (!is_null($valorParaGuardar) && !in_array($valorParaGuardar, $valoresPermitidos)) {
                    echo json_encode(['respuesta' => 'error_validacion', 'mensaje' => 'Valor no permitido.']);
                    exit;
                }
                $respuesta_db = actualizarCalifDocente($conn, $idCalificacion, $columna, $valorParaGuardar);
                echo json_encode(['respuesta' => $respuesta_db]);
            } else {
                echo json_encode(['respuesta' => 'error_datos', 'mensaje' => 'Datos POST incompletos para actualizar calificación.']);
            }
            break;

        case 'update_abandono':
            if (isset($_POST['idAlumno'], $_POST['idMateria'], $_POST['estado'])) {
                $idAlumno = $_POST['idAlumno'];
                $idMateria = $_POST['idMateria'];
                $estado = $_POST['estado'];
                $respuesta_db = actualizarAbandonoCursado($conn, $idAlumno, $idMateria, $estado);
                echo json_encode(['respuesta' => $respuesta_db, 'nuevoEstado' => $estado]);
            } else {
                echo json_encode(['respuesta' => 'error_datos', 'mensaje' => 'Datos POST incompletos para abandono.']);
            }
            break;

        case 'get_examenes':
            if (isset($_POST['idAlumno'], $_POST['idMateria'])) {
                $examenes = buscarExamenes($conn, $_POST['idAlumno'], $_POST['idMateria']);
                echo json_encode(['examenes' => $examenes]);
            } else {
                echo json_encode(['examenes' => []]);
            }
            break;
            
        case 'get_condiciones_cursado':
            $condiciones = obtenerCondicionesCursado($conn);
            echo json_encode(['success' => true, 'data' => $condiciones]);
            break;

        default:
            echo json_encode(['respuesta' => 'error', 'mensaje' => 'Acción no reconocida.']);
            break;
    }
    exit;
}
// --- FIN DEL BLOQUE AJAX POST ---


// --- LÓGICA DE CARGA DE PÁGINA (GET) ---
$idAlumno = $_GET['idAlumno'] ?? null;
if (!$idAlumno) {
    $_SESSION['mensaje_error'] = "No se especificó el alumno.";
    header('Location: buscarAlumno.php?origin=califxalumno');
    exit;
}

$datosAlumno = obtenerDatosBasicosAlumno($conn, $idAlumno);
$nombreCompletoAlumno = htmlspecialchars(($datosAlumno['apellido'] ?? '') . ', ' . ($datosAlumno['nombre'] ?? ''));
$materiasDelAlumno = obtenerMateriasConCalificacionesPorAlumno($conn, $idAlumno);
$planesDelAlumno = obtenerPlanesDeAlumnoConCalificaciones($conn, $idAlumno);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificaciones por Alumno - Secretaría</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/material/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .table-responsive { cursor: grab; }
        .table-responsive.grabbing { cursor: grabbing; user-select: none; }
        .col-extra { display: none; }
        .prom-header-content { display: flex; justify-content: center; align-items: center; height: 100%; text-align: center; }
        th.text-center { text-align: center !important; }
        .calif-cell, .prom-cell { min-width: 50px; padding: 8px; text-align: center; border: 1px solid #dee2e6; }
        .prom-cell { min-width: 100px; }
        
        /* >>> NUEVOS ESTILOS PARA MATERIAS APROBADAS Y ABANDONADAS <<< */
        .fila-abandonada td { background-color: #e9ecef !important; color: #6c757d; }
        .fila-abandonada .calif-cell, .fila-abandonada .prom-cell { pointer-events: none; }

        .fila-aprobada td { background-color: #8ddfbaff   }
        .fila-aprobada .calif-cell, .fila-aprobada .prom-cell, .fila-aprobada .check-abandono { pointer-events: none; } /* Deshabilita todo menos detalles */

        .estado-cursado-aprobado { color: green; font-weight: bold; }
        .estado-cursado-desaprobado { color: red; font-weight: bold; }
        .estado-cursado-en-curso { color: blue; }
        .estado-cursado-abandonado { color: orange; font-weight: bold; }
        .estado-cursado-libre { color: purple; font-weight: bold; }
        
        .modal-body strong.primary-link { color: var(--bs-link-color); }
        .icono-detalle { font-size: 1.2rem; color: black; text-decoration: none; transition: color 0.2s ease-in-out; }
        .icono-detalle:hover { color: var(--bs-link-color); }
        .scroll-top {overflow-x: auto; overflow-y: hidden;}
        .scroll-top-inner {height: 1px;}
    </style>
</head>
<body>
    <div id="info-pagina" 
         data-id-alumno="<?php echo htmlspecialchars($idAlumno); ?>"
         data-nombre-alumno="<?php echo $nombreCompletoAlumno; ?>">
    </div>

    <?php include '../funciones/menu_secretaria.php'; ?>

    <div class="container-fluid fondo">
        <br>
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
                <li class="breadcrumb-item"><a href="buscarAlumno.php?origin=califxalumno">Buscar Alumno</a></li>
                <li class="breadcrumb-item active">Calificaciones por Alumno</li>
            </ol>

            <div class="card p-4 mb-4">
                <h5><?php echo "Usuario: " . htmlspecialchars($_SESSION['sec_nombreUsuario'] ?? 'Secretaría'); ?> </h5>
                <h5><?php echo "Alumno: " . $nombreCompletoAlumno; ?> </h5>
                
                <?php if (!empty($planesDelAlumno)): ?>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="filtroPlan" class="form-label"><strong>Filtrar por Plan de Estudio:</strong></label>
                            <select class="form-select" id="filtroPlan">
                                <?php foreach ($planesDelAlumno as $plan): ?>
                                    <option value="<?php echo htmlspecialchars($plan['idPlan']); ?>">
                                        <?php echo htmlspecialchars($plan['nombrePlan']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>

                <br>
                <p><small>* Las calificaciones se guardan automáticamente al salir de la celda. Una celda verde indica un guardado exitoso.
                <br>Valores permitidos: <strong>1-10</strong> (notas), <strong>A</strong> (ausente), <strong>AP</strong> (aprobado), <strong>NA</strong> (no aprobado).
                </small></p>
            </div>

            <?php if (empty($materiasDelAlumno)): ?>
                <div class="alert alert-info">El alumno no tiene calificaciones registradas.</div>
            <?php else: ?>
                <div class="mb-3 text-center">
                    <a href="#" id="btnImprimirCalificaciones" class="btn btn-primary" target="_blank">
                        <i class="bi bi-printer-fill"></i> Imprimir Calificaciones
                    </a>
                </div>
                <div class="d-flex justify-content-end mb-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="switchColumnasExtra">
                        <label class="form-check-label" for="switchColumnasExtra">Mostrar columnas extra</label>
                    </div>
                </div>
                <div class="scroll-top mb-1" id="scrollTop">
                    <div class="scroll-top-inner"></div> 
                </div>
                <div class="table-responsive" id="tabla-calificaciones-container">
                    <table class="table table-hover">
                        <thead>
                            <tr class="table-primary">
                                <th>Materia</th>
                                <th class="col-extra">Curso</th>
                                <th class="col-extra">Ciclo Lectivo</th>
                                <th>P1</th><th>P2</th><th>P3</th><th>P4</th><th>P5</th><th>P6</th><th>P7</th><th>P8</th>
                                <th>R1</th><th>R2</th><th>R3</th><th>R4</th><th>R5</th><th>R6</th><th>R7</th><th>R8</th>
                                <th class="col-extra text-center"><div class="prom-header-content"><span>Prom</span></div></th>
                                <th class="col-extra">Asist.</th>
                                <th>Estado Cursado</th>
                                <th class="col-extra">Abandonó</th>
                                <th>Detalles</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-calificaciones-body">
                            <?php foreach ($materiasDelAlumno as $materia): 
                                $esAprobada = ($materia['materiaAprobada'] == 1);
                                $esAbandono = ($materia['estadoInscripcion'] === 'Abandonó Cursado');
                                $classFila = '';
                                if ($esAprobada) {
                                    $classFila = 'fila-aprobada';
                                } elseif ($esAbandono) {
                                    $classFila = 'fila-abandonada';
                                }
                            ?>
                                <tr class="<?php echo $classFila; ?>"
                                    data-idplan="<?php echo htmlspecialchars($materia['idPlan']); ?>"
                                    data-idcalificacion="<?php echo htmlspecialchars($materia['idCalificacion']); ?>" 
                                    data-idmateria="<?php echo htmlspecialchars($materia['idMateria']); ?>" 
                                    data-idalumno="<?php echo htmlspecialchars($materia['idAlumno']); ?>"
                                    data-info="<?php echo htmlspecialchars(json_encode($materia), ENT_QUOTES, 'UTF-8'); ?>">
                                    <td><?php echo htmlspecialchars($materia['nombreMateria'] ?? 'N/A'); ?></td>
                                    <td class="col-extra"><?php echo htmlspecialchars($materia['nombreCurso'] ?? 'N/A'); ?></td>
                                    <td class="col-extra"><?php echo htmlspecialchars($materia['anioCiclo'] ?? 'N/A'); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="n1"><?php echo htmlspecialchars($materia['n1'] ?? ''); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="n2"><?php echo htmlspecialchars($materia['n2'] ?? ''); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="n3"><?php echo htmlspecialchars($materia['n3'] ?? ''); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="n4"><?php echo htmlspecialchars($materia['n4'] ?? ''); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="n5"><?php echo htmlspecialchars($materia['n5'] ?? ''); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="n6"><?php echo htmlspecialchars($materia['n6'] ?? ''); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="n7"><?php echo htmlspecialchars($materia['n7'] ?? ''); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="n8"><?php echo htmlspecialchars($materia['n8'] ?? ''); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="r1"><?php echo htmlspecialchars($materia['r1'] ?? ''); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="r2"><?php echo htmlspecialchars($materia['r2'] ?? ''); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="r3"><?php echo htmlspecialchars($materia['r3'] ?? ''); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="r4"><?php echo htmlspecialchars($materia['r4'] ?? ''); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="r5"><?php echo htmlspecialchars($materia['r5'] ?? ''); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="r6"><?php echo htmlspecialchars($materia['r6'] ?? ''); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="r7"><?php echo htmlspecialchars($materia['r7'] ?? ''); ?></td>
                                    <td class="calif-cell" contenteditable="true" data-columna="r8"><?php echo htmlspecialchars($materia['r8'] ?? ''); ?></td>
                                    <td class="prom-cell col-extra" contenteditable="true" data-columna="examenIntegrador"><?php echo htmlspecialchars($materia['examenIntegrador'] ?? ''); ?></td>
                                    <td class="col-extra"><?php echo htmlspecialchars($materia['asistencia'] ?? ''); ?></td>
                                    <td>
                                        <span class="estado-cursado-display">
                                            <?php
                                            if ($esAprobada) {
                                                echo 'Materia Aprobada';
                                            } else {
                                                echo htmlspecialchars($materia['estadoCursado'] ?: 'Sin definir');
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td class="col-extra text-center">
                                        <input class="form-check-input check-abandono" type="checkbox" 
                                               <?php echo $esAbandono ? 'checked' : ''; ?>
                                               <?php echo $esAprobada ? 'disabled' : ''; ?>>
                                    </td>
                                    <td class="text-center">
                                        <a href="#" class="icono-detalle" data-bs-toggle="modal" data-bs-target="#modalDetalleMateria">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Todos los modales completos -->
    <div class="modal fade" id="modalDetalleMateria" tabindex="-1" aria-labelledby="modalDetalleMateriaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetalleMateriaLabel">Detalles de la Materia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong class="primary-link">Materia:</strong> <span id="modalMateriaNombre"></span></p>
                    <p><strong class="primary-link">Curso:</strong> <span id="modalMateriaCurso"></span></p>
                    <p><strong class="primary-link">Estado de Inscripción:</strong> <span id="modalEstadoInscripcion"></span></p>
                    <p><strong class="primary-link">Estado General:</strong> <span id="modalEstadoGeneral"></span></p>
                    <p><strong class="primary-link">Asistencia:</strong> <span id="modalAsistencia"></span></p>
                    <hr>
                    <h6 class="primary-link">Calificaciones de Cursado</h6>
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <tr><td>P1</td><td id="modalN1"></td><td>R1</td><td id="modalR1"></td></tr>
                            <tr><td>P2</td><td id="modalN2"></td><td>R2</td><td id="modalR2"></td></tr>
                            <tr><td>P3</td><td id="modalN3"></td><td>R3</td><td id="modalR3"></td></tr>
                            <tr><td>P4</td><td id="modalN4"></td><td>R4</td><td id="modalR4"></td></tr>
                            <tr><td>P5</td><td id="modalN5"></td><td>R5</td><td id="modalR5"></td></tr>
                            <tr><td>P6</td><td id="modalN6"></td><td>R6</td><td id="modalR6"></td></tr>
                            <tr><td>P7</td><td id="modalN7"></td><td>R7</td><td id="modalR7"></td></tr>
                            <tr><td>P8</td><td id="modalN8"></td><td>R8</td><td id="modalR8"></td></tr>
                            <tr><td colspan="2">Calificación Promoción (Prom)</td><td colspan="2" id="modalExamenIntegrador"></td></tr>
                        </tbody>
                    </table>
                    <hr>
                    <h6 class="primary-link">Calificaciones de Exámenes</h6>
                    <div id="examenes-container">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Fecha</th><th>Calificación</th></tr></thead>
                            <tbody id="modalExamenesBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalConfirmAbandono" tabindex="-1" aria-labelledby="modalConfirmAbandonoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="modalConfirmAbandonoLabel">Confirmar Abandono de Materia</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <p>Al marcar esta casilla, se bloqueará la edición de calificaciones para esta materia.</p>
                    <p>¿Desea continuar?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelarAbandonoBtn">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmarAbandonoBtn">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalRevertAbandono" tabindex="-1" aria-labelledby="modalRevertAbandonoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="modalRevertAbandonoLabel">Revertir Abandono de Materia</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <p>Por favor, seleccione el nuevo estado de cursado para el alumno:</p>
                    <div class="mb-3">
                        <label for="selectNewEstado" class="form-label">Estado:</label>
                        <select class="form-select" id="selectNewEstado"><option value="">Cargando...</option></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelarRevertBtn">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmarRevertBtn">Aceptar</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../funciones/footer.html'; ?>
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function() {
        const infoPagina = $('#info-pagina');
        const idAlumno = infoPagina.data('id-alumno');
        const nombreAlumno = infoPagina.data('nombre-alumno');

        // --- LÓGICA DEL FILTRO Y BOTÓN DE IMPRESIÓN ---
        const btnImprimir = $('#btnImprimirCalificaciones');
        const filtroPlanSelect = $('#filtroPlan');
        
        function actualizarEnlaceImpresion() {
            const idPlanSeleccionado = filtroPlanSelect.val();
            if (btnImprimir.length && idPlanSeleccionado) {
                let url = `../reportes/PDFcalificacionesAlumno.php?idAlumno=${idAlumno}&idPlan=${idPlanSeleccionado}&nombreAlumno=${encodeURIComponent(nombreAlumno)}`;
                btnImprimir.attr('href', url);
            }
        }
        function filtrarTablaPorPlan() {
            const idPlanSeleccionado = filtroPlanSelect.val();
            $('#tabla-calificaciones-body tr').each(function() {
                const fila = $(this);
                if (idPlanSeleccionado && fila.data('idplan') == idPlanSeleccionado) {
                    fila.show();
                } else {
                    fila.hide();
                }
            });
        }
        if (filtroPlanSelect.length > 0) {
            filtrarTablaPorPlan(); 
            actualizarEnlaceImpresion();
            filtroPlanSelect.on('change', function() {
                filtrarTablaPorPlan();
                actualizarEnlaceImpresion();
            });
        }
        
        $('#switchColumnasExtra').on('change', function() { $('.col-extra').toggle(this.checked); });

        const slider = document.querySelector('#tabla-calificaciones-container');
        if(slider) {
            let isDown = false, startX, scrollLeft;
            slider.addEventListener('mousedown', (e) => { isDown = true; slider.classList.add('grabbing'); startX = e.pageX - slider.offsetLeft; scrollLeft = slider.scrollLeft; });
            slider.addEventListener('mouseleave', () => { isDown = false; slider.classList.remove('grabbing'); });
            slider.addEventListener('mouseup', () => { isDown = false; slider.classList.remove('grabbing'); });
            slider.addEventListener('mousemove', (e) => { if (!isDown) return; e.preventDefault(); const x = e.pageX - slider.offsetLeft; const walk = (x - startX) * 2; slider.scrollLeft = scrollLeft - walk; });
        }
        
        function enviarAjax(action, data, successCallback) {
            data.action = action;
            $.ajax({ type: "POST", url: "carga_califxalumno_secretaria.php", data: data, dataType: 'json', success: successCallback,
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                    alert("Error de comunicación con el servidor.");
                }
            });
        }
        
        $(document).on('blur', '.calif-cell, .prom-cell', function() {
            const celda = $(this);
            if (celda.closest('tr').is('.fila-abandonada, .fila-aprobada')) return;
            const data = {
                idCalificacion: celda.closest('tr').data('idcalificacion'),
                columna: celda.data('columna'),
                nuevoValor: celda.text().trim()
            };
            enviarAjax('update_calif', data, function(response) {
                if (response && (response.respuesta === 'actualizado' || response.respuesta === 'sin_cambios')) {
                    celda.css('background-color', 'lightgreen');
                } else {
                    alert('Error: ' + (response.mensaje || 'Respuesta desconocida.'));
                    celda.css('background-color', 'lightcoral');
                }
            });
        });
        
        let currentCheckbox;
        const modalConfirmAbandono = new bootstrap.Modal('#modalConfirmAbandono');
        const modalRevertAbandono = new bootstrap.Modal('#modalRevertAbandono');

        function actualizarInfoFila(fila, nuevoEstadoInscripcion, nuevoEstadoCursado) {
            try {
                const info = JSON.parse(fila.attr('data-info'));
                info.estadoInscripcion = nuevoEstadoInscripcion;
                if (nuevoEstadoCursado !== undefined) info.estadoCursado = nuevoEstadoCursado;
                fila.attr('data-info', JSON.stringify(info));
            } catch(e) { console.error("Error al actualizar data-info:", e); }
        }

        $(document).on('change', '.check-abandono', function() {
            currentCheckbox = $(this);
            if (currentCheckbox.is(':checked')) {
                modalConfirmAbandono.show();
            } else {
                enviarAjax('get_condiciones_cursado', {}, function(response) {
                    if (response.success && response.data) {
                        const select = $('#selectNewEstado');
                        select.empty().append('<option value="">Seleccione un estado</option>');
                        response.data.forEach(cond => select.append($('<option>').val(cond.condicion).text(cond.condicion)));
                        modalRevertAbandono.show();
                    } else {
                        alert('Error al cargar estados.');
                        currentCheckbox.prop('checked', true);
                    }
                });
            }
        });

        $('#confirmarAbandonoBtn').on('click', function() {
            const fila = currentCheckbox.closest('tr');
            const data = { idAlumno: fila.data('idalumno'), idMateria: fila.data('idmateria'), estado: 'Abandonó Cursado' };
            enviarAjax('update_abandono', data, function(response) {
                if (response && response.respuesta === 'actualizado') {
                    fila.addClass('fila-abandonada');
                    fila.find('.estado-cursado-display').text('Abandonó Cursado');
                    actualizarInfoFila(fila, 'Abandonó Cursado', 'Abandonó Cursado');
                } else {
                    alert("Error al actualizar.");
                    currentCheckbox.prop('checked', false);
                }
                modalConfirmAbandono.hide();
            });
        });

        $('#cancelarAbandonoBtn, #modalConfirmAbandono .btn-close').on('click', function() {
            if(currentCheckbox) currentCheckbox.prop('checked', false);
            modalConfirmAbandono.hide();
        });

        $('#confirmarRevertBtn').on('click', function() {
            const nuevoEstado = $('#selectNewEstado').val();
            if (!nuevoEstado) { alert('Por favor, seleccione un estado.'); return; }
            const fila = currentCheckbox.closest('tr');
            const data = { idAlumno: fila.data('idalumno'), idMateria: fila.data('idmateria'), estado: nuevoEstado };
            enviarAjax('update_abandono', data, function(response) {
                if (response && response.respuesta === 'actualizado') {
                    fila.removeClass('fila-abandonada');
                    fila.find('.estado-cursado-display').text(nuevoEstado);
                    actualizarInfoFila(fila, nuevoEstado, nuevoEstado);
                } else {
                    alert("Error al actualizar.");
                    currentCheckbox.prop('checked', true);
                }
                modalRevertAbandono.hide();
            });
        });

        $('#cancelarRevertBtn, #modalRevertAbandono .btn-close').on('click', function() {
            if(currentCheckbox) currentCheckbox.prop('checked', true);
            modalRevertAbandono.hide();
        });

        $(document).on('click', '.icono-detalle', function(e) {
            e.preventDefault();
            const fila = $(this).closest('tr');
            const info = fila.data('info');
            const modal = $('#modalDetalleMateria');
            modal.find('#modalMateriaNombre').text(info.nombreMateria || 'N/A');
            modal.find('#modalMateriaCurso').text(info.nombreCurso || 'N/A');
            modal.find('#modalEstadoInscripcion').text(info.estadoInscripcion || 'N/A');
            modal.find('#modalAsistencia').text(info.asistencia || 'N/A');
            let estadoGeneral = `Cursando: ${info.estadoCursado || 'Sin definir'}`;
            if (info.materiaAprobada == 1) estadoGeneral = "Materia Aprobada";
            modal.find('#modalEstadoGeneral').text(estadoGeneral);
            modal.find('#modalN1').text(info.n1 || '-'); modal.find('#modalR1').text(info.r1 || '-');
            modal.find('#modalN2').text(info.n2 || '-'); modal.find('#modalR2').text(info.r2 || '-');
            modal.find('#modalN3').text(info.n3 || '-'); modal.find('#modalR3').text(info.r3 || '-');
            modal.find('#modalN4').text(info.n4 || '-'); modal.find('#modalR4').text(info.r4 || '-');
            modal.find('#modalN5').text(info.n5 || '-'); modal.find('#modalR5').text(info.r5 || '-');
            modal.find('#modalN6').text(info.n6 || '-'); modal.find('#modalR6').text(info.r6 || '-');
            modal.find('#modalN7').text(info.n7 || '-'); modal.find('#modalR7').text(info.r7 || '-');
            modal.find('#modalN8').text(info.n8 || '-'); modal.find('#modalR8').text(info.r8 || '-');
            modal.find('#modalExamenIntegrador').text(info.examenIntegrador || '-');
            const examenesBody = modal.find('#modalExamenesBody');
            examenesBody.html('<tr><td colspan="2">Cargando...</td></tr>');
            enviarAjax('get_examenes', {idAlumno: fila.data('idalumno'), idMateria: fila.data('idmateria')}, function(response) {
                examenesBody.empty();
                if (response && response.examenes && response.examenes.length > 0) {
                    response.examenes.forEach(ex => {
                        examenesBody.append(`<tr><td>${ex.Fecha || 'N/A'}</td><td>${ex.Calificacion || '-'}</td></tr>`);
                    });
                } else {
                    examenesBody.html('<tr><td colspan="2">No se encontraron exámenes rendidos.</td></tr>');
                }
            });
        });

        const topScroll = document.getElementById('scrollTop');
        const bottomScroll = document.getElementById('tabla-calificaciones-container');
        if (bottomScroll) {
            const table = bottomScroll.querySelector('table');
            const inner = topScroll.querySelector('.scroll-top-inner');
            function syncWidth() { if(inner && table) inner.style.width = table.scrollWidth + 'px'; }
            if(topScroll && table && inner) {
                topScroll.addEventListener('scroll', () => { bottomScroll.scrollLeft = topScroll.scrollLeft; });
                bottomScroll.addEventListener('scroll', () => { topScroll.scrollLeft = bottomScroll.scrollLeft; });
                new ResizeObserver(syncWidth).observe(table);
                syncWidth();
            }
        }
    });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>