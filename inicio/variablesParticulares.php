<?php
session_start();
  $_SESSION['idColegio']=1;// en banfield es el 1 pero puede ser 0 en otro hay que buscar el registro de nivel terciario
  $_SESSION['aluDebeFinal']=1;//1 usa alumnos que deben finales(banfield) - 0 no lo usa
  $_SESSION['logo']='../img/logo_banfield.jpg';//ubicación nombre logo para login
  $_SESSION['membrete']='https://sesystem.com.ar/se/prueba/img/membrete_banfield.png';//ubicacion nombre membrete para reportes
