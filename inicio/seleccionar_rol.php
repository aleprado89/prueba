<?php
session_start();

if (!isset($_SESSION['es_alumno_y_docente'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rol'])) {
    $rol = $_POST['rol'];

    if ($rol === 'alumno') {
        header('Location: ../alumnos/menualumnos.php');
        exit;
    } elseif ($rol === 'docente') {
        header('Location: ../docentes/menudocentes.php');
        exit;
    } else {
        // Si por alguna razón se envía un valor inválido
        $_SESSION['login_message'] = 'Rol seleccionado no válido.';
        $_SESSION['login_message_type'] = 'alert-danger';
        header('Location: login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Seleccionar Rol</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> 
<link rel="icon" type="image/png" href="../img/icon.png">

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
        <form method="post" action="seleccionar_rol.php">
          <label>El DNI ingresado está registrado como alumno y docente. Si esto es un error, informe en secretaría. De lo contrario, seleccione el rol que desea utilizar:</label>
          <div class="row col-md-4 margenes">
            <select name="rol" class="form-select" required>
              <option value="alumno">Alumno</option>
              <option value="docente">Docente</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary mt-3">Seleccionar</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
