<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); 
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
include '../funciones/verificarSesion.php';
//include '../funciones/pruebaSession.php';

//VARIABLES
$cicloLectivo =  $datosColegio[0]['anioautoweb'];
$idCicloLectivo = buscarIdCiclo($conn, $cicloLectivo);
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];
$idPlan = $_SESSION['idP'];
$nombrePlan = $_SESSION['nombreP'];
$idMateria = $_POST['idM'] ?? '';
$nombreMateria = $_POST['nombreMateria'] ?? '';
$nombreCurso = $_POST['nombreCurso'] ?? '';
$idDivision = $_POST['idDivision'] ?? '';




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

   // header("Location: examenes_solicitar.php");
   // exit();
  }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["fechaExamen"])) {//cuando se aprieta el boton solicitar
  $idFechaSeleccionada = $_POST["fechaExamen"];
  $idAlumno = $_SESSION['alu_idAlumno'];
  $cicloLectivo =  $datosColegio[0]['anioautoweb'];
  $idCicloLectivo = buscarIdCiclo($conn, $cicloLectivo);
  $idMateria = obtenerIdMateriaPorFechaExamen($conn, $idFechaSeleccionada); 
  solicitarExamen($conn, $idAlumno, $idMateria, $idCicloLectivo, $idFechaSeleccionada);

  // Redirigir a la misma página 
   // header("Location: examenes_solicitar.php");
  //exit();
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

<!DOCTYPE html>
<html lang="es"></html>
<head>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitar inscripción</title>
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
  <li class="breadcrumb-item active">Solicitar examen</li>
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
    <form id="formulario" action="../alumnos/examenes_solicitar.php" method="POST">
      
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
<input type="hidden" name="idM" value="<?php echo $idMateria; ?>" />
<input type="hidden" name="idDivision" value="<?php echo $idDivision; ?>" />
<input type="hidden" name="nombreMateria" value="<?php echo $nombreMateria; ?>" />
<input type="hidden" name="nombreCurso" value="<?php echo $nombreCurso; ?>" />
      <?php if ($habilitado == true) { ?>
<button type="button" id="btnSolicitar" onclick="abrirModal()" class="btn btn-primary">Solicitar</button>
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
            <input type="hidden" name="idM" value="<?php echo $idMateria; ?>" />
<input type="hidden" name="nombreMateria" value="<?php echo $nombreMateria; ?>" />
<input type="hidden" name="nombreCurso" value="<?php echo $nombreCurso; ?>" />
<input type="hidden" name="idDivision" value="<?php echo $idDivision; ?>" />
</table>
          <caption>Solicitudes Generadas</caption>
          <table class="table table-hover"  >
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
              <?php $hayRegistros = false; ?>
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
                $hayRegistros = true; // Establece la variable en true si se encuentra al menos un registro
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
                    $fechaFormateada = $fechaFormato->format('d/m/Y');
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
              <?php if (!$hayRegistros) { ?>
                <tr>
                  <td colspan="6">No hay registros</td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </form>

  </div>

 <!-- MODAL PARA CONFIRMAR SOLICITUD -->
 <div class="modal" id="confirmarSolicitudModal">
   <div class="modal-dialog" role="document">
     <div class="modal-content">
       <div class="modal-header">
         <h5 class="modal-title">Confirmar Solicitud</h5>
         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
           <span aria-hidden="true"></span>
         </button>
       </div>
       <div class="modal-body">
         <p>¿Estás seguro de que deseas solicitar el examen?</p>
       </div>
       <div class="modal-footer">
         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
         <button type="button" id="confirmarSolicitudBtn" class="btn btn-primary">Confirmar</button>
       </div>
     </div>
   </div>
 </div>
 <!-- MODAL PARA CANCELACIÓN -->
<div class="modal" id="confirmarCancelacionModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar Cancelación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true"></span>
        </button>
      </div>
      <div class="modal-body">
        <p>¿Estás seguro de que deseas cancelar esta solicitud?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        <button type="button" id="confirmarCancelacionBtn" class="btn btn-danger">Sí, Cancelar</button>
      </div>
    </div>
  </div>
</div>

    <script src="../funciones/sessionControl.js"></script>
  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.slim.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

  <script>
  let idInscripcionSeleccionado = null;

document.querySelectorAll('.cancelar-btn').forEach(function (boton) {
  boton.addEventListener('click', function (e) {
    e.preventDefault();
    let fila = this.closest('tr');
    idInscripcionSeleccionado = fila.querySelector("td:nth-child(1)").innerText.trim();
    $('#confirmarCancelacionModal').modal('show');
  });
});

document.getElementById("confirmarCancelacionBtn").addEventListener("click", function () {
  if (idInscripcionSeleccionado) {
    document.getElementById("idInscripcionWeb").value = idInscripcionSeleccionado;
    document.getElementById("cancelar").submit();
  }
});

    // SCRIPT PARA ABRIR MODAL Y ENVIAR FORMULARIO
    function abrirModal() {
      $('#confirmarSolicitudModal').modal('show');
    }
    
    document.getElementById("confirmarSolicitudBtn").addEventListener("click", function() {
      document.getElementById("formulario").submit();
    });
  </script>
  </div>

</div>


  <?php include '../funciones/footer.html'; ?>


</body>


</html>