<!DOCTYPE html>
<html lang="es">

<?php
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
include '../funciones/pruebaSession.php';

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
</head>

<body>
  <div class="card text-white bg-primary mb-3">
    <h3 class="card-header">
      <?php echo $nombreAlumno; ?>
    </h3>
    <h5 class="card-header">
      MATERIA:
      <?php echo $nombreMateria; ?>
    </h5>
    <h5 class="card-header">
      CURSO:
      <?php echo $nombreCurso; ?>
    </h5>
    <h5 class="card-header">
      TURNO:
      <?php echo $datosColegio[0]['nombreTurno']; ?>
    </h5>
  </div>

  <!-- FORM VOLVER -->
  <form id="volver" method="POST">
    <button type="submit" name="submitVolver" class="btn btn-secondary float-end mb-3">Volver</button>
  </form>

  <fieldset>
    <legend class="mt-4">Fechas Disponibles</legend>

    <!-- FORM SOLICITAR -->
    <form action="../alumnos/examenes_solicitar_ejecutar.php" method="POST">
      <select class="form-select mx-auto" name="fechaExamen" id="fechaExamen">
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
      <input type="hidden" name="idM" value=<?php echo $idMateria; ?> />
      <?php if ($habilitado == true) { ?>
        <button type="submit" id="btnSolicitar" class="btn btn-primary">Solicitar</button>
      <?php } else { ?>
        <label>Ya hay solicitudes en proceso o aprobadas.</label>
      <?php } ?>
    </form>

  </fieldset>
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
</body>

</html>