<!DOCTYPE html>
<html lang="es">

<?php

use FontLib\Table\Type\post;

session_start(); 
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
include '../funciones/verificarSesion.php';
//VARIABLES
$cicloLectivo = $datosColegio[0]['anioautoweb'];
$idCicloLectivo =buscarIdCiclo($conn, $cicloLectivo);
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];
$idPlan = $_SESSION['idP'];
$nombrePlan = $_SESSION['nombreP'];
$idMateria = $_POST['idMateria'] ?? '';
$nombreMateria = $_POST['nombreMateria'] ?? '';
$nombreCurso = $_POST['nombreCurso'] ?? '';

//echo $idMateria." - ".$nombreMateria." - ".$nombreCurso;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
   //BOTON SOLICITAR
if(isset($_POST['idMateria']) && isset($_POST['solicitarSubmit'])) {
  $idMateria = $_POST['idMateria'];
  $nombreMateria = $_POST['nombreMateria'];
  $nombreCurso = $_POST['nombreCurso'];
  solicitarCursado($conn, $idAlumno, $idMateria, $idCicloLectivo);
    //header("Location: materias_solicitar.php");
  //exit();
}


  //BOTON CANCELAR
  if (isset($_POST['idMatriculacionWeb'])) {
    $idMatriculacionWeb = $_POST["idMatriculacionWeb"];    
    cancelarCursado($conn, $idMatriculacionWeb);

    //header("Location: materias_solicitar.php");
    //exit();
  }


}


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
  <title>Solicitar Inscripción</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Bootstrap JS (necesario para el navvar) -->
<script src="../js/bootstrap.min.js"></script> 

<link rel="icon" type="image/png" href="../img/icon.png">

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
    
   <!-- FORM SOLICITAR -->
<form id="formSolicitar" action="../alumnos/materias_solicitar.php" method="POST">      
  <div class="col-12 col-md-6">
    <br>
    <input type="hidden" name="idMateria" value="<?php echo $idMateria; ?>" />
    <input type="hidden" name="nombreMateria" value="<?php echo $nombreMateria; ?>" />
    <input type="hidden" name="nombreCurso" value="<?php echo $nombreCurso; ?>" />
    <input type="hidden" name="solicitarSubmit" value="<?php echo true; ?>" />

    <?php if ($habilitado == true) { ?>
      <button type="button" id="btnSolicitar" onclick="abrirModal()" class="btn btn-primary">Solicitar</button>
    <?php } ?>        
  </div>
</form>


      </div>

    </div>
  </div>

  
  <div class="container mt-5">

    <!-- FORM CANCELAR -->
    <form id="cancelar" action="../alumnos/materias_solicitar.php" method="post">
      <input type="hidden" name="idMatriculacionWeb" id="idMatriculacionWeb">
      <input type="hidden" name="idMateria" value="<?php echo $idMateria; ?>" />
<input type="hidden" name="nombreMateria" value="<?php echo $nombreMateria; ?>" />
<input type="hidden" name="nombreCurso" value="<?php echo $nombreCurso; ?>" />

      <caption>Solicitudes Generadas</caption>
      <table class="table table-hover"  >        
    <thead>
      <tr class="table-primary">
        <th scope="col" style="display:none;">idMatriculacionWeb</th>
        <th scope="col">Materia</th>
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
        $idMatriculacionWeb = $listadoSolicitudes[$a]['idMatriculacionWeb'];
        $Materia = $listadoSolicitudes[$a]['Materia'];
        $Estado = $listadoSolicitudes[$a]['Estado'];
        $Observaciones = $listadoSolicitudes[$a]['Observaciones'];
        $a++;
        ?>

        <tr>
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
      <?php if ($cantidadSolicitudes == 0) { ?>
        <tr>
          <td colspan="5" >No hay registros</td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
    </form>

  </div>
<!-- MODAL CONFIRMAR -->
<div class="modal" id="confirmarSolicitudModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar Solicitud</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>¿Estás seguro de que deseas confirmar la solicitud?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="confirmarSolicitud()">Confirmar</button>
      </div>
    </div>
  </div>
</div>
<!-- MODAL CANCELAR -->
<div class="modal fade" id="confirmarCancelacionModal" tabindex="-1" aria-labelledby="cancelarModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cancelarModalLabel">Confirmar Cancelación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        ¿Estás seguro de que querés cancelar la solicitud de esta materia?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        <button type="button" class="btn btn-danger" id="btnConfirmarCancelacion">Sí, Cancelar</button>
      </div>
    </div>
  </div>
</div>


 
    <script src="../funciones/sessionControl.js"></script>
  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

 <script>
document.addEventListener("DOMContentLoaded", function () {
  const botones = document.querySelectorAll('.cancelar-btn');
  const form = document.getElementById("cancelar");
  const inputId = form.querySelector("#idMatriculacionWeb");
  let idMatriculacionTmp = null;

  botones.forEach(boton => {
    boton.addEventListener('click', function (e) {
      e.preventDefault();
      const fila = this.closest('tr');
      idMatriculacionTmp = fila.querySelector("td:nth-child(1)").innerText.trim();
      // Abrir el modal de confirmación
      const modal = new bootstrap.Modal(document.getElementById('confirmarCancelacionModal'));
      modal.show();
    });
  });

  // Confirmar desde el modal
  document.getElementById("btnConfirmarCancelacion").addEventListener("click", function () {
    if (idMatriculacionTmp) {
      inputId.value = idMatriculacionTmp;
      form.requestSubmit(); // Enviamos el formulario con el valor cargado
    }
  });
});
</script>


  <script>
  // Abrir el modal
  function abrirModal() {
    $('#confirmarSolicitudModal').modal('show');
  }

  // Confirmar y enviar el formulario original
  function confirmarSolicitud() {
    document.getElementById('formSolicitar').submit();
  }
</script>

  </div>

</div>


  <?php include '../funciones/footer.html'; ?>


</body>


</html>