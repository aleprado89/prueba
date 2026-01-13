<?php
/**
 * solicitudesExamen.php
 * Módulo de Administración para aceptar/rechazar inscripciones a examen.
 * Autor: Programador de SistemasEscolares
 */

// --- LIMPIEZA DE SALIDA ---
// Iniciamos buffer para atrapar cualquier error o eco indeseado antes del JSON
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
    // Limpiamos cualquier salida previa (warnings de includes, espacios en blanco)
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
                // Nuevo parámetro: Ver Histórico
                $verHistorico = filter_input(INPUT_POST, 'verHistorico', FILTER_VALIDATE_INT);

                if (!$idCiclo || !$idTurno || !$idPlan) {
                    echo json_encode(['success'=>false, 'message'=>'Faltan parámetros Ciclo/Turno/Plan.']);
                    exit;
                }

                // 1: Pendiente | 2: Correcta | 3: Rechazada | 4: Cancelada
                $estadosBuscar = ($verHistorico == 1) ? [2, 3, 4] : [1];

                // Verificamos si la función existe antes de llamar (evita Error 500)
                if (!function_exists('buscarSolicitudesExamenWeb')) {
                    throw new Exception("La función buscarSolicitudesExamenWeb no existe en consultas.php");
                }

                $solicitudes = buscarSolicitudesExamenWeb($conn, $idCiclo, $idTurno, $idPlan, $idCurso, $idMateria, $estadosBuscar);
                $dataProcessed = [];

                foreach ($solicitudes as $sol) {
                    $fechasAlt = [];
                    // Optimizamos: Solo buscamos fechas si NO es histórico
                    if ($verHistorico != 1) {
                         if(function_exists('obtenerFechasAlternativas')) {
                            $fechasAlt = obtenerFechasAlternativas($conn, $sol['idMateria'], $idCiclo, $idTurno);
                         }
                    }
                    
                    // Aseguramos ID (Fallback por si cambia el nombre de columna)
                    $idInscripcion = $sol['id_Inscripcion_web'] ?? $sol['idInscripcionWeb'] ?? $sol['id'] ?? 0;

                    // Texto de Estado (Solo histórico)
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
                     $response = ['success' => false, 'message' => 'Datos insuficientes para validar.'];
                } else {
                    $esValida = false;
                    $observacion = "Error verificación";
                    $condicionTexto = "S/D";
                    
                    if (function_exists('controlCorrelatividades')) {
                        $esValida = controlCorrelatividades((int)$idUnicoMateria, (int)$idAlumno, 1);
                    } else {
                        $observacion = "Fn controlCorrelatividades no existe";
                    }

                    if (function_exists('obtenerCondicionCursado')) {
                        $condicionTexto = obtenerCondicionCursado($conn, (int)$idAlumno, (int)$idUnicoMateria);
                    }

                    $accionSugerida = "ACEPTAR";

                    if ($esValida) {
                        $observacion = ""; 
                        $accionSugerida = "ACEPTAR";
                    } else {
                        $observacion = "No cumple correlativas/Requisitos.";
                        $accionSugerida = "RECHAZAR";
                    }

                    $response = [
                        'success' => true, 
                        'esValida' => $esValida, 
                        'observacion' => $observacion,
                        'condicionSugerida' => $condicionTexto,
                        'accionSugerida' => $accionSugerida
                    ];
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
// Vaciamos el buffer y salimos si no es POST para mostrar el HTML
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistemas Escolares - Solicitudes Examen</title>
    
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
        
        .btn-primary i, .btn-primary .fas, .btn-primary .bi, .btn-primary .fa {
            color: #ffffff !important;
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
                <li class="breadcrumb-item active">Solicitudes de Examen Web</li>
            </ol>
        </nav>

        <div class="card custom-card">
            <div class="card-header card-header-custom">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>Administrar Solicitudes de Examen
                </h5>
            </div>
            
            <div class="card-body">
                <form id="formFiltros" class="row g-3 align-items-end">
                    
                    <div class="col-md-2">
                        <label class="form-label">Ciclo Lectivo</label>
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
                        <label class="form-label">Turno Examen</label>
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
                    
                    <div class="col-md-11 text-end">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="checkHistorico" value="1">
                            <label class="form-check-label text-muted small" for="checkHistorico">
                                Buscar aceptadas, rechazadas o canceladas
                            </label>
                        </div>
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
                            <input type="text" class="form-control filtro-rapido-input" id="inputBusquedaRapida" placeholder="Filtrar por nombre...">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" id="tablaSolicitudes">
                        <thead class="table-light"></thead>
                        <tbody>
                            <tr><td colspan="100%" class="text-center text-muted">Use los filtros para buscar.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-primary" id="btnProcesar" style="display:none;" disabled>
                        <i class="bi bi-check-double me-2"></i>Procesar Seleccionados
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalProgreso" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Procesando Solicitudes</h5>
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

    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            const $selCiclo = $('#selectCiclo');
            const $selTurno = $('#selectTurno');
            const $selPlan = $('#selectPlan');
            const $selCurso = $('#selectCurso');
            const $selMateria = $('#selectMateria');
            
            const modalProgreso = new bootstrap.Modal(document.getElementById('modalProgreso'));
            const $barra = $('#barraProgreso');
            const $textoProgreso = $('#progresoTexto');
            const $detalleProgreso = $('#progresoDetalle');
            
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

            $('#inputBusquedaRapida').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $("#tablaSolicitudes tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            $('#formFiltros').on('submit', function(e) {
                e.preventDefault();
                if(!$selCiclo.val() || !$selTurno.val() || !$selPlan.val()){
                    alert("Complete los filtros requeridos.");
                    return;
                }

                const esHistorico = $('#checkHistorico').is(':checked');

                $barra.css('width', '0%').text('0%');
                $textoProgreso.text('Buscando solicitudes...');
                $detalleProgreso.text('Espere un momento...');
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
                    const $thead = $('#tablaSolicitudes thead');
                    const $tbody = $('#tablaSolicitudes tbody');
                    $thead.empty();
                    $tbody.empty();

                    let headerHtml = '';
                    if (esHistorico) {
                        headerHtml = `
                            <tr>
                                <th>Alumno</th>
                                <th>Materia</th>
                                <th>Fecha Mesa</th>
                                <th>Hora</th>
                                <th>Fecha Solicitud</th>
                                <th>Condición</th>
                                <th>Observación (Estado)</th>
                            </tr>`;
                        $('#btnProcesar').hide();
                    } else {
                        headerHtml = `
                            <tr>
                                <th class="text-center"><input type="checkbox" id="checkAll"></th>
                                <th>Alumno</th>
                                <th>Materia</th>
                                <th>Fecha Mesa</th>
                                <th>Hora</th>
                                <th>Fecha Solicitud</th>
                                <th>Condición</th>
                                <th>Observación</th>
                                <th>Acción</th>
                            </tr>`;
                        $('#btnProcesar').show();
                    }
                    $thead.html(headerHtml);

                    if (!res.success || !res.data || res.data.length === 0) {
                        $barra.css('width', '100%');
                        setTimeout(() => { modalProgreso.hide(); }, 500); 
                        $tbody.html('<tr><td colspan="100%" class="text-center text-muted fw-bold py-3">No hay solicitudes con los filtros seleccionados.</td></tr>');
                        if(!res.success && res.message) {
                             alert("Error servidor: " + res.message); // Mostrar el error real capturado
                        }
                        return;
                    }

                    const solicitudes = res.data;
                    const total = solicitudes.length;
                    $textoProgreso.text(`Se encontraron ${total} solicitudes.`);
                    
                    solicitudes.forEach((row, index) => {
                        let tr = '';
                        const trId = `fila-${row.idInscripcionWeb}`;

                        if (esHistorico) {
                            tr = `
                                <tr class="bg-white">
                                    <td>${row.alumno}</td>
                                    <td>${row.materia}<br><small class="text-muted">${row.curso}</small></td>
                                    <td>${row.fechaActualTexto}</td>
                                    <td>${row.horaExamen}</td>
                                    <td>${row.fechaSolicitud}</td>
                                    <td>N/A</td>
                                    <td class="fw-bold text-secondary">${row.estadoTexto}</td>
                                </tr>
                            `;
                            $tbody.append(tr);
                        } 
                        else {
                            let fechaDisplay = '';
                            if (row.fechasAlternativas && row.fechasAlternativas.length > 0) {
                                fechaDisplay = `<select class="form-select select-fecha-tabla" name="fecha_${row.idInscripcionWeb}">`;
                                row.fechasAlternativas.forEach(f => {
                                    let horaCorta = f.hora.substring(0,5);
                                    let selected = (f.idFechaExamen == row.fechaActualId) ? 'selected' : '';
                                    fechaDisplay += `<option value="${f.idFechaExamen}" ${selected}>${f.fecha} (${horaCorta})</option>`;
                                });
                                fechaDisplay += `</select>`;
                            } else {
                                fechaDisplay = row.fechaActualTexto;
                                fechaDisplay += `<input type="hidden" name="fecha_${row.idInscripcionWeb}" value="${row.fechaActualId}">`;
                            }

                            tr = `
                                <tr id="${trId}" class="table-loading" data-id="${row.idInscripcionWeb}">
                                    <td class="text-center"><input type="checkbox" class="solicitud-check" value="${row.idInscripcionWeb}"></td>
                                    <td>${row.alumno}</td>
                                    <td>${row.materia}<br><small class="text-muted">${row.curso}</small></td>
                                    <td>${fechaDisplay}</td>
                                    <td>${row.horaExamen}</td>
                                    <td>${row.fechaSolicitud}</td>
                                    <td class="celda-condicion"><i class="fas fa-spinner fa-spin"></i></td>
                                    <td class="celda-observacion">Verificando...</td>
                                    <td>
                                        <select class="form-select form-select-sm" name="accion_${row.idInscripcionWeb}">
                                            <option value="ACEPTAR">Aceptar</option>
                                            <option value="RECHAZAR">Rechazar</option>
                                        </select>
                                    </td>
                                </tr>
                            `;
                            $tbody.append(tr);
                        }
                    });

                    if (esHistorico) {
                        $barra.css('width', '100%');
                        setTimeout(() => { modalProgreso.hide(); }, 500);
                    } else {
                        procesarLote(solicitudes, 0);
                    }

                }, 'json').fail((jqXHR, textStatus, errorThrown) => {
                    setTimeout(() => { modalProgreso.hide(); }, 500);
                    console.error("AJAX Fail:", jqXHR.responseText);
                    alert("Error de conexión al buscar. Verifica la consola para más detalles.");
                });
            });

            function procesarLote(lista, index) {
                const total = lista.length;
                const porcentaje = Math.round((index / total) * 100);
                $barra.css('width', porcentaje + '%').text(porcentaje + '%');
                $textoProgreso.text(`Procesando solicitud ${index + 1} de ${total}`);

                if (index >= total) {
                    setTimeout(() => {
                        modalProgreso.hide();
                        $('#btnProcesar').prop('disabled', false);
                    }, 500);
                    return;
                }

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
                    } else {
                        $fila.addClass('table-danger-custom').css('background-color', '#f8d7da');
                    }
                    
                    $fila.find('.celda-condicion').html(res.condicionSugerida || 'S/D');
                    $fila.find('.celda-observacion').html(`<small>${res.observacion}</small>`);
                    
                    if (res.accionSugerida) {
                        $fila.find(`select[name="accion_${item.idInscripcionWeb}"]`).val(res.accionSugerida);
                    }

                    procesarLote(lista, index + 1);

                }, 'json').fail(function() {
                    const $fila = $(`#fila-${item.idInscripcionWeb}`);
                    $fila.addClass('table-warning').css('background-color', '#fff3cd');
                    $fila.find('.celda-observacion').text('Error de conexión.');
                    procesarLote(lista, index + 1);
                });
            }

            $(document).on('change', '#checkAll', function() {
                $('.solicitud-check').prop('checked', $(this).is(':checked'));
            });
        });
    </script>
</body>
</html>