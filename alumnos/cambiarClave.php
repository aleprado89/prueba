<?php
// Inicia la sesión
session_start();
include '../inicio/conexion.php';
include '../funciones/parametrosWeb.php'; 

// Verifica si el alumno ha iniciado sesión
if (!isset($_SESSION['alu_idAlumno'])) {
    // Redirige al alumno a la página de inicio de sesión
    header('Location: ../inicio/login.php');
    exit;
}

// Verifica si se han enviado los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtiene los datos del formulario
    $claveNueva = $_POST['claveNueva'];
    $claveNuevaConfirmar = $_POST['claveNuevaConfirmar'];

    // Verifica si las claves son iguales y tienen un mínimo de 4 caracteres
    if ($claveNueva == $claveNuevaConfirmar && strlen($claveNueva) >= 4) {
        // Actualiza el registro de la tabla passwords_alumnos
        $sql = "UPDATE passwords_alumnos SET password = '$claveNueva' WHERE idAlumno = '".$_SESSION['alu_idAlumno']."'";
        $conn->query($sql);

        // Cierra la conexión a la base de datos
        $conn->close();
        $_SESSION['cambiarClave'] = 0;


        // Muestra un mensaje de éxito
        $mensaje = '<div class="alert alert-success">
          Contraseña actualizada con éxito.
        </div>
        <meta http-equiv="refresh" content="3;url=menualumnos.php">';
    } else {
        // Muestra un mensaje de error si las claves no son iguales o no tienen un mínimo de 4 caracteres
        if ($claveNueva != $claveNuevaConfirmar) {
            $error = 'Las claves no son iguales';
        } elseif (strlen($claveNueva) < 4) {
            $error = 'La clave nueva debe tener un mínimo de 4 caracteres';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cambiar Clave</title>
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="[[https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .alert {
      margin-top: 20px;
    }
  </style>
</head>

<body>

<?php include '../funciones/menu.php'; ?>

<div class="container-fluid fondo">
  <br>

  <div class="container">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="menualumnos.php">Inicio</a></li>
      <li class="breadcrumb-item active">Cambiar Clave</li>
    </ol>
    <h3 class="text-center">Cambiar Clave</h3>
    <br>
    <div class="card padding col-12 col-md-6 offset-md-3">
      <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <div class="form-group">
          <label for="claveNueva">Clave nueva:</label>
          <input type="password" class="form-control" id="claveNueva" name="claveNueva" required>
        </div>
        <div class="form-group">
          <label for="claveNuevaConfirmar">Confirmar clave nueva:</label>
          <input type="password" class="form-control" id="claveNuevaConfirmar" name="claveNuevaConfirmar" required>
        </div>
        <br>
        <div class="text-center">
        <button type="submit" class="btn btn-primary btn-block">Guardar cambios</button><br></div>
      </form>
    </div>
    <?php if (isset($error)) { ?>
      <div class="alert alert-danger">
        <?php echo $error; ?>
      </div>
    <?php } elseif (isset($mensaje)) { ?>
      <?php echo $mensaje; ?>
    <?php } ?>
  </div>
</div>

  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.slim.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

<?php include '../funciones/footer.html'; ?>

</body>

</html>