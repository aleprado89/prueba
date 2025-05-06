<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

require 'configMail.php';

  // Incluye la conexión a la base de datos
  include 'inicio/conexion.php';

  // Obtiene el DNI del usuario
  $dni = $_POST['dni'];
  // Busca el correo electrónico del usuario con el DNI proporcionado
  $sql = "SELECT mail FROM persona WHERE dni = '$dni'";
  $resultado = $conn->query($sql);
  $fila = $resultado->fetch_assoc();
  // Si se encuentra el usuario, busca y envia la contraseña x mail
  if ($fila) {
    $email = $fila['mail'];

    // Busca la contraseña del usuario
    $sql = "SELECT * FROM persona p 
            INNER JOIN personal per ON p.idPersona = per.idPersona 
            INNER JOIN passwords pass ON per.legajo = pass.legajo 
            WHERE p.dni = '$dni'";
    $resultado = $conn->query($sql);
    $fila = $resultado->fetch_assoc();

    if ($fila) {
      // Es docente
      $contraseña = $fila['password'];
      $nombre = $fila['nombre'];
    } else {
      // Es alumno
      $sql = "SELECT * FROM persona p 
              INNER JOIN alumnosterciario a ON p.idPersona = a.idPersona 
              INNER JOIN passwords_alumnos pass ON a.idAlumno = pass.idAlumno 
              WHERE p.dni = '$dni'";
      $resultado = $conn->query($sql);
      $fila = $resultado->fetch_assoc();
      if ($fila) {
        $contraseña = $fila['password'];
        $nombre = $fila['nombre'];
      } else {
        // No se encontró un registro en passwords ni en passwords_alumnos
        echo "Si es su primer ingreso, consulte la contraseña inicial en su institución.";
        exit;
      }
    }

    // Envía el correo electrónico
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->Port = MAIL_PORT;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USER;
    $mail->Password = MAIL_PASSWORD;
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress($email, $nombre);
    $mail->Subject = 'Recuperación de contraseña';
    $mail->Body = 'Hola, su contraseña es: ' . $contraseña;

    try {
        $mail->send();
        echo 'Se envió un mail con su contraseña a su dirección de correro de recuperación. Si no le llega es probable que su correo de recuperación no esté cargado o sea incorrecto. En ese caso consulte en su institución.';
    } catch (Exception $e) {
        echo 'Error al enviar el correo electrónico de recuperación de contraseña: ' . $e->getMessage().' Consulte su clave en la institución.';
    }
   
  } else {
    // Devuelve un mensaje de error
    echo "No se encontró el usuario con el DNI proporcionado.";
  }
?>