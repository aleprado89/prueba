<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/verificarSesion.php';
//include '../funciones/pruebaSession.php';

$idAlumno = $_SESSION['alu_idAlumno'];
$idPlan = $_SESSION['idP'];
$nombreAlumno = $_SESSION['alu_nombre']." ".$_SESSION['alu_apellido'];
$nombrePlan = $_SESSION['nombreP'];

$idCursoPredeterminado = " ";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['curso'])) {
    $idCursoPredeterminado = $_POST['curso'];
  }
}

if ($idCursoPredeterminado == " ")
{
  $cursoM = " ";
  $cursoM = buscarCursoMatriculado($conn, $idPlan, $idAlumno);
  if ($cursoM != " "){$idCursoPredeterminado = $cursoM;}
}

$listadoCursosP = array();
$listadoCursosP = buscarCursoPredeterminado($conn, $idPlan);
$cantidadCursos = count($listadoCursosP);

if ($idCursoPredeterminado == "T")
{
  $listadoCalificaciones = array();
  $listadoCalificaciones = buscarMaterias($conn, $idAlumno, $idPlan);
  $cantidad = count($listadoCalificaciones);
}
else
{
$listadoCalificaciones = array();
$listadoCalificaciones = buscarMateriasCurso($conn, $idAlumno, $idPlan, $idCursoPredeterminado);
$cantidad = count($listadoCalificaciones);
}
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

<?php include '../funciones/menu.php'; ?>

<div class="container-fluid fondo">
<br>

<div class="container">
<ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="menualumnos.php">Inicio</a></li>
  <li class="breadcrumb-item"><a href="calificaciones_planes.php">Carreras</a></li>
  <li class="breadcrumb-item active">Materias</li>
