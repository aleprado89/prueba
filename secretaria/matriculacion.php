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
            $planes = obtenerPlanesDeEstudio($conn);
        }
        echo json_encode($planes);
        exit;
    }
}
// --- Fin Lógica para manejar peticiones AJAX ---


$idAlumno = $_GET['idAlumno'] ?? null;
if (!$idAlumno) {
    $_SESSION['message_matriculacion'] = ['text' => 'ID de alumno no proporcionado para matriculación.', 'type' => 'danger'];
    header('Location: buscarAlumno.php?origin=matriculacion');
    exit;
}

$alumnoData = obtenerDatosAlumno($conn, $idAlumno);
if (!$alumnoData) {
    $_SESSION['message_matriculacion'] = ['text' => 'Alumno no encontrado.', 'type' => 'danger'];
    header('Location: buscarAlumno.php?origin=matriculacion');
    exit;
}

$message = '';
$message_type = '';

// Procesamiento de formulario de Matriculación Plan/Curso (INSERT / UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_matriculacion_plan') {
    $matriculacion_data = [
        'idAlumno' => $idAlumno,
        'idPlanDeEstudio' => $_POST['idPlanDeEstudio'] ?? null,
        'idCurso' => $_POST['idCurso'] ?? null,
        'fechaMatriculacion' => $_POST['fechaMatriculacion'] ?? null,
        'fechaBajaMatriculacion' => $_POST['fechaBajaMatriculacion'] ?? null,
        'pagoMatricula' => isset($_POST['pagoMatricula']) ? 1 : 0,
        'pagoMonto' => $_POST['pagoMonto'] ?? null,
        'certificadoSalud' => 0,
        'certificadoTrabajo' => isset($_POST['certificadoTrabajo']) ? 1 : 0,
        'tarde' => 0,
        'anio' => $_POST['anioMatriculacion'] ?? date('Y') // Guarda el año seleccionado
    ];

    $idMatriculacionToUpdate = $_POST['idMatriculacion'] ?? null;

    if (empty($matriculacion_data['idPlanDeEstudio']) || empty($matriculacion_data['idCurso'] )) {
        $message = "Error: Plan de Estudio y Curso son campos requeridos para la matriculación.";
        $message_type = 'danger';
    } elseif (empty($matriculacion_data['fechaMatriculacion'])) {
        $message = "Error: La Fecha de Matriculación es un campo requerido.";
        $message_type = 'danger';
    } elseif (empty($matriculacion_data['anio'])) {
        $message = "Error: El Año de Matriculación es un campo requerido.";
        $message_type = 'danger';
    }
    else {
        if ($idMatriculacionToUpdate) {
            if (actualizarMatriculacionPlan($conn, $idMatriculacionToUpdate, $matriculacion_data)) {
                $message = "Matriculación de plan/curso actualizada correctamente.";
                $message_type = 'success';
            } else {
                $message = "Error al actualizar la matriculación de plan/curso: " . $conn->error;
                $message_type = 'danger';
            }
        } else {
            if (insertarMatriculacionPlan($conn, $matriculacion_data)) {
                $message = "Matriculación de plan/curso guardada correctamente.";
                $message_type = 'success';
            } else {
                $message = "Error al guardar la matriculación de plan/curso: " . $conn->error;
                $message_type = 'danger';
            }
        }
    }
    $_SESSION['message_matriculacion'] = ['text' => $message, 'type' => $message_type];
    header('Location: matriculacion.php?idAlumno=' . $idAlumno);
    exit;
}

// Procesamiento de eliminación de Matriculación Plan/Curso
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'delete_matriculacion_plan') {
    $idMatriculacion = $_GET['idMatriculacion'] ?? null;
    if ($idMatriculacion) {
        if (eliminarMatriculacionPlan($conn, $idMatriculacion)) {
            $message = "Matriculación de plan/curso eliminada correctamente.";
            $message_type = 'success';
        } else {
            $message = "Error al eliminar la matriculación de plan/curso.";
            $message_type = 'danger';
        }
    } else {
        $message = "ID de matriculación no proporcionado para eliminar.";
        $message_type = 'warning';
    }
    $_SESSION['message_matriculacion'] = ['text' => $message, 'type' => $message_type];
    header('Location: matriculacion.php?idAlumno=' . $idAlumno);
    exit;
}


