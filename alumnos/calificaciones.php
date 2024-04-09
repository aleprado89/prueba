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

$listadoCalificaciones = array();
$listadoCalificaciones = buscarMaterias($conn, $idAlumno, $idPlan);
$cantidad = count($listadoCalificaciones);
?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calificaciones</title>
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
          <!-- 24 columnas -->
          <th scope="col" style="display:none;">idMateria</th>
          <th scope="col" style="display:none;">Materia Completo</th>
          <th scope="col">Materia</th>
          <th scope="col">Curso</th>
          <th scope="col">n1</th>
          <th scope="col">n2</th>
          <th scope="col">n3</th>
          <th scope="col">n4</th>
          <th scope="col">n5</th>
          <th scope="col">n6</th>
          <th scope="col">n7</th>
          <th scope="col">n8</th>
          <th scope="col">r1</th>
          <th scope="col">r2</th>
          <th scope="col">r3</th>
          <th scope="col">r4</th>
          <th scope="col">r5</th>
          <th scope="col">r6</th>
          <th scope="col">r7</th>
          <th scope="col">r8</th>
          <th scope="col">Asistencia</th>
          <th scope="col">Estado</th>
          <th scope="col">Calif. Final</th>
          <th scope="col">Examenes</th>
        </tr>
      </thead>
      <tbody>

        <?php

        //RECORRER TABLA DE CALIFICACIONES
        
        $a = 0;
        while ($a < $cantidad) {

          //LIMITAR CANTIDAD DE PALABRAS DE NOMBRE MATERIA
        
          $idMateria = $listadoCalificaciones[$a]['idMateria'];
          $Materia = $listadoCalificaciones[$a]['Materia'];
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
          $Curso = $listadoCalificaciones[$a]['Curso'];
          $n1 = $listadoCalificaciones[$a]['n1'];
          $n2 = $listadoCalificaciones[$a]['n2'];
          $n3 = $listadoCalificaciones[$a]['n3'];
          $n4 = $listadoCalificaciones[$a]['n4'];
          $n5 = $listadoCalificaciones[$a]['n5'];
          $n6 = $listadoCalificaciones[$a]['n5'];
          $n7 = $listadoCalificaciones[$a]['n5'];
          $n8 = $listadoCalificaciones[$a]['n5'];
          $r1 = $listadoCalificaciones[$a]['n5'];
          $r2 = $listadoCalificaciones[$a]['n5'];
          $r3 = $listadoCalificaciones[$a]['n5'];
          $r4 = $listadoCalificaciones[$a]['n5'];
          $r5 = $listadoCalificaciones[$a]['n5'];
          $r6 = $listadoCalificaciones[$a]['n5'];
          $r7 = $listadoCalificaciones[$a]['n5'];
          $r8 = $listadoCalificaciones[$a]['n5'];
          $Asistencia = $listadoCalificaciones[$a]['Asistencia'];
          $Estado = $listadoCalificaciones[$a]['Estado'];
          $CalificacionFinal = $listadoCalificaciones[$a]['CalificacionFinal'];
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
              <?php echo $n1 ?>
            </td>
            <td>
              <?php echo $n2 ?>
            </td>
            <td>
              <?php echo $n3 ?>
            </td>
            <td>
              <?php echo $n4 ?>
            </td>
            <td>
              <?php echo $n5 ?>
            </td>
            <td>
              <?php echo $n6 ?>
            </td>
            <td>
              <?php echo $n7 ?>
            </td>
            <td>
              <?php echo $n8 ?>
            </td>
            <td>
              <?php echo $r1 ?>
            </td>
            <td>
              <?php echo $r2 ?>
            </td>
            <td>
              <?php echo $r3 ?>
            </td>
            <td>
              <?php echo $r4 ?>
            </td>
            <td>
              <?php echo $r5 ?>
            </td>
            <td>
              <?php echo $r6 ?>
            </td>
            <td>
              <?php echo $r7 ?>
            </td>
            <td>
              <?php echo $r8 ?>
            </td>
            <td>
              <?php echo $Asistencia ?>
            </td>
            <td>
              <?php echo $Estado ?>
            </td>
            <td>
              <?php echo $CalificacionFinal ?>
            </td>
            <td><button type="button" onclick="verExamenes(this)" class="btn btn-primary">Ver Exámenes</button></td>
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
    function verExamenes(boton) {
      // Cargar idMateria y nombreMateria para pasar
      var idMateriaSeleccionada = boton.closest('tr').querySelector('td:nth-child(1)').textContent;
      var nombreMateriaCompleto = boton.closest('tr').querySelector('td:nth-child(2)').textContent;
      // Redirigir a otra página y pasar los datos como parámetro en la URL
      window.location.href =
        '../alumnos/calificaciones_verExamenes.php?idM=' + encodeURIComponent(idMateriaSeleccionada) +
        '&nombreM=' + encodeURIComponent(nombreMateriaCompleto);
    }
  </script>

</body>

</html>