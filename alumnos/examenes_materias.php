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

$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];

$listadoMaterias = array();
$listadoMaterias = buscarMaterias($conn, $idAlumno, $idPlan);
$cantidad = count($listadoMaterias);
?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Materias</title>
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
  <button type="button" class="btn btn-secondary float-end mb-3" onclick="window.history.back();">Volver</button>
  <div class="text-center mb-3">
    <form action="../alumnos/examenes_solicitudes_listado.php" method="post">
      <input type="hidden" name="idP" id="idP" value="<?php echo $idPlan; ?>">
      <input type="hidden" name="nombreP" id="nombreP" value="<?php echo $nombrePlan; ?>">
      <button type="submit" name="submit" class="btn btn-primary">Ver Solicitudes</button>
    </form>
  </div>
  <div class="container mt-5">

    <form id="envio" action="../alumnos/examenes_solicitar.php" method="post">
      <!-- Inputs ocultos para enviar los datos de la primera y segunda columna -->
      <input type="hidden" name="idM" id="idM">
      <input type="hidden" name="nombreM" id="nombreM">
      <input type="hidden" name="nombreC" id="nombreC">
    </form>

    <table id="materias" class="table table-hover">
      <thead>
        <tr class="table-primary">
          <th scope="col" style="display:none;">idMateria</th>
          <th scope="col" style="display:none;">Materia Completo</th>
          <th scope="col">Materia</th>
          <th scope="col">Curso</th>
          <th scope="col">Estado</th>
          <th scope="col">Calif. Final</th>
          <th scope="col">Solicitar Exámen</th>
        </tr>
      </thead>
      <tbody>

        <?php

        //RECORRER TABLA DE CALIFICACIONES
        
        $a = 0;
        while ($a < $cantidad) {

          //LIMITAR CANTIDAD DE PALABRAS DE NOMBRE MATERIA
        
          $idMateria = $listadoMaterias[$a]['idMateria'];
          $Materia = $listadoMaterias[$a]['Materia'];
          $MateriaCompleto = $Materia;
          $MateriaArray = explode(" ", $Materia);
          $cantidadPabras = count($MateriaArray);
          $b = 0;
          while ($b < $cantidadPabras) {
            if ($b == 0) {
              $Materia = $MateriaArray[$b];
            } else {
              $Materia = $Materia . " " . $MateriaArray[$b];
            }
            $b++;
            if ($b == 8) {
              $Materia = $Materia . "...";
              break;
            }
          }
          $Curso = $listadoMaterias[$a]['Curso'];
          $Estado = $listadoMaterias[$a]['Estado'];
          $CalificacionFinal = $listadoMaterias[$a]['CalificacionFinal'];
          $a++;
          ?>

          <tr class="table-info">
            <td style="display:none;">
              <?php echo $idMateria ?>
            </td>
            <td style="display:none;">
              <?php echo $MateriaCompleto ?>
            </td>
            <td>
              <?php echo $Materia ?>
            </td>
            <td>
              <?php echo $Curso ?>
            </td>
            <td>
              <?php echo $Estado ?>
            </td>
            <td>
              <?php echo $CalificacionFinal ?>
            </td>
            <?php
            if (empty(trim($CalificacionFinal)) || $CalificacionFinal == null) { ?>
              <td><button type="button" class="btn btn-primary ver-btn">Solicitar</button></td>
            <?php } else {
              ?>
              <td><button type="button" style="display:none;" class="btn btn-primary ver-btn">Solicitar</button></td>
            <?php } ?>
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
          var idMateriaSeleccionada = fila.querySelector("td:nth-child(1)").innerText;
          var nombreMateriaCompleto = fila.querySelector("td:nth-child(2)").innerText;
          var nombreCursoCompleto = fila.querySelector("td:nth-child(4)").innerText;
          // Asignar los valores de idPlanSeleccionado y nombrePlanCompleto a los inputs ocultos del formulario
          document.getElementById("idM").value = idMateriaSeleccionada;
          document.getElementById("nombreM").value = nombreMateriaCompleto;
          document.getElementById("nombreC").value = nombreCursoCompleto;
          // Enviar el formulario
          document.getElementById("envio").submit();
        });
      });
    });
  </script>

</body>

</html>