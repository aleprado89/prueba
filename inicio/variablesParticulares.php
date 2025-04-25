<?php
session_start();
  //$_SESSION['idColegio']=1;// en banfield es el 1 pero puede ser 0 en otro hay que buscar el registro de nivel terciario. Esto esta por si hubiera mas de un terciario
  $_SESSION['aluDebeFinal']=1;//1 usa alumnos que deben finales(banfield) - 0 no lo usa
  $_SESSION['logo']='../img/logo_banfield.jpg';//ubicación nombre logo para login
  $_SESSION['membrete']='../img/membrete_banfield.png';//ubicacion nombre membrete para reportes
  $_SESSION['profeModCiclo']=1;//profes pueden cambiar ciclo lectivo
  $_SESSION['profeModCalifAsis']=1;//profes pueden modificar calificaciones y asistencias
  $_SESSION['claveDocente']="banfield";//clave para el primer acceso a la seccion de docente