// Recuperar mensaje de sesión si existe
if (isset($_SESSION['message_matriculacion'])) {
    $message = $_SESSION['message_matriculacion']['text'];
    $message_type = $_SESSION['message_matriculacion']['type'];
    unset($_SESSION['message_matriculacion']);
}


// Datos para los dropdowns
$planes_generales = obtenerPlanesDeEstudio($conn); // Planes generales para el primer formulario
$matriculaciones_plan = obtenerMatriculacionesPlanAlumno($conn, $idAlumno);

// Obtener el último año de ciclo lectivo para el select de año principal
$ultimoCiclo = obtenerUltimoCicloLectivo($conn);
$ultimoAnio = $ultimoCiclo ? $ultimoCiclo['anio'] : date('Y');

// Fecha actual para campos de fecha por defecto
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Matriculación - Secretaría</title>
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
    console.log("INJECTED window.usuarioActual (en matriculacion):", window.usuarioActual);
</script>

<?php include '../funciones/menu_secretaria.php'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
      <li class="breadcrumb-item"><a href="buscarAlumno.php?origin=matriculacion">Buscar alumno</a></li>
      <li class="breadcrumb-item active">Matriculación alumno</li>
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

      <!-- Bloque de Matriculación a Plan y Curso -->
      <fieldset class="mb-4 p-3 border rounded">
        <legend class="float-none w-auto px-2">Matriculación a Plan y Curso</legend>
        <form id="matriculacionPlanForm" method="POST" action="matriculacion.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>">
          <input type="hidden" name="action" value="save_matriculacion_plan">

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="anioMatriculacion" class="form-label">Año de matriculación <span class="text-danger">*</span></label>
              <select class="form-select" id="anioMatriculacion" name="anioMatriculacion" required onchange="loadPlanesPorAnio()">
                <option value="">Seleccione el año</option>
                <?php
                $currentYear = date('Y');
                for ($i = $currentYear; $i >= $currentYear - 10; $i--): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($i == $ultimoAnio) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="idPlanDeEstudio" class="form-label">Plan de estudio <span class="text-danger">*</span></label>
              <select class="form-select" id="idPlanDeEstudio" name="idPlanDeEstudio" required onchange="loadCursos()">
                <option value="">Seleccione un plan de estudio </option>
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="idCurso" class="form-label">Curso <span class="text-danger">*</span></label>
              <select class="form-select" id="idCurso" name="idCurso" required>
                <option value="">Seleccione un curso</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="fechaMatriculacion" class="form-label">Fecha de matriculación <span class="text-danger">*</span></label>
              <input type="date" class="form-control form-select" id="fechaMatriculacion" name="fechaMatriculacion" value="<?php echo $today; ?>" required>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="fechaBajaMatriculacion" class="form-label">Fecha de baja matriculación (Opcional)</label>
              <input type="date" class="form-control" id="fechaBajaMatriculacion" name="fechaBajaMatriculacion">
              <div class="form-text">Si se carga, la matriculación se registrará como "De Baja"</div>
            </div>
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="pagoMatricula" name="pagoMatricula" value="1" onchange="togglePagoMonto()">
                <label class="form-check-label" for="pagoMatricula"> Pago matrícula</label>
              </div>
              <input type="number" step="0.01" class="form-control mt-2" id="pagoMonto" name="pagoMonto" placeholder="Monto abonado" style="display: none;">
            </div>
          </div>

          <div class="mb-3 form-check">
            <input class="form-check-input" type="checkbox" id="certificadoTrabajo" name="certificadoTrabajo" value="1">
            <label class="form-check-label" for="certificadoTrabajo">% Asistencia reducida (Certificado Salud/Trabajo)</label>
          </div>
          <input type="hidden" name="tarde" value="0">
          <input type="hidden" name="certificadoSalud" value="0">

          <button type="submit" class="btn btn-primary mt-3">Guardar matriculación plan</button>
        </form>

        <h6 class="mt-4">Matriculaciones de plan existentes:</h6>
        <?php if (!empty($matriculaciones_plan)): ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover mt-3">
            <thead>
              <tr>
                <th>Plan</th>
                <th>Curso</th>
                <th>Fecha matr.</th>
                <th>Pago matr.</th>
                <th>Monto</th>
                <th>Fec. baja</th>
                <th>Asist. red.</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($matriculaciones_plan as $mat_plan): ?>
              <tr>
                <td><?php echo htmlspecialchars($mat_plan['nombrePlan']); ?></td>
                <td><?php echo htmlspecialchars($mat_plan['nombreCurso']); ?></td>
                <td><?php echo htmlspecialchars($mat_plan['fechaMatriculacion']); ?></td>
                <td><?php echo ($mat_plan['pagoMatricula'] ? 'Sí' : 'No'); ?></td>
                <td><?php echo htmlspecialchars($mat_plan['pagoMonto'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($mat_plan['fechaBajaMatriculacion'] ?? '-'); ?></td>
                <td><?php echo ($mat_plan['certificadoTrabajo'] ? 'Sí' : 'No'); ?></td>
                <td>
                  <div class="action-icon-container">
                    <div class="row g-0">
                      <div class="col-6 text-center">
                          <a href="#" onclick='showEditMatriculacionModal(<?php echo json_encode($mat_plan); ?>); return false;'
                             class="action-icon-link edit-icon" title="Editar">
                              <i class="bi bi-pencil-square text-warning" style="color: black !important;"></i>
                          </a>
                      </div>
                      <div class="col-6 text-center">
                          <a href="matriculacion.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>&action=delete_matriculacion_plan&idMatriculacion=<?php echo htmlspecialchars($mat_plan['idMatriculacion']); ?>"
                             class="action-icon-link delete-icon" onclick="return confirm('¿Está seguro de eliminar esta matriculación de plan?');" title="Eliminar">
                              <i class="bi bi-trash text-danger" style="color: black !important;"></i>
                          </a>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <p>No hay matriculaciones de plan registradas para este alumno.</p>
        <?php endif; ?>
      </fieldset>
      <a href="inscripcionMateria.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>" class="text-center primary-link">Ir a Inscripción de Materias</a>
    </div>
  </div>
</div>

<?php include '../funciones/footer.html'; ?>

<!-- Modal de Edición de Matriculación de Plan/Curso -->
<div class="modal fade" id="editMatriculacionModal" tabindex="-1" aria-labelledby="editMatriculacionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editMatriculacionModalLabel">Editar matriculación de plan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editMatriculacionForm" method="POST" action="matriculacion.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>">
        <input type="hidden" name="action" value="save_matriculacion_plan">
        <input type="hidden" name="idMatriculacion" id="edit_idMatriculacion">
        <input type="hidden" name="idAlumno" value="<?php echo htmlspecialchars($idAlumno); ?>">

        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_anioMatriculacion" class="form-label">Año de matriculación</label>
            <select class="form-select" id="edit_anioMatriculacion" name="anioMatriculacion" required onchange="loadCursosEditModal()">
              <option value="">Seleccione el año</option>
              <?php
              $currentYear = date('Y');
              for ($i = $currentYear; $i >= $currentYear - 10; $i--): ?>
                  <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_idPlanDeEstudio" class="form-label">Plan de estudio</label>
            <select class="form-select" id="edit_idPlanDeEstudio" name="idPlanDeEstudio" required onchange="loadCursosEditModal()">
              <option value="">Seleccione un plan</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_idCurso" class="form-label">Curso</label>
            <select class="form-select" id="edit_idCurso" name="idCurso" required>
              <option value="">Seleccione un curso</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_fechaMatriculacion" class="form-label">Fecha de matriculación</label>
            <input type="date" class="form-control" id="edit_fechaMatriculacion" name="fechaMatriculacion" required>
          </div>
          <div class="mb-3">
            <label for="edit_fechaBajaMatriculacion" class="form-label">Fecha de baja (Opcional)</label>
            <input type="date" class="form-control" id="edit_fechaBajaMatriculacion" name="fechaBajaMatriculacion">
          </div>
          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="edit_pagoMatricula" name="pagoMatricula" value="1" onchange="togglePagoMontoEditModal()">
            <label class="form-check-label" for="edit_pagoMatricula">Pago matrícula</label>
          </div>
          <div class="mb-3">
            <input type="number" step="0.01" class="form-control" id="edit_pagoMonto" name="pagoMonto" placeholder="Monto abonado" style="display: none;">
          </div>
          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="edit_certificadoTrabajo" name="certificadoTrabajo" value="1">
            <label class="form-check-label" for="edit_certificadoTrabajo">Asistencia reducida (Certificado Salud/Trabajo)</label>
          </div>
          <input type="hidden" name="tarde" value="0">
          <input type="hidden" name="certificadoSalud" value="0">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>
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
  // Lógica para mostrar/ocultar el monto de pago para el formulario principal
  function togglePagoMonto() {
    var pagoMatriculaCheckbox = $('#pagoMatricula');
    var pagoMontoInput = $('#pagoMonto');
    if (pagoMatriculaCheckbox.is(':checked')) {
      pagoMontoInput.show();
      pagoMontoInput.prop('required', true);
    } else {
      pagoMontoInput.hide();
      pagoMontoInput.prop('required', false);
      pagoMontoInput.val('');
    }
  }

  // --- Funciones para la sección de Matriculación Plan/Curso ---

  // Carga los planes de estudio para un año seleccionado en el primer formulario
  function loadPlanesPorAnio() {
    var idAlumno = "<?php echo $idAlumno; ?>";
    var anio = $('#anioMatriculacion').val();
    var planSelect = $('#idPlanDeEstudio');
    planSelect.empty().append('<option value="">Cargando planes...</option>');
    $('#idCurso').empty().append('<option value="">Seleccione un plan primero</option>'); // Limpiar cursos

    if (idAlumno && anio) {
      $.ajax({
        url: 'matriculacion.php',
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
            console.error("Error al cargar planes por año: " + textStatus, errorThrown);
            planSelect.empty().append('<option value="">Error al cargar planes</option>');
        }
      });
    } else {
      planSelect.empty().append('<option value="">Seleccione el año primero</option>');
    }
  }

  // Carga los cursos según el plan seleccionado para la matriculación de plan
  function loadCursos(selectedCursoId = null) {
    var idPlan = $('#idPlanDeEstudio').val();
    var idCursoSelect = $('#idCurso');
    idCursoSelect.empty().append('<option value="">Cargando cursos...</option>');

    if (idPlan) {
      var anio = $('#anioMatriculacion').val(); // Obtener el año seleccionado
      $.ajax({
        url: 'matriculacion.php',
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
            console.error("Error al cargar cursos: " + textStatus, errorThrown);
            idCursoSelect.empty().append('<option value="">Error al cargar cursos</option>');
        }
      });
    } else {
      idCursoSelect.empty().append('<option value="">Seleccione un plan primero</option>');
    }
  }

  // --- Funciones para el Modal de Edición de Matriculación de Plan ---
  function showEditMatriculacionModal(matriculacionData) {
    $('#edit_idMatriculacion').val(matriculacionData.idMatriculacion);
    $('#edit_fechaMatriculacion').val(matriculacionData.fechaMatriculacion);
    $('#edit_fechaBajaMatriculacion').val(matriculacionData.fechaBajaMatriculacion);

    $('#edit_pagoMatricula').prop('checked', matriculacionData.pagoMatricula == 1);
    $('#edit_pagoMonto').val(matriculacionData.pagoMonto);
    togglePagoMontoEditModal();

    $('#edit_certificadoTrabajo').prop('checked', matriculacionData.certificadoTrabajo == 1);

    // Cargar los planes y cursos para la edición
    var anioMatriculacion = matriculacionData.fechaMatriculacion ? matriculacionData.fechaMatriculacion.split('-')[0] : new Date().getFullYear();
    $('#edit_anioMatriculacion').val(anioMatriculacion); // Establecer el año en el select de edición

    loadPlanesParaEdicion(matriculacionData.idPlanDeEstudio, matriculacionData.idCurso);

    $('#editMatriculacionModal').modal('show');
  }

  // Función para cargar planes y luego cursos en el modal de edición
  function loadPlanesParaEdicion(selectedPlanId = null, selectedCursoId = null) {
    var idPlanSelect = $('#edit_idPlanDeEstudio');
    idPlanSelect.empty().append('<option value="">Cargando planes...</option>');
    $('#edit_idCurso').empty().append('<option value="">Seleccione un plan primero</option>');

    var idPlanToLoad = selectedPlanId;

    if (idPlanToLoad) {
        var anio = $('#edit_anioMatriculacion').val();
        $.ajax({
            url: 'matriculacion.php',
            type: 'GET',
            data: { ajax_action: 'get_planes_por_anio', idAlumno: "<?php echo $idAlumno; ?>", anio: anio },
            dataType: 'json',
            success: function(planes) {
                idPlanSelect.empty().append('<option value="">Seleccione un plan</option>');
                $.each(planes, function(i, plan) {
                    idPlanSelect.append('<option value="' + plan.idPlan + '">' + plan.nombre + '</option>');
                });
                if (selectedPlanId) {
                    idPlanSelect.val(selectedPlanId);
                    loadCursosEditModal(selectedCursoId); // Carga los cursos y pre-selecciona el curso
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error al cargar planes en modal: " + textStatus, errorThrown);
                idPlanSelect.empty().append('<option value="">Error al cargar planes</option>');
            }
        });
    } else {
        idPlanSelect.empty().append('<option value="">Seleccione un año primero</option>');
    }
  }

  // Función para cargar cursos en el modal de edición
  function loadCursosEditModal(selectedCursoId = null) {
    var idPlan = $('#edit_idPlanDeEstudio').val();
    var idCursoSelect = $('#edit_idCurso');
    idCursoSelect.empty().append('<option value="">Cargando cursos...</option>');

    if (idPlan) {
      var anio = $('#edit_anioMatriculacion').val();
      $.ajax({
        url: 'matriculacion.php',
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
            console.error("Error al cargar cursos en modal: " + textStatus, errorThrown);
            idCursoSelect.empty().append('<option value="">Error al cargar cursos</option>');
        }
      });
    } else {
      idCursoSelect.empty().append('<option value="">Seleccione un plan primero</option>');
    }
  }

  // Lógica para mostrar/ocultar el monto de pago en el modal de edición
  function togglePagoMontoEditModal() {
    var pagoMatriculaCheckbox = $('#edit_pagoMatricula');
    var pagoMontoInput = $('#edit_pagoMonto');
    if (pagoMatriculaCheckbox.is(':checked')) {
      pagoMontoInput.show();
      pagoMontoInput.prop('required', true);
    } else {
      pagoMontoInput.hide();
      pagoMontoInput.prop('required', false);
      pagoMontoInput.val('');
    }
  }

  // --- Inicialización al cargar el documento ---
  $(document).ready(function() {
    // Inicialización para el formulario de Matriculación Plan/Curso
    togglePagoMonto();
    // Cargar planes y cursos iniciales si hay una matriculación plan guardada y coincide el año
    var initialAnio = $('#anioMatriculacion').val();
    if (initialAnio) {
        loadPlanesPorAnio();
    }

    // Pre-carga para la edición de matriculación de plan
    <?php if (!empty($matriculaciones_plan)): ?>
        var firstMatPlan = <?php echo json_encode(reset($matriculaciones_plan)); ?>;
        if (firstMatPlan) {
            var currentAnio = $('#anioMatriculacion').val();
            var matriculaAnio = firstMatPlan.fechaMatriculacion ? firstMatPlan.fechaMatriculacion.split('-')[0] : null;
            if (matriculaAnio == currentAnio || !$('#idPlanDeEstudio').val()) {
                $('#idPlanDeEstudio').val(firstMatPlan.idPlanDeEstudio);
                loadCursos(firstMatPlan.idCurso); // Carga los cursos y preselecciona el curso
            }
        }
    <?php endif; ?>


    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });

</script>

</body>
</html>