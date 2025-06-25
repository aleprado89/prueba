<?php
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la sesión completamente, borre también la cookie de sesión.
// Nota: Esto destruirá la sesión, y no solo los datos de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// *** Limpiar el localStorage para la clave de sesión activa ***
// Esto es CRUCIAL para el control de sesiones entre pestañas/ventanas
echo '<script type="text/javascript">';
echo 'localStorage.removeItem("usuario_sesion_activa");';
echo 'window.location.href = "../inicio/loginAdmin.php";'; // Redirige al login de admin
echo '</script>';
exit();
?>