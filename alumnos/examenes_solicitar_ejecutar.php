<?php

include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/pruebaSession.php';

$idCicloLectivo = $_SESSION['idCiclo'];
$idAlumno = $_SESSION['alu_idAlumno'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $idFechaSeleccionada = $_POST["fechaExamen"];
    $idMateria = $_POST['idM'];
    solicitarExamen($conn, $idAlumno, $idMateria, $idCicloLectivo, $idFechaSeleccionada);

    //setcookie("idM", $$idMateria, time() + (86400 * 30), "/"); // 86400 segundos = 1 día
    //setcookie("nombreMateria", $nombreMateria, time() + (86400 * 30), "/"); // 86400 segundos = 1 día
    //setcookie("nombreCurso", $nombreCurso, time() + (86400 * 30), "/"); // 86400 segundos = 1 día

    header("refresh:3; url=../alumnos/examenes_solicitar.php");
    exit();

} else {
    // Si no es una solicitud POST, redirigir o manejar de otra manera
    echo "Error: Método no permitido.";
}
?>