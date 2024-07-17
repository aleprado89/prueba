<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
//include '../funciones/pruebaSession.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  //BOTON LISTADO SOLICITUDES
  if (isset($_POST['submitVer'])) {
    header("Location: ../alumnos/materias_solicitudes_listado.php");
    exit;
  }

  //BOTON SOLICITAR
  if (isset($_POST['submitSolicitar'])) {
    $idM = $_POST['idM'];
    $nombreM = $_POST['nombreM'];
    $nombreC = $_POST['nombreC'];
    $_SESSION['idM'] = $idM;
    $_SESSION['nombreM'] = $nombreM;
    $_SESSION['nombreC'] = $nombreC;

    header("Location: ../alumnos/materias_solicitar.php");
    exit;
  }
}

//VARIABLES
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];
$idPlan = $_SESSION['idP'];
$nombrePlan = $_SESSION['nombreP'];

//FUNCIONES
//LISTAR MATERIAS
$listadoMaterias = array();
$listadoMaterias = buscarMaterias($conn, $idAlumno, $idPlan);
$cantidad = count($listadoMaterias);
?>
<!DOCTYPE html>
<html lang="es">

</html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exámenes</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Bootstrap JS (necesario para el navvar) -->
<script src="../js/bootstrap.min.js"></script> 

</head>
<body>
  <?php include '../funciones/menu.php'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
  <ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="/prueba/alumnos/menualumnos.php">Inicio</a></li>
  <li class="breadcrumb-item"><a href="/prueba/alumnos/materias_planes.php">Inscripción a cursado</a></li>
  <li class="breadcrumb-item active">Materias</li>
</ol>

      <div class="card padding col-12">
        <h5><?php echo "Alumno: " . $nombreAlumno; ?> </h5>
        <h5><?php echo "Materia: " . $nombrePlan; ?></h5>
      </div>
      <br>
      <div class="text-center">
        <!-- FORM VER LISTADO SOLICITUDES -->
        <form id="ver" method="post">
          <button type="submit" name="submitVer" class="btn btn-primary">Ver Solicitudes</button>
        </form>
      </div>

      <div class="container">
        <br>
        <!-- FORM SOLICITAR -->
        <form id="envio" method="post">
          <input type="hidden" name="idM" id="idM">
          <input type="hidden" name="nombreM" id="nombreM">
          <input type="hidden" name="nombreC" id="nombreC">

          <table id="materias" class="table table-hover col-12">
            <thead>
              <tr class="table-primary">
                <th scope="col" style="display:none;">idMateria</th>
                <th scope="col" style="display:none;">Materia Completo</th>
                <th scope="col">Materia</th>
                <th scope="col">Curso</th>
                <th scope="col">Estado</th>
                <!-- <th scope="col">Calif. Final</th> -->
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

                <?php if (empty(trim($CalificacionFinal)) || $CalificacionFinal == null) { ?>

                  <tr>
                    <td style="display:none;" name="idM">
                      <?php echo $idMateria ?>
                    </td>
                    <td style="display:none;" name="nombreM">
                      <?php echo $MateriaCompleto ?>
                    </td>
                    <td>
                      <?php echo $Materia ?>
                    </td>
                    <td name="nombreC">
                      <?php echo $Curso ?>
                    </td>
                    <td>
                      <?php echo $Estado ?>
                    </td>
                    
                    <!-- <td>
                    $calificacionFinal iba aca
                    </td> -->

                    <?php
                    if (empty(trim($CalificacionFinal)) || $CalificacionFinal == null) { ?>
                      <td><button type="submit" name="submitSolicitar" class="btn btn-primary ver-btn">Solicitar</button></td>
                    <?php } else {
                      ?>
                      <td><button type="submit" name="submitSolicitar" style="display:none;"
                          class="btn btn-primary ver-btn">Solicitar</button></td>
                    <?php } ?>
                  </tr>
                  
                  <?php
                }
              }
              ?>

            </tbody>
          </table>
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.slim.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

  <script>
    //SCRIPT PARA SELECCIONAR DATOS DE LA MATERIA A SOLICITAR
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
          // Cargar Datos
          document.getElementById("idM").value = idMateriaSeleccionada;
          document.getElementById("nombreM").value = nombreMateriaCompleto;
          document.getElementById("nombreC").value = nombreCursoCompleto;
          // Enviar el formulario
          document.getElementById("envio").submit();
        });
      });
    });
  </script>


  <?php include '../funciones/footer.html'; ?>

</body>

</html>