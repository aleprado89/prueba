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
if (isset($_GET['ajax_action'])) {
    header('Content-Type: application/json');
    if ($_GET['ajax_action'] == 'get_cursos') {
        $idPlan = $_GET['idPlan'] ?? null;
        $anio = $_GET['anio'] ?? date('Y'); // Obtener el año si es necesario
        $idCiclo = buscarIdCiclo($conn, $anio); // Obtener el ID del ciclo lectivo para el año

        $cursos = [];
        if (!is_null($idPlan) && !is_null($idCiclo)) {
            $cursos = buscarCursosPlanCiclo($conn, $idPlan, $idCiclo);
        }
        echo json_encode($cursos);
        exit; // Terminar el script para no renderizar el HTML completo
    } elseif ($_GET['ajax_action'] == 'get_materias') {
        $idCurso = $_GET['idCurso'] ?? null;
        $materias = [];
        if (!is_null($idCurso)) {
            $materias = materiasPlanCurso($conn, null, $idCurso); // El null es para el idPlan que no se usa si se filtra por idCurso
        }
        echo json_encode($materias);
        exit; // Terminar el script
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

// Procesamiento de formulario de Matriculación Plan/Curso
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_matriculacion_plan') {
    $matriculacion_data = [
        'idAlumno' => $idAlumno,
        'idPlanDeEstudio' => $_POST['idPlanDeEstudio'] ?? null,
        'idCurso' => $_POST['idCurso'] ?? null,
        'fechaMatriculacion' => $_POST['fechaMatriculacion'] ?? null,
        'fechaBajaMatriculacion' => $_POST['fechaBajaMatriculacion'] ?? null,
        'pagoMatricula' => isset($_POST['pagoMatricula']) ? 1 : 0,
        'pagoMonto' => $_POST['pagoMonto'] ?? null,
        'certificadoSalud' => 0, // No hay checkbox específico, asumo 0
        'certificadoTrabajo' => isset($_POST['certificadoTrabajo']) ? 1 : 0, // Este es el checkbox de asistencia reducida
        'tarde' => 0 // Asumo 0 por defecto
    ];

    // Validaciones básicas antes de insertar
    if (empty($matriculacion_data['idPlanDeEstudio']) || empty($matriculacion_data['idCurso'] )) {
        $message = "Error: Plan y Curso son campos requeridos para la matriculación de plan/curso.";
        $message_type = 'danger';
    } elseif (empty($matriculacion_data['fechaMatriculacion'])) {
        $message = "Error: La Fecha de Matriculación es un campo requerido para la matriculación de plan/curso.";
        $message_type = 'danger';
    }
    else {
        if (insertarMatriculacionPlan($conn, $matriculacion_data)) {
            $message = "Matriculación de plan/curso guardada correctamente.";
            $message_type = 'success';
        } else {
            $message = "Error al guardar la matriculación de plan/curso: " . $conn->error;
            $message_type = 'danger';
        }
    }
    // Para mantener el mensaje después de la redirección
    $_SESSION['message_matriculacion'] = ['text' => $message, 'type' => $message_type];
    header('Location: matriculacion.php?idAlumno=' . $idAlumno);
    exit;
}

// Procesamiento de formulario de Inscripción a Materia
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_matriculacion_materia') {
    $matriculacion_materia_data = [
        'idAlumno' => $idAlumno,
        'idMateria' => $_POST['idMateriaMatriculacion'] ?? null,
        'fechaMatriculacionMateria' => $_POST['fechaMatriculacionMateria'] ?? null,
        'fechaBajaMatriculacionMateria' => $_POST['fechaBajaMatriculacionMateriaMatriculacion'] ?? null, // Usar el nombre del campo del formulario
    ];

    // Validaciones básicas
    if (empty($matriculacion_materia_data['idMateria']) || empty($matriculacion_materia_data['fechaMatriculacionMateria'])) {
        $message = "Error: Materia y Fecha de Matriculación de Materia son campos requeridos.";
        $message_type = 'danger';
    } else {
        if (insertarMatriculacionMateria($conn, $matriculacion_materia_data)) {
            $message = "Inscripción de materia guardada correctamente.";
            $message_type = 'success';
        } else {
            $message = "Error al guardar la inscripción de materia: " . $conn->error;
            $message_type = 'danger';
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

// Procesamiento de eliminación de Inscripción de Materia
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'delete_matriculacion_materia') {
    $idMatriculacionMateria = $_GET['idMatriculacionMateria'] ?? null;
    if ($idMatriculacionMateria) {
        if (eliminarMatriculacionMateria($conn, $idMatriculacionMateria)) {
            $message = "Inscripción de materia eliminada correctamente.";
            $message_type = 'success';
        } else {
            $message = "Error al eliminar la inscripción de materia.";
            $message_type = 'danger';
        }
    } else {
        $message = "ID de inscripción de materia no proporcionado para eliminar.";
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
$planes = obtenerPlanesDeEstudio($conn);
$matriculaciones_plan = obtenerMatriculacionesPlanAlumno($conn, $idAlumno);
$matriculaciones_materia = obtenerMatriculacionesMateriaAlumno($conn, $idAlumno);

// Fecha actual para campos de fecha por defecto
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Matriculación - Secretaría</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .cuil-display {
      font-weight: bold;
      color: #007bff;
    }
    /* Estilos para los íconos de acción */
    .action-icon-container {
        display: flex;
        justify-content: center; /* Centrar horizontalmente */
        align-items: center; /* Centrar verticalmente */
        height: 100%; /* Asegúrate de que el contenedor tenga altura */
        gap: 10px; /* Espacio entre los iconos */
    }
    .action-icon-link, .action-icon-button {
        background: none; /* Sin fondo */
        border: none; /* Sin borde */
        padding: 0; /* Sin padding */
        cursor: pointer;
        color: inherit; /* Hereda el color del texto de la tabla (negro por defecto) */
        text-decoration: none; /* Quita subrayado de enlaces */
        transition: color 0.15s ease-in-out, transform 0.15s ease-in-out;
    }
    .action-icon-link:hover, .action-icon-button:hover {
        transform: scale(1.1); /* Ligero aumento de tamaño al pasar el mouse */
    }
    .action-icon-link .bi, .action-icon-button .bi {
        font-size: 1.25rem; /* Tamaño del icono */
        color: black; /* Color del icono por defecto (negro) */
    }
    /* Colores específicos para hover */
    .action-icon-link.edit-icon:hover .bi, .action-icon-button.edit-icon:hover .bi {
        color: #ffc107; /* Amarillo para editar */
    }
    .action-icon-link.delete-icon:hover .bi, .action-icon-button.delete-icon:hover .bi {
        color: #dc3545; /* Rojo para eliminar */
    }
    /* Asegurar que el td que contiene los iconos tenga un buen tamaño */
    td:last-child {
        width: 100px; /* Ancho fijo para la columna de acciones */
        text-align: center;
    }
  </style>
</head>
<body>

<!-- CRÍTICO: Inyecta window.usuarioActual aquí, justo después de abrir <body> -->
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
      <li class="breadcrumb-item"><a href="buscarAlumno.php?origin=matriculacion">Buscar Alumno</a></li>
      <li class="breadcrumb-item active">Matriculación Alumno</li>
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
        <form method="POST" action="matriculacion.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>">
          <input type="hidden" name="action" value="save_matriculacion_plan">

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="idPlanDeEstudio" class="form-label">Plan de Estudio <span class="text-danger">*</span></label>
              <select class="form-select" id="idPlanDeEstudio" name="idPlanDeEstudio" required onchange="loadCursos()">
                <option value="">Seleccione un plan</option>
                <?php foreach ($planes as $plan): ?>
                  <option value="<?php echo htmlspecialchars($plan['idPlan']); ?>"><?php echo htmlspecialchars($plan['nombre']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="idCurso" class="form-label">Curso <span class="text-danger">*</span></label>
              <select class="form-select" id="idCurso" name="idCurso" required>
                <option value="">Seleccione un curso</option>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="fechaMatriculacion" class="form-label">Fecha de Matriculación <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="fechaMatriculacion" name="fechaMatriculacion" value="<?php echo $today; ?>" required>
            </div>
            <div class="col-md-6">
              <label for="fechaBajaMatriculacion" class="form-label">Fecha de Baja Matriculación (Opcional)</label>
              <input type="date" class="form-control" id="fechaBajaMatriculacion" name="fechaBajaMatriculacion">
              <div class="form-text">Si se carga, la matriculación se registrará como "De Baja"</div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="pagoMatricula" name="pagoMatricula" value="1" onchange="togglePagoMonto()">
                <label class="form-check-label" for="pagoMatricula"> Pago Matrícula</label>
              </div>
              <input type="number" step="0.01" class="form-control mt-2" id="pagoMonto" name="pagoMonto" placeholder="Monto abonado" style="display: none;">
            </div>
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="certificadoTrabajo" name="certificadoTrabajo" value="1">
                <label class="form-check-label" for="certificadoTrabajo">% Asistencia reducida (Certificado Salud/Trabajo)</label>
              </div>
            </div>
          </div>
          <input type="hidden" name="tarde" value="0"> <!-- Valor por defecto para `tarde` -->
          <input type="hidden" name="certificadoSalud" value="0"> <!-- Valor por defecto para `certificadoSalud` si no se usa -->

          <button type="submit" class="btn btn-primary mt-3">Guardar Matriculación Plan</button>
        </form>

        <h6 class="mt-4">Matriculaciones de Plan Existentes:</h6>
        <?php if (!empty($matriculaciones_plan)): ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover mt-3">
            <thead>
              <tr>
                <th>Plan</th>
                <th>Curso</th>
                <th>Fec. Matr.</th>
                <th>Pago Matr.</th>
                <th>Monto</th>
                <th>Fec. Baja</th>
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
                <td>
                  <div class="action-icon-container">
                      <button class="action-icon-button edit-icon" onclick='showEditMatriculacionModal(<?php echo json_encode($mat_plan); ?>)' title="Editar">
                          <i class="bi bi-pencil-square text-warning" style="color: black !important;"></i>
                      </button>
                      <a href="matriculacion.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>&action=delete_matriculacion_plan&idMatriculacion=<?php echo htmlspecialchars($mat_plan['idMatriculacion']); ?>" 
                         class="action-icon-link delete-icon" onclick="return confirm('¿Está seguro de eliminar esta matriculación de plan?');" title="Eliminar">
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
        <p>No hay matriculaciones de plan registradas para este alumno.</p>
        <?php endif; ?>
      </fieldset>

      <!-- Bloque de Inscripción a Materias -->
      <fieldset class="mb-4 p-3 border rounded">
        <legend class="float-none w-auto px-2">Inscripción a Materias</legend>
        <form method="POST" action="matriculacion.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>">
          <input type="hidden" name="action" value="save_matriculacion_materia">

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="planMatriculado" class="form-label">Desde Plan Matriculado <span class="text-danger">*</span></label>
              <select class="form-select" id="planMatriculado" name="idPlanDeEstudio" required onchange="loadCursosMatriculacionMateria()">
                <option value="">Seleccione un plan</option>
                <?php 
                // Obtener planes únicos de las matriculaciones de plan existentes para este alumno
                $planes_matriculados_unicos = []; // Reiniciar para que sea sólo para este dropdown
                foreach ($matriculaciones_plan as $mat_plan) {
                    $planes_matriculados_unicos[$mat_plan['idPlanDeEstudio']] = $mat_plan['nombrePlan'];
                }
                foreach ($planes_matriculados_unicos as $idPlan => $nombrePlan): ?>
                  <option value="<?php echo htmlspecialchars($idPlan); ?>"><?php echo htmlspecialchars($nombrePlan); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="cursoMatriculacion" class="form-label">Curso del Plan <span class="text-danger">*</span></label>
              <select class="form-select" id="cursoMatriculacion" name="idCurso" required onchange="loadMateriasMatriculacionMateria()">
                <option value="">Seleccione un curso</option>
              
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="idMateriaMatriculacion" class="form-label">Materia <span class="text-danger">*</span></label>
              <select class="form-select" id="idMateriaMatriculacion" name="idMateriaMatriculacion" required>
                <option value="">Seleccione una materia</option>
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
        <?php if (!empty($matriculaciones_materia)): ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover mt-3">
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
                      <a href="matriculacion.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>&action=delete_matriculacion_materia&idMatriculacionMateria=<?php echo htmlspecialchars($mat_materia['idMatriculacionMateria']); ?>" 
                         class="action-icon-link delete-icon" onclick="return confirm('¿Está seguro de eliminar esta inscripción de materia?');" title="Eliminar">
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
    </div>
  </div>
</div>

<?php include '../funciones/footer.html'; ?>
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../funciones/sessionControl.js"></script>

<!-- Scripts para lógica de la página -->
<script>
  // Lógica para mostrar/ocultar el monto de pago
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

  // Lógica para cargar cursos dinámicamente en Matriculación Plan/Curso
  function loadCursos(selectedCursoId = null) {
    var idPlan = $('#idPlanDeEstudio').val();
    var idCursoSelect = $('#idCurso');
    idCursoSelect.empty().append('<option value="">Cargando cursos...</option>');

    if (idPlan) {
      // Obtenemos el año actual del lado del cliente
      var currentYear = new Date().getFullYear();
      $.ajax({
        url: 'matriculacion.php', // Apunta al mismo archivo PHP
        type: 'GET',
        data: { ajax_action: 'get_cursos', idPlan: idPlan, anio: currentYear },
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

  // Lógica para cargar cursos dinámicamente en Inscripción a Materias
  function loadCursosMatriculacionMateria(selectedCursoId = null, selectedMateriaId = null) {
    var idPlan = $('#planMatriculado').val();
    var idCursoMatriculacionSelect = $('#cursoMatriculacion');
    idCursoMatriculacionSelect.empty().append('<option value="">Cargando cursos...</option>');
    $('#idMateriaMatriculacion').empty().append('<option value="">Seleccione un curso primero</option>');

    if (idPlan) {
      var currentYear = new Date().getFullYear();
      $.ajax({
        url: 'matriculacion.php', // Apunta al mismo archivo PHP
        type: 'GET',
        data: { ajax_action: 'get_cursos', idPlan: idPlan, anio: currentYear },
        dataType: 'json',
        success: function(cursos) {
          idCursoMatriculacionSelect.empty().append('<option value="">Seleccione un curso</option>');
          $.each(cursos, function(i, curso) {
            idCursoMatriculacionSelect.append('<option value="' + curso.idCurso + '">' + curso.nombre + '</option>');
          });
          if (selectedCursoId) {
            idCursoMatriculacionSelect.val(selectedCursoId);
            // Si hay una materia para precargar, llamamos a loadMaterias... aquí
            if(selectedMateriaId) {
                loadMateriasMatriculacionMateria(selectedMateriaId);
            }
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error al cargar cursos de materia: " + textStatus, errorThrown);
            idCursoMatriculacionSelect.empty().append('<option value="">Error al cargar cursos</option>');
        }
      });
    } else {
      idCursoMatriculacionSelect.empty().append('<option value="">Seleccione un plan primero</option>');
    }
  }

  // Lógica para cargar materias dinámicamente en Inscripción a Materias
  function loadMateriasMatriculacionMateria(selectedMateriaId = null) {
    var idCurso = $('#cursoMatriculacion').val();
    var idMateriaMatriculacionSelect = $('#idMateriaMatriculacion');
    idMateriaMatriculacionSelect.empty().append('<option value="">Cargando materias...</option>');

    if (idCurso) {
      $.ajax({
        url: 'matriculacion.php', // Apunta al mismo archivo PHP
        type: 'GET',
        data: { ajax_action: 'get_materias', idCurso: idCurso },
        dataType: 'json',
        success: function(materias) {
          idMateriaMatriculacionSelect.empty().append('<option value="">Seleccione una materia</option>');
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
      idMateriaMatriculacionSelect.empty().append('<option value="">Seleccione un curso primero</option>');
    }
  }

  // Lógica para mostrar Modal de Edición de Matriculación Plan/Curso
  function showEditMatriculacionModal(matriculacionData) {
    // Rellenamos los campos del modal
    $('#edit_idMatriculacion').val(matriculacionData.idMatriculacion);
    $('#edit_idPlanDeEstudio').val(matriculacionData.idPlanDeEstudio);
    $('#edit_fechaMatriculacion').val(matriculacionData.fechaMatriculacion);
    $('#edit_fechaBajaMatriculacion').val(matriculacionData.fechaBajaMatriculacion);
    
    $('#edit_pagoMatricula').prop('checked', matriculacionData.pagoMatricula == 1);
    $('#edit_pagoMonto').val(matriculacionData.pagoMonto); 
    togglePagoMontoEditModal(); // Llama para establecer la visibilidad según los datos cargados

    $('#edit_certificadoTrabajo').prop('checked', matriculacionData.certificadoTrabajo == 1); 

    // Cargar cursos para el plan seleccionado en el modal manteniendo el curso actual
    loadCursosEditModal(matriculacionData.idCurso); 

    $('#editMatriculacionModal').modal('show');
  }

  // Lógica para cargar cursos en el modal de edición de Matriculación Plan/Curso
  function loadCursosEditModal(selectedCursoId = null) {
    var idPlan = $('#edit_idPlanDeEstudio').val();
    var idCursoSelect = $('#edit_idCurso');
    idCursoSelect.empty().append('<option value="">Cargando cursos...</option>');

    if (idPlan) {
      var currentYear = new Date().getFullYear();
      $.ajax({
        url: 'matriculacion.php', // Apunta al mismo archivo PHP
        type: 'GET',
        data: { ajax_action: 'get_cursos', idPlan: idPlan, anio: currentYear },
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

  // Lógica para mostrar Modal de Edición de Inscripción a Materia
  function showEditMatriculacionMateriaModal(matriculacionMateriaDataJson) {
    const matriculacionMateriaData = JSON.parse(matriculacionMateriaDataJson);
    
    // Asigna el ID de la matriculación de materia
    $('#edit_idMatriculacionMateria').val(matriculacionMateriaData.idMatriculacionMateria);
    
    // Rellena campos de fecha
    $('#edit_fechaMatriculacionMateria').val(matriculacionMateriaData.fechaMatriculacion);
    $('#edit_fechaBajaMatriculacionMateriaMatriculacion').val(matriculacionMateriaData.fechaBajaMatriculacion);

    // Selecciona el plan de estudio y luego carga cursos y materias
    // Asume que matriculacionMateriaData contiene 'idPlan' de la materia
    $('#edit_planMatriculado').val(matriculacionMateriaData.idPlan); 
    
    // Llama a cargar cursos, pasando el ID del curso y la materia para que se pre-seleccionen
    // La función loadCursosEditMatriculacionMateria ahora se encargará de esto en cascada
    loadCursosEditMatriculacionMateria(matriculacionMateriaData.idCurso, matriculacionMateriaData.idMateria);

    $('#editMatriculacionMateriaModal').modal('show');
  }

  // Lógica para cargar cursos en el modal de edición de Inscripción a Materia (con precarga de curso y materia)
  function loadCursosEditMatriculacionMateria(selectedCursoId = null, selectedMateriaId = null) {
    var idPlan = $('#edit_planMatriculado').val();
    var idCursoSelect = $('#edit_cursoMatriculacion');
    idCursoSelect.empty().append('<option value="">Cargando cursos...</option>');
    $('#edit_idMateriaMatriculacion').empty().append('<option value="">Seleccione un curso primero</option>');

    if (idPlan) {
      var currentYear = new Date().getFullYear();
      $.ajax({
        url: 'matriculacion.php', // Apunta al mismo archivo PHP
        type: 'GET',
        data: { ajax_action: 'get_cursos', idPlan: idPlan, anio: currentYear },
        dataType: 'json',
        success: function(cursos) {
          idCursoSelect.empty().append('<option value="">Seleccione un curso</option>');
          $.each(cursos, function(i, curso) {
            idCursoSelect.append('<option value="' + curso.idCurso + '">' + curso.nombre + '</option>');
          });
          if (selectedCursoId) {
            idCursoSelect.val(selectedCursoId);
            // Cuando el curso está seleccionado, cargar las materias de ese curso
            loadMateriasEditMatriculacionMateria(selectedMateriaId);
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

  // Lógica para cargar materias en el modal de edición de Inscripción a Materia (con precarga de materia)
  function loadMateriasEditMatriculacionMateria(selectedMateriaId = null) {
    var idCurso = $('#edit_cursoMatriculacion').val();
    var idMateriaMatriculacionSelect = $('#edit_idMateriaMatriculacion');
    idMateriaMatriculacionSelect.empty().append('<option value="">Cargando materias...</option>');

    if (idCurso) {
      $.ajax({
        url: 'matriculacion.php', // Apunta al mismo archivo PHP
        type: 'GET',
        data: { ajax_action: 'get_materias', idCurso: idCurso },
        dataType: 'json',
        success: function(materias) {
          idMateriaMatriculacionSelect.empty().append('<option value="">Seleccione una materia</option>');
          $.each(materias, function(i, materia) {
            idMateriaMatriculacionSelect.append('<option value="' + materia.idMateria + '">' + materia.nombreMateria + '</option>');
          });
          if (selectedMateriaId) {
            idMateriaMatriculacionSelect.val(selectedMateriaId);
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error al cargar materias en modal: " + textStatus, errorThrown);
            idMateriaMatriculacionSelect.empty().append('<option value="">Error al cargar materias</option>');
        }
      });
    } else {
      idMateriaMatriculacionSelect.empty().append('<option value="">Seleccione un curso primero</option>');
    }
  }

  $(document).ready(function() {
    togglePagoMonto(); // Para inicializar el estado del campo de monto
  });
</script>

<!-- Modal for editing Matriculación Plan/Curso -->
<div class="modal fade" id="editMatriculacionModal" tabindex="-1" aria-labelledby="editMatriculacionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editMatriculacionModalLabel">Editar Matriculación de Plan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editMatriculacionForm" method="POST" action="matriculacion.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>">
        <input type="hidden" name="action" value="save_matriculacion_plan">
        <input type="hidden" name="idMatriculacion" id="edit_idMatriculacion">
        <input type="hidden" name="idAlumno" value="<?php echo htmlspecialchars($idAlumno); ?>"> <!-- Asegura idAlumno en POST -->
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_idPlanDeEstudio" class="form-label">Plan de Estudio</label>
            <select class="form-select" id="edit_idPlanDeEstudio" name="idPlanDeEstudio" required onchange="loadCursosEditModal()">
              <option value="">Seleccione un plan</option>
              <?php foreach ($planes as $plan): ?>
                <option value="<?php echo htmlspecialchars($plan['idPlan']); ?>"><?php echo htmlspecialchars($plan['nombre']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_idCurso" class="form-label">Curso</label>
            <select class="form-select" id="edit_idCurso" name="idCurso" required>
              <option value="">Seleccione un curso</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_fechaMatriculacion" class="form-label">Fecha de Matriculación</label>
            <input type="date" class="form-control" id="edit_fechaMatriculacion" name="fechaMatriculacion" required>
          </div>
          <div class="mb-3">
            <label for="edit_fechaBajaMatriculacion" class="form-label">Fecha de Baja (Opcional)</label>
            <input type="date" class="form-control" id="edit_fechaBajaMatriculacion" name="fechaBajaMatriculacion">
          </div>
          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="edit_pagoMatricula" name="pagoMatricula" value="1" onchange="togglePagoMontoEditModal()">
            <label class="form-check-label" for="edit_pagoMatricula">Pago Matrícula</label>
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
          <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal for editing Inscripción a Materia -->
<div class="modal fade" id="editMatriculacionMateriaModal" tabindex="-1" aria-labelledby="editMatriculacionMateriaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editMatriculacionMateriaModalLabel">Editar Inscripción a Materia</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editMatriculacionMateriaForm" method="POST" action="matriculacion.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>">
        <input type="hidden" name="action" value="update_matriculacion_materia">
        <input type="hidden" name="idMatriculacionMateria" id="edit_idMatriculacionMateria">
         <input type="hidden" name="idAlumno" value="<?php echo htmlspecialchars($idAlumno); ?>"> <!-- Asegura idAlumno en POST -->
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_planMatriculado" class="form-label">Desde Plan Matriculado</label>
            <select class="form-select" id="edit_planMatriculado" name="idPlanDeEstudio" required onchange="loadCursosEditMatriculacionMateria()">
              <option value="">Seleccione un plan</option>
              <?php 
                // Asegúrate de que $planes esté accesible aquí
                foreach ($planes as $plan): ?>
                  <option value="<?php echo htmlspecialchars($plan['idPlan']); ?>"><?php echo htmlspecialchars($plan['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_cursoMatriculacion" class="form-label">Curso del Plan</label>
            <select class="form-select" id="edit_cursoMatriculacion" name="idCurso" required onchange="loadMateriasEditMatriculacionMateria()">
              <option value="">Seleccione un curso</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_idMateriaMatriculacion" class="form-label">Materia</label>
            <select class="form-select" id="edit_idMateriaMatriculacion" name="idMateriaMatriculacion" required>
              <option value="">Seleccione una materia</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_fechaMatriculacionMateria" class="form-label">Fecha de Inscripción</label>
            <input type="date" class="form-control" id="edit_fechaMatriculacionMateria" name="fechaMatriculacionMateria" required>
          </div>
           <div class="mb-3">
            <label for="edit_fechaBajaMatriculacionMateriaMatriculacion" class="form-label">Fecha de Baja Inscripción (Opcional)</label>
            <input type="date" class="form-control" id="edit_fechaBajaMatriculacionMateriaMatriculacion" name="fechaBajaMatriculacionMateriaMatriculacion">
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