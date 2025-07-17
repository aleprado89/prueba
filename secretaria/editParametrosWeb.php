<?php
// Habilitar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CRÍTICO: Incluir verificarSesion.php al principio.
// Esto se encarga de session_start(), la validación de $_SESSION['sec_nombreUsuario'],
// y la redirección si no está logueado. También establece $_SESSION['active_user_identifier'].
include '../funciones/verificarSesion.php';

// Las líneas comentadas a continuación ya no son necesarias gracias a verificarSesion.php.
// session_start();
// if (!isset($_SESSION['sec_nombreUsuario'])) {
//    header('Location: loginAdmin.php');
//    exit;
// }

// Incluir la conexión a la base de datos
include '../inicio/conexion.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Error crítico: Conexión a la base de datos no establecida o inválida.");
}

// Variables para almacenar mensajes de feedback
$message = '';
$message_type = ''; // 'success' o 'danger'

// --- 1. PROCESAR EL FORMULARIO SI SE HA ENVIADO ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ya no necesitamos $idColegio de $_POST, usaremos codnivel = 6 directamente.

    try {
        switch ($_POST['form_action'] ?? '') {
            case 'update_exam':
                $anioautoweb = $_POST['anioautoweb_exam'] ?? null;
                $iDturnoautoweb = $_POST['iDturnoautoweb'] ?? null;
                $inscExamDesde = !empty($_POST['inscExamDesde']) ? date('Y-m-d H:i:s', strtotime($_POST['inscExamDesde'])) : null;
                $inscExamLectDesde = !empty($_POST['inscExamLectDesde']) ? date('Y-m-d H:i:s', strtotime($_POST['inscExamLectDesde'])) : null;
                $inscExamHasta = !empty($_POST['inscExamHasta']) ? date('Y-m-d H:i:s', strtotime($_POST['inscExamHasta'])) : null;

                // CAMBIO: Usamos WHERE codnivel = 6
                $stmt = $conn->prepare("UPDATE colegio SET anioautoweb = ?, iDturnoautoweb = ?, inscExamDesde = ?, inscExamLectDesde = ?, inscExamHasta = ? WHERE codnivel = 6");
                // CAMBIO: No pasamos $idColegio en bind_param
                $stmt->bind_param("iisss", $anioautoweb, $iDturnoautoweb, $inscExamDesde, $inscExamLectDesde, $inscExamHasta);

                if ($stmt->execute()) {
                    $message = "Parámetros de inscripción a exámenes actualizados correctamente.";
                    $message_type = 'success';
                } else {
                    throw new Exception("Error al actualizar inscripción a exámenes: " . $stmt->error);
                }
                $stmt->close();
                break;

            case 'update_cursado':
                $anioautoweb = $_POST['anioautoweb_cursado'] ?? null;
                $inscCursDesde = !empty($_POST['inscCursDesde']) ? date('Y-m-d H:i:s', strtotime($_POST['inscCursDesde'])) : null;
                $inscCursLectDesde = !empty($_POST['inscCursLectDesde']) ? date('Y-m-d H:i:s', strtotime($_POST['inscCursLectDesde'])) : null;
                $inscCursHasta = !empty($_POST['inscCursHasta']) ? date('Y-m-d H:i:s', strtotime($_POST['inscCursHasta'])) : null;

                // CAMBIO: Usamos WHERE codnivel = 6
                $stmt = $conn->prepare("UPDATE colegio SET anioautoweb = ?, inscCursDesde = ?, inscCursLectDesde = ?, inscCursHasta = ? WHERE codnivel = 6");
                // CAMBIO: No pasamos $idColegio en bind_param
                $stmt->bind_param("isss", $anioautoweb, $inscCursDesde, $inscCursLectDesde, $inscCursHasta);

                if ($stmt->execute()) {
                    $message = "Parámetros de inscripción a cursado actualizados correctamente.";
                    $message_type = 'success';
                } else {
                    throw new Exception("Error al actualizar inscripción a cursado: " . $stmt->error);
                }
                $stmt->close();
                break;

            case 'update_actas': // Este case antes también contenía cargaActaVolTurno
                $anio_carga_notas = $_POST['anio_carga_notas'] ?? null;
                $cargaActaVolTurno = $_POST['cargaActaVolTurno'] ?? null;
                $cargaActaVolDesde = !empty($_POST['cargaActaVolDesde']) ? date('Y-m-d H:i:s', strtotime($_POST['cargaActaVolDesde'])) : null;
                $cargaActaVolHasta = !empty($_POST['cargaActaVolHasta']) ? date('Y-m-d H:i:s', strtotime($_POST['cargaActaVolHasta'])) : null;

                // CAMBIO: Usamos WHERE codnivel = 6
                $stmt = $conn->prepare("UPDATE colegio SET anio_carga_notas = ?, cargaActaVolTurno = ?, cargaActaVolDesde = ?, cargaActaVolHasta = ? WHERE codnivel = 6");
                // CAMBIO: No pasamos $idColegio en bind_param
                $stmt->bind_param("iisss", $anio_carga_notas, $cargaActaVolTurno, $cargaActaVolDesde, $cargaActaVolHasta);

                if ($stmt->execute()) {
                    $message = "Parámetros de carga de actas actualizados correctamente.";
                    $message_type = 'success';
                } else {
                    throw new Exception("Error al actualizar carga de actas: " . $stmt->error);
                }
                $stmt->close();
                break;

            case 'update_docente_perms': // NUEVO CASE para el switch de permisos docentes
                $docenteModifica = isset($_POST['docenteModifica']) ? 1 : 0; // 1 si está marcado, 0 si no
                
                // CAMBIO: Usamos WHERE codnivel = 6
                $stmt = $conn->prepare("UPDATE colegio SET docenteModifica = ? WHERE codnivel = 6");
                $stmt->bind_param("i", $docenteModifica);

                if ($stmt->execute()) {
                    $message = "Permisos de modificación para docentes actualizados correctamente.";
                    $message_type = 'success';
                } else {
                    throw new Exception("Error al actualizar permisos de docentes: " . $stmt->error);
                }
                $stmt->close();
                break;

            default:
                $message = "Acción de formulario no reconocida.";
                $message_type = 'danger';
        }
    } catch (Exception $e) {
        $message = "Error en la base de datos: " . $e->getMessage();
        $message_type = 'danger';
    }

    // Redirigir para evitar reenvío del formulario al refrescar
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $message_type;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Recuperar mensajes de sesión y limpiarlos
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// --- 2. OBTENER LOS DATOS ACTUALES PARA MOSTRAR EN EL FORMULARIO ---
$colegioParams = [];
// CAMBIO: Seleccionamos docenteModifica también
$stmt = $conn->prepare("SELECT * FROM colegio WHERE codnivel = 6 LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $colegioParams = $result->fetch_assoc();
    // Ya no necesitamos idColegio para WHERE, pero podemos mantenerlo si se usa para mostrar algo.
    // $colegioId = $colegioParams['idColegio']; 
} else {
    error_log("No se encontró ningún registro en la tabla 'colegio' con codnivel = 6.");
    $colegioParams = array_fill_keys([
        'idColegio', 'anioautoweb', 'iDturnoautoweb', 'inscExamDesde', 'inscExamHasta',
        'inscCursDesde', 'inscCursHasta', 'inscExamLectDesde', 'inscCursLectDesde',
        'cargaActaVolDesde', 'cargaActaVolHasta', 'anio_carga_notas', 'cargaActaVolTurno',
        'docenteModifica' // NUEVO: Inicializar también
    ], null);
    $message = "Advertencia: No se encontraron parámetros de colegio para editar (codnivel = 6).";
    $message_type = 'warning';
}
$stmt->close();


