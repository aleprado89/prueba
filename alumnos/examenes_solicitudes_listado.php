<!DOCTYPE html>
<html lang="es">

<?php
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/pruebaSession.php';

if (isset($_COOKIE['idP']) && isset($_COOKIE['nombreP'])) 
{
  $idPlan = $_COOKIE['idP'];
  $nombrePlan = $_COOKIE['nombreP'];
}
else
{
  if ($_SERVER["REQUEST_METHOD"] == "POST") 
  {
    $idPlan = $_POST['idP'];
    $nombrePlan = $_POST['nombreP'];

    setcookie("idP", $idPlan, time() + (86400 * 30), "/"); // 86400 segundos = 1 día
    setcookie("nombreP", $nombrePlan, time() + (86400 * 30), "/"); // 86400 segundos = 1 día
  }
}

$idCicloLectivo = $_SESSION['idCiclo'];
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];

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
  <button type="button" class="btn btn-secondary float-end mb-3"
  onclick="window.history.back();">Volver</button>
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