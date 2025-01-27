<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';

$doc_legajo = $_SESSION['doc_legajo'];
$nombreDoc = $_SESSION['doc_apellido'] . ", " . $_SESSION['doc_nombre'];
$idMateria = $_SESSION['idMateria'];
$ciclolectivo = $_SESSION['ciclolectivo'];
$plan = $_SESSION['plan'];
$materia = $_SESSION['materia'];
?>
<!DOCTYPE html>
<html lang="es">

</html>

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
  <?php include '../funciones/menu_docente.php'; ?>

  <div class="container-fluid fondo">
    <br>
    <div class="container">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="menudocentes.php">Inicio</a></li>
        <li class="breadcrumb-item active">Carga de calificaciones parciales</li>
      </ol>

      <div class="card padding col-12">
        <h5><?php echo  "Docente: " . $nombreDoc; ?> </h5>
        <h5><?php echo  "Ciclo lectivo: " . $ciclolectivo; ?> </h5>
        <h5><?php echo  "Carrera: " . $plan; ?> </h5>
        <h5><?php echo  "Materia: " . $materia; ?> </h5><br>
        <label for="fecha">Seleccione fecha:</label>
        <input type="date" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>">    
      </div>


      <!-- Bootstrap JS y jQuery (necesario para el modal) -->
      <script src="../js/jquery-3.7.1.min.js"></script>
      <script src="../js/popper.min.js"></script>
      <script src="../js/bootstrap.min.js"></script>
      <br>
      <div>
        
      </div>
    </div>
  </div>
  </div>



  <?php include '../funciones/footer.html'; ?>
</body>

</html>