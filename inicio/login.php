<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Formulario de Login</title>
    <!-- Bootswatch Material theme -->
  <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      
      <div class="card">
         <!-- Agregar la imagen centrada -->
      <img src="../img/logo merce.jpg" class="mx-auto d-block" alt="logo" style="max-width: 30%; height: auto;">
        <div class="card-header">
         
          <h3 class="text-center">Iniciar Sesión</h3>
        </div>
        <div class="card-body">
          <form id="loginForm" method="POST" action="loginResult.php" class="was-validated" >
            <div class="form-group">
              <label for="username">Usuario:</label>
              <input type="text" class="form-control" id="username" name="username" required minlength="8">
            </div>
            <div class="form-group">
              <label for="password">Contraseña:</label>
              <input type="password" class="form-control" id="password" name="password" required minlength="4">
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

<!-- Modal -->
<div class="modal" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-light">
        <h5 class="modal-title" id="loginModalLabel">Resultado del Inicio de Sesión</h5>
         </div>
      <div class="modal-body">
        <!-- Aquí se mostrará el mensaje del inicio de sesión -->
        <p id="loginMessage"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<!-- Bootstrap JS y jQuery (necesario para el modal) -->
<!-- <script src="../js/jquery-3.7.1.slim.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script> -->

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<!-- JavaScript para mostrar el modal -->
<script>
  <?php
  session_start();
  if (isset($_SESSION['login_message'])) {
    $loginMessage = $_SESSION['login_message'];
    unset($_SESSION['login_message']); // Limpiamos la variable de sesión después de mostrar el mensaje
    echo 'jQuery(document).ready(function($){';
    echo '$("#loginModal").modal("show");';
    echo '$("#loginMessage").text("' . $loginMessage . '");';
    echo '});';
  }
  ?>
</script>

</body>
</html>
