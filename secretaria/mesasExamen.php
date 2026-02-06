<?php
// Incluir el script de verificación de sesión
include '../funciones/verificarSesion.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../inicio/conexion.php';
// AHORA INCLUIMOS TODAS LAS FUNCIONES DE CONSULTA
include '../funciones/consultas.php'; 

// ==================================================================
// BLOQUE 1: MANEJADOR DE PETICIONES AJAX
// ==================================================================
// (Utilizado por el JS de la página para poblar selects dependientes en la carga)
if (isset($_GET['ajax_action'])) {
    
    header('Content-Type: application/json');
    
    try {
        switch ($_GET['ajax_action']) {
            
            case 'get_cursos':
                $idPlan = filter_input(INPUT_GET, 'idPlan', FILTER_VALIDATE_INT);
                $idCiclo = filter_input(INPUT_GET, 'idCiclo', FILTER_VALIDATE_INT);
                $cursos = [];
                if ($idPlan && $idCiclo) {
                    $cursos = buscarCursosPlanCiclo($conn, $idPlan, $idCiclo);
                }
                echo json_encode($cursos);
                break;

            case 'get_materias':
                $idPlan = filter_input(INPUT_GET, 'idPlan', FILTER_VALIDATE_INT);
                $idCurso = filter_input(INPUT_GET, 'idCurso', FILTER_VALIDATE_INT);
                $materias = [];
                if ($idPlan && $idCurso) {
                    $materias = materiasPlanCurso($conn, $idPlan, $idCurso);
                }
                echo json_encode($materias);
                break;
            
            case 'get_teachers':
                $docentes = obtenerDocentesActivos($conn);
                echo json_encode($docentes);
                break;

            default:
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Acción AJAX no válida.']);
        }
    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => $e->getMessage()]);
    }
    
    exit; // Detener la ejecución del script después de manejar AJAX
}


// ==================================================================
// BLOQUE 2: MANEJADOR DE ACCIONES POST/GET (Formularios y Borrado)
// ==================================================================
// (La lógica de Crear, Actualizar, Asignar y Borrar sigue idéntica)

$message = '';
$message_type = '';

// ACCIÓN: CREAR MESA DE EXAMEN (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'create') {
    $idCicloLectivo = filter_input(INPUT_POST, 'idCicloLectivo', FILTER_VALIDATE_INT);
    $idTurno = filter_input(INPUT_POST, 'idTurno', FILTER_VALIDATE_INT);
    $idMateria = filter_input(INPUT_POST, 'idMateria', FILTER_VALIDATE_INT);
    $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_STRING);
    $hora = filter_input(INPUT_POST, 'hora', FILTER_SANITIZE_STRING);

    if (!$idCicloLectivo || !$idTurno || !$idMateria || empty($fecha) || empty($hora)) {
        $_SESSION['message_mesas'] = ['text' => 'Error: Faltan datos requeridos (Ciclo, Turno, Materia, Fecha, Hora).', 'type' => 'danger'];
    } else {
        try {
            if (crearMesaExamen($conn, $idMateria, $idTurno, $idCicloLectivo, $fecha, $hora)) {
                $_SESSION['message_mesas'] = ['text' => 'Mesa de examen creada correctamente.', 'type' => 'success'];
            }
        } catch (Exception $e) {
            $_SESSION['message_mesas'] = ['text' => 'Error al crear la mesa de examen: ' . $e->getMessage(), 'type' => 'danger'];
        }
    }
    $redirect_params = http_build_query([
        'idCicloLectivo' => $idCicloLectivo,
        'idTurno' => $idTurno,
        'idPlan' => $_POST['idPlan'] ?? '',
        'idCurso' => $_POST['idCurso'] ?? ''
    ]);
    header('Location: mesasExamen.php?' . $redirect_params);
    exit;
}

