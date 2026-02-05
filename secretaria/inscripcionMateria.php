<?php
// Incluir el script de verificación de sesión
include '../funciones/verificarSesion.php';
// --- NUEVO INCLUDE PARA CONTROL DE CORRELATIVIDAD ---
include '../funciones/controlCorrelatividad.php';

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
        $idPlan = $_GET['idPlan'] ?? null;
        $materias = [];
        if (!is_null($idCurso) && !is_null($idPlan)) {
            $materias = materiasPlanCurso($conn, $idPlan, $idCurso);
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
    // ENDPOINT PARA OBTENER LAS MATRICULACIONES FILTRADAS DE LA TABLA
    elseif ($_GET['ajax_action'] == 'get_matriculaciones_filtradas') {
        $idAlumno = $_GET['idAlumno'] ?? null;
        $idPlan = $_GET['idPlan'] ?? null;
        $idCurso = $_GET['idCurso'] ?? null;
        $matriculaciones_filtradas = [];

        if ($idAlumno) {
            $matriculaciones_filtradas = obtenerMatriculacionesMateriaAlumno($conn, $idAlumno, $idPlan ?: null, $idCurso ?: null);
        }
        echo json_encode($matriculaciones_filtradas);
        exit;
    }
    // ENDPOINT PARA OBTENER LAS CONDICIONES DE CURSADO (ESTADOS)
    elseif ($_GET['ajax_action'] == 'get_condiciones_cursado') {
        $condiciones = obtenerCondicionesCursado($conn);
        echo json_encode($condiciones);
        exit;
    }
    // --- FIN DE CASOS AJAX ---
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
    $idMateriaSeleccionada = $_POST['idMateriaMatriculacion'] ?? null;
    $idCurso = $_POST['hiddenCursoId'] ?? null;
    $idPlan = $_POST['hiddenPlanId'] ?? null;
    
    $fechaMatriculacionMateria = $_POST['fechaMatriculacionMateria'] ?? null;
    $estadoMatriculacionMateria = $_POST['estadoMatriculacionMateria'] ?? null;

    $materiasAInscribir = [];
    $mensajesExito = [];
    $mensajesError = [];

    // Determinar qué materias se van a inscribir
    if ($idMateriaSeleccionada == 'all') {
        if ($idPlan && $idCurso) {
            $materiasAInscribir = materiasPlanCurso($conn, $idPlan, $idCurso);
        }
    } elseif ($idMateriaSeleccionada) {
        $stmt_unico = $conn->prepare("SELECT idUnicoMateria, nombre FROM materiaterciario WHERE idMateria = ?");
        $stmt_unico->bind_param("i", $idMateriaSeleccionada);
        $stmt_unico->execute();
        $result_unico = $stmt_unico->get_result();
        $materiaData = $result_unico->fetch_assoc();
        if($materiaData){
            $materiasAInscribir[] = [
                'idMateria' => $idMateriaSeleccionada,
                'idUnicoMateria' => $materiaData['idUnicoMateria'],
                'nombreMateria' => $materiaData['nombre']
            ];
        }
    }

    if (empty($materiasAInscribir) || empty($fechaMatriculacionMateria) || empty($estadoMatriculacionMateria)) {
        $message = "Error: No se seleccionaron materias o faltan datos requeridos (Fecha, Estado).";
        $message_type = 'danger';
    } else {
        $conn->begin_transaction();
        
        foreach ($materiasAInscribir as $materia) {
           if (controlCorrelatividades($materia['idUnicoMateria'], $idAlumno, 0)) {
    
    // Capturamos el año seleccionado en el combo (NO la fecha actual)
    $anioSeleccionado = $_POST['anioInscripcionMateria'] ?? date('Y');

    $matriculacion_materia_data = [
        'idAlumno' => $idAlumno,
        'idMateria' => $materia['idMateria'],
        'fechaMatriculacionMateria' => $fechaMatriculacionMateria,
        'fechaBajaMatriculacionMateria' => $_POST['fechaBajaMatriculacionMateriaMatriculacion'] ?? null,
        'estadoMatriculacionMateria' => $estadoMatriculacionMateria,
        'anio' => $anioSeleccionado // <--- AGREGAMOS ESTE DATO IMPORTANTE
    ];
    
    // Llamamos a la función corregida
    if (insertarMatriculacionMateria($conn, $matriculacion_materia_data)) {
        
        // Ahora buscamos el ID de ciclo para inicializar asistencias
        // Nota: buscarIdCiclo ya lo usará insertarMatriculacionMateria internamente,
        // pero lo necesitamos aquí para la asistencia.
        $idCicloLectivo = buscarIdCiclo($conn, $anioSeleccionado);
                    if ($idCicloLectivo) {
                        inicializarAsistenciaMateria($conn, $idAlumno, $materia['idMateria'], $idCicloLectivo);
                        inicializarCalificacionMateria($conn, $idAlumno, $materia['idMateria']);
                        $mensajesExito[] = "Inscripción exitosa en: " . htmlspecialchars($materia['nombreMateria']);
                    } else {
                        $mensajesError[] = "Error al inscribir en '" . htmlspecialchars($materia['nombreMateria']) . "': No se pudo determinar el Ciclo Lectivo.";
                    }
                } else {
                    $mensajesError[] = "Error al guardar la inscripción para: " . htmlspecialchars($materia['nombreMateria']);
                }
            } else {
                global $materiasAdeuda;
                $mensajesError[] = "No se puede inscribir en <strong>" . htmlspecialchars($materia['nombreMateria']) . "</strong>. El alumno debe: " . htmlspecialchars($materiasAdeuda);
            }
        }

        if (empty($mensajesError)) {
            $conn->commit();
            $message_type = 'success';
            $message = implode("<br>", $mensajesExito);
        } else {
            $conn->rollback();
            $message_type = 'danger';
            $message = implode("<br>", $mensajesError);
            if(!empty($mensajesExito)){
                 $message .= "<br><br><strong>Inscripciones exitosas (revertidas debido a errores):</strong><br>" . implode("<br>", $mensajesExito);
            }
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
        $matriculacion_materia_data = obtenerDetallesMatriculacionMateria($conn, $idMatriculacionMateria);

        if ($matriculacion_materia_data) {
            if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
                $conn->begin_transaction();
                $delete_success = true;

                if (!eliminarAsistenciaMateria($conn, $matriculacion_materia_data['idAlumno'], $matriculacion_materia_data['idMateria'], $matriculacion_materia_data['idCicloLectivo'])) {
                    $message = "Error al eliminar registros de asistencia para la materia.";
                    $message_type = 'danger';
                    $delete_success = false;
                }
                if ($delete_success && !eliminarCalificacionMateria($conn, $matriculacion_materia_data['idAlumno'], $matriculacion_materia_data['idMateria'])) {
                    $message = "Error al eliminar registro de calificación para la materia.";
                    $message_type = 'danger';
                    $delete_success = false;
                }
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


// Tabla de inscripciones se carga dinámicamente por AJAX
$matriculaciones_materia = [];


// Obtener el último año de ciclo lectivo para el select de año principal
$ultimoCiclo = obtenerUltimoCicloLectivo($conn);
$ultimoAnio = $ultimoCiclo ? $ultimoCiclo['anio'] : date('Y');

// Obtener los planes en los que el alumno está matriculado para el filtro de planes
$planesMatriculadosAlumno = obtenerMatriculacionesPlanAlumno($conn, $idAlumno);
$planesParaFiltro = [];
$planesYaAgregados = []; 

foreach ($planesMatriculadosAlumno as $matriculacion) {
    $idPlan = $matriculacion['idPlanDeEstudio'];
    $nombrePlan = $matriculacion['nombrePlan'];
    if (!in_array($idPlan, $planesYaAgregados)) {
        $planesParaFiltro[] = ['idPlan' => $idPlan, 'nombre' => $nombrePlan];
        $planesYaAgregados[] = $idPlan;
    }
}
usort($planesParaFiltro, function($a, $b) {
    return strcmp($a['nombre'], $b['nombre']);
});
// Guardar el primer plan para la preselección
$primerPlanId = !empty($planesParaFiltro) ? $planesParaFiltro[0]['idPlan'] : null;

// Fecha actual para campos de fecha por defecto
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inscripción de Materias - Secretaría</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
      <li class="breadcrumb-item"><a href="buscarAlumno.php?origin=inscripcionMateria">Buscar Alumno</a></li>
      <li class="breadcrumb-item active">Inscripción de Materias</li>
    </ol>

    <?php if ($message): ?>
      <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
        <?php echo $message; // No escapar HTML para que <br> funcione ?>
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
          <input type="hidden" id="hiddenPlanId" name="hiddenPlanId">
          <input type="hidden" id="hiddenCursoId" name="hiddenCursoId">

          <div class="row mb-3">
            <div class="col-md-4">
              <label for="anioInscripcionMateria" class="form-label">Ciclo lectivo <span class="text-danger">*</span></label>
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
              <label for="planMatriculado" class="form-label">Plan de Estudio<span class="text-danger">*</span></label>
              <select class="form-select" id="planMatriculado" name="idPlanDeEstudio" required onchange="loadCursosMatriculacionMateria()">
                <option value="">Seleccione un plan</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="cursoMatriculacion" class="form-label">Curso <span class="text-danger">*</span></label>
              <select class="form-select" id="cursoMatriculacion" name="idCurso" required onchange="loadMateriasMatriculacionMateria()">
                <option value="">Seleccione un curso</option>
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-5">
              <label for="idMateriaMatriculacion" class="form-label">Materia <span class="text-danger">*</span></label>
              <select class="form-select" id="idMateriaMatriculacion" name="idMateriaMatriculacion" required>
                <option value="">Seleccione una materia</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="estadoMatriculacionMateria" class="form-label">Estado de Inscripción <span class="text-danger">*</span></label>
              <select class="form-select" id="estadoMatriculacionMateria" name="estadoMatriculacionMateria" required>
                <option value="">Seleccione un estado</option>
              </select>
            </div>
            <div class="col-md-3">
              <label for="fechaMatriculacionMateria" class="form-label">Fecha de Inscripción <span class="text-danger">*</span></label>
              <input type="date" class="form-control form-select" id="fechaMatriculacionMateria" name="fechaMatriculacionMateria" value="<?php echo $today; ?>" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="fechaBajaMatriculacionMateriaMatriculacion" class="form-label">Fecha de Baja Inscripción (Opcional)</label>
            <input type="date" class="form-control" id="fechaBajaMatriculacionMateriaMatriculacion" name="fechaBajaMatriculacionMateriaMatriculacion">
          </div>

          <button type="submit" class="btn btn-primary mt-3">Inscribir Materia</button>
        </form>

        <h6 class="mt-4">Inscripciones a Materias Existentes:</h6>

        <div class="row mb-3 mt-3">
            <div class="col-md-6">
                <label for="filtroPlan" class="form-label">Filtrar por Plan:</label>
                <select class="form-select" id="filtroPlan" onchange="handleFiltroPlanChange()">
                    <?php if (empty($planesParaFiltro)): ?>
                        <option value="">No hay planes matriculados</option>
                    <?php else: ?>
                        <?php foreach ($planesParaFiltro as $plan): ?>
                            <option value="<?php echo htmlspecialchars($plan['idPlan']); ?>" <?php echo ($plan['idPlan'] == $primerPlanId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($plan['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="filtroCurso" class="form-label">Filtrar por Curso:</label>
                <select class="form-select" id="filtroCurso" onchange="filterTableInscripciones()">
                    <option value="">Todos los Cursos</option>
                </select>
            </div>
        </div>
        
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
              <!-- Las filas se cargarán aquí mediante AJAX con los filtros -->
            </tbody>
          </table>
        </div>

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
  var deleteUrl = '';
  var currentPlanValueForCoursePopulate = '';

  // Carga los planes para el formulario de inscripción
  function loadPlanesPorAnioInscripcionMateria() {
    var idAlumno = "<?php echo $idAlumno; ?>";
    var anio = $('#anioInscripcionMateria').val();
    var planSelect = $('#planMatriculado');
    planSelect.empty().append('<option value="">Cargando planes...</option>');
    $('#cursoMatriculacion').empty().append('<option value="">Seleccione un plan primero</option>');
    $('#idMateriaMatriculacion').empty().append('<option value="">Seleccione un curso primero</option>');

    if (idAlumno && anio) {
      $.ajax({
        url: 'inscripcionMateria.php',
        type: 'GET',
        data: { ajax_action: 'get_planes_por_anio', idAlumno: idAlumno, anio: anio },
        dataType: 'json',
        success: function(planes) {
          planSelect.empty().append('<option value="">Seleccione un plan</option>');
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

  // Carga los cursos para el formulario de inscripción
  function loadCursosMatriculacionMateria(selectedCursoId = null) {
    var idPlan = $('#planMatriculado').val();
    var idCursoSelect = $('#cursoMatriculacion');
    idCursoSelect.empty().append('<option value="">Cargando cursos...</option>');
    $('#idMateriaMatriculacion').empty().append('<option value="">Seleccione un curso primero</option>');

    if (idPlan) {
      var anio = $('#anioInscripcionMateria').val();
      $.ajax({
        url: 'inscripcionMateria.php',
        type: 'GET',
        data: { ajax_action: 'get_cursos', idPlan: idPlan, anio: anio },
        dataType: 'json',
        success: function(cursos) {
          idCursoSelect.empty().append('<option value="">Seleccione un curso</option>');
          $.each(cursos, function(i, curso) {
            idCursoSelect.append('<option value="' + curso.idCurso + '">' + curso.nombre + '</option>');
          });
          if (selectedCursoId) {
            idCursoSelect.val(selectedCursoId);
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

  // Carga las materias para el formulario de inscripción
  function loadMateriasMatriculacionMateria(selectedMateriaId = null) {
    var idCurso = $('#cursoMatriculacion').val();
    var idPlan = $('#planMatriculado').val();
    
    $('#hiddenPlanId').val(idPlan);
    $('#hiddenCursoId').val(idCurso);

    var idMateriaMatriculacionSelect = $('#idMateriaMatriculacion');
    idMateriaMatriculacionSelect.empty().append('<option value="">Cargando materias...</option>');

    if (idCurso && idPlan) {
      $.ajax({
        url: 'inscripcionMateria.php',
        type: 'GET',
        data: { ajax_action: 'get_materias', idCurso: idCurso, idPlan: idPlan },
        dataType: 'json',
        success: function(materias) {
          idMateriaMatriculacionSelect.empty().append('<option value="">Seleccione una materia</option>');
          if (materias.length > 0) {
              idMateriaMatriculacionSelect.append('<option value="all" style="font-weight: bold; background-color: #e9ecef;">-- Inscribir a Todas --</option>');
          }
          $.each(materias, function(i, materia) {
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

  // --- Funciones para el Filtro de la Tabla de Inscripciones ---
  function populateFiltroCursos(matriculaciones, keepCurrentValue = true) {
    var filtroCursoSelect = $('#filtroCurso');
    var currentValue = keepCurrentValue ? filtroCursoSelect.val() : '';

    filtroCursoSelect.empty().append('<option value="">Todos los Cursos</option>');

    if (matriculaciones && matriculaciones.length > 0) {
      var uniqueCourses = {};
      $.each(matriculaciones, function(i, mat) {
        if (!uniqueCourses[mat.idCursoFK]) {
          uniqueCourses[mat.idCursoFK] = { nombre: mat.nombreCurso, anio: mat.anioCicloLectivo };
        }
      });

      var sortedCourses = Object.entries(uniqueCourses).sort(([, dataA], [, dataB]) => {
          if (dataA.anio !== dataB.anio) return dataA.anio - dataB.anio;
          return dataA.nombre.localeCompare(dataB.nombre);
      });

      $.each(sortedCourses, function(index, [idCurso, data]) {
        var selectedAttribute = (keepCurrentValue && idCurso == currentValue) ? 'selected' : '';
        filtroCursoSelect.append('<option value="' + idCurso + '" ' + selectedAttribute + '>' + data.nombre + ' (' + data.anio + ')</option>');
      });
    }
  }

  function filterTableInscripciones() {
    var selectedPlanValue = $('#filtroPlan').val();
    var selectedCursoValue = $('#filtroCurso').val();
    var idAlumno = "<?php echo $idAlumno; ?>";

    if (!selectedPlanValue) {
        $('#tablaInscripciones tbody').empty();
        $('#filtroCurso').empty().append('<option value="">Todos los Cursos</option>');
        currentPlanValueForCoursePopulate = '';
        return;
    }

    $.ajax({
        url: 'inscripcionMateria.php',
        type: 'GET',
        data: {
            ajax_action: 'get_matriculaciones_filtradas',
            idAlumno: idAlumno,
            idPlan: selectedPlanValue || null,
            idCurso: selectedCursoValue || null
        },
        dataType: 'json',
        success: function(matriculaciones) {
            var $tbody = $('#tablaInscripciones tbody');
            $tbody.empty();

            if (matriculaciones && matriculaciones.length > 0) {
                if (selectedPlanValue && selectedPlanValue !== currentPlanValueForCoursePopulate) {
                    populateFiltroCursos(matriculaciones, true);
                    currentPlanValueForCoursePopulate = selectedPlanValue;
                } else if (!selectedPlanValue) {
                    populateFiltroCursos(matriculaciones, true);
                    currentPlanValueForCoursePopulate = '';
                }

                $.each(matriculaciones, function(i, mat) {
                    var fechaBaja = mat.fechaBajaMatriculacion ? mat.fechaBajaMatriculacion : '-';
                    var row = `<tr><td>${mat.nombreMateria}</td><td>${mat.nombreCurso}</td><td>${mat.nombrePlan}</td><td>${mat.fechaMatriculacion}</td><td>${mat.estado}</td><td>${fechaBaja}</td><td>${mat.anioCicloLectivo}</td><td><div class="action-icon-container"><a href="#" onclick='showConfirmDeleteModal("inscripcionMateria.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>&action=delete_matriculacion_materia&idMatriculacionMateria=${mat.idMatriculacionMateria}&confirm=yes"); return false;' class="action-icon-link delete-icon" title="Eliminar"><i class="bi bi-trash text-danger" style="color: black !important;"></i></a></div></td></tr>`;
                    $tbody.append(row);
                });
            } else {
                $tbody.append('<tr><td colspan="8">No se encontraron inscripciones con los filtros seleccionados.</td></tr>');
                if (selectedPlanValue && selectedPlanValue !== currentPlanValueForCoursePopulate) {
                   $('#filtroCurso').empty().append('<option value="">Todos los Cursos</option>');
                }
                currentPlanValueForCoursePopulate = selectedPlanValue;
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error al filtrar tabla de inscripciones: " + textStatus, errorThrown);
            $('#tablaInscripciones tbody').html('<tr><td colspan="8">Error al cargar datos de inscripciones.</td></tr>');
        }
    });
  }

  function handleFiltroPlanChange() {
      var selectedPlanValue = $('#filtroPlan').val();
      if (!selectedPlanValue) {
          $('#filtroCurso').empty().append('<option value="">Todos los Cursos</option>');
          $('#tablaInscripciones tbody').empty();
          currentPlanValueForCoursePopulate = '';
      } else {
          $('#filtroCurso').val(''); 
          filterTableInscripciones();
      }
  }

  function showConfirmDeleteModal(url) {
      deleteUrl = url;
      $('#confirmDeleteModal').modal('show');
  }

  // --- Inicialización al cargar el documento ---
  $(document).ready(function() {
    // Cargar los planes para el formulario de inscripción al iniciar la página
    var initialAnioInscripcion = $('#anioInscripcionMateria').val();
    if (initialAnioInscripcion) {
        loadPlanesPorAnioInscripcionMateria();
    }
    
    // Filtro inicial al cargar la página con el plan preseleccionado
    handleFiltroPlanChange();

    $('#filtroPlan').on('change', handleFiltroPlanChange);
    $('#filtroCurso').on('change', function() {
        filterTableInscripciones();
    });

    // Cargar los estados disponibles para el select de estado de inscripción
    $.ajax({
        url: 'inscripcionMateria.php',
        type: 'GET',
        data: { ajax_action: 'get_condiciones_cursado' },
        dataType: 'json',
        success: function(condiciones) {
            var estadoSelect = $('#estadoMatriculacionMateria');
            estadoSelect.empty().append('<option value="">Seleccione un estado</option>');
            $.each(condiciones, function(i, condicion) {
                estadoSelect.append('<option value="' + condicion.idCondicion + '">' + condicion.condicion + '</option>');
            });
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error al cargar estados de inscripción: " + textStatus, errorThrown);
            $('#estadoMatriculacionMateria').empty().append('<option value="">Error al cargar estados</option>');
        }
    });

    $('#confirmDeleteModal .btn-danger').on('click', function() {
      if (deleteUrl) {
        window.location.href = deleteUrl;
      }
    });

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });

</script>

</body>
</html>