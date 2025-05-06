<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
  include 'variablesParticulares.php';
  include '../inicio/conexion.php';
    //busco el nombre colegio segun el id para crear la variable de sesion
$sql = "SELECT nombreColegio FROM colegio WHERE codnivel=6"; 
$resultado = $conn->query($sql);
$fila = $resultado->fetch_assoc();
$nombre = $fila["nombreColegio"];
$_SESSION['nombreColegio']=$nombre;
//s$_SESSION['membrete']='../img/membrete_banfield.png';//ubicacion nombre membrete para reportes

    ?>
    <!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inicio de sesión </title>
    <!-- Bootswatch Material theme -->
    <!-- <link rel="stylesheet" href="../css/bootstrap.min.css"> -->

  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      
      <div class="card"><br>
         <!-- Agregar la imagen centrada -->
      <img src="<?php echo $_SESSION['logo']; ?>" class="mx-auto d-block " alt="logo" style="max-width: 40%; height: auto;">
        <div>
          <br>
          <h3 class="text-center">Inicio de sesión</h3>
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
          <div class="row"><button id="forgot-password" class="btn btn-link">Olvidó su contraseña</button>
          </div>

          <div id="spinner" style="display: none;">
        <div class="spinner-border" role="status">
          <span class="sr-only"></span>
        </div>
      </div>

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
        <h5 class="modal-title">Ups!</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true"></span>
        </button>
      </div>
      <div class="modal-body">
      <p id="loginMessage"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<!-- Modal para recuperar contraseña -->
<div class="modal" id="recoverModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Revise su correo para recuperar su contraseña</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true"></span>
        </button>
      </div>
      <div class="modal-body">
        <p id="recoverMessage"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Aceptar</button>
      </div>
    </div>
  </div>
</div>
<!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.min.js"></script>
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
<script>
  $(document).ready(function(){
    $('#forgot-password').click(function(){
      // Obtiene el DNI del usuario
      var dni = $('#username').val();
      $("#spinner").show(); // Muestra el spinner

      // Envía el correo electrónico
      $.ajax({
        type: 'POST',
        url: '../forgot-password.php',
        data: {dni: dni},
        success: function(resultado) {
          $("#spinner").hide(); // Oculta el spinner
          //console.log('Resultado:', resultado);
// Muestra el resultado en el modal
$("#recoverMessage").text(resultado);
      $("#recoverModal").modal("show");        },
        error: function(xhr, status, error) {
          $("#spinner").hide(); // Oculta el spinner

          console.log('Error:', error);
        }
      });
    });
  });
</script>
</body>
</html>
