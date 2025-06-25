<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/verificarSesion.php';

$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];

// Obtener el ID del ciclo lectivo desde la URL
$idCicloLectivo = isset($_GET['idCiclo']) ? filter_var($_GET['idCiclo'], FILTER_SANITIZE_NUMBER_INT) : null;
$mesSeleccionado = isset($_GET['mes']) ? filter_var($_GET['mes'], FILTER_SANITIZE_NUMBER_INT) : date('n'); // Por defecto, el mes actual

// Si no se proporcionó un idCiclo, o el idCiclo no es válido, intentar obtener el más reciente del alumno
if (!$idCicloLectivo || !is_numeric($idCicloLectivo)) {
    $primerMateria = null;
    // Esto es un parche para obtener el idCicloLectivo si el usuario llega aquí sin él.
    // Lo ideal es que siempre llegue con un idCiclo
    $materiasAlumno = buscarMaterias($conn, $idAlumno, $_SESSION['idP']); // Asume que $_SESSION['idP'] es el plan actual
    if (!empty($materiasAlumno)) {
        // Tomamos el idCicloLectivo de la primera materia
        $idCicloLectivo = $materiasAlumno[0]['idCicloLectivoMateria'];
    }
}

$anioCiclo = $idCicloLectivo ? buscarnombreCiclo($conn, $idCicloLectivo) : 'N/A';

// Obtener los meses con registro de asistencia para el alumno en el ciclo lectivo seleccionado
$mesesConAsistencia = obtenerMesesConAsistencia($conn, $idAlumno, $idCicloLectivo);

// Si el mes seleccionado no tiene asistencia, o no se ha seleccionado ninguno y hay meses con asistencia,
// seleccionar el primer mes con asistencia si existe.
if (!in_array($mesSeleccionado, $mesesConAsistencia) && !empty($mesesConAsistencia)) {
    $mesSeleccionado = $mesesConAsistencia[0];
} elseif (empty($mesesConAsistencia)) {
    $mesSeleccionado = null; // No hay meses con asistencia
}

// Nombres de los meses
$nombreMeses = array(
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
);

$listadoMateriasAsistencia = [];
if ($idCicloLectivo && $mesSeleccionado) {
    $listadoMaterias = obtenerMateriasDeAlumnoEnCiclo($conn, $idAlumno, $idCicloLectivo);
    foreach ($listadoMaterias as $materia) {
        $asistenciaMateria = obtenerAsistenciaDeMateriaParaAlumno($conn, $idAlumno, $materia['idMateria'], $mesSeleccionado, $idCicloLectivo);
        if ($asistenciaMateria) { // Solo si hay registro para esa materia en ese mes
            $listadoMateriasAsistencia[] = [
                'nombreMateria' => $materia['nombreMateria'],
                'asistencia' => $asistenciaMateria
            ];
        }
    }
}

// Obtener el número de días del mes seleccionado para limitar el bucle.
$num_dias_mes_seleccionado = ($mesSeleccionado && $anioCiclo != 'N/A') ? cal_days_in_month(CAL_GREGORIAN, $mesSeleccionado, $anioCiclo) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ver Asistencia</title>
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="../js/bootstrap.min.js"></script>
  <style>
    .attendance-card {
      margin-bottom: 15px;
      padding: 15px;
      border: 1px solid #dee2e6;
      border-radius: 5px;
      background-color: #f8f9fa;
    }
    .attendance-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .attendance-item {
      display: inline-block;
      margin-right: 15px;
      margin-bottom: 5px;
      font-size: 0.9em;
    }
    .attendance-item strong {
        font-weight: normal; /* No negrita para el día */
        color: #0056b3; /* Color para los valores */
    }
    .attendance-item span {
        font-weight: bold; /* Negrita para el código de asistencia */
        color: #212529;
    }
  </style>
</head>

<body>
<?php include '../funciones/menu.php'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="menualumnos.php">Inicio</a></li>
      <li class="breadcrumb-item"><a href="calificaciones_planes.php">Carreras</a></li>
      <li class="breadcrumb-item"><a href="calificaciones.php">Materias</a></li>
      <li class="breadcrumb-item active">Asistencia</li>
    </ol>

    <div class="card padding col-12">
      <h5><?php echo "Alumno: " . $nombreAlumno; ?> </h5>
      <h5><?php echo "Ciclo Lectivo: " . $anioCiclo; ?></h5>
    </div>
    <br>

    <div class="row">
      <div class="col-md-6 mb-3">
        <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="form-filter">
            <input type="hidden" name="idCiclo" value="<?php echo $idCicloLectivo; ?>">
            <label for="mes_asistencia">Seleccionar Mes:</label>
            <select class="form-select" id="mes_asistencia" name="mes" onchange="this.form.submit()">
                <?php if (empty($mesesConAsistencia)): ?>
                    <option value="">No hay meses con asistencia</option>
                <?php else: ?>
                    <?php foreach ($mesesConAsistencia as $mes): ?>
                        <option value="<?php echo $mes; ?>" <?php echo ($mes == $mesSeleccionado) ? 'selected' : ''; ?>>
                            <?php echo $nombreMeses[$mes]; ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </form>
      </div>
      <!-- Se ha eliminado el botón de "Imprimir Asistencia del Mes" -->
    </div>

    <h4>Asistencia del mes de <?php echo !empty($mesSeleccionado) ? $nombreMeses[$mesSeleccionado] : 'N/A'; ?></h4>

    <?php if ($mesSeleccionado && !empty($listadoMateriasAsistencia)): ?>
      <?php foreach ($listadoMateriasAsistencia as $materiaData): ?>
        <div class="attendance-card">
          <h5>Materia: <?php echo $materiaData['nombreMateria']; ?></h5>
          <ul class="attendance-list">
            <?php
            $asistencia_detallada = [];
            for ($i = 1; $i <= $num_dias_mes_seleccionado; $i++) {
                $dia_key = 'd' . $i;
                if (!empty($materiaData['asistencia'][$dia_key])) {
                    $asistencia_detallada[] = "<strong>" . $i . "/" . $mesSeleccionado . ":</strong> <span>" . $materiaData['asistencia'][$dia_key] . "</span>";
                }
            }
            echo '<li class="attendance-item">' . (empty($asistencia_detallada) ? 'No hay registros de asistencia para este mes.' : implode(', </li><li class="attendance-item">', $asistencia_detallada)) . '</li>';
            ?>
          </ul>
        </div>
      <?php endforeach; ?>
    <?php elseif ($mesSeleccionado === null && !empty($mesesConAsistencia)): ?>
        <div class="alert alert-info" role="alert">
            Por favor, seleccione un mes de la lista para ver la asistencia.
        </div>
    <?php else: ?>
      <div class="alert alert-info" role="alert">
        No hay registros de asistencia disponibles para este alumno en el ciclo lectivo <?php echo $anioCiclo; ?> o en el mes seleccionado.
      </div>
    <?php endif; ?>

  </div>
</div>

<script src="../funciones/sessionControl.js"></script>
<script src="../js/jquery-3.7.1.slim.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>

<?php include '../funciones/footer.html'; ?>

</body>
</html>