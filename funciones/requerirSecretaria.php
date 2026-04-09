<?php
/**
 * Restringe el script a sesión de secretaría (usuarios administrativos).
 * Incluir inmediatamente después de verificarSesion.php en carpetas secretaria/.
 */
if (empty($_SESSION['sec_nombreUsuario'])) {
    header('Location: ../inicio/loginAdmin.php');
    exit;
}
