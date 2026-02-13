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
    $idPlan = $_GET['idPlan'] ?? null;
    $idCiclo = $_GET['idCiclo'] ?? null;
    $idCurso = $_GET['idCurso'] ?? null;

    if ($_GET['ajax_action'] == 'get_cursos') {
        $cursos = [];
        if (!is_null($idPlan) && !is_null($idCiclo) && $idCiclo != 0) {
            // 1. Intentar buscar cursos con el ciclo seleccionado
            $cursos = buscarCursosPlanCiclo($conn, $idPlan, $idCiclo);
            
            // 2. Si no se encuentran (ej. es un ciclo nuevo sin cursos definidos),
            //    usar el último ciclo lectivo como referencia para encontrar los cursos del plan.
            if (empty($cursos)) {
                $ultimoCiclo = obtenerUltimoCicloLectivo($conn);
                if ($ultimoCiclo && $ultimoCiclo['idciclolectivo'] != $idCiclo) {
                    $cursos = buscarCursosPlanCiclo($conn, $idPlan, $ultimoCiclo['idciclolectivo']);
                }
            }
        }
        echo json_encode($cursos);
        exit;
    } elseif ($_GET['ajax_action'] == 'get_materias') {
        $materias = [];
        if (!is_null($idCurso) && !is_null($idPlan)) {
            $materias = materiasPlanCurso($conn, $idPlan, $idCurso);
        }
        echo json_encode($materias);
        exit;
    }
    exit;
}
// --- Fin Lógica para manejar peticiones AJAX ---

// Sanitizar y obtener el idAlumno
$idAlumno = filter_input(INPUT_GET, 'idAlumno', FILTER_VALIDATE_INT);
if (!$idAlumno) {
    $_SESSION['message_buscarAlumno'] = ['text' => 'ID de alumno no proporcionado.', 'type' => 'danger'];
    header('Location: buscarAlumno.php?origin=presistema');
    exit;
}

$alumnoData = obtenerDatosAlumno($conn, $idAlumno);
if (!$alumnoData) {
    $_SESSION['message_buscarAlumno'] = ['text' => 'Alumno no encontrado.', 'type' => 'danger'];
    header('Location: buscarAlumno.php?origin=presistema');
    exit;
}

$message = '';
$message_type = '';

