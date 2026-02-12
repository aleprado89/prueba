<?php
// Inicia la sesión
session_start();
include '../inicio/conexion.php';
include '../funciones/parametrosWeb.php'; 
include '../funciones/verificarSesion.php';
// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['doc_legajo'])) {
    // Redirige al usuario a la página de inicio de sesión
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
        // Actualiza el registro de la tabla passwords
        $sql = "UPDATE passwords SET password = '$claveNueva' WHERE legajo = '".$_SESSION['doc_legajo']."'";
        $conn->query($sql);

        // Cierra la conexión a la base de datos
        $conn->close();
        $_SESSION['cambiarClave'] = 0;

        // Muestra un mensaje de éxito
        $mensaje = '<div class="alert alert-success">
          Contraseña actualizada con éxito. Redirigiendo al menú de docentes...
        </div>
        <meta http-equiv="refresh" content="3;url=menudocentes.php">';
   
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="../js/bootstrap.min.js"></script> 

  <style>
    .alert {
      margin-top: 20px;
    }
  </style>

<link rel="icon" type="image/png" href="../img/icon.png">

</head>

<body>

<?php include '../funciones/menu_docente.php'; ?>

<div class="container-fluid fondo">
  <br>

  <div class="container">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="menudocentes.php">Inicio</a></li>
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
        <br>
        <div class="form-group">
          <label for="claveNuevaConfirmar">Confirmar clave nueva:</label>
          <input type="password" class="form-control" id="claveNuevaConfirmar" name="claveNuevaConfirmar" required>
        </div>
        <br>
        <div class="text-center">
        <button type="submit" class="btn btn-primary btn-block">Guardar cambios</button></div>
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
<?php
$usuarioDocente = isset($_SESSION["doc_legajo"]) ? htmlspecialchars($_SESSION["doc_legajo"], ENT_QUOTES, "UTF-8") : "";
$usuarioAlumno = isset($_SESSION["alu_idAlumno"]) ? htmlspecialchars($_SESSION["alu_idAlumno"], ENT_QUOTES, "UTF-8") : "";
$usuarioActual = $usuarioDocente ?: $usuarioAlumno;
?>

<script>
  window.usuarioActual = "<?php echo $usuarioActual; ?>";
    console.log("usuarioActual cargado:", window.usuarioActual);
</script>
    <script src="../funciones/sessionControl.js"></script>

  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

<?php include '../funciones/footer.html'; ?>

</body>

</html>