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
  <br>
  <div class="container">

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