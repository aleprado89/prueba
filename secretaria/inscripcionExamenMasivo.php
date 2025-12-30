<?php
// ==============================================================================
// NOMBRE DEL ARCHIVO: inscripcionExamenMasivo.php
// OBJETIVO: Inscribir masivamente alumnos a una mesa de examen.
// ESTILO: Bootstrap + Bootswatch Materia (SistemasEscolares)
// ==============================================================================

include_once '../funciones/verificarSesion.php';

// Configuración de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

include_once '../inicio/conexion.php';
include_once '../funciones/consultas.php';

// Inicializar variable para control de correlatividades
$materiasAdeuda = ''; 
include_once '../funciones/controlCorrelatividad.php';

// ==============================================================================
// BLOQUE LÓGICO: AJAX (Backend)
// ==============================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json'); 
    $response = ['success' => false, 'message' => 'Acción no válida.'];

    try {
        switch ($_POST['action']) {
            
            // 1. Cargar Cursos
            case 'load_cursos':
                $idPlan = filter_input(INPUT_POST, 'idPlan', FILTER_VALIDATE_INT);
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                if ($idPlan && $idCiclo) {
                    $response = ['success' => true, 'data' => buscarCursosPlanCiclo($conn, $idPlan, $idCiclo)];
                }
                break;

            // 2. Cargar Materias
            case 'load_materias':
                $idPlan = filter_input(INPUT_POST, 'idPlan', FILTER_VALIDATE_INT);
                $idCurso = filter_input(INPUT_POST, 'idCurso', FILTER_VALIDATE_INT);
                if ($idPlan && $idCurso) {
                    $response = ['success' => true, 'data' => materiasPlanCurso($conn, $idPlan, $idCurso)];
                }
                break;

            // 3. Cargar Mesas Disponibles
            case 'load_mesas':
                $idCiclo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
                $idTurno = filter_input(INPUT_POST, 'idTurno', FILTER_VALIDATE_INT);
                $idMateria = filter_input(INPUT_POST, 'idMateria', FILTER_VALIDATE_INT);
                if ($idCiclo && $idTurno && $idMateria) {
                    $response = ['success' => true, 'data' => filtrarMesasExamen($conn, $idCiclo, $idTurno, null, null, $idMateria)];
                }
                break;

            // 4. Buscar Alumnos Candidatos
            case 'buscar_candidatos':
                $idUnicoMateria = $_POST['idUnicoMateria'] ?? ''; 
                $idCurso = filter_input(INPUT_POST, 'idCurso', FILTER_VALIDATE_INT);
                $condicionTexto = $_POST['condicionTexto'] ?? ''; 

                if (!$idUnicoMateria || !$idCurso || !$condicionTexto) {
                    $response['message'] = 'Faltan datos para buscar candidatos.';
                    break;
                }

                $alumnosEncontrados = buscarAlumnosAptosPorCondicion($conn, $idUnicoMateria, $idCurso, $condicionTexto);
                
                $candidatos = [];
                foreach ($alumnosEncontrados as $alu) {
                    $candidatos[] = [
                        'idAlumno' => $alu['idAlumno'],
                        'dni' => $alu['dni'],
                        'nombreCompleto' => $alu['apellido'] . ', ' . $alu['nombre'],
                        'estado' =>  ($alu['estadoCursado'] ?? '-') 
                    ];
                }
                $response = ['success' => true, 'data' => $candidatos];
                break;

            // 5. EJECUCIÓN DE INSCRIPCIÓN MASIVA
            case 'inscribir_masivo':
                $listaAlumnos = $_POST['listaAlumnos'] ?? [];
                $idFechaExamen = filter_input(INPUT_POST, 'idFechaExamen', FILTER_VALIDATE_INT);
                $condicionTexto = $_POST['condicionTexto'] ?? '';

                if (!is_array($listaAlumnos) && !empty($listaAlumnos)) {
                    $decoded = json_decode($listaAlumnos, true);
                    if (json_last_error() === JSON_ERROR_NONE) $listaAlumnos = $decoded;
                }

                if (empty($listaAlumnos) || !$idFechaExamen) {
                    $response = ['success' => false, 'message' => 'Datos incompletos.'];
                } else {
                    // Llama a la función en consultas.php que devuelve el array 'detalles'
                    $resultado = inscribirAlumnosMasivo($conn, $listaAlumnos, $idFechaExamen, $condicionTexto);
                    $response = $resultado;
                }
                break;
        }
    } catch (Throwable $e) {
        $response = ['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()];
    }

    echo json_encode($response);
    exit;
}

