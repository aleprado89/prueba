<?php
/**
 * Recuperacion de contrasena por correo (docente o alumno terciario).
 */
ini_set('display_errors', 0);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'configMail.php';
include 'inicio/conexion.php';
require_once __DIR__ . '/funciones/password_web.php';

$dni = isset($_POST['dni']) ? trim((string) $_POST['dni']) : '';
if ($dni === '') {
    echo 'No se recibio el DNI.';
    exit;
}

$stmtMail = $conn->prepare('SELECT mail, nombre FROM persona WHERE dni = ? LIMIT 1');
if (!$stmtMail) {
    echo 'No se pudo procesar la solicitud. Intente mas tarde.';
    exit;
}
$stmtMail->bind_param('s', $dni);
$stmtMail->execute();
$rowPersona = $stmtMail->get_result()->fetch_assoc();
$stmtMail->close();

if (!$rowPersona || empty($rowPersona['mail'])) {
    echo 'No se encontro un correo de recuperacion para ese DNI.';
    exit;
}

$email = $rowPersona['mail'];
$nombre = $rowPersona['nombre'];

$contraseña = null;
$sqlDoc = 'SELECT pass.password FROM passwords pass
    INNER JOIN personal per ON pass.legajo = per.legajo
    INNER JOIN persona p ON per.idPersona = p.idPersona
    WHERE p.dni = ? LIMIT 1';
$stmtDoc = $conn->prepare($sqlDoc);
if ($stmtDoc) {
    $stmtDoc->bind_param('s', $dni);
    $stmtDoc->execute();
    $r = $stmtDoc->get_result()->fetch_assoc();
    if ($r && isset($r['password'])) {
        $contraseña = (string) $r['password'];
    }
    $stmtDoc->close();
}

if ($contraseña === null) {
    $sqlAlu = 'SELECT pass.password FROM passwords_alumnos pass
        INNER JOIN alumnosterciario a ON pass.idAlumno = a.idAlumno
        INNER JOIN persona p ON a.idPersona = p.idPersona
        WHERE p.dni = ? LIMIT 1';
    $stmtAlu = $conn->prepare($sqlAlu);
    if ($stmtAlu) {
        $stmtAlu->bind_param('s', $dni);
        $stmtAlu->execute();
        $r2 = $stmtAlu->get_result()->fetch_assoc();
        if ($r2 && isset($r2['password'])) {
            $contraseña = (string) $r2['password'];
        }
        $stmtAlu->close();
    }
}

if ($contraseña === null) {
    echo 'Si es su primer ingreso, consulte la contrasena inicial en su institucion.';
    exit;
}

if (password_web_is_hashed($contraseña)) {
    echo 'Su cuenta tiene una contrasena almacenada de forma segura. No puede enviarse por correo. Use la opcion Cambiar clave en el portal o consulte en secretaria.';
    exit;
}

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
$mail->Subject = 'Recuperacion de contrasena';
$mail->Body = 'Hola, su contrasena es: ' . $contraseña;

try {
    $mail->send();
    echo 'Se envio un mail con su contrasena a su direccion de correo de recuperacion. Si no le llega es probable que su correo no este cargado o sea incorrecto. En ese caso consulte en su institucion.';
} catch (Exception $e) {
    error_log('forgot-password mail: ' . $e->getMessage());
    echo 'No se pudo enviar el correo en este momento. Consulte su clave en la institucion.';
}
