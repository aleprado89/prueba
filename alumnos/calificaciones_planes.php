<!DOCTYPE html>
<html lang="es">

<?php
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/pruebaSession.php';

$idAlumno = $_SESSION['idAlumno'];
$nombreAlumno = $_SESSION['nombreAlumno'];

$listadoPlanes = array();
$listadoPlanes = buscarPlanes($conn, $idAlumno);
$cantidad = count($listadoPlanes);
?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Planes</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>

<body>
  <div class="card text-white bg-primary mb-3">
    <h3 class="card-header">
      <?php echo $nombreAlumno; ?>
    </h3>
  </div>
  <div class="container mt-5">
    <table class="table table-hover">
      <thead>
        <tr class="table-primary">
          <th scope="col" style="display:none;">idPlan</th>
          <th scope="col">Carrera</th>
          <th scope="col">Calificaciones</th>
        </tr>
      </thead>
      <tbody>

        <?php

        //RECORRER TABLA DE PLANES
        
        $a = 0;
        while ($a < $cantidad) {
          $idPlan = $listadoPlanes[$a]['idPlan'];
          $Plan = $listadoPlanes[$a]['Plan'];
          $a++;
          ?>

          <tr class="table-info">
            <td style="display:none;">
              <?php echo $idPlan ?>
            </td>
            <td>
              <?php echo $Plan ?>
            </td>
            <td><button type="button" onclick="verCalificaciones(this)" class="btn btn-primary">Ver
                Calificaciones</button></td>
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

  <script>
    function verCalificaciones(boton) {
      // Cargar idMateria y nombreMateria para pasar
      var idPlanSeleccionado = boton.closest('tr').querySelector('td:nth-child(1)').textContent;
      var nombrePlanCompleto = boton.closest('tr').querySelector('td:nth-child(2)').textContent;
      // Redirigir a otra página y pasar los datos como parámetro en la URL
      window.location.href =
        '../alumnos/calificaciones.php?idP=' + encodeURIComponent(idPlanSeleccionado) +
        '&nombreP=' + encodeURIComponent(nombrePlanCompleto);
    }
  </script>

</body>

</html>