// --- LÓGICA DE PROCESAMIENTO DE FORMULARIOS (POST y GET) ---

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // La transacción se inicia DESPUÉS de la validación
    try {
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

        if ($action == 'create_presistema') {
            
            // --- INICIO VALIDACIÓN PREVIA ---
            $materias = $_POST['materias'] ?? [];
            if (empty($materias)) {
                throw new Exception("Debe seleccionar al menos una materia.");
            }

            // Validar Ciclo Lectivo
            $idCicloLectivo = filter_input(INPUT_POST, 'idCiclo', FILTER_VALIDATE_INT);
            if (empty($idCicloLectivo) || $idCicloLectivo == 0) {
                throw new Exception("Debe seleccionar un Ciclo Lectivo válido.");
            }

            // Validar inscripciones existentes usando la función de Equivalencias
            $conflictos = [];
            
            // Preparamos una consulta para buscar el nombre de la materia SÓLO si hay conflicto
            $stmt_materia_nombre = $conn->prepare("SELECT nombre FROM materiaterciario WHERE idMateria = ?");

            foreach ($materias as $idMateria) {
                $idMateria = filter_var($idMateria, FILTER_VALIDATE_INT);
                if (!$idMateria) continue;

                // 1. Usamos la función correcta (checkMatriculacionMateriaExiste)
                $existe = checkMatriculacionMateriaExiste($conn, $idAlumno, $idMateria);
                
                if ($existe) {
                    // 2. Si existe, buscamos el nombre para el modal de error
                    $nombreMateriaConflicto = 'Materia (ID ' . $idMateria . ')'; // Fallback
                    if ($stmt_materia_nombre) {
                        $stmt_materia_nombre->bind_param("i", $idMateria);
                        $stmt_materia_nombre->execute();
                        $result_nombre = $stmt_materia_nombre->get_result();
                        if ($row_nombre = $result_nombre->fetch_assoc()) {
                            $nombreMateriaConflicto = $row_nombre['nombre'];
                        }
                    }
                    $conflictos[] = $nombreMateriaConflicto;
                }
            }
            if ($stmt_materia_nombre) {
                $stmt_materia_nombre->close(); // Cerramos el statement preparado
            }

            if (!empty($conflictos)) {
                // Si hay conflictos, no iniciar la transacción.
                // Guardar el error para el modal y redirigir.
                $mensajeError = "Este alumno ya posee una inscripción en la(s) siguiente(s) materia(s) (o sus equivalentes): <br><br><strong>" . implode("<br>", array_unique($conflictos)) . "</strong>";
                $_SESSION['presistema_error_detalle'] = $mensajeError;
                header('Location: registrosPresistema.php?idAlumno=' . $idAlumno);
                exit;
            }
            // --- FIN VALIDACIÓN PREVIA ---

            // Si pasa la validación, iniciar la transacción
            $conn->begin_transaction();

            $condicionTipo = filter_input(INPUT_POST, 'condicionTipo', FILTER_SANITIZE_STRING);
            $fechaObtencion = filter_input(INPUT_POST, 'fechaObtencion', FILTER_SANITIZE_STRING);

            if ($condicionTipo == 'Regular') {
                $turnos = filter_input(INPUT_POST, 'turnosTranscurridos', FILTER_VALIDATE_INT) ?: 0;
                foreach ($materias as $idMateria) {
                    $idMateria = filter_var($idMateria, FILTER_VALIDATE_INT);
                    // Pasar $idCicloLectivo
                    insertarPresistemaRegular($conn, $idAlumno, $idMateria, $fechaObtencion, $idCicloLectivo, $turnos);
                }
                $message = "Registros de regularidad presistema creados exitosamente.";
            
            } elseif ($condicionTipo == 'Aprobado') {
                $calificacion = filter_input(INPUT_POST, 'calificacion', FILTER_SANITIZE_STRING);
                $idCondicionExamen = filter_input(INPUT_POST, 'condicionExamen', FILTER_VALIDATE_INT);
                $libro = filter_input(INPUT_POST, 'libro', FILTER_SANITIZE_STRING);
                $folio = filter_input(INPUT_POST, 'folio', FILTER_SANITIZE_STRING);

                if (empty($calificacion) || empty($idCondicionExamen)) {
                    throw new Exception("Para 'Aprobado', la calificación y la condición de examen son obligatorias.");
                }

                foreach ($materias as $idMateria) {
                    $idMateria = filter_var($idMateria, FILTER_VALIDATE_INT);
                    // Pasar $idCicloLectivo
                    insertarPresistemaAprobado($conn, $idAlumno, $idMateria, $fechaObtencion, $idCicloLectivo, $calificacion, $idCondicionExamen, $libro, $folio);
                }
                $message = "Registros de aprobación presistema creados exitosamente.";
            }
            $conn->commit(); // Commit solo si la acción fue exitosa
            $message_type = 'success';

        } elseif ($action == 'update_presistema') {
            // --- INICIO TRANSACCIÓN (Update) ---
            $conn->begin_transaction();
            
            $idMatriculacionMateria = filter_input(INPUT_POST, 'editIdMatriculacion', FILTER_VALIDATE_INT);
            $idCalificacion = filter_input(INPUT_POST, 'editIdCalificacion', FILTER_VALIDATE_INT);
            $idInscripcion = filter_input(INPUT_POST, 'editIdInscripcion', FILTER_VALIDATE_INT);
            $nuevoCondicionTipo = filter_input(INPUT_POST, 'editCondicionTipo', FILTER_SANITIZE_STRING);
            $fechaObtencion = filter_input(INPUT_POST, 'editFechaObtencion', FILTER_SANITIZE_STRING);
            $estadoOriginal = filter_input(INPUT_POST, 'editEstadoOriginal', FILTER_SANITIZE_STRING);

            $detalles = obtenerDetallesPresistema($conn, $idMatriculacionMateria);
            if (!$detalles) throw new Exception("No se encontró el registro a actualizar.");

            if ($estadoOriginal == 'Aprobación PreSistema' && $nuevoCondicionTipo == 'Regular') {
                throw new Exception("No se puede cambiar un estado 'Aprobado' a 'Regular'. Debe eliminarlo y crearlo de nuevo.");
            }

            if ($nuevoCondicionTipo == 'Regular') {
                $turnos = filter_input(INPUT_POST, 'editTurnosTranscurridos', FILTER_VALIDATE_INT) ?: 0;
                actualizarPresistemaRegular($conn, $idMatriculacionMateria, $idCalificacion, $fechaObtencion, $turnos);
                $message = "Registro actualizado a 'Regular' exitosamente.";
            
            } elseif ($nuevoCondicionTipo == 'Aprobado') {
                $calificacion = filter_input(INPUT_POST, 'editCalificacion', FILTER_SANITIZE_STRING);
                $idCondicionExamen = filter_input(INPUT_POST, 'editCondicionExamen', FILTER_VALIDATE_INT);
                $libro = filter_input(INPUT_POST, 'editLibro', FILTER_SANITIZE_STRING);
                $folio = filter_input(INPUT_POST, 'editFolio', FILTER_SANITIZE_STRING);

                if (empty($calificacion) || empty($idCondicionExamen)) {
                    throw new Exception("Para 'Aprobado', la calificación y la condición de examen son obligatorias.");
                }

                if ($estadoOriginal == 'Regularidad PreSistema') {
                    // Conversión de Regular -> Aprobado
                    // NOTA: La conversión usará el idCicloLectivo que ya estaba en la matriculación original
                    convertirPresistemaRegularAAprobado($conn, $idMatriculacionMateria, $idCalificacion, $detalles['idMateria'], $idAlumno, $fechaObtencion, $calificacion, $idCondicionExamen, $libro, $folio);
                    $message = "Registro convertido a 'Aprobado' exitosamente.";
                } else {
                    // Actualización de Aprobado -> Aprobado
                    actualizarPresistemaAprobado($conn, $idMatriculacionMateria, $idCalificacion, $idInscripcion, $fechaObtencion, $calificacion, $idCondicionExamen, $libro, $folio);
                    $message = "Registro 'Aprobado' actualizado exitosamente.";
                }
            }
            $conn->commit(); // Commit del update
            $message_type = 'success';
        }

    } catch (Exception $e) {
        // Rollback solo si la transacción fue iniciada
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
    $_SESSION['message_presistema'] = ['text' => $message, 'type' => $message_type];
    header('Location: registrosPresistema.php?idAlumno=' . $idAlumno);
    exit;

} elseif ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'delete_presistema') {
    // Lógica de Eliminación con confirmación
    $idMatriculacionMateria = filter_input(INPUT_GET, 'idMatriculacion', FILTER_VALIDATE_INT);
    if ($idMatriculacionMateria) {
        if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
            $conn->begin_transaction();
            try {
                eliminarPresistema($conn, $idMatriculacionMateria);
                $conn->commit();
                $message = "Registro presistema eliminado exitosamente.";
                $message_type = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error al eliminar el registro: " . $e->getMessage();
                $message_type = 'danger';
            }
            $_SESSION['message_presistema'] = ['text' => $message, 'type' => $message_type];
        } else {
            // Si no está confirmado, simplemente redirigimos (el JS debe manejar la confirmación)
        }
    }
    header('Location: registrosPresistema.php?idAlumno=' . $idAlumno);
    exit;
}

