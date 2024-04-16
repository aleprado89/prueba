<?php
include 'conexion.php';

// Obtener los datos del formulario
$username = $_POST['username'];
$password = $_POST['password'];

// Consulta para verificar el usuario y la contraseña del DOCENTE
$sql = "SELECT p.nombre,p.apellido,p.dni,pl.legajo,p.idPersona FROM persona p INNER JOIN personal pl ON
p.idPersona=pl.idPersona INNER JOIN passwords ON
pl.legajo=passwords.legajo WHERE p.dni='$username' AND passwords.password='$password'";
$result = $conn->query($sql);

// Verificar si se encontró un registro en la base de datos
if ($result->num_rows > 0) {
  // Usuario y contraseña válidos
  session_start();

  while($row = $result->fetch_assoc()) {
    $_SESSION['doc_nombre'] = $row["nombre"];
    $_SESSION['doc_apellido'] = $row["apellido"];
    $_SESSION['doc_dni'] = $row["dni"];
    $_SESSION['doc_legajo'] = $row["legajo"];
    $_SESSION['doc_idPersona'] = $row["idPersona"];

}
  $conn->close();
//Link al menu docentes
   header("Location:../docentes/menudocentes.php");
   exit();
} else {
// Consulta SQL para buscar el ALUMNO en la tabla de persona y password_alumnos
$sql2 = "SELECT p.nombre,p.apellido,p.dni,a.idAlumno,p.idPersona FROM persona p INNER JOIN alumnosterciario a ON
p.idPersona=a.idPersona INNER JOIN passwords_alumnos ON
a.idAlumno=passwords_alumnos.idAlumno WHERE p.dni='$username' AND passwords_alumnos.password='$password'";
$result2 = $conn->query($sql2);



// Verificar si se encontró un registro en la base de datos osea ingreso correcto
if ($result2->num_rows > 0) {
  session_start();

  while($row = $result2->fetch_assoc()) {
    $_SESSION['alu_nombre'] = $row["nombre"];
    $_SESSION['alu_apellido'] = $row["apellido"];
    $_SESSION['alu_dni'] = $row["dni"];
    $_SESSION['alu_idAlumno'] = $row["idAlumno"];
    $_SESSION['alu_idPersona'] = $row["idPersona"];

}


  $conn->close();

  // Usuario y contraseña válidos

   header("Location:../alumnos/menualumnos.php");
   exit();
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
