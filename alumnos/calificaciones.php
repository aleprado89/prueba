<?php
session_start();

include '../inicio/conexion.php';
include '../funciones/consultas.php';
//include '../funciones/pruebaSession.php';

$idAlumno = $_SESSION['alu_idAlumno'];
$idPlan = $_SESSION['idP'];
$nombreAlumno = $_SESSION['alu_nombre']." ".$_SESSION['alu_apellido'];
$nombrePlan = $_SESSION['nombreP'];

$idCursoPredeterminado = " ";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $idCursoPredeterminado = $_POST['curso'];
}

$listadoCursosP = array();
$listadoCursosP = buscarCursoPredeterminado($conn, $idPlan);
$cantidadCursos = count($listadoCursosP);

$listadoCalificaciones = array();
$listadoCalificaciones = buscarMateriasCurso($conn, $idAlumno, $idPlan, $idCursoPredeterminado);
$cantidad = count($listadoCalificaciones);
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calificaciones</title>
   <!-- Bootstrap CSS -->
   <!-- <link rel="stylesheet" href="../css/estilo-prebootstrap.css"> -->
   <link rel="stylesheet" href="../css/material/bootstrap.min.css">
   <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> 

<!-- Bootstrap JS (necesario para el navvar) -->
<script src="../js/bootstrap.min.js"></script> 

</head>

<body>

<nav class="navbar navbar-expand-lg " data-bs-theme="light">
  <div class="container ">
  <div class="d-flex flex-column align-items-center centrarlogomenu ">
    <img src="../img/logo merce.jpg" class="est-logo img-fluid mx-auto" alt="logo" >
  </div>
    <button class="navbar-toggler margenbottom ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
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
          <a class="nav-link" href="#" style="display: flex; flex-direction: column; align-items: center;" onmouseover="this.style.color='#2e8b97'" onmouseleave="this.style.color='#646261'"><i class="bi bi-power" ></i>Cerrar Sesión</a>
        </li> 

  </ul>
    </div>
  </div>
</nav>
<div class="container-fluid fondo">

<br>
<div class="container">
<h3 class="text-center">Consulta de Calificaciones</h3>
<br>
<div class="card padding col-12">
  <h5><?php echo "Alumno: ".$nombreAlumno; ?></h5>
  <h5><?php echo "Carrera: ".$nombrePlan; ?></h5>
  <br>
  <div class="row col-12 col-md-4">
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <label for="anios">Selecciona el Curso:</label>
      <select class="form-select margenes padding" id="curso" name="curso" onchange="this.form.submit()">
      
      <?php
        $a = 0;
        while ($a < $cantidadCursos) {
          $idCursoP = $listadoCursosP[$a]['idcursopredeterminado'];
          $nombreC = $listadoCursosP[$a]['nombreCurso'];
          ?>
          <option type="submit" value="<?php echo $idCursoP; ?>"
          <?php if($idCursoPredeterminado == $idCursoP) echo 'selected'; ?>>
            <?php echo $nombreC; ?>
          </option>
          <?php
          $a++;
        }
        if ($idCursoPredeterminado == " ") {      
        ?>        
        <option type="submit" selected>  </option>
        <?php } ?>
        
      </select>
    </form>
  </div>
</div>
  