// ACCIÓN: ACTUALIZAR MESA (FECHA/HORA) (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    $idFechaExamen = filter_input(INPUT_POST, 'idFechaExamen', FILTER_VALIDATE_INT);
    $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_STRING);
    $hora = filter_input(INPUT_POST, 'hora', FILTER_SANITIZE_STRING);

    if (!$idFechaExamen || empty($fecha) || empty($hora)) {
        $_SESSION['message_mesas'] = ['text' => 'Error: Faltan datos para actualizar.', 'type' => 'danger'];
    } else {
        try {
            actualizarMesaExamen($conn, $idFechaExamen, $fecha, $hora);
            $_SESSION['message_mesas'] = ['text' => 'Mesa de examen actualizada.', 'type' => 'success'];
        } catch (Exception $e) {
            $_SESSION['message_mesas'] = ['text' => 'Error al actualizar: ' . $e->getMessage(), 'type' => 'danger'];
        }
    }
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// ACCIÓN: ASIGNAR DOCENTES (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'assign_teachers') {
    $idFechaExamen = filter_input(INPUT_POST, 'idFechaExamen', FILTER_VALIDATE_INT);
    $p1 = !empty($_POST['p1']) ? filter_input(INPUT_POST, 'p1', FILTER_VALIDATE_INT) : null;
    $p2 = !empty($_POST['p2']) ? filter_input(INPUT_POST, 'p2', FILTER_VALIDATE_INT) : null;
    $p3 = !empty($_POST['p3']) ? filter_input(INPUT_POST, 'p3', FILTER_VALIDATE_INT) : null;
    $p4 = !empty($_POST['p4']) ? filter_input(INPUT_POST, 'p4', FILTER_VALIDATE_INT) : null;
    $p5 = !empty($_POST['p5']) ? filter_input(INPUT_POST, 'p5', FILTER_VALIDATE_INT) : null;
    $p6 = !empty($_POST['p6']) ? filter_input(INPUT_POST, 'p6', FILTER_VALIDATE_INT) : null;
    $p7 = !empty($_POST['p7']) ? filter_input(INPUT_POST, 'p7', FILTER_VALIDATE_INT) : null;

    if (!$idFechaExamen) {
        $_SESSION['message_mesas'] = ['text' => 'Error: ID de mesa no válido.', 'type' => 'danger'];
    } else {
        try {
            asignarDocentesMesa($conn, $idFechaExamen, $p1, $p2, $p3, $p4, $p5, $p6, $p7);
            $_SESSION['message_mesas'] = ['text' => 'Docentes asignados correctamente.', 'type' => 'success'];
        } catch (Exception $e) {
             $_SESSION['message_mesas'] = ['text' => 'Error al asignar docentes: ' . $e->getMessage(), 'type' => 'danger'];
        }
    }
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}


// ACCIÓN: ELIMINAR MESA (GET)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['idFechaExamen'])) {
    $idFechaExamen = filter_input(INPUT_GET, 'idFechaExamen', FILTER_VALIDATE_INT);

    if (!$idFechaExamen) {
        $_SESSION['message_mesas'] = ['text' => 'Error: ID de mesa no válido.', 'type' => 'danger'];
    } else {
        $conn->begin_transaction();
        try {
            eliminarInscripcionesPorMesa($conn, $idFechaExamen);
            eliminarFechaExamen($conn, $idFechaExamen);
            $conn->commit();
            $_SESSION['message_mesas'] = ['text' => 'Mesa de examen e inscripciones asociadas eliminadas.', 'type' => 'success'];
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message_mesas'] = ['text' => 'Error en la transacción al eliminar: ' . $e->getMessage(), 'type' => 'danger'];
        }
    }
    unset($_GET['action'], $_GET['idFechaExamen']);
    $redirect_params = http_build_query($_GET);
    header('Location: mesasExamen.php?' . $redirect_params);
    exit;
}


// ==================================================================
// BLOQUE 3: LÓGICA DE CARGA DE PÁGINA (GET estándar)
// ==================================================================

