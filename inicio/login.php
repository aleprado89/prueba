<?php
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Lógica para determinar si debemos limpiar la sesión o si hay un mensaje de login pendiente
$should_clear_session = true; // Por defecto, limpia la sesión

// Si existe un mensaje en $_SESSION['login_message'], es porque venimos de un loginResult.php
// y ese mensaje necesita ser procesado antes de limpiar la sesión.
if (isset($_SESSION['login_message'])) {
    $should_clear_session = false; // No limpiar la sesión ahora, necesitamos el mensaje.
}

if ($should_clear_session) {
    // Es crucial vaciar completamente el array $_SESSION antes de destruirla
    // para asegurar que no queden referencias a datos de la sesión antigua.
    $_SESSION = array();
    session_destroy();    // Destruir la sesión actual (incluyendo la cookie)
    session_start();      // Iniciar una nueva sesión.
                          // Necesario para que $_SESSION['login_message'] funcione si lo enviamos desde otro script.
}
?>
<script>
  // Crucial: Limpia el indicador de sesión activa en localStorage al cargar el login
  // Esto asegura que sessionControl.js detecte que no hay una sesión activa de usuario
  localStorage.removeItem('usuario_sesion_activa_global'); // Usa la clave global del sessionControl.js
</script>

<?php
  include 'variablesParticulares.php'; // Contiene variables como $_SESSION['logo'] y quizás claveDocente
  include '../inicio/conexion.php'; // Conexión a la base de datos

  if (!isset($conn) || !($conn instanceof mysqli)) {
      // Manejar el error de conexión aquí
      error_log("Error crítico: Conexión a la base de datos no válida en login.php.");
  }

  // Busco el nombre del colegio según el id para crear la variable de sesión
  $nombreColegio = "Nombre de Colegio Desconocido"; // Fallback por defecto
  // Solo consultamos si la conexión es válida
  if (isset($conn) && $conn instanceof mysqli) {
    $sql = "SELECT nombreColegio FROM colegio WHERE codnivel=6 LIMIT 1";
    $resultado = $conn->query($sql);
    if ($resultado && $resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        $nombreColegio = $fila["nombreColegio"];
    } else {
        error_log("No se encontró ningún registro en la tabla 'colegio' con codnivel = 6 en login.php.");
    }
  }
  $_SESSION['nombreColegio'] = $nombreColegio; // Siempre establecer, incluso con fallback


  // Manejar y mostrar mensajes de estado (desde $_GET o $_SESSION)
  $display_message = '';
  $message_type = 'alert-info'; // Default para mensajes informativos

  // Mensajes desde logout.php (vía GET)
  if (isset($_GET['motivo'])) {
      switch ($_GET['motivo']) {
          case 'inactividad':
              $display_message = "Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.";
              $message_type = 'alert-warning';
              break;
          case 'cambio_usuario_local':
          case 'cierre_externo':
          case 'otro_login_externo':
              $display_message = "Tu sesión ha finalizado o se ha iniciado sesión con otro usuario. Por favor, inicia sesión.";
              $message_type = 'alert-warning';
              break;
          case 'sesion_caducada_local':
              $display_message = "Tu sesión ha caducado en esta pestaña. Por favor, inicia sesión de nuevo.";
              $message_type = 'alert-warning';
              break;
          case 'logout_manual':
              $display_message = "Has cerrado tu sesión.";
              $message_type = 'alert-success';
              break;
          default:
              $display_message = "Por favor, inicia sesión para continuar.";
              $message_type = 'alert-info';
              break;
      }
  }

  // Mensajes desde loginResult.php (vía SESSION)
  if (isset($_SESSION['login_message'])) {
    $display_message = $_SESSION['login_message'];
    $message_type = $_SESSION['login_message_type'] ?? 'alert-info'; // Usar tipo si está definido
    unset($_SESSION['login_message']); // Limpiar después de mostrar
    unset($_SESSION['login_message_type']);
  }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inicio de sesión </title>
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
<link rel="icon" type="image/png" href="../img/icon.png">

</head>
<body>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <?php if ($display_message): ?>
        <div class="alert <?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($display_message); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      <div class="card"><br>
         <!-- Agregar la imagen centrada -->
      <img src="<?php echo $_SESSION['logo'] ?? ''; ?>" class="mx-auto d-block " alt="logo" style="max-width: 40%; height: auto;">
        <div>
          <br>
          <h3 class="text-center">Inicio de Sesión</h3><h4 class="text-center">Autogestión Docente y Estudiantes</h4>
        </div>
        <div class="card-body">
          <form id="loginForm" method="POST" action="loginResult.php" class="was-validated">
            <div class="form-group mb-3">
              <label for="username">Usuario:</label>
              <input type="text" class="form-control" id="username" name="username" required minlength="8">
            </div>
            <div class="form-group mb-3">
              <label for="password">Contraseña:</label>
              <input type="password" class="form-control" id="password" name="password" required minlength="4">
            </div>
            <div class="text-center mt-3">
            <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
            </div>
          </form>
          <div class="row">
            <button id="forgot-password" class="btn btn-link">Olvidé mi contraseña</button>
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

<!-- Modal para recuperar contraseña -->
<!-- Nota: el modal "loginModal" fue removido ya que los mensajes ahora usan alertas de Bootstrap directamente en el HTML. -->
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

<script>
  $(document).ready(function(){
    $('#forgot-password').click(function(){
      var dni = $('#username').val();
      $("#spinner").show();

      $.ajax({
        type: 'POST',
        url: '../forgot-password.php', // Asegúrate de que esta ruta es correcta
        data: {dni: dni},
        success: function(resultado) {
          $("#spinner").hide();
          $("#recoverMessage").text(resultado);
          $("#recoverModal").modal("show");
        },
        error: function(xhr, status, error) {
          $("#spinner").hide();
          console.error('Error al recuperar contraseña:', error);
          $("#recoverMessage").text("Error al procesar su solicitud. Intente de nuevo.");
          $("#recoverModal").modal("show");
        }
      });
    });
  });
</script>
</body>
</html>