</div>

   
  <div class="container mt-5">
     <table id="tablamaterias" class="table table-hover">
      <thead>
        <tr class="table-primary">
          <!-- 24 columnas -->
          <th scope="col" style="display:none;">idMateria</th>
          <th scope="col" style="display:none;">Materia Completo</th>
          <th scope="col">Materia</th>
          
          <th scope="col">Asistencia</th>
          <th scope="col">Estado</th>
          <th scope="col">Exa. Final</th>
          <th scope="col">Ver Calificaciones</th>
        </tr>
      </thead>
      <tbody>

        <?php

        //RECORRER TABLA DE CALIFICACIONES
        
        $a = 0;
        while ($a < $cantidad) {

          //LIMITAR CANTIDAD DE PALABRAS DE NOMBRE MATERIA
        
          $idMateria = $listadoCalificaciones[$a]['idMateria'];
          $Materia = $listadoCalificaciones[$a]['Materia'];
          $MateriaCompleto = $Materia;
          $MateriaArray = explode(" ", $Materia);
          $cantidadPabras = count($MateriaArray);
          $b = 0;
          while ($b < $cantidadPabras) {
            if ($b == 0) {
              $Materia = $MateriaArray[$b];
            } else {
              $Materia = $Materia . " " . $MateriaArray[$b];
            }
            $b++;
            if ($b == 8) {
              $Materia = $Materia . "...";
              break;
            }
          }
          $Curso = $listadoCalificaciones[$a]['Curso'];
          $n1 = $listadoCalificaciones[$a]['n1'];
          $n2 = $listadoCalificaciones[$a]['n2'];
          $n3 = $listadoCalificaciones[$a]['n3'];
          $n4 = $listadoCalificaciones[$a]['n4'];
          $n5 = $listadoCalificaciones[$a]['n5'];
          $n6 = $listadoCalificaciones[$a]['n5'];
          $n7 = $listadoCalificaciones[$a]['n5'];
          $n8 = $listadoCalificaciones[$a]['n5'];
          $r1 = $listadoCalificaciones[$a]['n5'];
          $r2 = $listadoCalificaciones[$a]['n5'];
          $r3 = $listadoCalificaciones[$a]['n5'];
          $r4 = $listadoCalificaciones[$a]['n5'];
          $r5 = $listadoCalificaciones[$a]['n5'];
          $r6 = $listadoCalificaciones[$a]['n5'];
          $r7 = $listadoCalificaciones[$a]['n5'];
          $r8 = $listadoCalificaciones[$a]['n5'];
          $Asistencia = $listadoCalificaciones[$a]['Asistencia'];
          $Estado = $listadoCalificaciones[$a]['Estado'];
          $CalificacionFinal = $listadoCalificaciones[$a]['CalificacionFinal'];
          $a++;
          ?>

          <tr class="table-light">
            <td style="display:none;">
              <?php echo $idMateria ?>
            </td>
            <td style="display:none;">
              <?php echo $MateriaCompleto ?>
            </td>
            <td>
              <?php echo $Materia ?>
            </td>
           
            </td>
            <td>
              <?php echo $Asistencia ?>
            </td>
            <td>
              <?php echo $Estado ?>
            </td>
            <td>
              <?php echo $CalificacionFinal ?>
            </td>
            <td><button type="button" onclick="verCalificaciones(this)" class="btn btn-primary">Ver Calificaciones</button></td>
          </tr>

          <?php
        }
        ?>

      </tbody>
    </table>
  </div>
</div>
  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.slim.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

  <script>
    function verCalificaciones(boton) {
      // Cargar idMateria y nombreMateria para pasar
      var idMateriaSeleccionada = boton.closest('tr').querySelector('td:nth-child(1)').textContent;
      var nombreMateriaCompleto = boton.closest('tr').querySelector('td:nth-child(2)').textContent;
      // Redirigir a otra página y pasar los datos como parámetro en la URL
      window.location.href =
        '../alumnos/calificaciones_verCalificaciones.php?idM=' + encodeURIComponent(idMateriaSeleccionada) +
        '&nombreM=' + encodeURIComponent(nombreMateriaCompleto);
    }
//FUNCION PARA QUE SI HAGO CLICK EN UNA FILA HACA LO MISMO DEL BOTON VER CALIFICACINES
    document.addEventListener("DOMContentLoaded", function() {
    var table = document.getElementById("tablamaterias");
    if (table) {
      var rows = table.getElementsByTagName("tr");
      for (var i = 0; i < rows.length; i++) {
        rows[i].onclick = function() {
          // Acción a realizar cuando se hace clic en una fila
          verCalificaciones(this);
                };
      }
    }
  });
  </script>

</body>

</html>