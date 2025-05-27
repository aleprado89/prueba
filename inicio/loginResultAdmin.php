<?php
session_start();
  // Incluye la conexión a la base de datos
  include '../inicio/conexion.php';

  // Verifica si se ha enviado el formulario
  if (isset($_POST['username']) && isset($_POST['password'])) {
    // Obtiene los valores del formulario
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Verifica si el usuario y clave existen en la tabla usuarios
    $sql = "SELECT * FROM usuarios WHERE nombreUsuario = '$username' AND clave = '$password'";
    $resultado = $conn->query($sql);

    // Verifica si se ha encontrado un resultado
    if ($resultado->num_rows > 0) {
      // Inicia la sesión
      $_SESSION['username'] = $username;

      // Redirige al usuario a la página de menú de secretaria
      header('Location: ../secretaria/menusecretaria.php');
      exit;
    } else {
      // Muestra un mensaje de error
      $_SESSION['login_message'] = 'Usuario o clave incorrectos.';
      header('Location: loginAdmin.php');
      exit;
    }
  } else {
    // Muestra un mensaje de error
   $_SESSION['login_message'] = 'Usuario o clave incorrectos.';
header('Location: loginAdmin.php');
exit;
  }
?>