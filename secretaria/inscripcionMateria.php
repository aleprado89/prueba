<?php
// Incluir el script de verificación de sesión
include '../funciones/verificarSesion.php';

// Habilitar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir la conexión a la base de datos y consultas
include '../inicio/conexion.php'; // Asegúrate de que esta ruta sea correcta
include '../funciones/consultas.php'; // Asegúrate de que esta ruta sea correcta

// --- Lógica para manejar peticiones AJAX ---
if (isset($_GET['ajax_action'])) {
    header('Content-Type: application/json');
    if ($_GET['ajax_action'] == 'get_cursos') {
        $idPlan = $_GET['idPlan'] ?? null;
        $anio = $_GET['anio'] ?? date('Y');
        $idCiclo = buscarIdCiclo($conn, $anio);

        $cursos = [];
        if (!is_null($idPlan) && !is_null($idCiclo)) {
            $cursos = buscarCursosPlanCiclo($conn, $idPlan, $idCiclo);
        }
        echo json_encode($cursos);
        exit;
    } elseif ($_GET['ajax_action'] == 'get_materias') {
        $idCurso = $_GET['idCurso'] ?? null;
        $idPlan = $_GET['idPlan'] ?? null; // <-- AHORA SE RECIBE EL IDPLAN
        $materias = [];
        if (!is_null($idCurso) && !is_null($idPlan)) {
            $materias = materiasPlanCurso($conn, $idPlan, $idCurso); // <-- SE LLAMA CON IDPLAN
        }
        echo json_encode($materias);
        exit;
    } elseif ($_GET['ajax_action'] == 'get_planes_por_anio') {
        $idAlumno = $_GET['idAlumno'] ?? null;
        $anio = $_GET['anio'] ?? null;
        $planes = [];
        if (!is_null($idAlumno) && !is_null($anio)) {
            $planes = obtenerPlanesMatriculadosPorAnio($conn, $idAlumno, $anio);
        }
        echo json_encode($planes);
        exit;
    }
    // --- NUEVO CASO PARA EL FILTRO DE CURSOS ---
    elseif ($_GET['ajax_action'] == 'get_cursos_por_plan_filtro') {
        $idPlan = $_GET['idPlan'] ?? null;
        $cursos = [];
        if (!is_null($idPlan)) {
            // Aquí usamos tu nueva función buscarCursosPlan
            $cursos = buscarCursosPlan($conn, $idPlan);
        }
        echo json_encode($cursos);
        exit;
    }
    // --- FIN NUEVO CASO ---
}
// --- Fin Lógica para manejar peticiones AJAX ---


$idAlumno = $_GET['idAlumno'] ?? null;
if (!$idAlumno) {
    $_SESSION['message_inscripcionMateria'] = ['text' => 'ID de alumno no proporcionado para inscripción.', 'type' => 'danger'];
    header('Location: buscarAlumno.php?origin=inscripcionMateria');
    exit;
}

$alumnoData = obtenerDatosAlumno($conn, $idAlumno);
if (!$alumnoData) {
    $_SESSION['message_inscripcionMateria'] = ['text' => 'Alumno no encontrado.', 'type' => 'danger'];
    header('Location: buscarAlumno.php?origin=inscripcionMateria');
    exit;
}

$message = '';
$message_type = '';

