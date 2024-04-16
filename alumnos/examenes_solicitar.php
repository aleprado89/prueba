<!DOCTYPE html>
<html lang="es">

<?php
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
include '../funciones/pruebaSession.php';

$idCicloLectivo = $_SESSION['idCicloLectivo'];
$idAlumno = $_SESSION['idAlumno'];
$idMateria = $_GET['idM'];
$nombreAlumno = $_SESSION['nombreAlumno'];
$nombreMateria = $_GET['nombreM'];
$nombreCurso = $_GET['nombreC'];

$existeSolicitud = array();
$existeSolicitud = existeSolicitudExamen($conn, $idAlumno, $idMateria, $idCicloLectivo, $datosColegio[0]['idTurno']);
$cantidadSolicitudes = count($existeSolicitud);

$listadoFechasExamenes = array();
$listadoFechasExamenes = buscarFechasExamenTurno($conn, $idMateria, $nombreCurso, $idCicloLectivo, $datosColegio[0]['idTurno']);
$cantidadFechas = count($listadoFechasExamenes);
?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitudes</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>

<body>
  <div class="card text-white bg-primary mb-3">
    <h3 class="card-header">
      <?php echo $nombreAlumno; ?>
    </h3>
    <h5 class="card-header">
      MATERIA:
      <?php echo $nombreMateria; ?>
    </h5>
    <h5 class="card-header">
      CURSO:
      <?php echo $nombreCurso; ?>
    </h5>
    <h5 class="card-header">
      TURNO:
      <?php echo $datosColegio[0]['nombreTurno']; ?>
    </h5>
  </div>

  <fieldset>
    <legend class="mt-4">Fechas Disponibles</legend>

    <?php
    $a = 0;
    while ($a < $cantidadFechas) {
      $idFechaExamen = $listadoFechasExamenes[$a]['idFechaExamen'];
      $Fecha = $listadoFechasExamenes[$a]['Fecha'];
      $Hora = $listadoFechasExamenes[$a]['Hora'];
      $idRB = "fechaId" . $a;
      ?>

      <div class="form-check">
        <div class="card text-white bg-success mb-3" style="max-width: 20rem;">
          <input class="form-check-input" type="radio" name="optionsRadios" value="<?php echo $idFechaExamen; ?>">
          <label class="form-check-label" for="optionsRadios1">
            <?php echo $Fecha . " " . $Hora; ?>
          </label>
        </div>
      </div>

      <?php
      $a++;
    }
    if ($cantidadFechas == 0) {
      ?>

      <div class="form-check">
        <input class="form-check-input" type="radio" name="optionsRadios" id="optionsRadios1" value="option1" checked=""
          disabled="">
        <label class="form-check-label" for="optionsRadios1">
          Sin Fechas
        </label>
      </div>

      <?php
    }
    ?>

  </fieldset>

  <div class="container mt-5">
    <table class="table table-hover">
      <caption>Solicitudes Existentes</caption>
      <thead>
        <tr class="table-primary">
          <th scope="col" style="display:none;">idInscripcionWeb</th>
          <th scope="col">Materia</th>
          <th scope="col">Fecha</th>
          <th scope="col">Estado</th>
          <th scope="col">Observaciones</th>
        </tr>
      </thead>
      <tbody>

        <?php

        //RECORRER TABLA DE SOLICITUDES
        
        $a = 0;
        while ($a < $cantidadSolicitudes) {
          $idInscripcionWeb = $listadoSolicitudes[$a]['idInscripcionWeb'];
          $Materia = $listadoSolicitudes[$a]['Materia'];
          $Fecha = $listadoSolicitudes[$a]['Fecha'];
          $Estado = $listadoSolicitudes[$a]['Estado'];
          $Observaciones = $listadoSolicitudes[$a]['Observaciones'];
          $a++;
          ?>

          <tr class="table-info">
            <td style="display:none;">
              <?php echo $idInscripcionWeb ?>
            </td>
            <td>
              <?php echo $Materia ?>
            </td>
            <td>
              <?php echo $Fecha ?>
            </td>
            <td>
              <?php echo $Estado ?>
            </td>
            <td>
              <?php echo $Observaciones ?>
            </td>
          </tr>

          <?php
        }
        ?>

      </tbody>
    </table>
  </div>

  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.slim.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>
</body>

</html>