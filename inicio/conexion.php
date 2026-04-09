<?php
// Modo prueba (PHPUnit / CI): variables de entorno + base dedicada
if (getenv('SESYSTEM_TEST_MODE') === '1' && is_readable(__DIR__ . '/conexion.test.php')) {
    require __DIR__ . '/conexion.test.php';
    return;
}

//configuracion para trabajar en nuestras pc local
$servername = "localhost";
$username = "se_user";
$password = "88668866";
$dbname = "sesystem_prueba";

//configuración para pruebas en servidor web sesystem
/*$servername = "localhost";
$username = "u577894275_se_system";
$password = "Srsr4065.pase";
$dbname = "u577894275_se_system";*/

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    error_log('MySQL connect_error: ' . $conn->connect_error);
    die('Error de conexion a la base de datos. Contacte al administrador.');
}

// Establecer el juego de caracteres a UTF-8 (opcional)
$conn->set_charset("utf8mb4");