// Recuperar mensaje de sesión
if (isset($_SESSION['message_mesas'])) {
    $message = $_SESSION['message_mesas']['text'];
    $message_type = $_SESSION['message_mesas']['type'];
    unset($_SESSION['message_mesas']);
}

// Obtener filtros de la URL
$idCicloFilter = filter_input(INPUT_GET, 'idCicloLectivo', FILTER_VALIDATE_INT);
$idTurnoFilter = filter_input(INPUT_GET, 'idTurno', FILTER_VALIDATE_INT);
$idPlanFilter = filter_input(INPUT_GET, 'idPlan', FILTER_VALIDATE_INT);
$idCursoFilter = filter_input(INPUT_GET, 'idCurso', FILTER_VALIDATE_INT);
$idMateriaFilter = filter_input(INPUT_GET, 'idMateria', FILTER_VALIDATE_INT);

// Cargar datos para los selects principales
$ciclos = levantarCiclosLectivos($conn);
$turnos = obtenerTodosTurnos($conn);
$planes = obtenerPlanesDeEstudio($conn);

// Cargar la grilla de mesas de examen (Filtrado)
$mesas_examen = filtrarMesasExamen(
    $conn, 
    $idCicloFilter, 
    $idTurnoFilter, 
    $idPlanFilter, 
    $idCursoFilter, 
    $idMateriaFilter
);

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mesas de examen - Secretaría</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .teacher-select-group { margin-bottom: 5px; }
    
    #loadingOverlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.7);
        z-index: 1060; 
        display: none; 
        justify-content: center;
        align-items: center;
    }
  </style>
</head>
<body>

<div id="loadingOverlay">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando...</span>
    </div>
</div>

<script>
    window.usuarioActual = "<?php echo htmlspecialchars($_SESSION['active_user_identifier'] ?? 'null'); ?>";
</script>

