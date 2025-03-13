<!DOCTYPE html>
<html lang="es">

<?php 
session_start(); 
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
//include '../funciones/pruebaSession.php';

//VARIABLES
$idCicloLectivo = $_SESSION['idCiclo'];
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];
$idPlan = $_SESSION['idP'];
$nombrePlan = $_SESSION['nombreP'];
$idMateria = $_SESSION['idM'];
$nombreMateria = $_SESSION['nombreM'];
$nombreCurso = $_SESSION['nombreC'];
$idDivision = $_SESSION['idDivision'];


if ($_SERVER["REQUEST_METHOD"] == "POST") {

  //BOTON VOLVER
  if (isset($_POST['submitVolver'])) {
    header("Location: ../alumnos/examenes_materias.php");
    exit;
  }

  //BOTON CANCELAR
  if (isset($_POST['idInscripcionWeb'])) {
    $idInscripcionWeb = $_POST["idInscripcionWeb"];    
    cancelarExamen($conn, $idInscripcionWeb);

    header("Location: examenes_solicitar.php");
    exit();
  }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["fechaExamen"])) {//cuando se aprieta el boton solicitar
  $idFechaSeleccionada = $_POST["fechaExamen"];
  $idMateria = $_POST['idM'];
  solicitarExamen($conn, $idAlumno, $idMateria, $idCicloLectivo, $idFechaSeleccionada);

  // Redirigir a la misma página 
    header("Location: examenes_solicitar.php");
  exit();
}

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
$listadoFechasExamenes = buscarFechasExamenTurno(conexion: $conn, idMateria: $idMateria, idCicloLectivo: $idCicloLectivo, idTurno: $datosColegio[0]['idTurno'], idDivision: $idDivision);
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

<?php include '../funciones/menu.php'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
  <ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="menualumnos.php">Inicio</a></li>
  <li class="breadcrumb-item"><a href="examenes_planes.php">Inscripción a exámenes</a></li>
  <li class="breadcrumb-item"><a href="examenes_materias.php">Materias</a></li>
  <li class="breadcrumb-item active">Solicitar exámen</li>
</ol>

  <div class="card padding col-12">
    <h5>
      <?php echo $nombreAlumno; ?>
    </h5>
    <h5>
      Carrera: <?php echo $nombrePlan; ?>
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

  <div class="row col-12 " <?php if ($habilitado == false) { ?> style="display:none;" <?php } ?>>
    <div class="col-12 col-md-6">
    <h5 class="padding">Fechas Disponibles</h5>
    <!-- FORM SOLICITAR -->
    <form action="../alumnos/examenes_solicitar.php" method="POST">
      <select class="form-select margenes padding" name="fechaExamen" id="fechaExamen">
        <?php
        $a = 0;
        while ($a < $cantidadFechas) {
          $idFechaExamen = $listadoFechasExamenes[$a]['idFechaExamen'];
          $Fecha = $listadoFechasExamenes[$a]['Fecha'];
          $Hora = $listadoFechasExamenes[$a]['Hora'];
          ?>
          <option value="<?php echo $idFechaExamen; ?>">
            <?php 
            $fechaFormato = DateTime::createFromFormat('Y-m-d', $Fecha);
            $fechaFormateada = $fechaFormato->format('d-m-Y');
            echo $fechaFormateada . " " . $Hora; ?>
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
    <form id="cancelar" action="../alumnos/examenes_solicitar.php" method="post">
      <input type="hidden" name="idInscripcionWeb" id="idInscripcionWeb">
      <caption>Solicitudes Generadas</caption>
      <table class="table table-hover" <?php if ($cantidadSolicitudes == 0) { ?> style="display:none;" <?php } ?> >
        <thead>
          <tr class="table-primary">
            <th scope="col" style="display:none;">idInscripcionWeb</th>
            <th scope="col">Materia</th>
            <th scope="col">Fecha</th>
            <th scope="col">Estado</th>
            <th scope="col">Observaciones</th>
            <th scope="col"></th>
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
            $Hora = $listadoSolicitudes[$a]['Hora'];
            $Estado = $listadoSolicitudes[$a]['Estado'];
            $Observaciones = $listadoSolicitudes[$a]['Observaciones'];
            $a++;
            ?>

            <tr>
              <td style="display:none;">
                <?php echo $idInscripcionWeb ?>
              </td>
              <td>
                <?php echo $Materia ?>
              </td>
              <td>
                <?php 
                $fechaFormato = DateTime::createFromFormat('Y-m-d', $Fecha);
                $fechaFormateada = $fechaFormato->format('d-m-Y');
                echo $fechaFormateada ?>  <?php echo $Hora ?> 
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