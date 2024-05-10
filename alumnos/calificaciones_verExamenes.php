<!DOCTYPE html>
<html lang="es">

<?php
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/pruebaSession.php';

$idAlumno = $_SESSION['alu_idAlumno'];
$idMateria = $_GET['idM'];
$nombreAlumno = $_SESSION['alu_apellido'].", ".$_SESSION['alu_nombre'];
$nombreMateria = $_GET['nombreM'];

$listadoExamenes = array();
$listadoExamenes = buscarExamenes($conn, $idAlumno, $idMateria);
$cantidad = count($listadoExamenes);
?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exámenes</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>

<body>
  <div class="card text-white bg-primary mb-3">
    <h3 class="card-header">
      <?php echo $nombreAlumno; ?>
    </h3>
    <h4 class="card-header">
      <?php echo $nombreMateria; ?>
    </h4>
  </div>
  <div class="container mt-5">
    <table class="table table-hover">
      <thead>
        <tr class="table-primary">
          <th scope="col">Fecha</th>
          <th scope="col">Calificación</th>
        </tr>
      </thead>
      <tbody>

        <?php

        //RECORRER TABLA DE EXAMENES
        
        $a = 0;
        while ($a < $cantidad) {
          $Fecha = $listadoExamenes[$a]['Fecha'];
          $Calificacion = $listadoExamenes[$a]['Calificacion'];
          $a++;
          ?>

          <tr class="table-info">
            <td>
              <?php echo $Fecha ?>
            </td>
            <td>
              <?php echo $Calificacion ?>
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