// Recuperar mensaje de sesión si existe
if (isset($_SESSION['message_presistema'])) {
    $message = $_SESSION['message_presistema']['text'];
    $message_type = $_SESSION['message_presistema']['type'];
    unset($_SESSION['message_presistema']);
}

// --- OBTENER DATOS PARA LA PÁGINA ---
$planes = obtenerPlanesDeEstudio($conn);
$ciclos = levantarCiclosLectivos($conn);
$condicionesExamen = obtenerCondicionesExamen($conn);
$registrosPresistema = obtenerRegistrosPresistema($conn, $idAlumno);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registros presistema - Secretaría</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .campo-aprobado, .campo-regular { display: none; }
    .select2-container .select2-selection--multiple {
        min-height: 38px; /* Altura de Bootstrap */
        border: 1px solid #ced4da;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #007bff;
        border-color: #0069d9;
        color: white;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: rgba(255,255,255,0.7);
    }
  </style>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<link rel="icon" type="image/png" href="../img/icon.png">

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
      <li class="breadcrumb-item"><a href="buscarAlumno.php?origin=presistema">Buscar alumno</a></li>
      <li class="breadcrumb-item active">Registros presistema</li>
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

      <fieldset class="mb-4 p-3 border rounded">
        <legend class="float-none w-auto px-2">Nuevo registro presistema</legend>
        <form id="createPresistemaForm" method="POST" action="registrosPresistema.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>">
          <input type="hidden" name="action" value="create_presistema">

          <div class="row mb-3">
            <div class="col-md-4">
              <label for="selectCiclo" class="form-label">Ciclo lectivo <span class="text-danger">*</span></label>
              <select class="form-select" id="selectCiclo" name="idCiclo" required>
                <option value="" selected disabled>Seleccione un ciclo</option>
                <?php foreach ($ciclos as $ciclo): ?>
                    <option value="<?php echo htmlspecialchars($ciclo['idCicloLectivo']); ?>">
                        <?php echo htmlspecialchars($ciclo['anio']); ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="selectPlan" class="form-label">Plan de estudio <span class="text-danger">*</span></label>
              <select class="form-select" id="selectPlan" name="idPlan" required>
                <option value="">Seleccione un plan</option>
                <?php foreach ($planes as $plan): ?>
                    <option value="<?php echo htmlspecialchars($plan['idPlan']); ?>">
                        <?php echo htmlspecialchars($plan['nombre']); ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="selectCurso" class="form-label">Curso <span class="text-danger">*</span></label>
              <select class="form-select" id="selectCurso" name="idCurso" required>
                <option value="">Seleccione un plan y ciclo</option>
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-12">
              <label for="selectMaterias" class="form-label">Materia(s) <span class="text-danger">*</span></label>
              <select class="form-select" id="selectMaterias" name="materias[]" multiple="multiple" required>
                <option value="">Seleccione un curso</option>
              </select>
            </div>
          </div>
          <div class="row mb-3 align-items-center">
            <div class="col-md-4">
                <label class="form-label">Condición <span class="text-danger">*</span></label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="condicionTipo" id="radioAprobado" value="Aprobado" required>
                    <label class="form-check-label" for="radioAprobado">Aprobado</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="condicionTipo" id="radioRegular" value="Regular" required>
                    <label class="form-check-label" for="radioRegular">Regular</label>
                </div>
            </div>
            <div class="col-md-4">
              <label for="fechaObtencion" class="form-label">Fecha de obtención <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="fechaObtencion" name="fechaObtencion" required>
            </div>
          </div>

          <div class="row mb-3 campo-aprobado">
            <div class="col-md-4">
              <label for="condicionExamen" class="form-label">Condición examen <span class="text-danger">*</span></label>
              <select class="form-select" id="condicionExamen" name="condicionExamen">
                <option value="">Seleccione condición</option>
                <?php foreach ($condicionesExamen as $cond): ?>
                    <option value="<?php echo htmlspecialchars($cond['idCondicion']); ?>">
                        <?php echo htmlspecialchars($cond['condicion']); ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="calificacion" class="form-label">Calificación <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="calificacion" name="calificacion">
            </div>
          </div>
          <div class="row mb-3 campo-aprobado">
            <div class="col-md-4">
              <label for="libro" class="form-label">Libro (Opcional)</label>
              <input type="text" class="form-control" id="libro" name="libro">
            </div>
            <div class="col-md-4">
              <label for="folio" class="form-label">Folio (Opcional)</label>
              <input type="text" class="form-control" id="folio" name="folio">
            </div>
          </div>

          <div class="row mb-3 campo-regular">
            <div class="col-md-8">
              <label for="turnosTranscurridos" class="form-label">Turnos de examen transcurridos (Opcional)</label>
              <input type="number" class="form-control" id="turnosTranscurridos" name="turnosTranscurridos" min="0" value="0">
              <div class="form-text">Para control de vencimiento de regularidad.</div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary mt-3">Registrar presistema</button>
        </form>
      </fieldset>

      <h6 class="mt-4">Registros presistema cargados:</h6>
      <div class="table-responsive">
          <table class="table table-striped table-hover mt-3" id="tablaPresistema">
            <thead>
              <tr>
                <th>Plan</th>
                <th>Curso</th>
                <th>Materia</th>
                <th>Condición</th>
                <th>Fecha</th>
                <th>Calificación</th>
                <th>Libro/Folio</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
                <?php if (empty($registrosPresistema)): ?>
                    <tr><td colspan="8">No hay registros presistema para este alumno.</td></tr>
                <?php else: ?>
                    <?php foreach ($registrosPresistema as $reg): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reg['nombrePlan']); ?></td>
                            <td><?php echo htmlspecialchars($reg['nombreCurso']); ?></td>
                            <td><?php echo htmlspecialchars($reg['nombreMateria']); ?></td>
                            <td>
                                <?php if($reg['estado'] == 'Aprobación PreSistema'): ?>
                                    <span class="badge bg-success">Aprobado</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Regular</span>
                                <?php endif; ?>
                            </td>
                       <td><?php echo date("d/m/Y", strtotime($reg['fechaMatriculacion'])); ?></td>                            <td><?php echo htmlspecialchars($reg['calificacion'] ?? ($reg['examenIntegrador'] ?? '-')); ?></td>
                            <td><?php echo htmlspecialchars($reg['libro'] ?? '-'); ?> / <?php echo htmlspecialchars($reg['folio'] ?? '-'); ?></td>
                            <td>
                                <div class="action-icon-container">
                                    <a href="#" class="action-icon-link edit-icon"
                                       data-bs-toggle="modal"
                                       data-bs-target="#editModal"
                                       data-id-matriculacion="<?php echo htmlspecialchars($reg['idMatriculacionMateria']); ?>"
                                       data-id-calificacion="<?php echo htmlspecialchars($reg['idCalificacion'] ?? ''); ?>"
                                       data-id-inscripcion="<?php echo htmlspecialchars($reg['idInscripcion'] ?? ''); ?>"
                                       data-materia-nombre="<?php echo htmlspecialchars($reg['nombreMateria']); ?>"
                                       data-estado-original="<?php echo htmlspecialchars($reg['estado']); ?>"
                                       data-fecha="<?php echo htmlspecialchars($reg['fechaMatriculacion']); ?>"
                                       data-calificacion="<?php echo htmlspecialchars($reg['calificacion'] ?? ($reg['examenIntegrador'] ?? '')); ?>"
                                       data-id-condicion-examen="<?php echo htmlspecialchars($reg['idCondicion'] ?? ''); ?>"
                                       data-libro="<?php echo htmlspecialchars($reg['libro'] ?? ''); ?>"
                                       data-folio="<?php echo htmlspecialchars($reg['folio'] ?? ''); ?>"
                                       data-turnos="<?php echo htmlspecialchars($reg['turnosTranscurridos'] ?? '0'); ?>"
                                       title="Editar">
                                       <i class="bi bi-pencil-square" style="color: black !important;"></i>
                                    </a>
                                    <a href="#" onclick="confirmDeleteModal(<?php echo htmlspecialchars($reg['idMatriculacionMateria']); ?>); return false;" 
                                       class="action-icon-link delete-icon" title="Eliminar">
                                       <i class="bi bi-trash text-danger" style="color: black !important;"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
          </table>
      </div>

    </div>
  </div>
