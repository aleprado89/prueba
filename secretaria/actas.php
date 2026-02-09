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

// --- Lógica para manejar peticiones AJAX ---
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];

    try {
        switch ($_POST['ajax_action']) {
            case 'get_cursos':
                $idPlan = $_POST['idPlan'] ?? null;
                $idCiclo = $_POST['idCiclo'] ?? null;
                $cursos = [];
                if ($idPlan && $idCiclo) {
                    $cursos = buscarCursosPlanCiclo($conn, $idPlan, $idCiclo);
                }
                echo json_encode(['data' => $cursos, 'success' => true]);
                exit;

            case 'get_materias':
                $idCurso = $_POST['idCurso'] ?? null;
                $idPlan = $_POST['idPlan'] ?? null;
                $materias = [];
                if ($idCurso && $idPlan) {
                    $materias = materiasPlanCurso($conn, $idPlan, $idCurso);
                }
                echo json_encode(['data' => $materias, 'success' => true]);
                exit;

            case 'get_mesas':
                $idCiclo = $_POST['idCiclo'] ?? null;
                $idTurno = $_POST['idTurno'] ?? null;
                $idMateria = $_POST['idMateria'] ?? null;
                $mesas = [];
                if ($idCiclo && $idTurno && $idMateria) {
                    $mesas = buscarMesasExamen($conn, $idCiclo, $idTurno, $idMateria);
                }
                echo json_encode(['data' => $mesas, 'success' => true]);
                exit;

            case 'get_acta':
                $idFechaExamen = $_POST['idFechaExamen'] ?? null;
                
                // Usamos la nueva función que devuelve ['cabecera'] y ['alumnos']
                if ($idFechaExamen) {
                    $resultado = obtenerDetalleActaCompleto($conn, $idFechaExamen);
                    echo json_encode(['data' => $resultado, 'success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'ID faltante']);
                }
                exit;

            case 'update_nota':
                // Validación básica de entrada
                $idInscripcion = isset($_POST['idInscripcion']) ? intval($_POST['idInscripcion']) : 0;
                $campo = $_POST['campo'] ?? '';
                $valor = isset($_POST['valor']) ? trim($_POST['valor']) : null;
                
                if ($idInscripcion > 0 && $campo !== '') {
                    // Llamada a la función en consultas.php
                    $resultado = actualizarNotaInscripcion($conn, $idInscripcion, $campo, $valor);
                    echo json_encode($resultado);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
                }
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Carga inicial de datos para los selectores
$ciclos = levantarCiclosLectivos($conn);
$planes = buscarTodosPlanes($conn);
$turnos = obtenerTodosTurnos($conn);

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Actas de examen - Secretaría</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <style>
    /* Estilos adicionales para la tabla editable */
    .editable-input {
        border: 1px solid transparent;
        background: transparent;
        width: 100%;
        text-align: center;
        padding: 5px;
    }
    .editable-input:focus {
        border: 1px solid #ced4da;
        background: #fff;
        outline: none;
        border-radius: 4px;
    }
    .editable-input:hover {
        background: #f8f9fa;
    }
    .status-icon {
        font-size: 0.8rem;
    }
    /* Spinner pequeño para carga */
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
        border-width: 0.15em;
    }
  </style>
</head>
<body>

<script>
    window.usuarioActual = "<?php echo htmlspecialchars($_SESSION['active_user_identifier'] ?? 'null'); ?>";
</script>

<?php include '../funciones/menu_secretaria.php'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
      <li class="breadcrumb-item active">Actas de examen</li>
    </ol>

    <div class="card padding col-12">
      <h5>Gestión de actas volantes</h5>
      <br>

      <fieldset class="mb-4 p-3 border rounded">
        <legend class="float-none w-auto px-2">Selección de mesa</legend>
        
        <form id="formFiltrosActas">
            <div class="row mb-3">
                <div class="col-md-2">
                  <label for="idCiclo" class="form-label">Ciclo lectivo <span class="text-danger">*</span></label>
                  <select class="form-select" id="idCiclo" name="idCiclo">
                    <option value="">Seleccione...</option>
                    <?php foreach ($ciclos as $c): ?>
                        <option value="<?= $c['idCicloLectivo'] ?>"><?= $c['anio'] ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-2">
                  <label for="idTurno" class="form-label">Turno <span class="text-danger">*</span></label>
                  <select class="form-select" id="idTurno" name="idTurno" disabled>
                    <option value="">Seleccione...</option>
                    <?php foreach ($turnos as $t): ?>
                        <option value="<?= $t['idTurno'] ?>"><?= $t['nombre'] ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-4">
                  <label for="idPlan" class="form-label">Plan de estudio <span class="text-danger">*</span></label>
                  <select class="form-select" id="idPlan" name="idPlan" disabled>
                    <option value="">Seleccione...</option>
                    <?php foreach ($planes as $p): ?>
                        <option value="<?= $p['idPlan'] ?>"><?= $p['nombre'] ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-4">
                  <label for="idCurso" class="form-label">Curso <span class="text-danger">*</span></label>
                  <select class="form-select" id="idCurso" name="idCurso" disabled>
                    <option value="">Seleccione...</option>
                  </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                  <label for="idMateria" class="form-label">Materia <span class="text-danger">*</span></label>
                  <select class="form-select" id="idMateria" name="idMateria" disabled>
                    <option value="">Seleccione...</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label for="idMesa" class="form-label">Mesa de examen <span class="text-danger">*</span></label>
                  <select class="form-select" id="idMesa" name="idMesa" disabled>
                    <option value="">Seleccione...</option>
                  </select>
                </div>
            </div>
        </form>
      </fieldset>

      <div id="contenedorActa" style="display: none;">
          <div class="row mb-3 mt-3 align-items-center">
             <div class="col-md-5">
                 <h6 class="mb-0">
                     Docente Titular: <span id="txtDocenteTitular" class=" fw-bold">---</span>
                 </h6>
             </div>
             <div class="col-md-7 text-end">
                 <button type="button" class="btn btn-primary btn-sm me-3" id="btnImprimirActa">
                     <i class="bi bi-printer-fill"></i> Imprimir Acta Volante
                 </button>

                 <span class="border-end mx-2"></span> 
                 <label for="filtroCondicion" class="me-2">Filtrar:</label>
                 <select id="filtroCondicion" class="form-select d-inline-block w-auto input-sm">
                     <option value="todos">Todos</option>
                 </select>
             </div>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-hover mt-3 align-middle" id="tablaActa">
              <thead>
                <tr>
                  <th style="width: 30%;">Alumno</th>
                  <th>Condición</th>
                  <th class="text-center">Escrito</th>
                  <th class="text-center">Oral</th>
                  <th class="text-center">Calif.</th>
                  <th class="text-center">Libro</th>
                  <th class="text-center">Folio</th>
                  <th style="width: 50px;"></th>
                </tr>
              </thead>
              <tbody id="bodyTablaActa">
                </tbody>
            </table>
          </div>
          <div class=" mt-3 py-2">
              <i class="bi bi-info-circle me-2"></i> Los cambios en las notas se guardan automáticamente al salir de la casilla (click fuera o TAB).
          </div>
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

    // --- FUNCIONES AUXILIARES ---
    function resetSelect(selector, text = "Seleccione...") {
        $(selector).html('<option value="">' + text + '</option>').prop('disabled', true);
    }

    // --- CASCADA DE SELECTORES ---

    // 1. Cambio en Ciclo -> Habilita Turno y Plan
    $('#idCiclo').change(function() {
        resetSelect('#idCurso, #idMateria, #idMesa');
        $('#contenedorActa').hide();
        
        if($(this).val()) {
            $('#idTurno').prop('disabled', false);
            $('#idPlan').prop('disabled', false);
        } else {
            $('#idTurno, #idPlan').prop('disabled', true);
        }
    });

    // 2. Cambio en Plan (requiere Ciclo) -> Carga Cursos
    $('#idPlan').change(function() {
        resetSelect('#idCurso, #idMateria, #idMesa');
        let idPlan = $(this).val();
        let idCiclo = $('#idCiclo').val();

        if(idPlan && idCiclo) {
            let select = $('#idCurso');
            select.html('<option value="">Cargando...</option>');
            
            $.post('actas.php', { ajax_action: 'get_cursos', idPlan: idPlan, idCiclo: idCiclo }, function(res) {
                select.html('<option value="">Seleccione un curso</option>');
                if(res.success && res.data.length > 0) {
                    res.data.forEach(c => {
                        select.append(`<option value="${c.idCurso}">${c.nombre}</option>`);
                    });
                    select.prop('disabled', false);
                } else {
                    select.html('<option value="">No hay cursos disponibles</option>');
                }
            }, 'json');
        }
    });

    // 3. Cambio en Curso -> Carga Materias
    $('#idCurso').change(function() {
        resetSelect('#idMateria, #idMesa');
        let idCurso = $(this).val();
        let idPlan = $('#idPlan').val();

        if(idCurso) {
            let select = $('#idMateria');
            select.html('<option value="">Cargando...</option>');

            $.post('actas.php', { ajax_action: 'get_materias', idCurso: idCurso, idPlan: idPlan }, function(res) {
                select.html('<option value="">Seleccione una materia</option>');
                if(res.success && res.data.length > 0) {
                    res.data.forEach(m => {
                        select.append(`<option value="${m.idMateria}">${m.nombreMateria}</option>`);
                    });
                    select.prop('disabled', false);
                } else {
                    select.html('<option value="">No hay materias disponibles</option>');
                }
            }, 'json');
        }
    });

    // 4. Cambio en Materia/Turno -> Carga Mesas (Necesita Ciclo, Turno, Materia)
    $('#idMateria, #idTurno').change(function() {
        resetSelect('#idMesa');
        $('#contenedorActa').hide();
        
        let idMateria = $('#idMateria').val();
        let idTurno = $('#idTurno').val();
        let idCiclo = $('#idCiclo').val();

        if(idMateria && idTurno && idCiclo) {
            let select = $('#idMesa');
            select.html('<option value="">Cargando...</option>');

            $.post('actas.php', { ajax_action: 'get_mesas', idCiclo: idCiclo, idTurno: idTurno, idMateria: idMateria }, function(res) {
                select.html('<option value="">Seleccione una mesa</option>');
                if(res.success && res.data.length > 0) {
                    res.data.forEach(m => {
                        select.append(`<option value="${m.idFechaExamen}">${m.fecha} - ${m.hora}hs</option>`);
                    });
                    select.prop('disabled', false);
                } else if (res.success && res.data.length === 0) {
                    select.html('<option value="">No hay mesas definidas</option>');
                }
            }, 'json');
        }
    });

    // 5. SELECCIÓN DE MESA -> CARGAR ACTA
    $('#idMesa').change(function() {
        let idFechaExamen = $(this).val();
        if(!idFechaExamen) {
            $('#contenedorActa').hide();
            return;
        }

        let tbody = $('#bodyTablaActa');
        let txtDocente = $('#txtDocenteTitular');
        
        tbody.html('<tr><td colspan="8" class="text-center">Cargando datos...</td></tr>');
        txtDocente.text('Cargando...');
        $('#contenedorActa').show();

        $.post('actas.php', { ajax_action: 'get_acta', idFechaExamen: idFechaExamen }, function(res) {
            tbody.empty();
            let condiciones = new Set();
            
            if(res.success && res.data && res.data.alumnos) {
                
                // Mostrar Docente
                let cabecera = res.data.cabecera;
                if(cabecera && cabecera.apellidoDocente) {
                    txtDocente.text(cabecera.apellidoDocente + ', ' + cabecera.nombreDocente);
                } else {
                    txtDocente.text('Sin asignar');
                }

                // Mostrar Alumnos
                let alumnos = res.data.alumnos;
                
                if(alumnos.length > 0) {
                    alumnos.forEach(alu => {
                        let cond = alu.condicion || 'Sin Condición';
                        condiciones.add(cond);
                        
                        let row = `
                            <tr data-condicion="${cond}">
                                <td>
                                    ${alu.apellido}, ${alu.nombre}<br>
                                    <small class="text-muted">DNI: ${alu.dni}</small>
                                </td>
                                <td><span class="badge bg-secondary">${cond}</span></td>
                                
                                <td><input type="number" class="form-control input-sm editable-input text-center" data-id="${alu.idInscripcion}" data-campo="escrito" value="${alu.escrito || ''}" min="1" max="10" oninput="validarNota(this)" placeholder="-"></td>
                                <td><input type="number" class="form-control input-sm editable-input text-center" data-id="${alu.idInscripcion}" data-campo="oral" value="${alu.oral || ''}" min="1" max="10" oninput="validarNota(this)" placeholder="-"></td>
                                <td><input type="number" class="form-control input-sm editable-input text-center fw-bold" data-id="${alu.idInscripcion}" data-campo="calificacion" value="${alu.calificacion || ''}" min="1" max="10" oninput="validarNota(this)" placeholder="-" style="border: 2px solid #e9ecef;"></td>
                                <td><input type="text" class="editable-input text-center" data-id="${alu.idInscripcion}" data-campo="libro" value="${alu.libro || ''}" placeholder="-"></td>
                                <td><input type="text" class="editable-input text-center" data-id="${alu.idInscripcion}" data-campo="folio" value="${alu.folio || ''}" placeholder="-"></td>
                                
                                <td class="text-center status-cell"></td>
                            </tr>
                        `;
                        tbody.append(row);
                    });

                    // Llenar Filtro
                    let filtro = $('#filtroCondicion');
                    filtro.html('<option value="todos">Todos (Ver pantalla)</option>');
                    condiciones.forEach(c => filtro.append(`<option value="${c}">${c}</option>`));

                } else {
                    tbody.html('<tr><td colspan="8" class="text-center">No se encontraron alumnos inscriptos en esta mesa.</td></tr>');
                }
            } else {
                tbody.html('<tr><td colspan="8" class="text-center text-danger">Error al recuperar datos del servidor.</td></tr>');
            }
        }, 'json').fail(function() {
            tbody.html('<tr><td colspan="8" class="text-center text-danger">Error de conexión.</td></tr>');
        });
    });

    // --- FILTRADO EN EL CLIENTE ---
    $('#filtroCondicion').change(function() {
        let val = $(this).val();
        let rows = $('#bodyTablaActa tr');
        if(val === 'todos') {
            rows.show();
        } else {
            rows.hide();
            rows.filter(`[data-condicion="${val}"]`).show();
        }
    });

    // --- GUARDADO AUTOMÁTICO (EDICIÓN DE NOTAS) ---
    $(document).on('change', '.editable-input', function() {
        let input = $(this);
        let id = input.data('id');
        let campo = input.data('campo');
        let valor = input.val();
        let cellStatus = input.closest('tr').find('.status-cell');

        cellStatus.html('<div class="spinner-border spinner-border-sm text-primary" role="status"></div>');

        $.post('actas.php', {
            ajax_action: 'update_nota',
            idInscripcion: id,
            campo: campo,
            valor: valor
        }, function(res) {
            if(res.success) {
                cellStatus.html('<i class="bi bi-check-circle-fill text-success"></i>');
                setTimeout(() => cellStatus.empty(), 2000); // Limpiar icono después de 2 seg
            } else {
                cellStatus.html('<i class="bi bi-x-circle-fill text-danger"></i>');
                alert('Error al guardar: ' + res.message);
            }
        }, 'json').fail(function() {
            cellStatus.html('<i class="bi bi-exclamation-triangle-fill text-danger"></i>');
            alert('Error de conexión.');
        });
    });

    // --- BOTÓN IMPRIMIR ACTA (Validación Estricta) ---
    $('#btnImprimirActa').click(function() {
        let idFechaExamen = $('#idMesa').val();
        let condicionSeleccionada = $('#filtroCondicion').val();
        
        // 1. Validar Mesa
        if (!idFechaExamen) {
            alert('Por favor, seleccione una mesa de examen válida.');
            return;
        }

        // 2. Validar Condición (Requisito para Acta Volante)
        if (condicionSeleccionada === 'todos' || !condicionSeleccionada) {
            alert('ATENCIÓN: Para imprimir el Acta Volante, debe seleccionar una CONDICIÓN específica (ej: "Regular" o "Libre") en el filtro.\n\nEl sistema genera actas separadas por condición.');
            $('#filtroCondicion').focus(); 
            return;
        }

        // 3. Abrir PDF
        window.open('../reportes/actaVolantePDF.php?idFechaExamen=' + idFechaExamen + '&condicion=' + encodeURIComponent(condicionSeleccionada), '_blank');
    });

});

function validarNota(input) {
    // 1. Limpieza estricta de caracteres no numéricos
    input.value = input.value.replace(/[^0-9]/g, '');

    // Si está vacío, terminamos
    if (input.value === '') return;

    // 2. Control de rango
    let valor = parseInt(input.value);

    if (valor > 10) {
        input.value = 10; 
    } else if (valor < 1) {
        input.value = 1;  
    }
}
</script>

</body>
</html>