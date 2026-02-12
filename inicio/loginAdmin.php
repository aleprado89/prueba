<?php
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); // Iniciar sesión para manejar mensajes y limpiar correctamente

// Lógica para limpiar la sesión al cargar la página de login si no hay un mensaje específico
// Esto asegura una "página de login limpia" a menos que haya un motivo para mostrar un mensaje
$skip_session_clear = false;
if (isset($_GET['motivo'])) { // Parámetros desde logout.php
    $skip_session_clear = true;
}
if (isset($_SESSION['login_message'])) { // Mensajes desde loginResultAdmin.php
    $skip_session_clear = true;
}

if (!$skip_session_clear) {
    $_SESSION = array(); // Borrar todas las variables de sesión
    session_destroy();    // Destruir la sesión actual (incluyendo la cookie)
    session_start();      // Iniciar una nueva sesión para esta carga de página.
                          // Necesario para que $_SESSION['login_message'] funcione si lo enviamos desde otro script.
}
?>
<script>
  // Crucial: Limpia el indicador de sesión activa en localStorage al cargar el login
  // Esto asegura que sessionControl.js detecte que no hay una sesión activa de usuario
  localStorage.removeItem('usuario_sesion_activa_global'); // Usa la clave global
</script>

<?php
  include 'variablesParticulares.php'; // Contiene variables como $_SESSION['logo']
  include '../inicio/conexion.php'; // Conexión a la base de datos

  if (!isset($conn) || !($conn instanceof mysqli)) {
      // Manejar el error de conexión aquí también si es posible antes de la consulta
      // Podrías mostrar un mensaje de error crítico si la base de datos no está disponible.
  }

  // Busco el nombre del colegio según el id para crear la variable de sesión
  $nombreColegio = "Nombre de Colegio Desconocido"; // Fallback por defecto
  // Solo consultamos si la conexión es válida
  if (isset($conn) && $conn instanceof mysqli) {
    $sql = "SELECT nombreColegio FROM colegio WHERE codnivel=6 LIMIT 1"; // Usar LIMIT 1 es buena práctica si esperas solo uno
    $resultado = $conn->query($sql);
    if ($resultado && $resultado->num_rows > 0) { // Verificar si se encontró un resultado
        $fila = $resultado->fetch_assoc();
        $nombreColegio = $fila["nombreColegio"];
    } else {
        error_log("No se encontró ningún registro en la tabla 'colegio' con codnivel = 6.");
    }
  } else {
      error_log("Error: Conexión a la base de datos no válida al intentar obtener el nombre del colegio en loginAdmin.php.");
  }
  $_SESSION['nombreColegio'] = $nombreColegio; // Siempre establecer, incluso con fallback


  // Manejar y mostrar mensajes de estado (desde GET o SESSION)
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

  // Mensajes desde loginResultAdmin.php (vía SESSION)
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
  <title>Inicio de sesión de Administrador</title>
  <!-- Bootswatch Material theme -->
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
          <h3 class="text-center">Acceso de Administrador</h3>
        </div>
        <div class="card-body">
          <form id="loginForm" method="POST" action="loginResultAdmin.php" class="was-validated">
            <div class="form-group mb-3"> <!-- Añadí mb-3 para espaciar un poco -->
              <label for="username">Usuario:</label>
              <input type="text" class="form-control" id="username" name="username" required minlength="4">
            </div>
            <div class="form-group mb-3"> <!-- Añadí mb-3 para espaciar un poco -->
              <label for="password">Contraseña:</label>
              <input type="password" class="form-control" id="password" name="password" required minlength="4">
            </div>
            <div class="text-center mt-3">
              <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
            </div>
          </form>
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

<!-- Modal para mensajes de login -->
<div class="modal" id="loginModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Atención!</h5>
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

<!-- Bootstrap JS y jQuery -->
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>

<!-- JavaScript para mostrar el modal si hay un mensaje de login -->
<script>
// El script PHP que generaba el modal aquí se ha movido/refactorizado
// para usar las alertas de Bootstrap directamente en el HTML.
// Si aún necesitas el modal para algún caso específico, asegúrate
// de que $_SESSION['login_message'] se establezca en loginResultAdmin.php
// y que este script lo capture y muestre el modal.
// Por ahora, lo dejaré solo comentado, asumiendo que las alertas son suficientes.
/*
<?php
  if (isset($_SESSION['login_message'])) {
    $loginMessage = $_SESSION['login_message'];
    unset($_SESSION['login_message']); // Limpiamos la variable de sesión después de mostrar el mensaje
    echo 'jQuery(document).ready(function($){';
    echo '$("#loginModal").modal("show");';
    echo '$("#loginMessage").text("' . $loginMessage . '");';
    echo '});';
  }
?>
*/
</script>

</body>
</html>