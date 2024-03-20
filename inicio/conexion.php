<?php
$servername = "localhost";
$username = "se_user";
$password = "88668866";
$dbname = "sistemas_terc_banfield";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// Establecer el juego de caracteres a UTF-8 (opcional)
$conn->set_charset("utf8");