</ol>
<h3 class="text-center">Consulta de calificaciones</h3>
<br>
<div class="card padding col-12">
  <h5><?php echo "Alumno: ".$nombreAlumno; ?></h5>
  <h5><?php echo "Carrera: ".$nombrePlan; ?></h5>
  <br>
  <div class="row col-12 col-md-4">
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <label for="anios">Selecciona el curso:</label>
      <select class="form-select margenes padding" id="curso" name="curso" onchange="this.form.submit()">
      
      <?php
        $a = 0;
        while ($a < $cantidadCursos) {
          $idCursoP = $listadoCursosP[$a]['idcursopredeterminado'];
          $nombreC = $listadoCursosP[$a]['nombreCP']; // Ya ajustado en la función de consultas
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
          
        <option type="submit" value="T" <?php if ($idCursoPredeterminado == "T"){?> selected <?php }; ?>>
        Todas las Materias </option>
        
      </select>
    </form>
  </div>
</div>
<br><br>
<div class="text-center">
   <!-- BOTON VER LISTADO CALIFICACIONES -->
           <a href="../reportes/PDFcalificacionesAlumno.php" target="_blank">
        <button class="btn btn-primary">Imprimir Calificaciones</button>
    </a>
      </div>
</div>

   
 <div class="container mt-5">
   <table id="tablamaterias" class="table table-hover">
     <thead>
       <tr class="table-primary">
         <!-- Columnas existentes ocultas -->
         <th scope="col" style="display:none;">idMateria</th>
         <th scope="col" style="display:none;">Materia Completo</th>
         <th scope="col" style="display:none;">idCicloLectivoMateria</th> <!-- Para pasar el ID del ciclo -->

         <!-- Columnas visibles -->
         <th scope="col">Materia</th>
         <th scope="col">Año</th> <!-- Nueva columna para el año de la materia -->
         <th scope="col">Asistencia</th>
         <th scope="col">Estado</th>
         <th scope="col">Exa. Final</th>
         <th scope="col">Ver Calificaciones</th>
         <th scope="col">Ver Asistencia</th> <!-- Nueva columna para ver asistencia global -->
       </tr>
     </thead>
     <tbody>
       <?php if ($cantidad == 0) { ?>
         <tr>
           <td colspan="9" >No hay registros</td>
         </tr>
       <?php } else { ?>
         <?php
         //RECORRER TABLA DE CALIFICACIONES
         
         $a = 0;
         while ($a < $cantidad) {
 
           //LIMITAR CANTIDAD DE PALABRAS DE NOMBRE MATERIA
         
           $idMateria = $listadoCalificaciones[$a]['idMateria'];
           $Materia = $listadoCalificaciones[$a]['Materia'];
           $MateriaCompleto = $Materia; // Asegúrate de tener el nombre completo para pasar
           $idCicloLectivoMateria = $listadoCalificaciones[$a]['idCicloLectivoMateria']; // Obtener el ID del ciclo
           $anioCiclo = $listadoCalificaciones[$a]['anioCiclo']; // Obtener el año

           // Esto era para limitar las palabras del nombre de la materia, lo mantengo si es necesario
           $MateriaArray = explode(" ", $Materia);
           $cantidadPabras = count($MateriaArray);
           $nombreMateriaCorta = "";
           $b = 0;
           while ($b < $cantidadPabras) {
             if ($b == 0) {
               $nombreMateriaCorta = $MateriaArray[$b];
             } else {
               $nombreMateriaCorta = $nombreMateriaCorta . " " . $MateriaArray[$b];
             }
             $b++;
             if ($b == 8) {
               $nombreMateriaCorta = $nombreMateriaCorta . "...";
               break;
             }
           }
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
             <td style="display:none;">
               <?php echo $idCicloLectivoMateria ?> <!-- ID del ciclolectivo para pasar a la vista de asistencia -->
             </td>
             <td style="color:#2196F3">
               <?php echo $nombreMateriaCorta ?>
             </td>
             <td>
               <?php echo $anioCiclo ?> <!-- Mostrar el año de la materia -->
             </td>          
             <td>
                <!-- Enlace en la columna Asistencia modificado -->
                <a href="../alumnos/calificaciones_verAsistencias.php?idMateria=<?php echo $idMateria; ?>&idCiclo=<?php echo $idCicloLectivoMateria; ?>&nombreMateria=<?php echo urlencode($MateriaCompleto); ?>" class="text-decoration-none">
                    <?php echo $Asistencia ?>
                </a>
             </td>
             <td>
               <?php echo $Estado ?>
             </td>
             <td class= "text-center">
               <?php echo $CalificacionFinal ?>
             </td>
             <td><button type="button" onclick="verCalificaciones(this)" class="btn btn-primary">Ver Calificaciones</button></td>
             <td>
                <!-- Nuevo botón "Ver Asistencia" modificado -->
                <button type="button" onclick="verTodaAsistencia(this)" class="btn btn-primary">Ver Asistencia</button>
             </td>
           </tr>
 
           <?php
         }
         ?>
       <?php } ?>
     </tbody>
   </table>
 </div>
</div>
    <script src="../funciones/sessionControl.js"></script>
  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.slim.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

  <script>
    function verCalificaciones(boton) {
      // Cargar idMateria y nombreMateria para pasar
      var row = boton.closest('tr');
      // Indices de las celdas ocultas: 0 (idMateria), 1 (Materia Completo), 2 (idCicloLectivoMateria)
      var idMateriaSeleccionada = row.cells[0].textContent.trim();
      var nombreMateriaCompleto = row.cells[1].textContent.trim();
      
      // Redirigir a otra página y pasar los datos como parámetro en la URL
      window.location.href =
        '../alumnos/calificaciones_verCalificaciones.php?idM=' + encodeURIComponent(idMateriaSeleccionada) +
        '&nombreM=' + encodeURIComponent(nombreMateriaCompleto);
    }

    // Función para el botón "Ver Asistencia" y el porcentaje
    function verTodaAsistencia(boton) {
        var row = boton.closest('tr');
        var idMateriaSeleccionada = row.cells[0].textContent.trim(); // idMateria está en la columna 0 (oculta)
        var nombreMateriaCompleto = row.cells[1].textContent.trim(); // Materia Completo está en la columna 1 (oculta)
        var idCicloLectivoMateria = row.cells[2].textContent.trim(); // idCicloLectivoMateria está en la columna 2 (oculta)
        
        // Redirigir a la nueva página de asistencia, pasando todos los parámetros necesarios
        window.location.href = '../alumnos/calificaciones_verAsistencias.php?' +
            'idMateria=' + encodeURIComponent(idMateriaSeleccionada) +
            '&idCiclo=' + encodeURIComponent(idCicloLectivoMateria) +
            '&nombreMateria=' + encodeURIComponent(nombreMateriaCompleto);
    }
    
    // Función para que al hacer click en la fila (excepto en un enlace/botón)
    // se comporte como el botón "Ver Calificaciones"
    document.addEventListener("DOMContentLoaded", function() {
        var table = document.getElementById("tablamaterias");
        if (table) {
            var rows = table.getElementsByTagName("tr");
            for (var i = 0; i < rows.length; i++) {
                if (rows[i].querySelector('th')) continue; // Skip header row
                rows[i].addEventListener('click', function(event) {
                    // Evitar que el evento se propague si se hizo clic en un enlace o botón dentro de la fila
                    if (event.target.closest('a') || event.target.closest('button')) {
                        return; // Si el clic fue en un enlace o botón, no hacer nada para la fila
                    }
                    verCalificaciones(this); // Llamar a la función principal para ver calificaciones
                });
            }
        }
    });
  </script>
  
 
 <?php include '../funciones/footer.html'; ?>

</body>

</html>