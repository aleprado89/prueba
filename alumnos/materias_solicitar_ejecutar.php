<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
//include '../funciones/pruebaSession.php';

$idCicloLectivo = $_SESSION['idCiclo'];
$idAlumno = $_SESSION['alu_idAlumno'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $idMateria = $_POST['idM'];
    solicitarCursado($conn, $idAlumno, $idMateria, $idCicloLectivo);

    header("refresh:3; url=../alumnos/materias_solicitar.php");
    exit();

} else {
    // Si no es una solicitud POST, redirigir o manejar de otra manera
    echo "Error: Método no permitido.";
}
 