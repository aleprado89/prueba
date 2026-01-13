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


/*Resultados
            0. Libre 
            1. Regular
            2. Coloquio
            3. Libre S/Asist
            4. Regular S/Asist
            5. Coloquio S/Asist
            6. Sin Calificaciones            
            7. Asistencia Requerida     
            8. Asistencia Insuficiente  
            9. Sin Asistencia
            10. Recursa
            11. Aprueba
            12. Recursa S/Asist
            13. Aprueba S/Asist
            14. Promocional
            15. Promocional S/Asist
            */

?>

