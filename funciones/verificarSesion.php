<?php
// Asegúrate de que session_start() se haya llamado ANTES de incluir este archivo
// La mayoría de tus páginas ya lo tienen al principio.

if (session_status() === PHP_SESSION_NONE) { // Verificar si la sesión no ha sido iniciada
    session_start();
}

// Identificador del usuario activo para pasar a JavaScript
$usuarioActualIdentifier = 'null';

// Determinar la URL de redirección en caso de sesión expirada
$redirect_on_expiration = '../inicio/login.php'; // Por defecto, login general
// Si la página actual está en el directorio 'secretaria', redirige a loginAdmin.php
if (strpos($_SERVER['PHP_SELF'], '/secretaria/') !== false) {
    $redirect_on_expiration = '../inicio/loginAdmin.php';
}

// ----- Comprobar qué tipo de usuario está loggeado y establecer el identificador -----
// Prioridad: Secretaria > Docente > Alumno (Ajusta el orden si tienes una lógica diferente)

// Secretaria
if (isset($_SESSION['sec_nombreUsuario']) && isset($_SESSION['sec_id'])) {
    $usuarioActualIdentifier = "sec_" . $_SESSION['sec_id'];
}
// Docente
elseif (isset($_SESSION['doc_legajo'])) {
    $usuarioActualIdentifier = "doc_" . $_SESSION['doc_legajo'];
}
// Alumno
elseif (isset($_SESSION['alu_idAlumno'])) {
    $usuarioActualIdentifier = "alu_" . $_SESSION['alu_idAlumno'];
}

// Si no se encontró ninguna sesión activa válida, redirigir al login correspondiente
if ($usuarioActualIdentifier === 'null') {
    header("Location: " . $redirect_on_expiration . "?sesion_status=expirada");
    exit();
}

// Establecer el identificador de usuario en la sesión para que otros scripts lo usen si es necesario
$_SESSION['active_user_identifier'] = $usuarioActualIdentifier;

// --- Paso de la variable SESSION al cliente para JavaScript (solo si no es una petición AJAX) ---
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax_request) {
?>
<script>
    // Asigna el identificador del usuario activo a una variable global en JavaScript
    window.usuarioActual = "<?php echo htmlspecialchars($usuarioActualIdentifier); ?>";
    // console.log("PHP set window.usuarioActual:", window.usuarioActual); // Opcional para depuración
</script>
<?php
}
?>