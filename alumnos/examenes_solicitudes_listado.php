<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/verificarSesion.php';
include '../funciones/parametrosWeb.php';

//VARIABLES
$cicloLectivo =  $datosColegio[0]['anioautoweb'];
$idCicloLectivo = buscarIdCiclo($conn, $cicloLectivo);
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];
$idPlan = $_SESSION['idP'];
$nombrePlan = $_SESSION['nombreP'];

if($_SERVER['REQUEST_METHOD']=='POST'){
  //BOTON CANCELAR
  if (isset($_POST['idInscripcionWeb'])) {
    $idInscripcionWeb = $_POST["idInscripcionWeb"];    
    cancelarExamen($conn, $idInscripcionWeb);

    header("Location: examenes_solicitudes_listado.php");
    exit();
  }

}

//FUNCIONES
//LISTAR SOLICITUDES
$listadoSolicitudes = array();
$listadoSolicitudes = buscarSolicitudesExamen($conn, $idAlumno, $idPlan, $idCicloLectivo);
$cantidad = count($listadoSolicitudes);
?>
<!DOCTYPE html>
<html lang="es"></html>
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
  <li class="breadcrumb-item active">Solicitudes</li>
</ol>
<div class="card padding col-12">
<h5><?php echo  "Alumno: ".$nombreAlumno; ?> </h5>
    <h5><?php echo  "Carrera: ".$nombrePlan; ?></h5>
  </div>
  <br>
  <div class="container text-center">
  <a href="../reportes/solicitudesExamPDF.php" class="btn btn-primary" target="_blank">Imprimir</a>
  <br><br>
  </div>
     <div class="container">
        <table class="table table-hover">
      <thead>
        <tr class="table-primary">
          <th scope="col" style="display:none;">idInscripcionWeb</th>
          <th scope="col">Materia</th>
          <th scope="col">Fecha</th>
          <th scope="col">Estado</th>
          <th scope="col">Observaciones</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $hayRegistros = false; ?>
        <?php
        //RECORRER TABLA DE SOLICITUDES        
        $a = 0;
        while ($a < $cantidad) {
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
                <form action="examenes_solicitudes_listado.php" method="post">
                  <input type="hidden" name="idInscripcionWeb" value="<?php echo $idInscripcionWeb; ?>">
<button type="button" class="btn btn-danger btn-cancelar" data-bs-toggle="modal" data-bs-target="#confirmarCancelacionModal" data-id="<?php echo $idInscripcionWeb; ?>">
  Cancelar
</button>
               </form>
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
  </div></div></div>

    <?php include '../funciones/footer.html'; ?>
<!-- Modal de cancelacion -->
<div class="modal fade" id="confirmarCancelacionModal" tabindex="-1" aria-labelledby="confirmarCancelacionLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="examenes_solicitudes_listado.php">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmarCancelacionLabel">Confirmar cancelación</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          ¿Estás seguro de que deseas cancelar esta solicitud de examen?
          <input type="hidden" name="idInscripcionWeb" id="inputIdInscripcionWeb">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
          <button type="submit" class="btn btn-danger">Sí, cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const cancelarButtons = document.querySelectorAll(".btn-cancelar");
    cancelarButtons.forEach(button => {
      button.addEventListener("click", function () {
        const id = this.getAttribute("data-id");
        document.getElementById("inputIdInscripcionWeb").value = id;
      });
    });
  });
</script>


    <script src="../funciones/sessionControl.js"></script>
</body>
</html>