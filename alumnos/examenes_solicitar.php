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
    header("Location: ../alumnos/examenes_materias.php");
    exit;
  }

  //BOTON CANCELAR
  if (isset($_POST['submitCancelar'])) {
    header("Location: ../alumnos/examenes_cancelar.php");
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
$listadoSolicitudes = existeSolicitudExamen($conn, $idAlumno, $idMateria, $idCicloLectivo, $datosColegio[0]['idTurno']);
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

//LISTAR FECHAS DISPONIBLES
$listadoFechasExamenes = array();
$listadoFechasExamenes = buscarFechasExamenTurno($conn, $idMateria, $nombreCurso, $idCicloLectivo, $datosColegio[0]['idTurno']);
$cantidadFechas = count($listadoFechasExamenes);
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

<?php include '../funciones/menu.html'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
  <ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="/prueba/alumnos/menualumnos.php">Inicio</a></li>
  <li class="breadcrumb-item"><a href="/prueba/alumnos/examenes_planes.php">Carreras</a></li>
  <li class="breadcrumb-item"><a href="/prueba/alumnos/examenes_materias.php">Materias</a></li>
  <li class="breadcrumb-item active">Solicitar exámen</li>
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
    <h5>
      Turno: <?php echo $datosColegio[0]['nombreTurno']; ?>
    </h5>
 
  <br>

  <div class="row col-12 ">
    <div class="col-12 col-md-6">
    <h5 class="padding">Fechas Disponibles</h5>
    <!-- FORM SOLICITAR -->
    <form action="../alumnos/examenes_solicitar_ejecutar.php" method="POST">
      <select class="form-select margenes padding" name="fechaExamen" id="fechaExamen">
        <?php
        $a = 0;
        while ($a < $cantidadFechas) {
          $idFechaExamen = $listadoFechasExamenes[$a]['idFechaExamen'];
          $Fecha = $listadoFechasExamenes[$a]['Fecha'];
          $Hora = $listadoFechasExamenes[$a]['Hora'];
          ?>
          <option value="<?php echo $idFechaExamen; ?>">
            <?php echo $Fecha . " " . $Hora; ?>
          </option>
          <?php
          $a++;
        }
        if ($cantidadFechas == 0) {
          ?>
          <option value="" disabled selected>No hay fechas disponibles</option>
          <?php
        }
        ?>
      </select>
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
    <form id="cancelar" action="../alumnos/examenes_cancelar.php" method="post">
      <input type="hidden" name="idInscripcionWeb" id="idInscripcionWeb">

      <table class="table table-hover">
        <caption>Solicitudes Existentes</caption>
        <thead>
          <tr class="table-primary">
            <th scope="col" style="display:none;">idInscripcionWeb</th>
            <th scope="col">Materia</th>
            <th scope="col">Fecha</th>
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
          var idInscripcionWeb = fila.querySelector("td:nth-child(1)").innerText;          
          // Cargar Datos
          document.getElementById("idInscripcionWeb").value = idInscripcionWeb;          
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