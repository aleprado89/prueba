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
<nav class="navbar navbar-expand-lg " data-bs-theme="light">
  <div class="container ">
  <div class="d-flex flex-column align-items-center centrarlogomenu ">
    <img src="../img/logo merce.jpg" class="est-logo img-fluid mx-auto" alt="logo" >
  </div>
    <button class="navbar-toggler margenbottom ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
 
    <div class="collapse navbar-collapse" id="navbarColor01">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link " href="menualumnos.php">Inicio
            
          </a>
        </li>
        <li class="nav-item active">
          <a class="nav-link" href="calificaciones_planes.php">Calificaciones
          <span class="visually-hidden">(current)</span>
          </a>

        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Insc.Cursado</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Insc. Exámen</a>
        </li>
      </ul>
      <ul class="ms-auto" style="list-style-type: none;">
        <li class="nav-item">
          <a class="nav-link" href="#" style="display: flex; flex-direction: column; align-items: center;" onmouseover="this.style.color='#2e8b97'" onmouseleave="this.style.color='#646261'"><i class="bi bi-power" ></i>Cerrar Sesión</a>
        </li> 

  </ul>
    </div>
  </div>
</nav>
<div class="container-fluid fondo">

  <div class="card text-white bg-primary mb-3">
    <h3 class="card-header">
      <?php echo $nombreAlumno; ?>
    </h3>
    <h4 class="card-header">
      <?php echo $nombreMateria; ?>
    </h4>
  </div>
  <div class="container mt-5">
    <table class="table table-hover">
      <thead>
        <tr class="table-primary">
          <th scope="col">Fecha</th>
          <th scope="col">Calificación</th>
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

          <tr class="table-info">
            <td>
              <?php echo $Fecha ?>
            </td>
            <td>
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
  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.slim.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

</body>

</html>