<!DOCTYPE html>
<html lang="es">

<?php
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/pruebaSession.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  //BOTON VOLVER
  if (isset($_POST['submitVolver'])) {
    header("Location: ../alumnos/menualumnos.php");
    exit;
  }

  //BOTON SELECCIONAR PLAN
  if (isset($_POST['submitVer'])) {

    $idP = $_POST['idP'];
    $nombreP = $_POST['nombreP'];
    $_SESSION['idP'] = $idP;
    $_SESSION['nombreP'] = $nombreP;

    header("Location: ../alumnos/examenes_materias.php");
    exit;
  }
}

//VARIABLES
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];

//FUNCIONES
//LISTAR PLANES
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

  <!-- FORM VOLVER -->
  <form id="volver" method="POST">
    <button type="submit" name="submitVolver" class="btn btn-secondary float-end mb-3">Volver</button>
  </form>

  <div class="container mt-5">

    <!-- FORM VER PLAN -->
    <form id="envio" method="post">
      <input type="hidden" name="idP" id="idP">
      <input type="hidden" name="nombreP" id="nombreP">

      <!-- TABLA DE PLANES -->
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

          //RECORRER PARA ARMAR TABLA DE PLANES        
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
                <button type="submit" name="submitVer" class="btn btn-primary ver-btn">Ver</button>
              </td>
            </tr>

            <?php
          }
          ?>

        </tbody>
      </table>
    </form>
    
  </div>

  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.slim.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

  <script>
    //SCRIPT PARA SELECCIONAR DATOS DEL PLAN A VER
    document.addEventListener("DOMContentLoaded", function () {
      // Agregar un evento de clic a todos los botones con la clase 'ver-btn'
      var botones = document.querySelectorAll('.ver-btn');
      botones.forEach(function (boton) {
        boton.addEventListener('click', function () {
          // Obtener los datos de la fila seleccionada
          var fila = this.closest('tr');
          var idPlanSeleccionado = fila.querySelector("td:nth-child(1)").innerText;
          var nombrePlanCompleto = fila.querySelector("td:nth-child(2)").innerText;
          // Cargar Datos
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