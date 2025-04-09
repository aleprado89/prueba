<!DOCTYPE html>
<html lang="es">

<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
//include '../funciones/pruebaSession.php';

$idAlumno = $_SESSION['alu_idAlumno'];
$idMateria = $_GET['idM'];
$nombreAlumno = $_SESSION['alu_apellido'].", ".$_SESSION['alu_nombre'];
$nombreMateria = $_GET['nombreM'];

$listadoExamenes = array();
$listadoExamenes = buscarExamenes($conn, $idAlumno, $idMateria);
$cantidad = count($listadoExamenes);

$datosCursado = array();
$datosCursado = cursadoMateria($conn, $idMateria, $idAlumno);
?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ver calificaciones</title>
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
  <li class="breadcrumb-item"><a href="calificaciones_planes.php">Carreras</a></li>
  <li class="breadcrumb-item"><a href="calificaciones.php">Materias</a></li>
  <li class="breadcrumb-item active">Calificaciones</li>
</ol>

  <div class="card padding col-12">
    <h5><?php echo  "Alumno: ".$nombreAlumno; ?> </h5>
    <h5><?php echo  "Materia: ".$nombreMateria; ?></h5>
  </div>
  <br>
  <div>
    <table class="table table-hover col-12">
      <thead>
        <tr class="table-primary">
          <th scope="col">Exámenes parciales</th>
          <th scope="col" class= "text-center">Calificación</th>
        </tr>
      </thead>

      <tbody>     
    
      <tr>
        <td>Parcial 1°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["n1"]; ?></td>
      </tr>
      <tr>
        <td>R.1°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["r1"]; ?></td>
      </tr>
      <tr>
        <td>Parcial 2°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["n2"]; ?></td>
      </tr>
      <tr>
        <td>Recuperatorio 2°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["r2"]; ?></td>
      </tr>
      <tr>
        <td>Parcial 3°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["n3"]; ?></td>
      </tr>
      <tr>
        <td>Recuperatorio 3°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["r3"]; ?></td>
      </tr>
      <tr>
        <td>Parcial 4°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["n4"]; ?></td>
      </tr>
      <tr>
        <td>Recuperatorio 4°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["r4"]; ?></td>
      </tr>
      <tr>
        <td>Parcial 5°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["n5"]; ?></td>
      </tr>
      <tr>
        <td>Recuperatorio 5°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["r5"]; ?></td>
      </tr>
      <tr>
        <td>Parcial 6°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["n6"]; ?></td>
      </tr>
      <tr>
        <td>Recuperatorio 6°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["r6"]; ?></td>
      </tr>
      <tr>
        <td>Parcial 7°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["n7"]; ?></td>
      </tr>
      <tr>
        <td>Recuperatorio 7°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["r7"]; ?></td>
      </tr>
      <tr>
        <td>Parcial 8°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["n8"]; ?></td>
      </tr>
      <tr>
        <td>Recuperatorio 8°</td>
        <td class= "text-center"><?php echo $datosCursado[0]["r8"]; ?></td>
      </tr>

      </tbody>
    </table>
  </div>

  <br>

  <div>
    <table class="table table-hover col-12">
      <thead>
        <tr class="table-primary">
          <th scope="col">Exámenes finales</th>
          <th scope="col" class= "text-center">Calificación</th>
        </tr>
      </thead>

      <tbody>

     

      <?php

//RECORRER TABLA DE EXAMENES

$a = 0;
while ($a < $cantidad) {
  $Fecha = $listadoExamenes[$a]['Fecha'];
  $Calificacion = $listadoExamenes[$a]['Calificacion'];
  if ($Calificacion != "") {
    ?>
    <tr>
      <td>
        <?php 
        $fechaFormato = DateTime::createFromFormat('Y-m-d', $Fecha);
        $fechaFormateada = $fechaFormato->format('d-m-Y');
        echo $fechaFormateada ?>
      </td>
      <td class= "text-center">
        <?php echo $Calificacion ?>
      </td>
    </tr>
    <?php
  }
  $a++;
}

// Si no hay registros, mostrar mensaje
if ($cantidad == 0) {
  ?>
  <tr>
    <td colspan="2" class="text-center">No hay registros</td>
  </tr>
  <?php
} else {
  $hayRegistros = false;
  foreach ($listadoExamenes as $examen) {
    if ($examen['Calificacion'] != "") {
      $hayRegistros = true;
      break;
    }
  }
  if (!$hayRegistros) {
    ?>
    <tr>
      <td colspan="2" >No hay registros</td>
    </tr>
    <?php
  }
}

?>

      </tbody>
    </table>
  </div>
      </div>
</div>


  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.slim.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

  <?php include '../funciones/footer.html'; ?>

</body>

</html>