// Procesamiento de formulario de Inscripción a Materia (INSERT)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_matriculacion_materia') {
    // Debugging: Imprimir el valor recibido para idMateriaMatriculacion
    // echo "Valor de \$_POST['idMateriaMatriculacion']: " . ($_POST['idMateriaMatriculacion'] ?? 'undefined');

    $matriculacion_materia_data = [
        'idAlumno' => $idAlumno,
        'idMateria' => $_POST['idMateriaMatriculacion'] ?? null,
        'fechaMatriculacionMateria' => $_POST['fechaMatriculacionMateria'] ?? null,
        'fechaBajaMatriculacionMateria' => $_POST['fechaBajaMatriculacionMateriaMatriculacion'] ?? null,
    ];

    if (empty($matriculacion_materia_data['idMateria']) || empty($matriculacion_materia_data['fechaMatriculacionMateria'])) {
        $message = "Error: Materia y Fecha de Inscripción son campos requeridos.";
        $message_type = 'danger';
    } else {
        $conn->begin_transaction();
        $op_success = true;

        if (insertarMatriculacionMateria($conn, $matriculacion_materia_data)) {
            $idMateria = $matriculacion_materia_data['idMateria'];
            $anio_inscripcion = date('Y', strtotime($matriculacion_materia_data['fechaMatriculacionMateria']));
            $idCicloLectivo = buscarIdCiclo($conn, $anio_inscripcion);

            if ($idCicloLectivo === null) {
                $message = "Error: No se pudo determinar el Ciclo Lectivo para la fecha de inscripción ({$anio_inscripcion}).";
                $message_type = 'danger';
                $op_success = false;
            } else {
                // Aquí se usan las variables $idAlumno, $idMateria, $idCicloLectivo
                if (!inicializarAsistenciaMateria($conn, $idAlumno, $idMateria, $idCicloLectivo)) {
                    $message = "Error al inicializar registros de asistencia para la materia (ID Materia: {$idMateria}).";
                    $message_type = 'danger';
                    $op_success = false;
                }

                if ($op_success && !inicializarCalificacionMateria($conn, $idAlumno, $idMateria)) {
                    $message = "Error al inicializar registro de calificación para la materia (ID Materia: {$idMateria}).";
                    $message_type = 'danger';
                    $op_success = false;
                }
            }
        } else {
            $message = "Error al guardar la inscripción en matriculacionmateria.";
            $message_type = 'danger';
            $op_success = false;
        }

        if ($op_success) {
            $conn->commit();
            $message = "Inscripción de materia guardada correctamente.";
            $message_type = 'success';
        } else {
            $conn->rollback();
        }
    }
    $_SESSION['message_inscripcionMateria'] = ['text' => $message, 'type' => $message_type];
    header('Location: inscripcionMateria.php?idAlumno=' . $idAlumno);
    exit;
}

// Procesamiento de eliminación de Inscripción de Materia
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'delete_matriculacion_materia') {
    $idMatriculacionMateria = $_GET['idMatriculacionMateria'] ?? null;
    if ($idMatriculacionMateria) {
        // Obtener detalles necesarios antes de la confirmación
        $matriculacion_materia_data = obtenerDetallesMatriculacionMateria($conn, $idMatriculacionMateria);

        if ($matriculacion_materia_data) {
            // Si se recibe el parámetro de confirmación, procedemos a eliminar
            if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
                $conn->begin_transaction();
                $delete_success = true;

                // Eliminar de asistenciaterciario
                if (!eliminarAsistenciaMateria($conn, $matriculacion_materia_data['idAlumno'], $matriculacion_materia_data['idMateria'], $matriculacion_materia_data['idCicloLectivo'])) {
                    $message = "Error al eliminar registros de asistencia para la materia.";
                    $message_type = 'danger';
                    $delete_success = false;
                }

                // Eliminar de calificacionesterciario
                if ($delete_success && !eliminarCalificacionMateria($conn, $matriculacion_materia_data['idAlumno'], $matriculacion_materia_data['idMateria'])) {
                    $message = "Error al eliminar registro de calificación para la materia.";
                    $message_type = 'danger';
                    $delete_success = false;
                }

                // Eliminar de matriculacionmateria
                if ($delete_success && eliminarMatriculacionMateria($conn, $idMatriculacionMateria)) {
                    $message = "Inscripción de materia y sus registros asociados eliminados correctamente.";
                    $message_type = 'success';
                } else {
                    $message = "Error al eliminar la inscripción de materia.";
                    $message_type = 'danger';
                    $delete_success = false;
                }

                if ($delete_success) {
                    $conn->commit();
                } else {
                    $conn->rollback();
                }
            } else {
                // Si no hay confirmación, redirigir al modal de confirmación
                // Nota: Este redireccionamiento podría ser un problema si el modal se abre directamente en el JS.
                // Un mejor enfoque es que el JS abra el modal y el modal tenga un enlace que haga la redirección confirmada.
                // Por ahora, para simplificar, mantenemos la redirección.
                header('Location: inscripcionMateria.php?idAlumno=' . $idAlumno . '&action=delete_matriculacion_materia&idMatriculacionMateria=' . $idMatriculacionMateria . '&confirm=yes');
                exit;
            }
        } else {
            $message = "No se encontraron detalles de la inscripción a eliminar.";
            $message_type = 'warning';
        }
    } else {
        $message = "ID de inscripción de materia no proporcionado para eliminar.";
        $message_type = 'warning';
    }
    $_SESSION['message_inscripcionMateria'] = ['text' => $message, 'type' => $message_type];
    header('Location: inscripcionMateria.php?idAlumno=' . $idAlumno);
    exit;
}


