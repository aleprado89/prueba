<?php session_start();?>
<!DOCTYPE html>
<html lang="es">

<?php
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
$datosCursado = cursadoMateria($conn, $idCalificacion);
?>

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
<?php include '../funciones/menu.html'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
  <ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="/prueba/alumnos/menualumnos.php">Inicio</a></li>
  <li class="breadcrumb-item"><a href="/prueba/alumnos/calificaciones_planes.php">Carreras</a></li>
  <li class="breadcrumb-item"><a href="/prueba/alumnos/calificaciones.php">Materias</a></li>
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
        <td>Parcial 1</td>
        <td class= "text-center">8.5</td>
    </tr>
    <tr>
        <td>Parcial 2</td>
        <td class= "text-center">9.2</td>
    </tr>
    <tr>
        <td>Parcial 3</td>
        <td class= "text-center">7.8</td>
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
          $a++;
          ?>

          <tr>
            <td>
              <?php echo $Fecha ?>
            </td>
            <td class= "text-center">
              <?php echo $Calificacion ?>
            </td>
          </tr>

          <?php
        }
        ?>

      </tbody>
    </table>
  </div>
      </div>
</div>

<footer>
  <div class="container-fluid">

    <p> Desarrollado por  <a target="_blank"  style="text-decoration: none; color:#646261;" href="https://web.sistemasescolares.com.ar"  onmouseover="this.style.color='#2e8b97'" onmouseleave="this.style.color='#646261'">Sistemas escolares</a></p>
  
  </div>
</footer>

  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.slim.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

</body>

</html>