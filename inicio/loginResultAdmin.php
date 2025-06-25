<?php
session_start(); // Iniciar sesión al principio para poder manipularla
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir la conexión a la base de datos
// 'conexion.php' está en la misma carpeta 'inicio' que 'loginResultAdmin.php'
include 'conexion.php'; 

// Verificar que la conexión a la base de datos sea válida
if (!isset($conn) || !($conn instanceof mysqli)) {
    $_SESSION['login_message'] = "Error crítico: No se pudo establecer la conexión a la base de datos.";
    $_SESSION['login_message_type'] = 'alert-danger';
    header('Location: loginAdmin.php');
    exit();
}

// Recoger el nombre de usuario y la contraseña del formulario
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validar que los campos no estén vacíos
if (empty($username) || empty($password)) {
    $_SESSION['login_message'] = "Por favor, ingresa tu usuario y contraseña.";
    $_SESSION['login_message_type'] = 'alert-warning';
    header('Location: loginAdmin.php');
    exit();
}

/**
 * Función para verificar el acceso de un usuario de secretaría/administrador.
 * Ahora esta función no establece variables de sesión directamente, solo verifica.
 * La gestión de la sesión se hace en el script principal para mayor control.
 *
 * @param string $username El nombre de usuario introducido.
 * @param string $password La contraseña introducida.
 * @param mysqli $conn Objeto de conexión a la base de datos.
 * @return array|false Un array asociativo con los datos del usuario si el acceso es válido, false en caso contrario.
 */
function verificarAccesoSecretario($username, $password, $conn) {
    // Consulta SQL para buscar el usuario en la tabla 'usuarios'
    // Asumimos que 'clave' es la contraseña y 'idusuarios' es el ID único.
    $sql = "SELECT idusuarios, nombreUsuario, clave, tipoPermiso, idnivel FROM usuarios WHERE nombreUsuario = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        error_log("Error preparando la consulta para login de secretario: " . $conn->error);
        return false;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // **IMPORTANTE**: Comparación de contraseña.
        // Si las contraseñas están hashadas con password_hash(), usa password_verify($password, $row['clave']).
        // Si están en texto plano, usa $password == $row['clave'].
        if ($password == $row['clave']) { // Comparación para texto plano
        // O si las contraseñas están hasheadas (MÁS SEGURO):
        // if (password_verify($password, $row['clave'])) {
            return $row; // Devuelve los datos del usuario si el login es exitoso
        } else {
            return false; // Contraseña incorrecta
        }
    } else {
        return false; // Usuario no encontrado o duplicado (lo cual es un error en sí)
    }
}

// Ejecutar la función de verificación
$userData = verificarAccesoSecretario($username, $password, $conn);

if ($userData) {
    // --- LOGIN EXITOSO ---

    // 1. Limpiar TODAS las variables de sesión anteriores (CRUCIAL para evitar mezcla de sesiones)
    $_SESSION = array();
    session_destroy(); // Destruye la sesión antigua en el servidor
    session_start();   // Inicia una nueva sesión
    session_regenerate_id(true); // Regenerar ID de sesión para prevenir Session Fixation

    // 2. Establecer variables de sesión ESPECÍFICAS para la Secretaria en la NUEVA sesión
    $_SESSION['sec_id'] = $userData['idusuarios']; // Usamos 'idusuarios' como el ID numérico de la secretaria
    $_SESSION['sec_nombreUsuario'] = $userData['nombreUsuario'];
    $_SESSION['sec_tipoPermiso'] = $userData['tipoPermiso'];
    $_SESSION['sec_idnivel'] = $userData['idnivel'];
    $_SESSION['login_success'] = true; // Indicador de éxito de login


    // 3. Establecer el identificador de usuario activo global para el control de sesiones
    $_SESSION['active_user_identifier'] = 'sec_' . $_SESSION['sec_id'];
    
    // 4. Establecer la URL de redirección post-logout
    $_SESSION['redirect_after_logout'] = '../inicio/loginAdmin.php';

    // 5. Cargar variables globales (nombre de colegio, logo) en la NUEVA sesión
    // 'variablesParticulares.php' está en la misma carpeta 'inicio' que 'loginResultAdmin.php'
    include 'variablesParticulares.php'; // Esto debería establecer $_SESSION['logo']

    // Cargar nombre del colegio (si no lo hace variablesParticulares.php o si lo hace de otra forma)
    $nombreColegio = "Nombre de Colegio Desconocido"; // Fallback
    $sql_colegio = "SELECT nombreColegio FROM colegio WHERE codnivel=6 LIMIT 1";
    $result_colegio = $conn->query($sql_colegio);
    if ($result_colegio && $result_colegio->num_rows > 0) {
        $row_colegio = $result_colegio->fetch_assoc();
        $_SESSION['nombreColegio'] = $row_colegio['nombreColegio'];
        $result_colegio->free();
    } else {
        error_log("Error: No se encontró nombreColegio para codnivel=6 en DB o la consulta falló.");
        $_SESSION['nombreColegio'] = "Instituto Superior"; // Fallback en sesión si no se encuentra
    }

    // 6. Redirigir al menú de secretaría
    header('Location: ../secretaria/menusecretaria.php');
    exit;

} else {
    // --- LOGIN FALLIDO ---
    $_SESSION['login_message'] = "Usuario o contraseña incorrectos.";
    $_SESSION['login_message_type'] = 'alert-danger'; // Para usar en loginAdmin.php
    header('Location: loginAdmin.php');
    exit;
}

// Asegurarse de cerrar la conexión a la base de datos
if (isset($conn)) {
    $conn->close();
}
?>