// Recuperar mensaje de sesión si existe
if (isset($_SESSION['message_inscripcionMateria'])) {
    $message = $_SESSION['message_inscripcionMateria']['text'];
    $message_type = $_SESSION['message_inscripcionMateria']['type'];
    unset($_SESSION['message_inscripcionMateria']);
}


// Datos para los dropdowns
$matriculaciones_materia = obtenerMatriculacionesMateriaAlumno($conn, $idAlumno);

// Obtener el último año de ciclo lectivo para el select de año principal
$ultimoCiclo = obtenerUltimoCicloLectivo($conn);
$ultimoAnio = $ultimoCiclo ? $ultimoCiclo['anio'] : date('Y');

// --- MODIFICACIÓN AQUÍ ---
// Obtener los planes en los que el alumno está matriculado para el filtro de planes
$planesMatriculadosAlumno = obtenerMatriculacionesPlanAlumno($conn, $idAlumno);
// Creamos un array solo con los planes únicos para el selector de filtro
$planesParaFiltro = [];
$planesYaAgregados = []; // Para evitar duplicados si está matriculado en el mismo plan en diferentes cursos/años

foreach ($planesMatriculadosAlumno as $matriculacion) {
    $idPlan = $matriculacion['idPlanDeEstudio'];
    $nombrePlan = $matriculacion['nombrePlan'];
    if (!in_array($idPlan, $planesYaAgregados)) {
        $planesParaFiltro[] = ['idPlan' => $idPlan, 'nombre' => $nombrePlan];
        $planesYaAgregados[] = $idPlan;
    }
}
// Ordenar los planes por nombre
usort($planesParaFiltro, function($a, $b) {
    return strcmp($a['nombre'], $b['nombre']);
});
// --- FIN DE LA MODIFICACIÓN ---


// Fecha actual para campos de fecha por defecto
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inscripción de Materias - Secretaría</title>
  <!-- Incluir Bootstrap CSS -->
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <!-- Incluir tema Material Design -->
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <!-- Incluir tu archivo CSS general -->
  <link rel="stylesheet" href="../css/estilos.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<script>
    window.usuarioActual = "<?php echo htmlspecialchars($_SESSION['active_user_identifier'] ?? 'null'); ?>";
    console.log("INJECTED window.usuarioActual (en inscripcionMateria):", window.usuarioActual);
</script>

