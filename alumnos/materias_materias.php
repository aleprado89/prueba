<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
//include '../funciones/pruebaSession.php';

 if ($_SERVER["REQUEST_METHOD"] == "POST") {

   //BOTON LISTADO SOLICITUDES
   if (isset($_POST['submitVer'])) {
     header("Location: ../alumnos/materias_solicitudes_listado.php");
     exit;} 

   //BOTON SOLICITAR
   if (isset($_POST['submitSolicitar'])) {
     $idM = $_POST['idM'];
     $nombreM = $_POST['nombreM'];
     $nombreC = $_POST['nombreC'];
     $_SESSION['idM'] = $idM;
     $_SESSION['nombreM'] = $nombreM;
     $_SESSION['nombreC'] = $nombreC;

     header("Location: ../alumnos/materias_solicitar.php");
     exit;
   }
   if (isset($_POST['curso'])) {
     $cursoSeleccionado = $_POST['curso'];
     // Buscar las materias adeudadas para el curso seleccionado
     $listadoMaterias = buscarMateriasAdeuda($conn, $cicloLectivo, $idAlumno, $idPlan, $cursoSeleccionado);
     
     // Devolver los datos de la tabla en formato HTML
     echo '<table id="materias" class="table table-hover col-12">
       <thead>
         <tr class="table-primary">
           <th scope="col" style="display:none;">idMateria</th>
           <th scope="col">Materia</th>  
           <th scope="col">Curso</th>              
           <th scope="col">Solicitar Inscripción</th>
         </tr>
       </thead>
       <tbody>';
     if (empty($listadoMaterias)) {
       echo '<tr>
         <td colspan="4">Sin registros</td>
       </tr>';
     }
     foreach ($listadoMaterias as $materia) {
       echo '<tr>
         <td style="display:none;" name="idM">'.$materia['idMateria'].'</td>
         <td name="nombreM">'.$materia['Materia'].'</td>      
         <td name="nombreC">'.$materia['Curso'].'</td>             
         <td><button type="submit" name="submitSolicitar" class="btn btn-primary ver-btn">Solicitar</button></td>
       </tr>';
     }
     echo '</tbody>
     </table><br>';
     exit;
   }
 }

//VARIABLES
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];
$idPlan = $_SESSION['idP'];
$nombrePlan = $_SESSION['nombreP'];
$cicloLectivo =  $datosColegio[0]['anioautoweb'];

//FUNCIONES
//LISTAR MATERIAS
$listadoMaterias = array();
$cursosPredeterminados=buscarCursoPredeterminado($conn,$idPlan);
$maxIdCursoPredeterminado = max(array_column($cursosPredeterminados, 'idcursopredeterminado'));

$listadoMaterias = buscarMateriasAdeuda($conn, $cicloLectivo, $idAlumno, $idPlan, $maxIdCursoPredeterminado);
$cantidad = count($listadoMaterias);
?>
<!DOCTYPE html>
<html lang="es">

</html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Materias a cursar</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <!-- Bootstrap CSS -->
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
        <li class="breadcrumb-item"><a href="materias_planes.php">Inscripción a cursado</a></li>
        <li class="breadcrumb-item active">Materias</li>
      </ol>

      <div class="card padding col-12">
        <h5><?php echo "Alumno: " . $nombreAlumno; ?> </h5>
        <h5><?php echo "Carrera: " . $nombrePlan; ?></h5>
        <select id="curso" name="curso">
          <option value="">Seleccione un curso</option>
          <?php foreach ($cursosPredeterminados as $curso) { ?>
            <option value="<?php echo $curso['idcursopredeterminado']; ?>"><?php echo $curso['nombreCurso']; ?></option>
          <?php } ?>
        </select>
      </div>
      <br>
      <div class="text-center">
        <!-- FORM VER LISTADO SOLICITUDES -->
        <form id="ver" method="post">
          <button type="submit" name="submitVer" class="btn btn-primary">Ver Solicitudes</button>
        </form>
      </div>

      <div class="container">
        <br>
        <!-- FORM SOLICITAR -->
                <form id="envio" method="post">
                  <input type="hidden" name="idM" id="idM">
                  <input type="hidden" name="nombreM" id="nombreM">
                  <input type="hidden" name="nombreC" id="nombreC">
        <?php if($_SESSION['cursSoloLectura']==0){ ?>
                  <table id="materias" class="table table-hover col-12"  >
                    <thead>
                      <tr class="table-primary">
                        <th scope="col" style="display:none;">idMateria</th>
                        <th scope="col">Materia</th>  
                        <th scope="col">Curso</th>              
                        <th scope="col">Solicitar Inscripción</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $hayRegistros = false; ?>
                      <?php
        
                      //RECORRER TABLA DE MATERIAS
                      $a = 0;
                      while ($a < $cantidad) {
        
                        $idMateria = $listadoMaterias[$a]['idMateria'];
                        $Materia = $listadoMaterias[$a]['Materia'];
                        $Curso = $listadoMaterias[$a]['Curso'];
                        $a++;
                        $hayRegistros = true; // Establece la variable en true si se encuentra al menos un registro
                        ?>
                        <tr>
                          <td style="display:none;" name="idM">
                            <?php echo $idMateria ?>
                          </td>
                          <td name="nombreM">
                            <?php echo $Materia ?>
                          </td>      
                          <td name="nombreC">
                            <?php echo $Curso ?>
                          </td>             
                          <td><button type="submit" name="submitSolicitar" class="btn btn-primary ver-btn">Solicitar</button></td>
                        </tr>
        
                        <?php } ?>
                        <?php if (!$hayRegistros) { ?>
                          <tr>
                            <td colspan="4" >No hay registros</td>
                          </tr>
                        <?php } ?>
                    </tbody>
                  </table> <?php } else { echo "El período para generar solicitudes de inscripción ha finalizado."; }?>
                </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

  <script>
    //SCRIPT PARA SELECCIONAR DATOS DE LA MATERIA A SOLICITAR
    document.addEventListener("DOMContentLoaded", function () {

      // Agregar un evento de clic a todos los botones con la clase 'ver-btn'
      var botones = document.querySelectorAll('.ver-btn');
      botones.forEach(function (boton) {
        boton.addEventListener('click', function () {
          // Obtener los datos de la fila seleccionada
          var fila = this.closest('tr');
          var idMateriaSeleccionada = fila.querySelector("td:nth-child(1)").innerText;
          var nombreMateriaCompleto = fila.querySelector("td:nth-child(2)").innerText;
          var nombreCursoCompleto = fila.querySelector("td:nth-child(3)").innerText;
          // Cargar Datos
          document.getElementById("idM").value = idMateriaSeleccionada;
          document.getElementById("nombreM").value = nombreMateriaCompleto;
          document.getElementById("nombreC").value = nombreCursoCompleto;
          // Enviar el formulario
          document.getElementById("envio").submit();
        });
      });
    });

   $('#curso').change(function() {
     var cursoSeleccionado = $(this).val();
     console.log(cursoSeleccionado);
     $.ajax({
       type: 'POST',
       url: 'materias_materias.php',
       data: {curso: cursoSeleccionado},
       success: function(data)
        {
    console.log('Respuesta del servidor:', data);
    // Reemplazar solo el contenido de la tabla
    var tabla = $(data).filter('#materias');
    if (tabla.length > 0) {
      $('#materias').html(tabla.html());
    }
  },
  error: function(xhr, status, error) {
    console.log(JSON.stringify(data))  }
     });
   });
  </script>
 
  

  <?php include '../funciones/footer.html'; ?>

</body>

</html>