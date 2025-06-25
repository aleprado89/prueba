<?php
session_start();

// 1. Obtener la URL de redirección antes de destruir la sesión.
// Se le da una URL por defecto en caso de que $_SESSION['redirect_after_logout'] no esté definida.
$redirect_url = '../inicio/login.php'; // Redirige por defecto al login general
if (isset($_SESSION['redirect_after_logout'])) {
    $redirect_url = $_SESSION['redirect_after_logout'];
} else if (isset($_SESSION['sec_id'])) { // Si detectamos que era secretaria, pero no había redirect_after_logout
    $redirect_url = '../inicio/loginAdmin.php';
}

// 2. Destruir todas las variables de sesión.
$_SESSION = array();

// 3. Destruir la cookie de sesión (opcional pero recomendado para una limpieza completa).
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finalmente, destruir la sesión en el servidor.
session_destroy();

// 5. Usar JavaScript para limpiar localStorage (crucial para el control entre pestañas)
// y luego redirigir a la URL determinada.
echo '<script type="text/javascript">';
echo 'localStorage.removeItem("usuario_sesion_activa_global");'; // Nombre de clave usado en sessionControl.js
echo 'window.location.href = "' . htmlspecialchars($redirect_url) . '";';
echo '</script>';
exit();
?>