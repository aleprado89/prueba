<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/verificarSesion.php';
include '../funciones/parametrosWeb.php';
//VARIABLES
$cicloLectivo = $datosColegio[0]['anioautoweb'];
$idCicloLectivo =buscarIdCiclo($conn, $cicloLectivo);
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];
$idPlan = $_SESSION['idP'];
$nombrePlan = $_SESSION['nombreP'];

//BOTON CANCELAR
if($_SERVER['REQUEST_METHOD']=='POST'){
  $idMatriculacionWeb = $_POST["idMatriculacionWeb"];    
  cancelarCursado($conn, $idMatriculacionWeb);

 // header("Location: materias_solicitudes_listado.php");
 // exit();
}

//FUNCIONES
//LISTAR SOLICITUDES
$listadoSolicitudes = array();
$listadoSolicitudes = buscarSolicitudesMateria($conn, $idAlumno, $idPlan, $idCicloLectivo);
$cantidad = count($listadoSolicitudes);
?>
<!DOCTYPE html>
<html lang="es"></html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Listado Solicitudes</title>
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
  <li class="breadcrumb-item active">Solicitudes</li>
</ol>
<div class="card padding col-12">
<h5><?php echo  "Alumno: ".$nombreAlumno; ?> </h5>
    <h5><?php echo  "Carrera: ".$nombrePlan; ?></h5>
  </div>
  <br>
  <div class="container text-center">
  <a href="../reportes/solicitudesCursPDF.php" class="btn btn-primary " target="_blank">Imprimir</a>
     <br><br>
    </div>
     <div class="container">     
     <table class="table table-hover" >
  <thead>
    <tr class="table-primary">
      <th scope="col" style="display:none;">idMatriculacionWeb</th>
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
    while ($a < $cantidad) {
      $idMatriculacionWeb = $listadoSolicitudes[$a]['idMatriculacionWeb'];
      $Materia = $listadoSolicitudes[$a]['Materia'];
      $Estado = $listadoSolicitudes[$a]['Estado'];
      $Observaciones = $listadoSolicitudes[$a]['Observaciones'];
$fecha = $listadoSolicitudes[$a]['Fecha'];
$fechaFormateada = date('d/m/Y H:i:s', strtotime($fecha));      $a++;
      $hayRegistros = true; // Establece la variable en true si se encuentra al menos un registro
      ?>

      <tr>
        <td style="display:none;">
          <?php echo $idMatriculacionWeb ?>
        </td>
        <td>
          <?php echo $Materia ?>
        </td>
        <td><?php echo $fechaFormateada ?></td>
       <td>
         <?php echo $Estado ?>
         <?php if ($Estado == "Aprobada") { ?>
           <span style="color: green;">&#10004;</span>
         <?php } elseif ($Estado == "Rechazada" || $Estado == "Cancelada") { ?>
           <span style="color: red;">&#10006;</span>
         <?php } ?>
       </td>
        <td>
          <?php echo $Observaciones ?>
        </td>
        <td>
            <?php if ($Estado == "Pendiente") { ?>
              <form action="materias_solicitudes_listado.php" method="post">
                <input type="hidden" name="idMatriculacionWeb" value="<?php echo $idMatriculacionWeb; ?>">                  
              <button type="button" class="btn btn-danger cancelar-btn">Cancelar</button>
              </form>
            <?php } ?>
          </td>
      </tr>

      <?php } ?>
      <?php if (!$hayRegistros) { ?>
        <tr>
          <td colspan="6" >No hay registros</td>
        </tr>
      <?php } ?>
  </tbody>
</table>
  </div></div></div>

  <!-- Modal de Confirmación -->
<div class="modal fade" id="modalConfirmarCancelacion" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header  text-dark">
        <h5 class="modal-title" id="modalLabel">Confirmar cancelación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        ¿Estás seguro de que querés cancelar esta solicitud?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        <button type="button" class="btn btn-danger" id="confirmarCancelacionBtn">Sí, cancelar</button>
      </div>
    </div>
  </div>
</div>


  <?php include '../funciones/footer.html'; ?>

  <script>
document.addEventListener('DOMContentLoaded', function () {
  let formSeleccionado = null;

  document.querySelectorAll('.cancelar-btn').forEach(boton => {
    boton.addEventListener('click', function (e) {
      e.preventDefault();
      formSeleccionado = this.closest('form');
      const modal = new bootstrap.Modal(document.getElementById('modalConfirmarCancelacion'));
      modal.show();
    });
  });

  document.getElementById('confirmarCancelacionBtn').addEventListener('click', function () {
    if (formSeleccionado) {
      formSeleccionado.submit();
    }
  });
});
</script>

    <script src="../funciones/sessionControl.js"></script>
</body>
</html>