<?php include '../funciones/menu_secretaria.php'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
      <li class="breadcrumb-item active">Mesas de examen</li>
    </ol>

    <?php if ($message): ?>
      <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="card p-4 mb-4">
      <h5>Gestión de mesas de examen</h5>
      
      <form id="filtroForm" method="GET" action="mesasExamen.php">
        <fieldset class="mb-3 p-3 border rounded">
          <legend class="float-none w-auto px-2">Filtros</legend>
          <div class="row">
            <div class="col-md-3">
              <label for="filtroCicloLectivo" class="form-label">Ciclo lectivo <span class="text-danger">*</span></label>
              <select class="form-select filtro-auto-submit" id="filtroCicloLectivo" name="idCicloLectivo" required>
                <option value="">Seleccione...</option>
                <?php foreach ($ciclos as $ciclo): ?>
                  <option value="<?php echo $ciclo['idCicloLectivo']; ?>" <?php echo ($idCicloFilter == $ciclo['idCicloLectivo']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($ciclo['anio']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label for="filtroTurno" class="form-label">Turno <span class="text-danger">*</span></label>
              <select class="form-select filtro-auto-submit" id="filtroTurno" name="idTurno" required>
                <option value="">Seleccione...</option>
                <?php foreach ($turnos as $turno): ?>
                  <option value="<?php echo $turno['idTurno']; ?>" <?php echo ($idTurnoFilter == $turno['idTurno']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($turno['nombre']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label for="filtroPlan" class="form-label">Plan</label>
              <select class="form-select filtro-auto-submit" id="filtroPlan" name="idPlan">
                <option value="">Todos...</option>
                <?php foreach ($planes as $plan): ?>
                  <option value="<?php echo $plan['idPlan']; ?>" <?php echo ($idPlanFilter == $plan['idPlan']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($plan['nombre']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label for="filtroCurso" class="form-label">Curso</label>
              <select class="form-select filtro-auto-submit" id="filtroCurso" name="idCurso" <?php echo !$idPlanFilter ? 'disabled' : ''; ?>>
                <option value="">Todos...</option>
                </select>
            </div>
          </div>
          <div class="row mt-3">
             <div class="col-md-6">
              <label for="filtroMateria" class="form-label">Materia</label>
              <select class="form-select filtro-auto-submit" id="filtroMateria" name="idMateria" <?php echo !$idCursoFilter ? 'disabled' : ''; ?>>
                <option value="">Todas...</option>
                </select>
            </div>
            </div>
        </fieldset>
      </form>
      
      <form id="createForm" method="POST" action="mesasExamen.php">
        <input type="hidden" name="action" value="create">
        <input type="hidden" id="createCicloLectivo" name="idCicloLectivo">
        <input type="hidden" id="createTurno" name="idTurno">
        <input type="hidden" id="createPlan" name="idPlan">
        <input type="hidden" id="createCurso" name="idCurso">
        <input type="hidden" id="createMateria" name="idMateria">

        <fieldset class="mb-3 p-3 border rounded" style="background-color: #f8f9fa;">
          <legend class="float-none w-auto px-2">Agregar nueva mesa</legend>
          <p class="form-text">Complete los filtros (Ciclo, Turno, Plan, Curso y Materia) para habilitar esta sección.</p>
          <div class="row">
            <div class="col-md-6">
              <label for="fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="fecha" name="fecha" required disabled>
            </div>
            <div class="col-md-6">
              <label for="hora" class="form-label">Hora <span class="text-danger">*</span></label>
              <input type="time" class="form-control" id="hora" name="hora" required disabled>
            </div>
          </div>
          <button type="submit" id="btnCrearMesa" class="btn btn-primary mt-3" disabled>Agregar fecha</button>
        </fieldset>
      </form>
    </div>

    <div class="card p-4">
      <h5>Mesas de examen existentes</h5>
      <div class="table-responsive">
        <table class="table table-striped table-hover mt-3" id="tablaMesas">
          <thead>
            <tr>
              <th>Materia</th>
              <th>Curso</th>
              <th>Plan</th>
              <th>Ciclo</th>
              <th>Turno</th>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($mesas_examen)): ?>
              <tr>
                <td colspan="8" class="text-center">
                    <?php if (empty($idCicloFilter) || empty($idTurnoFilter)): ?>
                        Seleccione un Ciclo Lectivo y un Turno para comenzar.
                    <?php else: ?>
                        No se encontraron mesas para los filtros seleccionados.
                    <?php endif; ?>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($mesas_examen as $mesa): ?>
                <tr>
                  <td><?php echo htmlspecialchars($mesa['nombreMateria']); ?></td>
                  <td><?php echo htmlspecialchars($mesa['nombreCurso']); ?></td>
                  <td><?php echo htmlspecialchars($mesa['nombrePlan']); ?></td>
                  <td><?php echo htmlspecialchars($mesa['anioCiclo']); ?></td>
                  <td><?php echo htmlspecialchars($mesa['nombreTurno']); ?></td>
                  <td><?php echo date("d/m/Y", strtotime($mesa['fecha'])); ?></td>
                  <td><?php echo date("H:i", strtotime($mesa['hora'])); ?></td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal" 
                            data-id="<?php echo $mesa['idFechaExamen']; ?>" 
                            data-fecha="<?php echo $mesa['fecha']; ?>" 
                            data-hora="<?php echo $mesa['hora']; ?>" title="Editar Fecha/Hora">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#teacherModal" 
                            data-id="<?php echo $mesa['idFechaExamen']; ?>" 
                            data-p1="<?php echo $mesa['p1'] ?? ''; ?>"
                            data-p2="<?php echo $mesa['p2'] ?? ''; ?>"
                            data-p3="<?php echo $mesa['p3'] ?? ''; ?>"
                            data-p4="<?php echo $mesa['p4'] ?? ''; ?>"
                            data-p5="<?php echo $mesa['p5'] ?? ''; ?>"
                            data-p6="<?php echo $mesa['p6'] ?? ''; ?>"
                            data-p7="<?php echo $mesa['p7'] ?? ''; ?>"
                            title="<?php echo ($mesa['p1']) ? 'Ver/Editar Docentes' : 'Asignar Docentes'; ?>">
                      <i class="bi bi-person-plus"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" 
                            data-id="<?php echo $mesa['idFechaExamen']; ?>" 
                            data-materia="<?php echo htmlspecialchars($mesa['nombreMateria']); ?>"
                            data-fecha="<?php echo date("d/m/Y", strtotime($mesa['fecha'])); ?>" title="Eliminar Mesa">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
  <br>
</div>

<?php include '../funciones/footer.html'; ?>

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="mesasExamen.php">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="idFechaExamen" id="editIdFechaExamen">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Editar Mesa de Examen</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="editFecha" class="form-label">Fecha</label>
            <input type="date" class="form-control" id="editFecha" name="fecha" required>
          </div>
          <div class="mb-3">
            <label for="editHora" class="form-label">Hora</label>
            <input type="time" class="form-control" id="editHora" name="hora" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>¿Está seguro de que desea eliminar la mesa de examen de:</p>
        <p><strong id="deleteMateriaInfo"></strong></p>
        <p class="text-danger fw-bold">
          <i class="bi bi-exclamation-triangle-fill"></i> ¡Atención! Esta acción es irreversible. Se borrarán todas las inscripciones de alumnos a esta mesa y cualquier calificación registrada en ella.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a id="confirmDeleteButton" href="#" class="btn btn-danger">Eliminar</a>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="teacherModal" tabindex="-1" aria-labelledby="teacherModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="mesasExamen.php">
        <input type="hidden" name="action" value="assign_teachers">
        <input type="hidden" name="idFechaExamen" id="teacherIdFechaExamen">
        <div class="modal-header">
          <h5 class="modal-title" id="teacherModalLabel">Asignar Docentes a Mesa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          
          <div class="teacher-select-group">
            <label for="select_p1" class="form-label fw-bold">Titular (P1)</label>
            <select class="form-select teacher-select" id="select_p1" name="p1">
              <option value="">- Sin Asignar -</option>
              </select>
          </div>
          <hr>
          <div class="teacher-select-group">
            <label for="select_p2" class="form-label">Vocal (P2)</label>
            <select class="form-select teacher-select" id="select_p2" name="p2">
              <option value="">- Sin Asignar -</option>
            </select>
          </div>
          <div class="teacher-select-group">
            <label for="select_p3" class="form-label">Vocal (P3)</label>
            <select class="form-select teacher-select" id="select_p3" name="p3">
              <option value="">- Sin Asignar -</option>
            </select>
          </div>
          <div class="teacher-select-group">
            <label for="select_p4" class="form-label">Vocal (P4)</label>
            <select class="form-select teacher-select" id="select_p4" name="p4">
              <option value="">- Sin Asignar -</option>
            </select>
          </div>
          <div class="teacher-select-group">
            <label for="select_p5" class="form-label">Vocal (P5)</label>
            <select class="form-select teacher-select" id="select_p5" name="p5">
              <option value="">- Sin Asignar -</option>
            </select>
          </div>
           <div class="teacher-select-group">
            <label for="select_p6" class="form-label">Vocal (P6)</label>
            <select class="form-select teacher-select" id="select_p6" name="p6">
              <option value="">- Sin Asignar -</option>
            </select>
          </div>
           <div class="teacher-select-group">
            <label for="select_p7" class="form-label">Vocal (P7)</label>
            <select class="form-select teacher-select" id="select_p7" name="p7">
              <option value="">- Sin Asignar -</option>
            </select>
          </div>
          <div id="teacherLoading" class="text-center" style="display: none;">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Cargando docentes...</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar Asignación</button>
        </div>
      </form>
    </div>
  </div>
</div>


<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../funciones/sessionControl.js"></script>

<script>
$(document).ready(function() {
    
    // --- LÓGICA DE FILTROS DEPENDIENTES Y AUTO-SUBMIT ---
    
    // Valores actuales (cargados por PHP)
    const selectedCiclo = '<?php echo $idCicloFilter ?? ''; ?>';
    const selectedPlan = '<?php echo $idPlanFilter ?? ''; ?>';
    const selectedCurso = '<?php echo $idCursoFilter ?? ''; ?>';
    const selectedMateria = '<?php echo $idMateriaFilter ?? ''; ?>';
    const $overlay = $('#loadingOverlay');

    // 1. Manejar reseteo de filtros dependientes
    $('#filtroCicloLectivo, #filtroPlan').on('change', function() {
        if ($(this).is('#filtroPlan') && $(this).val() == '') {
             $('#filtroCurso').val(''); 
        }
        if ($(this).is('#filtroCicloLectivo') && $(this).val() == '') {
             $('#filtroCurso').val(''); 
        }
        $('#filtroMateria').val(''); 
    });

    $('#filtroCurso').on('change', function() {
        if ($(this).val() == '') {
            $('#filtroMateria').val('');
        }
    });

    // 2. Handler de auto-submit para TODOS los filtros
    $('.filtro-auto-submit').on('change', function() {
        $overlay.css('display', 'flex');
        $('#filtroForm').submit();
    });

    // --- LÓGICA DE CARGA DE PÁGINA (POST-RECARGA) ---

    // 1. Cargar Cursos si hay un Plan y Ciclo seleccionados
    if (selectedPlan && selectedCiclo) {
        loadCursos(selectedPlan, selectedCiclo, selectedCurso);
    }

    // 2. Función para Cargar Cursos (vía AJAX)
    function loadCursos(idPlan, idCiclo, cursoToSelect) {
        const $cursoSelect = $('#filtroCurso');
        $cursoSelect.empty().append('<option value="">Todos...</option>').prop('disabled', true);
        $('#filtroMateria').empty().append('<option value="">Todas...</option>').prop('disabled', true);

        if (idPlan && idCiclo) {
            $.ajax({
                url: 'mesasExamen.php', 
                type: 'GET',
                data: { ajax_action: 'get_cursos', idPlan: idPlan, idCiclo: idCiclo },
                dataType: 'json',
                success: function(cursos) {
                    $cursoSelect.prop('disabled', false); 
                    $.each(cursos, function(i, curso) {
                        $cursoSelect.append($('<option>', {
                            value: curso.idCurso,
                            text: curso.nombre
                        }));
                    });
                    
                    if (cursoToSelect) {
                        $cursoSelect.val(cursoToSelect);
                        loadMaterias(idPlan, cursoToSelect, selectedMateria);
                    }
                },
                error: function() {
                    $cursoSelect.append('<option value="">Error al cargar</option>');
                    $cursoSelect.prop('disabled', false);
                }
            });
        }
    }

    // 3. Función para Cargar Materias (vía AJAX)
    function loadMaterias(idPlan, idCurso, materiaToSelect) {
        const $materiaSelect = $('#filtroMateria');
        $materiaSelect.empty().append('<option value="">Todas...</option>').prop('disabled', true);

        if (idPlan && idCurso) {
            $.ajax({
                url: 'mesasExamen.php',
                type: 'GET',
                data: { ajax_action: 'get_materias', idPlan: idPlan, idCurso: idCurso },
                dataType: 'json',
                success: function(materias) {
                    $materiaSelect.prop('disabled', false); 
                    $.each(materias, function(i, materia) {
                        $materiaSelect.append($('<option>', {
                            value: materia.idMateria,
                            text: materia.nombreMateria
                        }));
                    });
                    if (materiaToSelect) {
                        $materiaSelect.val(materiaToSelect);
                    }
                    checkCreateFormState(); 
                },
                error: function() {
                    $materiaSelect.append('<option value="">Error al cargar</option>');
                    $materiaSelect.prop('disabled', false);
                }
            });
        } else {
             checkCreateFormState();
        }
    }
    
    
    // --- LÓGICA DEL FORMULARIO DE CREACIÓN ---
    
    function checkCreateFormState() {
        const ciclo = $('#filtroCicloLectivo').val();
        const turno = $('#filtroTurno').val();
        const plan = $('#filtroPlan').val();
        const curso = $('#filtroCurso').val();
        const materia = $('#filtroMateria').val();

        if (ciclo && turno && plan && curso && materia) {
            $('#fecha').prop('disabled', false);
            $('#hora').prop('disabled', false);
            $('#btnCrearMesa').prop('disabled', false);
        } else {
            $('#fecha').prop('disabled', true);
            $('#hora').prop('disabled', true);
            $('#btnCrearMesa').prop('disabled', true);
        }
    }
    
    // Copiar valores de filtros al formulario de creación al enviarlo
    $('#createForm').on('submit', function() {
        $('#createCicloLectivo').val($('#filtroCicloLectivo').val());
        $('#createTurno').val($('#filtroTurno').val());
        $('#createPlan').val($('#filtroPlan').val());
        $('#createCurso').val($('#filtroCurso').val());
        $('#createMateria').val($('#filtroMateria').val());
    });
    
    // --- LÓGICA DE MODALES ---
    
    $('.modal').on('show.bs.modal', function () {
        $overlay.hide();
    });

    // Modal EDITAR
    $('#editModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var fecha = button.data('fecha');
        var hora = button.data('hora');
        
        var modal = $(this);
        modal.find('#editIdFechaExamen').val(id);
        modal.find('#editFecha').val(fecha);
        modal.find('#editHora').val(hora);
    });
    
    // Modal BORRAR
    $('#deleteModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var materia = button.data('materia');
        var fecha = button.data('fecha');
        
        var modal = $(this);
        modal.find('#deleteMateriaInfo').text(materia + ' del ' + fecha);
        
        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('action', 'delete');
        currentUrl.searchParams.set('idFechaExamen', id);
        
        modal.find('#confirmDeleteButton').attr('href', currentUrl.href);
    });
    
    // Modal DOCENTES
    var teachersLoaded = false;
    // *** CORRECCIÓN: Inicializar la lista de docentes vacía ***
    var teacherListHtml = ''; 

    $('#teacherModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var modal = $(this);
        
        modal.find('#teacherIdFechaExamen').val(id);
        $('.teacher-select').val('');
        
        if (!teachersLoaded) {
            $('#teacherLoading').show();
            $.ajax({
                url: 'mesasExamen.php',
                type: 'GET',
                data: { ajax_action: 'get_teachers' },
                dataType: 'json',
                success: function(teachers) {
    $.each(teachers, function(i, teacher) {
        // Ahora usamos teacher.idPersona como value
        teacherListHtml += `<option value="${teacher.idPersona}">${teacher.apellido}, ${teacher.nombre}</option>`;
    });
                    // *** AHORA: .append() solo añade los docentes (sin el duplicado "-Sin Asignar-") ***
                    $('.teacher-select').append(teacherListHtml); 
                    teachersLoaded = true;
                    populateTeacherSelects(button);
                    $('#teacherLoading').hide();
                },
                error: function() {
                    $('#teacherLoading').html('Error al cargar docentes.');
                }
            });
        } else {
            populateTeacherSelects(button);
        }
    });

    function populateTeacherSelects(button) {
        $('#select_p1').val(button.data('p1') || '');
        $('#select_p2').val(button.data('p2') || '');
        $('#select_p3').val(button.data('p3') || '');
        $('#select_p4').val(button.data('p4') || '');
        $('#select_p5').val(button.data('p5') || '');
        $('#select_p6').val(button.data('p6') || '');
        $('#select_p7').val(button.data('p7') || '');
    }
    
    // *** CAMBIO: Filtro de búsqueda JS eliminado ***

    // Ejecutar al cargar la página
    checkCreateFormState();
    
    $(window).on('pageshow', function() {
        $overlay.hide();
    });
});
</script>

</body>
</html>