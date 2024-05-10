<!DOCTYPE html>
<html lang="es">

<?php
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/pruebaSession.php';

$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];

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
  <button type="button" class="btn btn-secondary float-end mb-3" onclick="window.history.back();">Volver</button>
  <div class="container mt-5">
    
    <form id="envio" action="../alumnos/examenes_materias.php" method="post">
      <!-- Inputs ocultos para enviar los datos de la primera y segunda columna -->
      <input type="hidden" name="idP" id="idP">
      <input type="hidden" name="nombreP" id="nombreP">
    </form>

    <table id="planes" class="table table-hover">
      <thead>
        <tr class="table-primary">
          <th scope="col" style="display:none;">idPlan</th>
          <th scope="col">Carrera</th>
          <th scope="col">Seleccionar Materias</th>
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
            <td>
              <button type="button" class="btn btn-primary ver-btn">Ver</button>
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

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      // Agregar un evento de clic a todos los botones con la clase 'ver-btn'
      var botones = document.querySelectorAll('.ver-btn');
      botones.forEach(function (boton) {
        boton.addEventListener('click', function () {
          // Obtener los datos de la fila seleccionada
          var fila = this.closest('tr');
          var idPlanSeleccionado = fila.querySelector("td:nth-child(1)").innerText;
          var nombrePlanCompleto = fila.querySelector("td:nth-child(2)").innerText;
          // Asignar los valores de idPlanSeleccionado y nombrePlanCompleto a los inputs ocultos del formulario
          document.getElementById("idP").value = idPlanSeleccionado;
          document.getElementById("nombreP").value = nombrePlanCompleto;
          // Enviar el formulario
          document.getElementById("envio").submit();
        });
      });
    });
  </script>

</body>

</html>