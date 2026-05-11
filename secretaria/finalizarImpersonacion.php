<?php
/**
 * Restaura la sesión de secretaría tras impersonar alumno/docente.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['_sec_backup']) || !is_array($_SESSION['_sec_backup'])) {
    header('Location: ../inicio/loginAdmin.php');
    exit;
}

$backup = $_SESSION['_sec_backup'];

foreach (array_keys($_SESSION) as $k) {
    if (strpos($k, 'alu_') === 0 || strpos($k, 'doc_') === 0) {
        unset($_SESSION[$k]);
    }
}

unset(
    $_SESSION['impersonando'],
    $_SESSION['impersonacion_label'],
    $_SESSION['es_alumno_y_docente'],
    $_SESSION['cambiarClave'],
    $_SESSION['_sec_backup']
);

$_SESSION['sec_id'] = $backup['sec_id'];
$_SESSION['sec_nombreUsuario'] = $backup['sec_nombreUsuario'];
$_SESSION['sec_tipoPermiso'] = $backup['sec_tipoPermiso'];
$_SESSION['sec_idnivel'] = $backup['sec_idnivel'];
if (isset($backup['login_success'])) {
    $_SESSION['login_success'] = $backup['login_success'];
}

$_SESSION['active_user_identifier'] = 'sec_' . (int) $_SESSION['sec_id'];
$_SESSION['redirect_after_logout'] = '../inicio/loginAdmin.php';

session_regenerate_id(true);

require_once __DIR__ . '/../inicio/conexion.php';
require_once __DIR__ . '/../inicio/variablesParticulares.php';

$stmtN = $conn->prepare('SELECT nombreColegio FROM colegio WHERE codnivel = 6 LIMIT 1');
if ($stmtN && $stmtN->execute()) {
    $r = $stmtN->get_result()->fetch_assoc();
    $_SESSION['nombreColegio'] = $r['nombreColegio'] ?? 'Institución';
    $stmtN->close();
}

$destino = 'menusecretaria.php';
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
echo '<script>localStorage.removeItem("usuario_sesion_activa_global");window.location.replace(' . json_encode($destino) . ');</script>';
echo '</body></html>';
exit;
