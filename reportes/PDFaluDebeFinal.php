<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Crear una instancia de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);

//PREPARO CONSULTAS PARA LOS DATOS DEL REPORTE HTML
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'].", ".$_SESSION['alu_nombre'];
$idPlan=$_SESSION['idP'];
$dni=$_SESSION['alu_dni'];
$membrete=$_SESSION['membrete'];

$nombrePlan=buscarNombrePlan($conn,$idPlan);
$anio=$_SESSION['anio'];

// Cargar el contenido HTML
$html = '

<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de inscripción a rendir solamente finales</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
                }
        .header {
            background-color: #fff;
            color: black;
            text-align: center;
        }
        .header img {
            max-width: 500px;
            height: auto;
        }
        .container {
            width: 100%;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            text-align: center;
        }
        table {
            font-size: 10;
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 3px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
            .columna1 {
        width: 20%;
    }
    .columna2 {
        width: 50%;
    }
        .columna3 {
        width: 25%;
    }.columna4 {
        width: 5%;
    }
    </style>
</head>
<body>
    <div class="header">
<img src="' . $membrete . '" alt="Logo">
<h3>SOLICITUD DE INSCRIPCIÓN A RENDIR SOLAMENTE FINALES</h3><br>
        <p>El alumno/a '.$nombreAlumno.' con dni: '.$dni.'. Solicita presentarse únicamente a rendir finales</p><br>
<h4>Carrera:'.$nombrePlan.'<br> Ciclo Lectivo: '.$anio.'</h4>

         </div>
    
</body>
</html>';
        
        
// Generar el PDF
$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream('calif_'.$nombreAlumno.'.pdf', array('Attachment' => 0));