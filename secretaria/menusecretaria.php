<?php 
session_start(); 
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';

//VARIABLES
?>
<!DOCTYPE html>
<html lang="es"></html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Secretaría</title>
  <!-- Bootstrap CSS -->
  <!-- <link rel="stylesheet" href="../css/estilo-prebootstrap.css"> -->
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Bootstrap JS (necesario para el navvar) -->
  <script src="../js/bootstrap.min.js"></script>
  <script src="../js/chartjs/dist/chart.umd.js"></script><!-- charjs  (necesario para el grafico de torta) -->
</head>
<body>
<?php include '../funciones/menu_secretaria.php'; ?>

  <div class="container-fluid fondo">
  <br>
  <div class="container">
  <ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="menualumnos.php">Inicio</a></li>
  <li class="breadcrumb-item active">Secretaría</li>
</ol>
   
    </div>



  </div>

  <?php include '../funciones/footer.html'; ?>

</body>

</html>