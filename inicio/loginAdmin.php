<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'variablesParticulares.php';
include '../inicio/conexion.php';

// Opcional: si querés cargar el nombre del colegio como hacías en login.php
$sql = "SELECT nombreColegio FROM colegio WHERE codnivel=6"; 
$resultado = $conn->query($sql);
$fila = $resultado->fetch_assoc();
$_SESSION['nombreColegio'] = $fila["nombreColegio"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inicio de sesión - Admin</title>
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card"><br>
        <img src="<?php echo $_SESSION['logo']; ?>" class="mx-auto d-block" alt="logo" style="max-width: 40%; height: auto;">
        <div><br>
          <h3 class="text-center">Inicio de sesión - Admin</h3>
        </div>
        <div class="card-body">
          <form method="POST" action="loginResultAdmin.php" class="was-validated">
            <div class="form-group">
              <label for="username">Usuario:</label>
              <input type="text" class="form-control" id="username" name="username" required minlength="4">
            </div>
            <div class="form-group">
              <label for="password">Contraseña:</label>
              <input type="password" class="form-control" id="password" name="password" required minlength="8">
            </div>
            <div class="text-center mt-3">
              <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal de error -->
<div class="modal" id="loginAdminModal" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Ups!</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true"></span>
        </button>
      </div>
      <div class="modal-body">
        <p id="loginAdminMessage"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>

<!-- Mostrar modal si hay mensaje -->
<script>
<?php
  if (isset($_SESSION['login_message'])) {
    $loginMessage = $_SESSION['login_message'];
    unset($_SESSION['login_message']);
    echo 'jQuery(document).ready(function($){';
    echo '$("#loginAdminModal").modal("show");';
    echo '$("#loginAdminMessage").text("' . addslashes($loginMessage) . '");';
    echo '});';
  }
?>
</script>

</body>
</html>
