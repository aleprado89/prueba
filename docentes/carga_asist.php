<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/verificarSesion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idMateria'])) {
$doc_legajo = $_SESSION['doc_legajo'];
$nombreDoc = $_SESSION['doc_apellido'] . ", " . $_SESSION['doc_nombre'];
$idMateria = $_POST['idMateria'];
$ciclolectivo = $_POST['ciclolectivo'];
$idCiclo = buscarIdCiclo($conn, $ciclolectivo);
$plan = $_POST['plan'];
$materia = $_POST['materia'];
$curso = $_POST['curso'];}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['actualizarTabla'])) {
    $anio = $_POST['anio'];
    $mes = $_POST['mes'];
    $dia = $_POST['dia'];
    $idMateria = $_POST['idMateria'];
    $materia = $_POST['materia'];
    $curso = $_POST['curso'];
    $plan = $_POST['plan'];
    $ciclolectivo = $_POST['ciclolectivo'];

    $idCicloLectivo = buscarIdCiclo($conn, $anio);
    $alumnosAsist = obtenerAsistenciaMateria($conn, $idMateria, $mes, $dia, $idCicloLectivo);

    $dia = substr($dia, 1);
    $fecha = $dia . '/' . $mes . '/' . $anio;

    $tabla = '<table id="tablaAsistencia" class="table table-hover col-12">';
    $tabla .= '<thead><tr class="table-primary"><th>Estudiante</th><th>' . $fecha . '</th></tr></thead><tbody>';

    if (empty($alumnosAsist)) {
      $tabla .= '<tr><td colspan="2">Sin registros</td></tr>';
    } else {
      foreach ($alumnosAsist as $alumno) {
        $tabla .= '<tr>';
        $tabla .= '<td>' . $alumno['apellido'] . ' ' . $alumno['nombre'] . '</td>';
        $tabla .= '<td class="border" contenteditable="true" data-id="' . $alumno['idAlumno'] . '">' . $alumno['dia'] . '</td>';
        $tabla .= '</tr>';
      }
    }
    $tabla .= '</tbody>***Guardado automático de todas las celdas modificadas que se encuentren en verde';
    $tabla .= '</table>';
    echo $tabla;
    die();
  } else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idAlumno'])) {
    $idAlumno = $_POST['idAlumno'];
    $anio = $_POST['anio'];
    $mes = $_POST['mes'];
    $dia = $_POST['dia'];
    $valor = $_POST['valor'];
    $idMateria = $_POST['idMateria'];
    $idCicloLectivo = buscarIdCiclo($conn, $anio);
    $respuesta = actualizarAsistxDocentes($conn, $idAlumno, $idCicloLectivo, $mes, $dia, $valor, $idMateria);
    echo $respuesta;}
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cargar Asistencias</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="../js/bootstrap.min.js"></script>

</head>
<body>
<?php include '../funciones/menu_docente.php'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="menudocentes.php">Inicio</a></li>
      <li class="breadcrumb-item active">Carga de asistencias</li>
    </ol>

    <div class="card padding col-12">
      <h5>Docente: <?= $nombreDoc ?></h5>
      <h5>Ciclo lectivo: <?= $ciclolectivo ?></h5>
      <h5>Carrera: <?= $plan ?></h5>
      <h5>Materia: <?= $materia ?></h5>
      <h5>Curso: <?= $curso ?></h5>
      <br>

      <div class="col-md-6">
        <label style="font-weight: bold;" for="fecha">SELECCIONE FECHA:</label>
<input type="date" id="fecha" name="fecha" min="<?= $ciclolectivo . '-01-01' ?>" max="<?= $ciclolectivo . '-12-31' ?>">
      </div>

      <br>
      <p><small>* Las asistencias se guardan automáticamente en cada modificación. La celda se pinta de verde cuando se guarda correctamente.<br>
        Valores permitidos: P/p (presente), A/a (ausente), J/j (justificado), M/m (media falta).
      </small></p>
    </div>

    <br>
    <div class="text-center">
      <a id="imprimir-asistencias" target="_blank">
    <button class="btn btn-primary" id="btn-imprimir" disabled>Imprimir Asistencias</button>
        </a>
    </div>
    <br>

    <div>
      <table id="tablaAsistencia" class="table table-hover col-12">
        <thead>
          <tr class="table-primary">
            <th>Estudiante</th>
            <th>Asistencia</th>
          </tr>
        </thead>
        <tbody>
          <tr><td colspan="2">Seleccione una fecha para ver asistencias.</td></tr>
        </tbody>
      </table>
    </div>

    <script src="../funciones/sessionControl.js"></script>
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
  </div>
