<?php
//configuracion para trabajar en nuestras pc local
$servername = "localhost";
$username = "se_user";
$password = "88668866";
$dbname = "sesystem_pruebas";

//configuración para pruebas en servidor web sesystem
/*$servername = "localhost";
$username = "u577894275_se_system";
$password = "Srsr4065.pase";
$dbname = "u577894275_se_system";*/

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// Establecer el juego de caracteres a UTF-8 (opcional)
$conn->set_charset("utf8mb4");

