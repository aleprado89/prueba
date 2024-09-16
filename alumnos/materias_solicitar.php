<!DOCTYPE html>
<html lang="es">

<?php
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
//include '../funciones/pruebaSession.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  //BOTON VOLVER
  if (isset($_POST['submitVolver'])) {
    header("Location: ../alumnos/materias_materias.php");
    exit;
  }

  //BOTON CANCELAR
  if (isset($_POST['submitCancelar'])) {
    header("Location: ../alumnos/materias_cancelar.php");
    exit;
  }
}

//VARIABLES
$idCicloLectivo = $_SESSION['idCiclo'];
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];
$idPlan = $_SESSION['idP'];
$nombrePlan = $_SESSION['nombreP'];
$idMateria = $_SESSION['idM'];
$nombreMateria = $_SESSION['nombreM'];
$nombreCurso = $_SESSION['nombreC'];

//FUNCIONES
//LISTAR SOLICITUDES
$listadoSolicitudes = array();
$listadoSolicitudes = existeSolicitudMateria($conn, $idAlumno, $idMateria, $idCicloLectivo);
$cantidadSolicitudes = count($listadoSolicitudes);

$habilitado = true;
$a = 0;
while ($a < $cantidadSolicitudes) {
  $Estado = $listadoSolicitudes[$a]['Estado'];
  if ($Estado == "Pendiente" || $Estado == "Aprobada") {
    $habilitado = false;
  }
  $a++;
}
?>

<head>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitudes</title>
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
  <li class="breadcrumb-item"><a href="menualumnos.php">Inicio</a></li>
  <li class="breadcrumb-item"><a href="materias_planes.php">Inscripción a cursado</a></li>
  <li class="breadcrumb-item"><a href="materias_materias.php">Materias</a></li>
  <li class="breadcrumb-item active">Solicitar cursado</li>
</ol>

  <div class="card padding col-12">
    <h5>
      <?php echo $nombreAlumno; ?>
    </h5>
    <h5>
      Materia: <?php echo $nombreMateria; ?>
    </h5>
    <h5>
      Curso: <?php echo $nombreCurso; ?>
    </h5>

  <div class="row col-12 ">
    <div class="col-12 col-md-6">    
    
    <!-- FORM SOLICITAR -->
    <form action="../alumnos/materias_solicitar_ejecutar.php" method="POST">      
      </div>
      
      <div class="col-12 col-md-6">
      <br>
      <input type="hidden" name="idM" value=<?php echo $idMateria; ?> />
      <?php if ($habilitado == true) { ?>
        <button type="submit" id="btnSolicitar" class="btn btn-primary">Solicitar</button>
      <?php } else { ?>
        <label>Ya hay solicitudes en proceso o aprobadas.</label>
      <?php } ?>
    </form>
      </div>

    </div>
  </div>

  
  <div class="container mt-5">

    <!-- FORM CANCELAR -->
    <form id="cancelar" action="../alumnos/materias_cancelar.php" method="post">
      <input type="hidden" name="idMatriculacionWeb" id="idMatriculacionWeb">

      <table class="table table-hover">
        <caption>Solicitudes Existentes</caption>
        <thead>
          <tr class="table-primary">
            <th scope="col" style="display:none;">idMatriculacionWeb</th>
            <th scope="col">Materia</th>
            <th scope="col">Estado</th>
            <th scope="col">Observaciones</th>
            <th scope="col">Cancelar</th>
          </tr>
        </thead>
        <tbody>

          <?php

          //RECORRER TABLA DE SOLICITUDES        
          $a = 0;
          while ($a < $cantidadSolicitudes) {
            $idMatriculacionWeb = $listadoSolicitudes[$a]['idMatriculacionWeb'];
            $Materia = $listadoSolicitudes[$a]['Materia'];
            $Estado = $listadoSolicitudes[$a]['Estado'];
            $Observaciones = $listadoSolicitudes[$a]['Observaciones'];
            $a++;
            ?>

            <tr class="table-info">
              <td style="display:none;">
                <?php echo $idMatriculacionWeb ?>
              </td>
              <td>
                <?php echo $Materia ?>
              </td>
              <td>
                <?php echo $Estado ?>
              </td>
              <td>
                <?php echo $Observaciones ?>
              </td>
              <td>
                <?php if ($Estado == "Pendiente") { ?>
                  <button type="submit" class="btn btn-danger cancelar-btn">Cancelar</button>
                <?php } ?>
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
    //SCRIPT PARA SELECCIONAR DATOS DE LA SOLICITUD
    document.addEventListener("DOMContentLoaded", function () {
      // Agregar un evento de clic a todos los botones con la clase 'cancelar-btn'
      var botones = document.querySelectorAll('.cancelar-btn');
      botones.forEach(function (boton) {
        boton.addEventListener('click', function () {
          // Obtener los datos de la fila seleccionada
          var fila = this.closest('tr');
          var idMatriculacionWeb = fila.querySelector("td:nth-child(1)").innerText;          
          // Cargar Datos
          document.getElementById("idMatriculacionWeb").value = idMatriculacionWeb;          
          // Enviar el formulario
          document.getElementById("cancelar").submit();
        });
      });
    });
  </script>
  </div>

</div>


  <?php include '../funciones/footer.html'; ?>


</body>


</html>