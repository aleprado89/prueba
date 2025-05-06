<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'configMail.php';

$mail = new PHPMailer(true);

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
    echo 'Correo electrónico enviado con éxito';
} catch (Exception $e) {
    echo 'Error al enviar el correo electrónico: ' . $e->getMessage();
}
?>