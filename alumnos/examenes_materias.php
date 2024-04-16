<!DOCTYPE html>
<html lang="es">

<?php
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/pruebaSession.php';

$idAlumno = $_SESSION['idAlumno'];
$idPlan = $_GET['idP'];
$nombreAlumno = $_SESSION['nombreAlumno'];
$nombrePlan = $_GET['nombreP'];

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
  <div class="text-center mb-3">
    <a href='../alumnos/examenes_solicitudes_listado.php?idP=<?php echo urlencode($idPlan); ?>&nombreP=<?php echo urlencode($nombrePlan); ?>'
      class="btn btn-primary">Ver Solicitudes enviadas</a>
  </div>
  <div class="container mt-5">
    <table class="table table-hover">
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
              <td><button type="button" onclick="examenesSolicitud(this)" class="btn btn-primary">Solicitar</button></td>
            <?php } else {
              ?>
              <td><button type="button" style="display:none;" onclick="examenesSolicitud(this)"
                  class="btn btn-primary">Solicitar</button></td>
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
    function examenesSolicitud(boton) {
      // Cargar idMateria y nombreMateria para pasar
      var idMateriaSeleccionada = boton.closest('tr').querySelector('td:nth-child(1)').textContent;
      var nombreMateriaCompleto = boton.closest('tr').querySelector('td:nth-child(2)').textContent;
      var nombreCursoCompleto = boton.closest('tr').querySelector('td:nth-child(4)').textContent;
      // Redirigir a otra página y pasar los datos como parámetro en la URL
      window.location.href =
        '../alumnos/examenes_solicitar.php?idM=' + encodeURIComponent(idMateriaSeleccionada) +
        '&nombreM=' + encodeURIComponent(nombreMateriaCompleto) + 
        '&nombreC=' + encodeURIComponent(nombreCursoCompleto);
    }  
  </script>

</body>

</html>