<?php include '../funciones/menu_secretaria.php'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
      <li class="breadcrumb-item"><a href="buscarAlumno.php?origin=inscripcionMateria">Buscar Alumno</a></li>
      <li class="breadcrumb-item active">Inscripción de Materias</li>
    </ol>

    <?php if ($message): ?>
      <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="card padding col-12">
      <h5><?php echo "Alumno: " . htmlspecialchars($alumnoData['apellido'] . ", " . $alumnoData['nombre']) . " &nbsp; DNI: " . htmlspecialchars($alumnoData['dni']); ?></h5>
      <br>

      <!-- Bloque de Inscripción a Materias -->
      <fieldset class="mb-4 p-3 border rounded">
        <legend class="float-none w-auto px-2">Inscripción a Materias</legend>
        <form id="matriculacionMateriaForm" method="POST" action="inscripcionMateria.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>">
          <input type="hidden" name="action" value="save_matriculacion_materia">

          <div class="row mb-3">
            <div class="col-md-4">
              <label for="anioInscripcionMateria" class="form-label">Año de Inscripción <span class="text-danger">*</span></label>
              <select class="form-select" id="anioInscripcionMateria" name="anioInscripcionMateria" required onchange="loadPlanesPorAnioInscripcionMateria()">
                <option value="">Seleccione el año</option>
                <?php
                $currentYear = date('Y');
                for ($i = $currentYear; $i >= $currentYear - 10; $i--): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($i == $ultimoAnio) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="planMatriculado" class="form-label">Plan de Estudio Matriculado <span class="text-danger">*</span></label>
              <select class="form-select" id="planMatriculado" name="idPlanDeEstudio" required onchange="loadCursosMatriculacionMateria()">
                <option value="">Seleccione un plan (según año)</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="cursoMatriculacion" class="form-label">Curso del Plan <span class="text-danger">*</span></label>
              <select class="form-select" id="cursoMatriculacion" name="idCurso" required onchange="loadMateriasMatriculacionMateria()">
                <option value="">Seleccione un curso (según plan)</option>
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="idMateriaMatriculacion" class="form-label">Materia <span class="text-danger">*</span></label>
              <select class="form-select" id="idMateriaMatriculacion" name="idMateriaMatriculacion" required>
                <option value="">Seleccione una materia (según curso)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="fechaMatriculacionMateria" class="form-label">Fecha de Inscripción <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="fechaMatriculacionMateria" name="fechaMatriculacionMateria" value="<?php echo $today; ?>" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="fechaBajaMatriculacionMateriaMatriculacion" class="form-label">Fecha de Baja Inscripción (Opcional)</label>
            <input type="date" class="form-control" id="fechaBajaMatriculacionMateriaMatriculacion" name="fechaBajaMatriculacionMateriaMatriculacion">
          </div>

          <button type="submit" class="btn btn-primary mt-3">Inscribir Materia</button>
        </form>

        <h6 class="mt-4">Inscripciones a Materias Existentes:</h6>

        <!-- NUEVOS FILTROS -->
        <div class="row mb-3 mt-3">
            <div class="col-md-6">
                <label for="filtroPlan" class="form-label">Filtrar por Plan:</label>
                <select class="form-select" id="filtroPlan" onchange="handleFiltroPlanChange()"> <!-- Cambiado el onchange -->
                    <option value="">Todos los Planes</option>
                    <?php
                        // Usamos el array $planesParaFiltro que ya se ha preparado
                        foreach ($planesParaFiltro as $plan): ?>
                        <option value="<?php echo htmlspecialchars($plan['idPlan']); ?>"><?php echo htmlspecialchars($plan['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="filtroCurso" class="form-label">Filtrar por Curso:</label>
                <select class="form-select" id="filtroCurso" onchange="filterTableInscripciones()">
                    <option value="">Todos los Cursos</option>
                    <!-- Las opciones de curso se cargarán dinámicamente -->
                </select>
            </div>
        </div>
        <!-- FIN NUEVOS FILTROS -->

        <?php if (!empty($matriculaciones_materia)): ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover mt-3" id="tablaInscripciones">
            <thead>
              <tr>
                <th>Materia</th>
                <th>Curso</th>
                <th>Plan</th>
                <th>Fec. Insc.</th>
                <th>Estado</th>
                <th>Fec. Baja</th>
                <th>Ciclo Lec.</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($matriculaciones_materia as $mat_materia): ?>
              <tr>
                <td><?php echo htmlspecialchars($mat_materia['nombreMateria']); ?></td>
                <td><?php echo htmlspecialchars($mat_materia['nombreCurso']); ?></td>
                <td><?php echo htmlspecialchars($mat_materia['nombrePlan']); ?></td>
                <td><?php echo htmlspecialchars($mat_materia['fechaMatriculacion']); ?></td>
                <td><?php echo htmlspecialchars($mat_materia['estado']); ?></td>
                <td><?php echo htmlspecialchars($mat_materia['fechaBajaMatriculacion'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($mat_materia['anioCicloLectivo']); ?></td>
                <td>
                  <div class="action-icon-container">
                      <!-- Enlace para eliminar inscripción de materia con modal de confirmación -->
                      <a href="#" onclick='showConfirmDeleteModal("inscripcionMateria.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>&action=delete_matriculacion_materia&idMatriculacionMateria=<?php echo htmlspecialchars($mat_materia['idMatriculacionMateria']); ?>&confirm=yes"); return false;'
                         class="action-icon-link delete-icon" title="Eliminar">
                          <i class="bi bi-trash text-danger" style="color: black !important;"></i>
                      </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <p>No hay inscripciones a materias registradas para este alumno.</p>
        <?php endif; ?>
      </fieldset>
      <a href="matriculacion.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>" class="text-center primary-link">Volver a Matriculación de Plan</a>
    </div>
  </div>
</div>

<?php include '../funciones/footer.html'; ?>

<!-- Modal de Confirmación de Eliminación -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        ¿Está seguro de que desea eliminar esta inscripción de materia y sus registros asociados?
        <br><br>
        <strong class="text-danger">Esta acción no se puede deshacer.</strong>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a id="confirmDeleteButton" href="#" class="btn btn-danger">Eliminar</a>
      </div>
    </div>
  </div>
</div>

<!-- Scripts de JavaScript -->
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../funciones/sessionControl.js"></script>

<!-- Scripts para lógica de la página -->
<script>
  // --- Funciones para la sección de Inscripción a Materias ---

  // Carga los planes de estudio matriculados para un año específico en la sección de inscripción de materias
  function loadPlanesPorAnioInscripcionMateria() {
    var idAlumno = "<?php echo $idAlumno; ?>";
    var anio = $('#anioInscripcionMateria').val();
    var planSelect = $('#planMatriculado');
    planSelect.empty().append('<option value="">Cargando planes...</option>');
    $('#cursoMatriculacion').empty().append('<option value="">Seleccione un plan primero</option>');
    $('#idMateriaMatriculacion').empty().append('<option value="">Seleccione un curso primero</option>');

    if (idAlumno && anio) {
      $.ajax({
        url: 'inscripcionMateria.php', // Asegúrate que la URL sea correcta
        type: 'GET',
        data: { ajax_action: 'get_planes_por_anio', idAlumno: idAlumno, anio: anio },
        dataType: 'json',
        success: function(planes) {
          planSelect.empty().append('<option value="">Seleccione un plan (según año)</option>');
          if (planes && planes.length > 0) {
            $.each(planes, function(i, plan) {
              planSelect.append('<option value="' + plan.idPlan + '">' + plan.nombre + '</option>');
            });
          } else {
            planSelect.append('<option value="">No se encontraron planes matriculados para este año</option>');
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error al cargar planes por año (inscripción materia): " + textStatus, errorThrown);
            planSelect.empty().append('<option value="">Error al cargar planes</option>');
        }
      });
    } else {
      planSelect.empty().append('<option value="">Seleccione el año primero</option>');
    }
  }

  // Carga los cursos según el plan seleccionado para la inscripción a materias
  function loadCursosMatriculacionMateria(selectedCursoId = null) {
    var idPlan = $('#planMatriculado').val();
    var idCursoSelect = $('#cursoMatriculacion');
    idCursoSelect.empty().append('<option value="">Cargando cursos...</option>');
    $('#idMateriaMatriculacion').empty().append('<option value="">Seleccione un curso primero</option>');

    if (idPlan) {
      var anio = $('#anioInscripcionMateria').val();
      $.ajax({
        url: 'inscripcionMateria.php', // Asegúrate que la URL sea correcta
        type: 'GET',
        data: { ajax_action: 'get_cursos', idPlan: idPlan, anio: anio },
        dataType: 'json',
        success: function(cursos) {
          idCursoSelect.empty().append('<option value="">Seleccione un curso</option>');
          $.each(cursos, function(i, curso) {
            idCursoSelect.append('<option value="' + curso.idCurso + '">' + curso.nombre + '</option>');
          });
          // Si se seleccionó un curso previamente para el filtrado, mantenerlo
          if (selectedCursoId) {
            idCursoSelect.val(selectedCursoId);
            // Una vez seleccionado el curso, cargamos las materias
            loadMateriasMatriculacionMateria(null); // Pasamos null porque la materia se seleccionará después
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error al cargar cursos de materia: " + textStatus, errorThrown);
            idCursoSelect.empty().append('<option value="">Error al cargar cursos</option>');
        }
      });
    } else {
      idCursoSelect.empty().append('<option value="">Seleccione un plan primero</option>');
    }
  }

  // Carga las materias según el curso y plan seleccionados para la inscripción a materias
  function loadMateriasMatriculacionMateria(selectedMateriaId = null) {
    var idCurso = $('#cursoMatriculacion').val();
    var idPlan = $('#planMatriculado').val(); // Necesitamos el ID del plan

    var idMateriaMatriculacionSelect = $('#idMateriaMatriculacion');
    idMateriaMatriculacionSelect.empty().append('<option value="">Cargando materias...</option>');

    if (idCurso && idPlan) {
      $.ajax({
        url: 'inscripcionMateria.php', // Asegúrate que la URL sea correcta
        type: 'GET',
        data: { ajax_action: 'get_materias', idCurso: idCurso, idPlan: idPlan }, // PASANDO AMBOS PARÁMETROS
        dataType: 'json',
        success: function(materias) {
          idMateriaMatriculacionSelect.empty().append('<option value="">Seleccione una materia</option>');
          $.each(materias, function(i, materia) {
            // Asegúrate que la respuesta AJAX contenga 'idMateria'
            idMateriaMatriculacionSelect.append('<option value="' + materia.idMateria + '">' + materia.nombreMateria + '</option>');
          });
          if (selectedMateriaId) {
            idMateriaMatriculacionSelect.val(selectedMateriaId);
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error al cargar materias: " + textStatus, errorThrown);
            idMateriaMatriculacionSelect.empty().append('<option value="">Error al cargar materias</option>');
        }
      });
    } else {
      idMateriaMatriculacionSelect.empty().append('<option value="">Seleccione un curso y plan primero</option>');
    }
  }

  // --- FUNCIÓN PARA FILTRAR LA TABLA DE INSCRIPCIONES ---
  function filterTableInscripciones() {
    var selectedPlanValue = $('#filtroPlan').val(); // Valor del <select> (idPlan o vacío)
    var selectedPlanText = $('#filtroPlan option:selected').text(); // Texto visible del <select> (NombrePlan o "Todos los Planes")
    var selectedCurso = $('#filtroCurso').val(); // Valor del <select> (NombreCurso o vacío)
    var $tablaRows = $('#tablaInscripciones tbody tr');

    $tablaRows.show().filter(function() {
      var $fila = $(this);
      var planFilaText = $fila.find('td:nth-child(3)').text(); // Texto del Plan en la fila
      var cursoFilaText = $fila.find('td:nth-child(2)').text(); // Texto del Curso en la fila

      // Comprueba si el plan de la fila coincide con el plan seleccionado en el filtro
      // Si el filtro de plan está vacío ("Todos los Planes"), coincide siempre.
      var coincidePlan = !selectedPlanValue || planFilaText === selectedPlanText;

      // Comprueba si el curso de la fila coincide con el curso seleccionado en el filtro
      // Si el filtro de curso está vacío ("Todos los Cursos"), coincide siempre.
      var coincideCurso = !selectedCurso || cursoFilaText === selectedCurso;

      // La fila se oculta si NO cumple CON AMBOS filtros (o si alguno de los filtros está en "Todos").
      // Por lo tanto, retornamos true para ocultarla si alguna condición no se cumple.
      return !(coincidePlan && coincideCurso);
    }).hide();
  }

  // --- FUNCIÓN PARA CARGAR LOS CURSOS EN EL SELECTOR DE FILTRO ---
  function loadCursosFiltroTabla(idPlan) { // Ya no necesita el año, solo el plan
    var filtroCursoSelect = $('#filtroCurso');
    filtroCursoSelect.empty().append('<option value="">Cargando cursos...</option>');

    if (idPlan) {
      $.ajax({
        url: 'inscripcionMateria.php',
        type: 'GET',
        // Solo pasamos idPlan, ya que buscarCursosPlan no necesita el año
        data: { ajax_action: 'get_cursos_por_plan_filtro', idPlan: idPlan },
        dataType: 'json',
        success: function(cursos) {
          filtroCursoSelect.empty().append('<option value="">Todos los Cursos</option>');
          $.each(cursos, function(i, curso) {
            // Importante: El value debe ser el nombre del curso para que coincida con el texto de la tabla.
            filtroCursoSelect.append('<option value="' + curso.nombre + '">' + curso.nombre + '</option>');
          });
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error al cargar cursos para filtro: " + textStatus, errorThrown);
            filtroCursoSelect.empty().append('<option value="">Error al cargar cursos</option>');
        }
      });
    } else {
      filtroCursoSelect.empty().append('<option value="">Seleccione un Plan primero</option>');
    }
  }

  // --- NUEVO MANEJADOR PARA EL CAMBIO EN FILTRO PLAN ---
  function handleFiltroPlanChange() {
      var selectedPlanValue = $('#filtroPlan').val();
      loadCursosFiltroTabla(selectedPlanValue); // Llamamos a la función para cargar los cursos
      filterTableInscripciones(); // Y aplicamos el filtro (que al principio solo tendrá "Todos los Cursos")
  }


  // --- Inicialización al cargar el documento ---
  $(document).ready(function() {
    // Inicialización para la carga en cascada de planes/cursos/materias para INSCRIPCIÓN A MATERIAS
    var initialAnioInscripcion = $('#anioInscripcionMateria').val();
    if (initialAnioInscripcion) {
        loadPlanesPorAnioInscripcionMateria();
    }

    // Al cambiar el plan en el filtro, actualizamos las opciones de curso y aplicamos el filtro
    $('#filtroPlan').on('change', handleFiltroPlanChange); // Usamos la nueva función manejadora

    // Al cambiar el curso en el filtro, aplicamos el filtro
    $('#filtroCurso').on('change', function() {
        filterTableInscripciones();
    });

    // Configurar el botón de confirmación del modal para que redirija a la URL correcta
    $('#confirmDeleteModal .btn-danger').on('click', function() {
      if (deleteUrl) {
        window.location.href = deleteUrl;
      }
    });

    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });

</script>

</body>
</html>