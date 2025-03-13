<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
//include '../funciones/pruebaSession.php';

//VARIABLES
$idCicloLectivo = $_SESSION['idCiclo'];
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
    <form action="examenes_solicitudes_listado.php" method="post">
      <input type="hidden" name="idInscripcionWeb" value="<?php echo $idInscripcionWeb; ?>">
      <button type="submit" class="btn btn-danger">Cancelar</button>
    </form>
  <?php } ?>
</td>
          </tr>

          <?php
        }
        ?>

      </tbody>
    </table>
  </div></div></div>

  <?php include '../funciones/footer.html'; ?>

</body>
</html>