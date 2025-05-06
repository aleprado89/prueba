<?php
session_start();

if (!isset($_SESSION['es_alumno_y_docente'])) {
    header('Location: login.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seleccionar Rol</title>
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
  <div class="container-fluid fondo">
    <br>
    <div class="container">
      <ol class="breadcrumb">
        <li class="breadcrumb-item active">Seleccionar Rol</li>
      </ol>

      <div class="card padding col-12">
        <h5>Seleccionar Rol</h5>
        <form action="seleccionar_rol.php" method="post">
          <label>El DNI ingresado esta registrado como alumno y docente, si esto es un error informe en secretaria. Ó seleccione: ¿Qué rol deseas utilizar?</label>
<div class="row col-md-4 margenes">
          <select name="rol" class="form-select">
            <option value="alumno">Alumno</option>
            <option value="docente">Docente</option>
          </select></div>
          <button type="submit" class="btn btn-primary">Seleccionar</button>
        </form>
      </div>
    </div>
  </div>

  <?php
  if (isset($_POST['rol'])) {
    $rol = $_POST['rol'];

    if ($rol == 'alumno') {
      // Redirigir al menú de alumnos
      header('Location: ../alumnos/menualumnos.php');
      exit;
    } elseif ($rol == 'docente') {
      // Redirigir al menú de docentes
      header('Location: ../docentes/menudocentes.php');
      exit;
    }
  }
  ?>
</body>
</html>