</div>

<?php include '../funciones/footer.html'; ?>

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editPresistemaForm" method="POST" action="registrosPresistema.php?idAlumno=<?php echo htmlspecialchars($idAlumno); ?>">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Editar Registro Presistema</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="update_presistema">
          <input type="hidden" id="editIdMatriculacion" name="editIdMatriculacion">
          <input type="hidden" id="editIdCalificacion" name="editIdCalificacion">
          <input type="hidden" id="editIdInscripcion" name="editIdInscripcion">
          <input type="hidden" id="editEstadoOriginal" name="editEstadoOriginal">

          <h5 id="editMateriaNombre"></h5>
          <hr>

          <div class="row mb-3 align-items-center">
            <div class="col-md-6">
                <label class="form-label">Condición <span class="text-danger">*</span></label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="editCondicionTipo" id="editRadioAprobado" value="Aprobado" required>
                    <label class="form-check-label" for="editRadioAprobado">Aprobado</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="editCondicionTipo" id="editRadioRegular" value="Regular" required>
                    <label class="form-check-label" for="editRadioRegular">Regular</label>
                </div>
            </div>
            <div class="col-md-6">
              <label for="editFechaObtencion" class="form-label">Fecha Obtención <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="editFechaObtencion" name="editFechaObtencion" required>
            </div>
          </div>

          <div class="row mb-3 edit-campo-aprobado">
            <div class="col-md-6">
              <label for="editCondicionExamen" class="form-label">Condición Examen <span class="text-danger">*</span></label>
              <select class="form-select" id="editCondicionExamen" name="editCondicionExamen">
                <option value="">Seleccione condición</option>
                <?php foreach ($condicionesExamen as $cond): ?>
                    <option value="<?php echo htmlspecialchars($cond['idCondicion']); ?>">
                        <?php echo htmlspecialchars($cond['condicion']); ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="editCalificacion" class="form-label">Calificación <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="editCalificacion" name="editCalificacion">
            </div>
          </div>
          <div class="row mb-3 edit-campo-aprobado">
            <div class="col-md-6">
              <label for="editLibro" class="form-label">Libro (Opcional)</label>
              <input type="text" class="form-control" id="editLibro" name="editLibro">
            </div>
            <div class="col-md-6">
              <label for="editFolio" class="form-label">Folio (Opcional)</label>
              <input type="text" class="form-control" id="editFolio" name="editFolio">
            </div>
          </div>

          <div class="row mb-3 edit-campo-regular">
            <div class="col-md-12">
              <label for="editTurnosTranscurridos" class="form-label">Turnos de examen transcurridos (Opcional)</label>
              <input type="number" class="form-control" id="editTurnosTranscurridos" name="editTurnosTranscurridos" min="0">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" onclick="confirmEdit()">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="confirmEditModal" tabindex="-1" aria-labelledby="confirmEditModalLabel" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmEditModalLabel">Confirmar cambios</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        ¿Está seguro de que desea guardar estos cambios?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="submitEditForm()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        ¿Está seguro de que desea eliminar este registro presistema?
        <br><br>
        <strong class="text-danger">Esta acción no se puede deshacer y eliminará todos los datos asociados.</strong>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a id="confirmDeleteButton" href="#" class="btn btn-danger">Eliminar</a>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="errorModalLabel">Error de Inscripción</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="errorModalBody">
        </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Entendido</button>
      </div>
    </div>
  </div>
