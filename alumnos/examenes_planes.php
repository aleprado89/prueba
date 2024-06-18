<?php
session_start(); 
include '../inicio/conexion.php';
include '../funciones/consultas.php';
//include '../funciones/pruebaSession.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  //BOTON VOLVER
  if (isset($_POST['submitVolver'])) {
    header("Location: ../alumnos/menualumnos.php");
    exit;
  }

  //BOTON SELECCIONAR PLAN
  if (isset($_POST['submitVer'])) {

    $idP = $_POST['idP'];
    $nombreP = $_POST['nombreP'];
    $_SESSION['idP'] = $idP;
    $_SESSION['nombreP'] = $nombreP;

    header("Location: ../alumnos/examenes_materias.php");
    exit;
  }
}

//VARIABLES
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];

//FUNCIONES
//LISTAR PLANES
$listadoPlanes = array();
$listadoPlanes = buscarPlanes($conn, $idAlumno);
$cantidad = count($listadoPlanes);
?>
<!DOCTYPE html>
<html lang="es"></html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Planes</title>
  <!-- Bootstrap CSS -->
   <!-- <link rel="stylesheet" href="../css/estilo-prebootstrap.css"> -->
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Bootstrap JS (necesario para el navvar) -->
  <script src="../js/bootstrap.min.js"></script>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>

<body>
<nav class="navbar navbar-expand-lg " data-bs-theme="light">
    <div class="container ">
      <div class="d-flex flex-column align-items-center centrarlogomenu ">
        <img src="../img/logo merce.jpg" class="est-logo img-fluid mx-auto" alt="logo">
      </div>
      <button class="navbar-toggler margenbottom ms-auto" type="button" data-bs-toggle="collapse"
        data-bs-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false"
        aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarColor01">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <a class="nav-link " href="menualumnos.php">Inicio

            </a>
          </li>
          <li class="nav-item active">
            <a class="nav-link" href="calificaciones_planes.php">Calificaciones
              <span class="visually-hidden">(current)</span>
            </a>

          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Insc.Cursado</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Insc. Exámen</a>
          </li>
        </ul>
        <ul class="ms-auto" style="list-style-type: none;">
          <li class="nav-item">
            <a class="nav-link" href="#" style="display: flex; flex-direction: column; align-items: center;"
              onmouseover="this.style.color='#2e8b97'" onmouseleave="this.style.color='#646261'"><i
                class="bi bi-power"></i>Cerrar Sesión</a>
          </li>

        </ul>
      </div>
    </div>
  </nav>
  <div class="container-fluid fondo">
<br>
<div class="container">

  <div class="card card padding col-12">
    <h3>
      <?php echo $nombreAlumno; ?>
    </h3>
  </div>

  <!-- FORM VOLVER -->
  <form id="volver" method="POST">
    <button type="submit" name="submitVolver" class="btn btn-secondary float-end mb-3">Volver</button>
  </form>

  <div class="container mt-5">

    <!-- FORM VER PLAN -->
    <form id="envio" method="post">
      <input type="hidden" name="idP" id="idP">
      <input type="hidden" name="nombreP" id="nombreP">

      <!-- TABLA DE PLANES -->
      <table id="planes" class="table table-hover">
        <thead>
          <tr class="table-primary">
            <th scope="col" style="display:none;">idPlan</th>
            <th scope="col">Carrera</th>
            <th scope="col">Seleccionar Materias</th>
          </tr>
        </thead>
        <tbody>

          <?php

          //RECORRER PARA ARMAR TABLA DE PLANES        
          $a = 0;
          while ($a < $cantidad) {
            $idPlan = $listadoPlanes[$a]['idPlan'];
            $Plan = $listadoPlanes[$a]['Plan'];
            $a++;
            ?>

            <tr class="table-light">
              <td style="display:none;">
                <?php echo $idPlan ?>
              </td>
              <td>
                <?php echo $Plan ?>
              </td>
              <td>
                <button type="submit" name="submitVer" class="btn btn-primary ">Ver</button>
              </td>
            </tr>

            <?php
          }
          ?>

        </tbody>
      </table>
    </form>
    
  </div></div></div>

  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.slim.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

  <script>
    //SCRIPT PARA SELECCIONAR DATOS DEL PLAN A VER
    document.addEventListener("DOMContentLoaded", function () {
      // Agregar un evento de clic a todos los botones con la clase 'ver-btn'
      var botones = document.querySelectorAll('.ver-btn');
      botones.forEach(function (boton) {
        boton.addEventListener('click', function () {
          // Obtener los datos de la fila seleccionada
          var fila = this.closest('tr');
          var idPlanSeleccionado = fila.querySelector("td:nth-child(1)").innerText;
          var nombrePlanCompleto = fila.querySelector("td:nth-child(2)").innerText;
          // Cargar Datos
          document.getElementById("idP").value = idPlanSeleccionado;
          document.getElementById("nombreP").value = nombrePlanCompleto;
          // Enviar el formulario
          document.getElementById("envio").submit();
        });
      });
    });
  </script>

</body>

</html>