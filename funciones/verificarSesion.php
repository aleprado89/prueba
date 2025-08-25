<?php
// Archivo: verificarSesion.php

// --- Asegurarse de que la sesión esté iniciada ---
// Esto debe ser la primera cosa que ocurra para gestionar la sesión.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Detectar si la petición es AJAX ---
// Comprueba la cabecera 'X-Requested-With' que típicamente envía jQuery AJAX.
$is_ajax_request = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// --- Determinar la URL de redirección en caso de sesión expirada ---
$redirect_on_expiration = '../inicio/login.php'; // Login general por defecto
// Si el script actual está dentro del directorio 'secretaria', usa el login de secretaria.
if (strpos($_SERVER['PHP_SELF'], '/secretaria/') !== false) {
    $redirect_on_expiration = '../inicio/loginAdmin.php';
}

// ----- Comprobar qué tipo de usuario está loggeado y establecer el identificador -----
// Este bloque determina si hay alguna sesión de usuario activa (Secretaria, Docente, Alumno).
$usuarioActualIdentifier = 'null'; // Valor por defecto si no se encuentra ninguna sesión

// Prioridad: Secretaria > Docente > Alumno (Ajusta el orden si tu lógica es diferente)

// Verifica si la sesión de Secretaria está activa
if (isset($_SESSION['sec_nombreUsuario']) && isset($_SESSION['sec_id'])) {
    $usuarioActualIdentifier = "sec_" . $_SESSION['sec_id'];
}
// Si no, verifica si la sesión de Docente está activa
elseif (isset($_SESSION['doc_legajo'])) {
    $usuarioActualIdentifier = "doc_" . $_SESSION['doc_legajo'];
}
// Si no, verifica si la sesión de Alumno está activa
elseif (isset($_SESSION['alu_idAlumno'])) {
    $usuarioActualIdentifier = "alu_" . $_SESSION['alu_idAlumno'];
}

// --- Lógica para el manejo de sesión expirada ---
// Si después de comprobar todo, no hay un usuario identificado válido:
if ($usuarioActualIdentifier === 'null') {
    if ($is_ajax_request) {
        // --- SI ES UNA PETICIÓN AJAX Y LA SESIÓN HA EXPIRADO ---
        // Es CRUCIAL devolver una respuesta JSON con código 401 Unauthorized
        // para que el JavaScript pueda interceptarla y actuar.
        header('Content-Type: application/json');
        http_response_code(401); // 401 Unauthorized
        echo json_encode([
            'session_expired' => true,
            'message' => 'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.'
        ]);
        // Salir del script inmediatamente para evitar que se envíe cualquier otra cosa (como HTML)
        exit;
    } else {
        // --- SI NO ES AJAX Y LA SESIÓN HA EXPIRADO ---
        // Redirigir al usuario a la página de login correspondiente.
        header("Location: " . $redirect_on_expiration . "?sesion_status=expirada");
        exit();
    }
}

// --- Si la sesión es válida ---
// Podemos establecer un identificador de usuario activo en la sesión para referencia futura
// si es necesario, aunque ya lo hemos usado para la comprobación.
$_SESSION['active_user_identifier'] = $usuarioActualIdentifier;

// --- Paso de la variable SESSION al cliente para JavaScript ---
// Esto solo se ejecuta si la página se está cargando inicialmente (no es una petición AJAX)
// y se utiliza para que el JavaScript de la página sepa quién está logueado.
if (!$is_ajax_request) {
?>
<script>
    // Asigna el identificador del usuario activo a una variable global en JavaScript
    // Esto es útil para que otros scripts JS en la página puedan saber quién es el usuario actual.
    window.usuarioActual = "<?php echo htmlspecialchars($usuarioActualIdentifier); ?>";
    // console.log("PHP set window.usuarioActual:", window.usuarioActual); // Opcional para depuración
</script>
<?php
}
// Si la petición es AJAX y la sesión es válida, este script no genera más salida aquí,
// permitiendo que el resto del script PHP (carga_califxalumno_secretaria.php) continúe.
?>