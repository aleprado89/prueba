<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/verificarSesion.php';

$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];

// Capturar los nuevos parámetros de la URL
$idMateria = isset($_GET['idMateria']) ? filter_var($_GET['idMateria'], FILTER_SANITIZE_NUMBER_INT) : null;
$idCicloLectivo = isset($_GET['idCiclo']) ? filter_var($_GET['idCiclo'], FILTER_SANITIZE_NUMBER_INT) : null;
$nombreMateria = isset($_GET['nombreMateria']) ? urldecode(filter_var($_GET['nombreMateria'], FILTER_SANITIZE_STRING)) : 'Materia Desconocida';

// Redireccionar si faltan parámetros críticos
if (!$idMateria || !$idCicloLectivo) {
    // Podrías redirigir a una página de error o a calificaciones.php
    header('Location: calificaciones.php');
    exit();
}

$anioCiclo = buscarnombreCiclo($conn, $idCicloLectivo);

// Obtener los meses con registro de asistencia para ESTA MATERIA en este ciclo lectivo.
$mesesConAsistencia = obtenerMesesConAsistenciaMateria($conn, $idAlumno, $idMateria, $idCicloLectivo);

// Determinar el mes seleccionado: por defecto el mes actual, o el primer mes con asistencia si el actual no tiene.
$mesActual = date('n');
$mesSeleccionado = isset($_GET['mes']) ? filter_var($_GET['mes'], FILTER_SANITIZE_NUMBER_INT) : $mesActual;

// Si el mes seleccionado (o el actual por defecto) no tiene registros para esta materia,
// y hay otros meses con asistencia, selecciona el primer mes con asistencia.
if (!in_array($mesSeleccionado, $mesesConAsistencia) && !empty($mesesConAsistencia)) {
    $mesSeleccionado = $mesesConAsistencia[0];
} elseif (empty($mesesConAsistencia)) {
    $mesSeleccionado = null; // No hay asistencia para ningún mes en esta materia
}

// Nombres de los meses
$nombreMeses = array(
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
);

$asistenciaRecord = null; // Variable para almacenar el registro de asistencia del mes
if ($idMateria && $idCicloLectivo && $mesSeleccionado && $anioCiclo != 'N/A') {
    $asistenciaRecord = obtenerAsistenciaRegistroMateriaMes($conn, $idAlumno, $idMateria, $mesSeleccionado, $idCicloLectivo);
}

// Obtener el número de días del mes seleccionado para limitar el bucle.
$num_dias_mes_seleccionado = ($mesSeleccionado && $anioCiclo != 'N/A') ? cal_days_in_month(CAL_GREGORIAN, $mesSeleccionado, $anioCiclo) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Asistencia por Materia</title>
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="../js/bootstrap.min.js"></script>
  <!-- Ya no se necesita el estilo personalizado attendance-table -->
<link rel="icon" type="image/png" href="../img/icon.png">

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
      <h5><?php echo "Materia: " . $nombreMateria; ?> </h5>
      <h5><?php echo "Ciclo Lectivo: " . $anioCiclo; ?></h5>
      <br>
      <!-- Selector de mes se mueve aquí, dentro del card -->
      <div class="row col-12 col-md-4">
        <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="form-filter">
            <input type="hidden" name="idMateria" value="<?php echo $idMateria; ?>">
            <input type="hidden" name="idCiclo" value="<?php echo $idCicloLectivo; ?>">
            <input type="hidden" name="nombreMateria" value="<?php echo urlencode($nombreMateria); ?>">
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
    </div>
    <br> <!-- Este <br> es para separar el card principal de la tabla de asistencia -->

    <h4>Asistencia para <?php echo $nombreMateria; ?> en <?php echo !empty($mesSeleccionado) ? $nombreMeses[$mesSeleccionado] . " de " . $anioCiclo : 'el mes seleccionado'; ?></h4>

    <div class="container mt-5">
      <?php if ($asistenciaRecord && $num_dias_mes_seleccionado > 0): ?>
        <table class="table table-hover"> <!-- Clases de tabla de calificaciones.php -->
          <thead>
            <tr class="table-primary">
              <th>Fecha</th>
              <th>Asistencia</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $hasData = false;
            for ($i = 1; $i <= $num_dias_mes_seleccionado; $i++) {
                $dia_key = 'd' . $i;
                if (isset($asistenciaRecord[$dia_key]) && !empty(trim($asistenciaRecord[$dia_key]))) {
                    $hasData = true;
                    $fecha = sprintf("%02d/%02d/%s", $i, $mesSeleccionado, $anioCiclo);
                    echo '<tr class="table-light">'; // Clase de fila de calificaciones.php
                    echo '<td>' . $fecha . '</td>';
                    echo '<td>' . trim($asistenciaRecord[$dia_key]) . '</td>';
                    echo '</tr>';
                }
            }
            if (!$hasData) {
                echo '<tr class="table-light"><td colspan="2">No hay registros de asistencia para el mes seleccionado en esta materia.</td></tr>';
            }
            ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="alert alert-danger" role="alert">
          No hay registros de asistencia disponibles para esta materia en el ciclo lectivo <?php echo $anioCiclo; ?> o en el mes seleccionado.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="../funciones/sessionControl.js"></script>
<script src="../js/jquery-3.7.1.slim.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>

<?php include '../funciones/footer.html'; ?>

</body>
</html>