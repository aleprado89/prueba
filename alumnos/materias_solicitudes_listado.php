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
  <title>Materias</title>
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
    <h5><?php echo  "Plan: ".$nombrePlan; ?></h5>
  </div>
  <br>
     <div class="container">
    <table class="table table-hover">
      <thead>
        <tr class="table-primary">
          <th scope="col" style="display:none;">idMatriculacionWeb</th>
          <th scope="col">Materia</th>
          <th scope="col">Estado</th>
          <th scope="col">Observaciones</th>
        </tr>
      </thead>
      <tbody>

        <?php

        //RECORRER TABLA DE SOLICITUDES        
        $a = 0;
        while ($a < $cantidad) {
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