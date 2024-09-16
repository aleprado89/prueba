<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
//include '../funciones/pruebaSession.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $idInscripcionWeb = $_POST["idInscripcionWeb"];    
    cancelarExamen($conn, $idInscripcionWeb);

    header("refresh:3; url=../alumnos/examenes_solicitar.php");
    exit();

} else {
    // Si no es una solicitud POST, redirigir o manejar de otra manera
    echo "Error: Método no permitido.";
}