// --- 3. OBTENER OPCIONES PARA SELECTS (Años y Turnos) ---
$anios = [];
$stmtAnios = $conn->query("SELECT anio FROM ciclolectivo ORDER BY anio DESC");
if ($stmtAnios) {
    while ($row = $stmtAnios->fetch_assoc()) {
        $anios[] = $row['anio'];
    }
    $stmtAnios->free();
} else {
    error_log("Error al obtener años de ciclolectivo: " . $conn->error);
}

$turnosExamenes = [];
$stmtTurnos = $conn->query("SELECT idTurno, nombre FROM turnosexamenes ORDER BY idTurno ASC");
if ($stmtTurnos) {
    while ($row = $stmtTurnos->fetch_assoc()) {
        $turnosExamenes[] = $row;
    }
    $stmtTurnos->free();
} else {
    error_log("Error al obtener turnos de examenes: " . $conn->error);
}

// Función auxiliar para formatear datetime de DB a HTML5 input (YYYY-MM-DDTHH:MM)
function formatDateTimeForInput($dateTimeStr) {
    if (empty($dateTimeStr) || $dateTimeStr === '0000-00-00 00:00:00') {
        return '';
    }
    try {
        $dt = new DateTime($dateTimeStr);
        return $dt->format('Y-m-d\TH:i');
    } catch (Exception $e) {
        error_log("Error al formatear fecha/hora: " . $dateTimeStr . " - " . $e->getMessage());
        return '';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar Parámetros Web - Secretaría</title>
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
</head>
<body>

<!-- CRÍTICO: Inyecta window.usuarioActual aquí, justo después de abrir <body> -->
<!-- Esto asegura que sessionControl.js tenga el valor antes de ejecutarse -->
<script>
    window.usuarioActual = "<?php echo htmlspecialchars($_SESSION['active_user_identifier'] ?? 'null'); ?>";
    console.log("INJECTED window.usuarioActual (en editParametrosWeb):", window.usuarioActual);
</script>

<?php include '../funciones/menu_secretaria.php'; ?>

<div class="container mt-4">
  <h2 class="mb-4">Editar Parámetros de la Plataforma Web</h2>

  <?php if ($message): ?>
    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($message); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    <!-- Card Inscripción a Examen -->
    <div class="col-md-6 mb-4">
      <div class="card shadow-sm h-100">
        <div class="card-header card-header-custom d-flex align-items-center">
          <i class="bi bi-calendar-check me-2"></i> Inscripción a Examen (Autogestión Alumnos)
        </div>
        <div class="card-body">
          <form method="POST" action="">
            <!-- Eliminamos el ID del colegio, ya no es necesario -->
            <input type="hidden" name="form_action" value="update_exam">

            <div class="mb-3">
              <label for="anioautoweb_exam" class="form-label">Año (Autogestión Alumnos)</label>
              <select class="form-select" id="anioautoweb_exam" name="anioautoweb_exam" required>
                <?php foreach ($anios as $año): ?>
                  <option value="<?php echo htmlspecialchars($año); ?>" <?php echo (isset($colegioParams['anioautoweb']) && $colegioParams['anioautoweb'] == $año) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($año); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="iDturnoautoweb" class="form-label">Turno (Autogestión Alumnos)</label>
              <select class="form-select" id="iDturnoautoweb" name="iDturnoautoweb">
                <option value="">Seleccione un Turno</option>
                <?php foreach ($turnosExamenes as $turno): ?>
                  <option value="<?php echo htmlspecialchars($turno['idTurno']); ?>" <?php echo (isset($colegioParams['iDturnoautoweb']) && $colegioParams['iDturnoautoweb'] == $turno['idTurno']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($turno['nombre']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="inscExamDesde" class="form-label">Inicio Inscripción</label>
              <input type="datetime-local" class="form-control" id="inscExamDesde" name="inscExamDesde" value="<?php echo formatDateTimeForInput($colegioParams['inscExamDesde'] ?? ''); ?>">
            </div>

            <div class="mb-3">
              <label for="inscExamLectDesde" class="form-label">Solo Lectura Desde</label>
              <input type="datetime-local" class="form-control" id="inscExamLectDesde" name="inscExamLectDesde" value="<?php echo formatDateTimeForInput($colegioParams['inscExamLectDesde'] ?? ''); ?>">
            </div>

            <div class="mb-3">
              <label for="inscExamHasta" class="form-label">Cierre Inscripción</label>
              <input type="datetime-local" class="form-control" id="inscExamHasta" name="inscExamHasta" value="<?php echo formatDateTimeForInput($colegioParams['inscExamHasta'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary btn-navbar-color">Guardar Inscripción Examen</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Card Inscripción a Cursado -->
    <div class="col-md-6 mb-4">
      <div class="card shadow-sm h-100">
        <div class="card-header card-header-custom d-flex align-items-center">
          <i class="bi bi-person-lines-fill me-2"></i> Inscripción a Cursado (Autogestión Alumnos)
        </div>
        <div class="card-body">
          <form method="POST" action="">
            <!-- Eliminamos el ID del colegio, ya no es necesario -->
            <input type="hidden" name="form_action" value="update_cursado">

            <div class="mb-3">
              <label for="anioautoweb_cursado" class="form-label">Año (Autogestión Alumnos)</label>
              <select class="form-select" id="anioautoweb_cursado" name="anioautoweb_cursado" required>
                <?php foreach ($anios as $año): ?>
                  <option value="<?php echo htmlspecialchars($año); ?>" <?php echo (isset($colegioParams['anioautoweb']) && $colegioParams['anioautoweb'] == $año) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($año); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="inscCursDesde" class="form-label">Inicio Inscripción</label>
              <input type="datetime-local" class="form-control" id="inscCursDesde" name="inscCursDesde" value="<?php echo formatDateTimeForInput($colegioParams['inscCursDesde'] ?? ''); ?>">
            </div>

            <div class="mb-3">
              <label for="inscCursLectDesde" class="form-label">Solo Lectura Desde</label>
              <input type="datetime-local" class="form-control" id="inscCursLectDesde" name="inscCursLectDesde" value="<?php echo formatDateTimeForInput($colegioParams['inscCursLectDesde'] ?? ''); ?>">
            </div>

            <div class="mb-3">
              <label for="inscCursHasta" class="form-label">Cierre Inscripción</label>
              <input type="datetime-local" class="form-control" id="inscCursHasta" name="inscCursHasta" value="<?php echo formatDateTimeForInput($colegioParams['inscCursHasta'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary btn-navbar-color">Guardar Inscripción Cursado</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Card Carga de Acta (Autogestión Docente) -->
    <div class="col-md-6 mb-4">
      <div class="card shadow-sm h-100">
        <div class="card-header card-header-custom d-flex align-items-center">
          <i class="bi bi-pencil-square me-2"></i> Carga de Acta (Autogestión Docente)
        </div>
        <div class="card-body">
          <form method="POST" action="">
            <!-- Eliminamos el ID del colegio, ya no es necesario -->
            <input type="hidden" name="form_action" value="update_actas">

            <div class="mb-3">
              <label for="anio_carga_notas" class="form-label">Año (Carga de Notas)</label>
              <select class="form-select" id="anio_carga_notas" name="anio_carga_notas" required>
                <?php foreach ($anios as $año): ?>
                  <option value="<?php echo htmlspecialchars($año); ?>" <?php echo (isset($colegioParams['anio_carga_notas']) && $colegioParams['anio_carga_notas'] == $año) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($año); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="cargaActaVolTurno" class="form-label">Turno (Carga de Actas)</label>
              <select class="form-select" id="cargaActaVolTurno" name="cargaActaVolTurno">
                <option value="">Seleccione un Turno</option>
                <?php foreach ($turnosExamenes as $turno): ?>
                  <option value="<?php echo htmlspecialchars($turno['idTurno']); ?>" <?php echo (isset($colegioParams['cargaActaVolTurno']) && $colegioParams['cargaActaVolTurno'] == $turno['idTurno']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($turno['nombre']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="cargaActaVolDesde" class="form-label">Inicio Carga de Acta</label>
              <input type="datetime-local" class="form-control" id="cargaActaVolDesde" name="cargaActaVolDesde" value="<?php echo formatDateTimeForInput($colegioParams['cargaActaVolDesde'] ?? ''); ?>">
            </div>

            <div class="mb-3">
              <label for="cargaActaVolHasta" class="form-label">Cierre Carga de Acta</label>
              <input type="datetime-local" class="form-control" id="cargaActaVolHasta" name="cargaActaVolHasta" value="<?php echo formatDateTimeForInput($colegioParams['cargaActaVolHasta'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary btn-navbar-color">Guardar Carga de Acta</button>
          </form>
        </div>
      </div>
    </div>
    
    <!-- NUEVA CARD: Permisos de Docentes -->
    <div class="col-md-6 mb-4">
      <div class="card shadow-sm h-100">
        <div class="card-header card-header-custom d-flex align-items-center">
          <i class="bi bi-gear me-2"></i> Permisos de Docentes
        </div>
        <div class="card-body">
          <form method="POST" action="">
            <input type="hidden" name="form_action" value="update_docente_perms">
            
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="docenteModificaSwitch" name="docenteModifica" value="1" <?php echo (isset($colegioParams['docenteModifica']) && $colegioParams['docenteModifica'] == 1) ? 'checked' : ''; ?>>
              <label class="form-check-label" for="docenteModificaSwitch">Docentes pueden modificar calificaciones y asistencias</label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-navbar-color">Guardar Permisos</button>
          </form>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Bootstrap JS y dependencias -->
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script>
// Inicializar tooltips de Bootstrap
$(function () {
  $('[data-bs-toggle="tooltip"]').tooltip();
})
</script>
<!-- Asegúrate de que sessionControl.js se cargue DESPUÉS de la inyección de window.usuarioActual -->
<script src="../funciones/sessionControl.js"></script>
<?php include '../funciones/footer.html'; ?>

</body>
</html>