// ==============================================================================
// BLOQUE VISTA HTML
// ==============================================================================
$ciclos = []; $turnos = []; $planes = []; $condicionesExamen = [];
try {
    $ciclos = levantarCiclosLectivos($conn);
    $turnos = obtenerTodosTurnos($conn);
    $planes = buscarTodosPlanes($conn); 
    $condicionesExamen = obtenerCondicionesExamen($conn); 
} catch (Exception $e) {
    die("Error al cargar combos iniciales.");
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
        /* Estilos específicos para esta página manteniendo la línea gráfica */
        #loader { 
            display: none; 
            position: fixed; 
            top: 0; left: 0; 
            width: 100%; height: 100%; 
            background: rgba(255,255,255,0.85); 
            z-index: 9999; 
            justify-content: center; 
            align-items: center; 
            flex-direction: column;
        }
        
        .step-section { 
            background-color: #f8f9fa; 
            border-left: 5px solid #2fa4e7; /* Color primario Materia */
            padding: 20px; 
            margin-bottom: 25px; 
            border-radius: 4px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .mesa-card { 
            transition: all 0.2s ease-in-out; 
            border: 1px solid #dee2e6; 
            cursor: pointer; 
            background-color: #fff;
        }
        .mesa-card:hover { 
            background-color: #e9ecef; 
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .mesa-card.active { 
            background-color: #e7f1ff; 
            border-color: #2fa4e7; 
            box-shadow: 0 0 0 2px rgba(47, 164, 231, 0.5); 
        }

        .table-container-custom {
            max-height: 450px; 
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        /* Ajuste para modal de resultados */
        .resultado-icon { font-size: 1.2rem; margin-right: 8px; vertical-align: middle; }
    </style>
</head>
<body>

    <div id="loader">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
        <h5 class="mt-3 text-primary fw-bold">Procesando solicitud...</h5>
    </div>

    <?php include '../funciones/menu_secretaria.php'; ?>

    <div class="container-fluid fondo">
        <br>
        <div class="container">
            
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
                    <li class="breadcrumb-item active">Inscripción Masiva</li>
                </ol>
            </nav>

            <div id="alertContainer"></div>

            <div class="card shadow-sm mb-5">
                <div class="card-header bg-white border-bottom">
                    <h3 class="text-primary mb-0">
                        <i class="bi bi-people-fill"></i> Inscripción Masiva a Exámenes
                    </h3>
                </div>
                
                <div class="card-body">
                    
                    <div class="step-section">
                        <h5 class="text-primary mb-3"><i class="bi bi-1-circle"></i> Seleccionar Filtros</h5>
                        <form id="formFiltros">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Ciclo Lectivo</label>
                                    <select class="form-select" id="idCiclo">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($ciclos as $c): echo "<option value='{$c['idCicloLectivo']}'>{$c['anio']}</option>"; endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Turno Examen</label>
                                    <select class="form-select" id="idTurno">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($turnos as $t): echo "<option value='{$t['idTurno']}'>{$t['nombre']}</option>"; endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Plan de Estudio</label>
                                    <select class="form-select" id="idPlan">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($planes as $p): echo "<option value='{$p['idPlan']}'>{$p['nombre']}</option>"; endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Condición Inscripción</label>
                                    <select class="form-select" id="idCondicion">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($condicionesExamen as $ce): echo "<option value='{$ce['idCondicion']}'>{$ce['condicion']}</option>"; endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">Curso</label>
                                    <select class="form-select" id="idCurso" disabled><option value="">(Seleccione Plan y Ciclo)</option></select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">Materia</label>
                                    <select class="form-select" id="idMateria" disabled><option value="">(Seleccione Curso)</option></select>
                                    <input type="hidden" id="idUnicoMateria"> 
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-primary w-100 shadow-sm" id="btnBuscar" disabled>
                                        <i class="bi bi-search"></i> BUSCAR
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div id="seccionMesas" style="display:none;" class="step-section">
                        <h5 class="text-primary mb-3"><i class="bi bi-2-circle"></i> Seleccionar Mesa de Destino</h5>
                        <p class="text-muted small">Haga clic sobre la mesa correspondiente para seleccionarla.</p>
                        
                        <div id="contenedorMesas" class="row g-3"></div>
                        
                        <div id="msgNoMesas" class="alert alert-warning mt-2 shadow-sm" style="display:none;">
                            <i class="bi bi-exclamation-triangle-fill"></i> No existen mesas configuradas para esta materia en el turno y ciclo seleccionados.
                        </div>
                    </div>

                    <div id="seccionAlumnos" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-primary mb-0"><i class="bi bi-3-circle"></i> Alumnos Aptos para Inscribir</h5>
                            <span class="badge bg-secondary fs-6">Total: <span id="badgeCount">0</span></span>
                        </div>
                        
                        <div class="table-container-custom">
                            <table class="table table-striped table-hover mb-0" id="tablaAlumnos">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>DNI</th>
                                        <th>Apellido y Nombre</th>
                                        <th>Estado Cursado</th>
                                        <th class="text-center" style="width: 100px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    </tbody>
                            </table>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button class="btn btn-success btn-lg shadow" id="btnProcesar" disabled>
                                <i class="bi bi-check-circle-fill"></i> CONFIRMAR INSCRIPCIÓN MASIVA
                            </button>
                        </div>
                    </div>

                </div> </div> </div>
    </div>

    <div class="modal fade" id="modalResultados" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="bi bi-card-checklist"></i> Resultado del Proceso</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3 text-muted">A continuación se detalla el resultado de la inscripción por cada alumno:</p>
                    <div id="contenedorResultados"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../funciones/footer.html'; ?>

    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../funciones/sessionControl.js"></script>

    <script>
    $(document).ready(function() {
        
        const loader = $('#loader');

        // --- FUNCIONES UI ---
        function showAlert(msg, type='danger') {
            $('#alertContainer').html(`
                <div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                    ${msg}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `);
            // Auto-cerrar alertas de éxito
            if(type === 'success') {
                setTimeout(() => { $('.alert').alert('close'); }, 5000);
            }
        }

        // --- COMBOS ENCADENADOS ---
        $('#idPlan, #idCiclo').change(function() {
            const plan = $('#idPlan').val();
            const ciclo = $('#idCiclo').val();
            $('#idCurso').html('<option value="">Cargando...</option>').prop('disabled', true);
            
            if(plan && ciclo) {
                $.post('inscripcionExamenMasivo.php', { action: 'load_cursos', idPlan: plan, idCiclo: ciclo }, function(res){
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
            $('#idUnicoMateria').val('');

            if(plan && curso) {
                $.post('inscripcionExamenMasivo.php', { action: 'load_materias', idPlan: plan, idCurso: curso }, function(res){
                    if(res.success) {
                        let opts = '<option value="">Seleccione Materia...</option>';
                        res.data.forEach(m => {
                            opts += `<option value="${m.idMateria}" data-unico="${m.idUnicoMateria}">${m.nombreMateria}</option>`;
                        });
                        $('#idMateria').html(opts).prop('disabled', false);
                    }
                }, 'json');
            }
        });

        $('#idMateria').change(function() {
            const unico = $(this).find(':selected').data('unico');
            $('#idUnicoMateria').val(unico);
        });

        // Activar botón Buscar
        $('#idMateria, #idCondicion, #idTurno').change(function() {
            const ok = $('#idMateria').val() && $('#idCondicion').val() && $('#idTurno').val();
            $('#btnBuscar').prop('disabled', !ok);
            // Resetear vistas si cambian filtros
            $('#seccionMesas, #seccionAlumnos').slideUp();
            $('#btnProcesar').prop('disabled', true);
        });

        // --- ACCIÓN: BUSCAR ---
        $('#btnBuscar').click(function() {
            const idMateria = $('#idMateria').val();
            const idUnico = $('#idUnicoMateria').val();
            const idCurso = $('#idCurso').val();
            const idCiclo = $('#idCiclo').val();
            const idTurno = $('#idTurno').val();
            const condicionTexto = $('#idCondicion option:selected').text(); 

            if(!idUnico) { showAlert("Error: Identificador de materia no válido."); return; }

            loader.css("display", "flex");
            $('#seccionMesas, #seccionAlumnos').hide();
            $('#contenedorMesas').empty();
            $('#tablaAlumnos tbody').empty();

            // 1. Cargar Mesas
            $.post('inscripcionExamenMasivo.php', {
                action: 'load_mesas', idCiclo: idCiclo, idTurno: idTurno, idMateria: idMateria
            }, function(resMesas) {
                
                if(resMesas.success && resMesas.data.length > 0) {
                    let htmlMesas = '';
                    resMesas.data.forEach(m => {
                        htmlMesas += `
                        <div class="col-md-6 col-lg-4">
                            <div class="card p-3 mesa-card h-100" data-id="${m.idFechaExamen}">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-calendar-check fs-3 text-primary me-3"></i>
                                    <div>
                                        <h6 class="mb-0 fw-bold text-primary">Fecha: ${m.fecha}</h6>
                                        <span class="text-muted small">Hora: ${m.hora}</span>
                                    </div>
                                </div>
                                <div class="small text-secondary mt-2 border-top pt-2">
                                    Tribunal (P1): ${m.p1 || 'Sin asignar'}
                                </div>
                            </div>
                        </div>`;
                    });
                    $('#contenedorMesas').html(htmlMesas);
                    $('#msgNoMesas').hide();
                    $('#seccionMesas').fadeIn();

                    // 2. Cargar Alumnos Candidatos
                    $.post('inscripcionExamenMasivo.php', {
                        action: 'buscar_candidatos',
                        idUnicoMateria: idUnico,
                        idCurso: idCurso,
                        condicionTexto: condicionTexto
                    }, function(resAlu) {
                        loader.hide();
                        if(resAlu.success) {
                            const lista = resAlu.data;
                            $('#badgeCount').text(lista.length);
                            
                            if(lista.length === 0) {
                                showAlert("No se encontraron alumnos aptos con la condición seleccionada.", "warning");
                            } else {
                                let html = '';
                                lista.forEach(a => {
                                    html += `
                                    <tr data-id="${a.idAlumno}" data-nombre="${a.nombreCompleto}">
                                        <td class="align-middle">${a.dni}</td>
                                        <td class="align-middle fw-bold">${a.nombreCompleto}</td>
                                        <td class="align-middle"><span class="badge bg-info text-dark">${a.estado}</span></td>
                                        <td class="text-center align-middle">
                                            <button class="btn btn-outline-danger btn-sm btn-borrar" title="Quitar de la lista">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>`;
                                });
                                $('#tablaAlumnos tbody').html(html);
                                $('#seccionAlumnos').fadeIn();
                            }
                        } else {
                            showAlert(resAlu.message);
                        }
                    }, 'json');

                } else {
                    loader.hide();
                    $('#msgNoMesas').show();
                    $('#seccionMesas').fadeIn();
                }
            }, 'json').fail(function(){ loader.hide(); showAlert("Error de conexión."); });
        });

        // Selección de Mesa
        $(document).on('click', '.mesa-card', function() {
            $('.mesa-card').removeClass('active');
            $(this).addClass('active');
            $('#btnProcesar').prop('disabled', false);
        });

        // Borrar fila de alumno
        $(document).on('click', '.btn-borrar', function() {
            $(this).closest('tr').fadeOut(300, function() { 
                $(this).remove(); 
                $('#badgeCount').text($('#tablaAlumnos tbody tr').length);
            });
        });

        // --- CONFIRMAR INSCRIPCIÓN ---
        $('#btnProcesar').click(function() {
            let listaIds = [];
            $('#tablaAlumnos tbody tr').each(function() {
                listaIds.push($(this).data('id'));
            });

            const idMesa = $('.mesa-card.active').data('id');
            const condTexto = $('#idCondicion option:selected').text();
            const btn = $(this);

            if(listaIds.length === 0) { alert('No hay alumnos en la lista.'); return; }
            if(!idMesa) { alert('Debe seleccionar una mesa (haga clic en una tarjeta).'); return; }

            // UI Cargando
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Procesando...');
            loader.css("display", "flex");

            // Envío AJAX
            $.post('inscripcionExamenMasivo.php', {
                action: 'inscribir_masivo',
                listaAlumnos: listaIds,
                idFechaExamen: idMesa,
                condicionTexto: condTexto
            }, function(response) {
                
                loader.hide();
                btn.prop('disabled', false).html('<i class="bi bi-check-circle-fill"></i> CONFIRMAR INSCRIPCIÓN MASIVA');

                if (response.success && response.detalles) {
                    
                    let htmlReporte = '<div class="table-responsive"><table class="table table-bordered table-striped align-middle">';
                    htmlReporte += '<thead class="table-light"><tr><th>Alumno</th><th>Resultado</th></tr></thead><tbody>';

                    response.detalles.forEach(function(item) {
                        let claseColor = 'text-dark';
                        let icono = '';
                        let bg = '';
                        
                        if (item.estado === 'success') {
                            claseColor = 'text-success'; 
                            icono = '<i class="bi bi-check-circle-fill resultado-icon"></i>';
                        } else if (item.estado === 'warning') {
                            claseColor = 'text-warning'; 
                            icono = '<i class="bi bi-exclamation-triangle-fill resultado-icon"></i>';
                        } else {
                            claseColor = 'text-danger'; 
                            icono = '<i class="bi bi-x-circle-fill resultado-icon"></i>';
                            bg = 'style="background-color: #fff5f5"';
                        }

                        htmlReporte += `<tr ${bg}>
                            <td class="fw-bold">${item.nombre}</td>
                            <td class="${claseColor}">${icono} ${item.mensaje}</td>
                        </tr>`;
                    });

                    htmlReporte += '</tbody></table></div>';
                    
                    $('#contenedorResultados').html(htmlReporte);
                    $('#modalResultados').modal('show');

                    // Limpiar tabla tras éxito
                    $('#tablaAlumnos tbody').empty();
                    $('#badgeCount').text('0');
                    $('#btnProcesar').prop('disabled', true);

                } else {
                    alert(response.message || "Error desconocido.");
                }

            }, 'json').fail(function() {
                loader.hide();
                btn.prop('disabled', false).html('<i class="bi bi-check-circle-fill"></i> CONFIRMAR INSCRIPCIÓN MASIVA');
                alert("Error de conexión con el servidor.");
            });
        });

    });
    </script>
</body>
</html>