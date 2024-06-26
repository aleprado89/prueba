<?php
  session_start();?>
  <!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Formulario de Login</title>
    <!-- Bootswatch Material theme -->
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <!-- <link rel="stylesheet" href="../css/bootstrap.min.css"> -->
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
<div class="modal" id="loginModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Resultado de Inicio de Sesión</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true"></span>
        </button>
      </div>
      <div class="modal-body">
      <p id="loginMessage"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.slim.min.js"></script>
 <script src="../js/bootstrap.min.js"></script> 
 <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>

 
<!-- JavaScript para mostrar el modal -->
<script>
<?php
  //session_start();
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
