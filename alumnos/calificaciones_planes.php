<!DOCTYPE html>
<html lang="es">

<?php
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/pruebaSession.php';

$idAlumno = $_SESSION['idAlumno'];
$nombreAlumno = $_SESSION['nombreAlumno'];

$listadoPlanes = array();
$listadoPlanes = buscarPlanes($conn, $idAlumno);
$cantidad = count($listadoPlanes);
?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Planes</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> 

<!-- Bootstrap JS (necesario para el navvar) -->
<script src="../js/bootstrap.min.js"></script> 
<script src="../js/chartjs/dist/chart.umd.js"></script><!-- charjs  (necesario para el grafico de torta) -->


</head>

<body>
  <nav class="navbar navbar-expand-lg bg-light" data-bs-theme="light">
  <div class="container">
  <img src="../img/logo merce.jpg" class="est-logo img-fluid mx-auto" alt="logo" >
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
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
          <a class="nav-link" href="#" style="display: flex; flex-direction: column; align-items: center;" onmouseover="this.style.color='#2e8b97'"><i class="bi bi-power" ></i>Cerrar Sesión</a>
        </li> 

  </ul>
    </div>
  </div>
</nav>
<div class="container-fluid fondo">

<br>
<div class="container">
<div class="row">


<div class="card margenes padding col-md-3">
  <br>
    <h3>
      <?php echo $_SESSION['alu_nombre']; ?>
    </h3>
    <h4>Progreso Carrera:</h4>
    <h6> <?php echo $listadoPlanes[0]['Plan']; ?></h6>
  
<!--        ESTADISTICCASSSSSSSSSSSSSSSSSSS        -->

  <div><canvas id="myPieChart"></canvas></div>

  </div>
  <div class="card col-md-8 margenes padding">
  <br>
  <h3>Carrera</h3>
    <table class="table table-borderless ">
      <thead>
        <tr >
          <th scope="col" style="display:none;">idPlan</th>
          <th scope="col"></th>
        </tr>
      </thead>
      <tbody>

        <?php

        //RECORRER TABLA DE PLANES
        
        $a = 0;
        while ($a < $cantidad) {
          $idPlan = $listadoPlanes[$a]['idPlan'];
          $Plan = $listadoPlanes[$a]['Plan'];
          $a++;
          ?>

          <tr >
            <td style="display:none;">
              <?php echo $idPlan ?>
            </td>
            <td>
              <?php echo $Plan ?>
            </td>
            <td><button type="button" onclick="verCalificaciones(this)" class="btn btn-primary btn-block">Ver
                Calificaciones</button></td>
          </tr>

          <?php
        }
        ?>

      </tbody>
    </table>
  </div>
  </div>
  </div>



  <script>
    
////////////////////////SCRIPT PARA GRAFICOS CHAR////////////////////////
    var ctx = document.getElementById('myPieChart').getContext('2d');
var myPieChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Aprobadas', 'Cursando', 'A cursar'],
        datasets: [{
            data: [30, 40, 30],
            backgroundColor: ['#40848d', '#333333', '#c1d7da']
        }]
    },
    options: {
        legend: {
            display: true,
            position: 'bottom' // Coloca la leyenda debajo del gráfico
        }
    }
});

    
    ///////////////////////////SCRIPT PARA BOTON VER CALIFICACIONES//////////////////////////////#


    function verCalificaciones(boton) {
      // Cargar idMateria y nombreMateria para pasar
      var idPlanSeleccionado = boton.closest('tr').querySelector('td:nth-child(1)').textContent;
      var nombrePlanCompleto = boton.closest('tr').querySelector('td:nth-child(2)').textContent;
      // Redirigir a otra página y pasar los datos como parámetro en la URL
      window.location.href =
        '../alumnos/calificaciones.php?idP=' + encodeURIComponent(idPlanSeleccionado) +
        '&nombreP=' + encodeURIComponent(nombrePlanCompleto);
    }
  </script>

</body>

</html>