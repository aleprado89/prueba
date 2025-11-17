<?php
//session_start();
  //$_SESSION['idColegio']=1;// en banfield es el 1 pero puede ser 0 en otro hay que buscar el registro de nivel terciario. Esto esta por si hubiera mas de un terciario
  $_SESSION['aluDebeFinal']=1;//1 usa alumnos que deben finales(banfield) - 0 no lo usa
  $_SESSION['logo']='../img/logo_banfield.jpg';//ubicación nombre logo para login
  $_SESSION['logoSE']='../img/logo_se.png';//ubicación nombre logo para login
  $_SESSION['membrete']='../img/membrete_banfield.png';//ubicacion nombre membrete para reportes
  $_SESSION['profeModCiclo']=1;//profes pueden cambiar ciclo lectivo
  $_SESSION['profeModCalifAsis']=1;//profes pueden modificar calificaciones y asistencias
  $_SESSION['claveDocente']="banfield";//clave para el primer acceso a la seccion de docente
//RECORDAR VER ARCHIVO CONFIGMAIL DE LA RAIZ DEL PROYECTO PARA PONER LA CUENTA QUE ENVIA 
//PARA RECUPERAR LA CLAVE

  //$_SESSION['profeModCalifAsis']=1;//profes pueden modificar calificaciones y asistencias
  // ESTO AHORA SE HACE POR BASE DE DATOS Y SE CARGA ESTA VARIABLE EN LOGINRESULT

// --- Requisitos de Documentación para legajoAlu.php ---
// Posiciones 1-6: Documentos básicos
$req1 = "Fotocopia DNI";
$req2 = "Fotos 4x4";
$req3 = "Titulo Habilitante";
$req4 = "Partida de Nacimiento";
$req5 = "Ficha Médica";
$req6 = "Libreta";

// Posiciones 7-10: Nivel Primario
$req7 = "Primario";       // Título de sección (no es checkbox)
$req8 = "Secundario";     // Título de sección (no es checkbox)
$req9 = "Completo";       // Checkbox (contexto: Primario)
$req10 = "Incompleto";    // Checkbox (contexto: Primario)

// Posición 11: Documento
$req11 = "Título en Trámite";

// Posiciones 12-13: Nivel Secundario
$req12 = "Completo";      // Checkbox (contexto: Secundario)
$req13 = "Incompleto";    // Checkbox (contexto: Secundario)

// Posiciones 14-16: Nivel Superior
$req14 = "Superior";      // Título de sección (no es checkbox)
$req15 = "Completo";      // Checkbox (contexto: Superior)
$req16 = "Incompleto";    // Checkbox (contexto: Superior)

// Posiciones 17-20: Libres (vacíos)
$req17 = " "; // Vacio
$req18 = " "; // Vacio
$req19 = " "; // Vacio
$req20 = " "; // Vacio



/*[16:38, 27/8/2025] Ale: Banfield:
$req17 = "Falta Documentación";
[16:39, 27/8/2025] Ale: Mercedarias:
$req17 = "Cert. Int. Sexual";
$req18 = "Pase Sanitario";
$req19 = "Apto Psicofísico";*/

?>

