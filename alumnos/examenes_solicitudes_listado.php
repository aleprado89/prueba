<!DOCTYPE html>
<html lang="es">

<?php
include '../inicio/conexion.php';
include '../funciones/consultas.php';

$idCicloLectivo = 1; //$_SESSION['idCicloLectivo'];
$idAlumno = 550; //$_SESSION['idAlumno'];
$idPlan = $_GET['idP'];
$nombreAlumno = "NOMBRE ALUMNO"; //$_SESSION['nombreAlumno'];
$nombrePlan = $_GET['nombreP'];

$listadoSolicitudes = array();
$listadoSolicitudes = buscarSolicitudesExamen($conn, $idAlumno, $idPlan, $idCicloLectivo);
$cantidad = count($listadoSolicitudes);
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
    <h4 class="card-header">
      <?php echo $nombrePlan; ?>
    </h4>
  </div>
  <div class="container mt-5">
    <table class="table table-hover">
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
        while ($a < $cantidad) {
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