</div>


<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../funciones/sessionControl.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Inicializar Select2
    $('#selectMaterias').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Seleccione una o más materias'
    });

    // --- Lógica para AJAX de selects ---
    function loadCursos() {
        var idPlan = $('#selectPlan').val();
        var idCiclo = $('#selectCiclo').val();
        var cursoSelect = $('#selectCurso');
        cursoSelect.empty().append('<option value="">Cargando cursos...</option>');
        $('#selectMaterias').empty().trigger('change');

        if (idPlan && idCiclo && idCiclo != 0) {
            $.ajax({
                url: 'registrosPresistema.php',
                type: 'GET',
                data: { ajax_action: 'get_cursos', idPlan: idPlan, idCiclo: idCiclo },
                dataType: 'json',
                success: function(cursos) {
                    cursoSelect.empty().append('<option value="">Seleccione un curso</option>');
                    if (cursos && cursos.length > 0) {
                        $.each(cursos, function(i, curso) {
                            cursoSelect.append('<option value="' + curso.idCurso + '">' + curso.nombre + '</option>');
                        });
                    } else {
                        cursoSelect.append('<option value="">No hay cursos para este plan/ciclo</option>');
                    }
                },
                error: function() {
                    cursoSelect.empty().append('<option value="">Error al cargar cursos</option>');
                }
            });
        } else {
            cursoSelect.empty().append('<option value="">Seleccione un plan y ciclo</option>');
            $('#selectMaterias').empty().trigger('change');
        }
    }

    function loadMaterias() {
        var idCurso = $('#selectCurso').val();
        var idPlan = $('#selectPlan').val();
        var materiaSelect = $('#selectMaterias');
        materiaSelect.empty();

        if (idCurso && idPlan) {
            $.ajax({
                url: 'registrosPresistema.php',
                type: 'GET',
                data: { ajax_action: 'get_materias', idCurso: idCurso, idPlan: idPlan },
                dataType: 'json',
                success: function(materias) {
                    if (materias && materias.length > 0) {
                        $.each(materias, function(i, materia) {
                            materiaSelect.append('<option value="' + materia.idMateria + '">' + materia.nombreMateria + '</option>');
                        });
                    }
                    materiaSelect.trigger('change'); // Notificar a Select2
                },
                error: function() {
                    materiaSelect.trigger('change');
                }
            });
        }
    }

    $('#selectCiclo').on('change', loadCursos);
    $('#selectPlan').on('change', loadCursos);
    $('#selectCurso').on('change', loadMaterias);

    // --- Lógica para mostrar/ocultar campos de Condición (Formulario CREAR) ---
    function toggleCreateFields() {
        var condicion = $('input[name="condicionTipo"]:checked').val();
        if (condicion === 'Aprobado') {
            $('.campo-aprobado').slideDown();
            $('.campo-regular').slideUp();
            // Hacer campos de aprobado requeridos
            $('#condicionExamen, #calificacion').prop('required', true);
        } else if (condicion === 'Regular') {
            $('.campo-aprobado').slideUp();
            $('.campo-regular').slideDown();
            // Quitar requerido de campos de aprobado
            $('#condicionExamen, #calificacion').prop('required', false);
        } else {
            $('.campo-aprobado, .campo-regular').slideUp();
            $('#condicionExamen, #calificacion').prop('required', false);
        }
    }
    $('input[name="condicionTipo"]').on('change', toggleCreateFields);

    // --- Lógica para mostrar/ocultar campos de Condición (Modal EDITAR) ---
    function toggleEditFields() {
        var condicion = $('input[name="editCondicionTipo"]:checked').val();
        if (condicion === 'Aprobado') {
            $('.edit-campo-aprobado').slideDown();
            $('.edit-campo-regular').slideUp();
            $('#editCondicionExamen, #editCalificacion').prop('required', true);
        } else if (condicion === 'Regular') {
            $('.edit-campo-aprobado').slideUp();
            $('.edit-campo-regular').slideDown();
            $('#editCondicionExamen, #editCalificacion').prop('required', false);
        }
    }
    $('input[name="editCondicionTipo"]').on('change', toggleEditFields);

    // --- Lógica para poblar el Modal de Edición ---
    $('#editModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Botón que activó el modal
        var modal = $(this);

        // Extraer datos de los atributos data-*
        var estadoOriginal = button.data('estado-original');
        var esAprobado = (estadoOriginal === 'Aprobación PreSistema');

        modal.find('#editIdMatriculacion').val(button.data('id-matriculacion'));
        modal.find('#editIdCalificacion').val(button.data('id-calificacion'));
        modal.find('#editIdInscripcion').val(button.data('id-inscripcion'));
        modal.find('#editEstadoOriginal').val(estadoOriginal);
        modal.find('#editMateriaNombre').text(button.data('materia-nombre'));
        modal.find('#editFechaObtencion').val(button.data('fecha'));

        // Configurar radios
        if (esAprobado) {
            modal.find('#editRadioAprobado').prop('checked', true);
            modal.find('#editRadioRegular').prop('checked', false).prop('disabled', true); // No se puede pasar de Aprobado a Regular
        } else {
            modal.find('#editRadioAprobado').prop('checked', false);
            modal.find('#editRadioRegular').prop('checked', true).prop('disabled', false);
        }

        // Poblar campos
        modal.find('#editCondicionExamen').val(button.data('id-condicion-examen'));
        modal.find('#editCalificacion').val(button.data('calificacion'));
        modal.find('#editLibro').val(button.data('libro'));
        modal.find('#editFolio').val(button.data('folio'));
        modal.find('#editTurnosTranscurridos').val(button.data('turnos'));

        // Disparar el toggle para mostrar los campos correctos
        toggleEditFields();
    });

    
    // --- CÓDIGO PARA MOSTRAR MODAL DE ERROR DE DUPLICADO (SI EXISTE) ---
    <?php if (isset($_SESSION['presistema_error_detalle'])): ?>
        var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        document.getElementById('errorModalBody').innerHTML = <?php echo json_encode($_SESSION['presistema_error_detalle']); ?>;
        errorModal.show();
        <?php unset($_SESSION['presistema_error_detalle']); ?>
    <?php endif; ?>
    
});

// --- Lógica de Confirmación (Eliminar y Editar) ---
var deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
var editConfirmModal = new bootstrap.Modal(document.getElementById('confirmEditModal'));

function confirmDeleteModal(idMatriculacion) {
    var url = `registrosPresistema.php?idAlumno=<?php echo $idAlumno; ?>&action=delete_presistema&idMatriculacion=${idMatriculacion}&confirm=yes`;
    $('#confirmDeleteButton').attr('href', url);
    deleteModal.show();
}

function confirmEdit() {
    // Opcional: Validar formulario del modal aquí si es necesario
    editConfirmModal.show();
}

function submitEditForm() {
    $('#editPresistemaForm').submit();
}
</script>

</body>
</html>