</div>

<?php include '../funciones/footer.html'; ?>

<script>
$(document).ready(function () {
  var idMateria = '<?php echo $idMateria; ?>';
var materia = '<?php echo $materia; ?>';
var curso = '<?php echo $curso; ?>';
var plan = '<?php echo $plan; ?>';
var ciclolectivo = '<?php echo $ciclolectivo; ?>';
  $('#fecha').on('change', function () {
  $('#btn-imprimir').prop('disabled', false);

    let fecha = $(this).val();
    if (!fecha) return;

    let [anio, mes, dia] = fecha.split('-');
    mes = mes.replace(/^0+/, '');
    dia = 'd' + dia.replace(/^0+/, '');

    $.ajax({
      type: 'POST',
      url: 'carga_asist.php',
      data: {
        actualizarTabla: true,
        anio: anio,
        mes: mes,
        dia: dia,
        idMateria: idMateria,
    materia: materia,
    curso: curso,
    plan: plan,
    ciclolectivo: ciclolectivo
      },
      success: function (respuesta) {
        $('#tablaAsistencia').html(respuesta);
      }
    });

    let url = '../reportes/asistenciaDocPDF.php?idMateria=<?= $idMateria ?>&materia=<?= $materia ?>&curso=<?= $curso ?>&plan=<?= $plan ?>&ciclolectivo=<?= $idCiclo ?>&mes=' + mes;
    $('#imprimir-asistencias').attr('href', url);
  });

  $('#tablaAsistencia').on('input', 'td[contenteditable="true"]', function () {
    let $this = $(this);
    let id = $this.data('id');
    let asistencia = $this.text().trim();
    let fecha = $('#fecha').val();
    let [anio, mes, dia] = fecha.split('-');
    dia = 'd' + dia.replace(/^0+/, '');
    mes = mes.replace(/^0+/, '');

    if (asistencia.length > 5) {
      alert('La asistencia puede tener hasta 5 caracteres');
      $this.text(asistencia.substring(0, 5));
    }

    if (!/^[AaPpJjMm]*$/.test(asistencia)) {
      alert('Solo se permiten letras A, a, P, p, J, j, M, m');
      $this.text('');
      $.post('carga_asist.php', {
        idAlumno: id,
        anio: anio,
        mes: mes,
        dia: dia,
        idMateria: idMateria,
    materia: materia,
    curso: curso,
    plan: plan,
    ciclolectivo: ciclolectivo,
        valor: ""
      });
    } else {
      $this.css('background-color', 'lightgreen');
      $.post('carga_asist.php', {
        idAlumno: id,
        anio: anio,
        mes: mes,
        dia: dia,
        idMateria: idMateria,
    materia: materia,
    curso: curso,
    plan: plan,
    ciclolectivo: ciclolectivo,
        valor: asistencia
      });
    }
  });
});
</script>

<script>
$('#tablaAsistencia').on('keydown', 'td[contenteditable="true"]', function(e) {
  let $this = $(this);
  let id = $this.data('id');
  let asistencia = $this.text().trim();

  if (e.key === 'Enter' || e.key === 'ArrowDown') {
    let $nextCell = $this.closest('tr').next('tr').find('td[contenteditable="true"]');
    if ($nextCell.length > 0) {
      $nextCell.focus();
    }
  }

  if (e.key === 'ArrowUp') {
    let $prevCell = $this.closest('tr').prev('tr').find('td[contenteditable="true"]');
    if ($prevCell.length > 0) {
      $prevCell.focus();
    }
  }
});
</script>
</body>
</html>