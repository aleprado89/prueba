<?php
include 'conexion.php';

// Obtener los datos del formulario
$username = $_POST['username'];
$password = $_POST['password'];

// Consulta para verificar el usuario y la contraseña del DOCENTE
$sql = "SELECT * FROM persona INNER JOIN personal ON
persona.idPersona=personal.idPersona INNER JOIN passwords ON
personal.legajo=passwords.legajo WHERE persona.dni='$username' AND passwords.password='$password'";
$result = $conn->query($sql);

// Verificar si se encontró un registro en la base de datos
if ($result->num_rows > 0) {
  // Usuario y contraseña válidos
  session_start();
  $_SESSION['login_message'] = "Inicio de sesión exitoso.";
} else {
// Consulta SQL para buscar el ALUMNO en la tabla de persona y password_alumnos
$sql2 = "SELECT * FROM persona INNER JOIN alumnosterciario ON
persona.idPersona=alumnosterciario.idPersona INNER JOIN passwords_alumnos ON
alumnosterciario.idAlumno=passwords_alumnos.idAlumno WHERE persona.dni='$username' AND passwords_alumnos.password='$password'";
$result2 = $conn->query($sql2);

// Verificar si se encontró un registro en la base de datos
if ($result2->num_rows > 0) {
  // Usuario y contraseña válidos
  session_start();
  $_SESSION['login_message'] = "Inicio de sesión exitoso.";
} else {
  // Usuario o contraseña incorrectos
  session_start();
  $_SESSION['login_message'] = "Usuario o contraseña incorrectos.";
}
}
$conn->close();

// Redirigir de vuelta al formulario de inicio de sesión
header("Location: login.php");
exit();
