<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
include '../funciones/verificarSesion.php';
//include '../funciones/pruebaSession.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  //BOTON LISTADO SOLICITUDES
  if (isset($_POST['submitVer'])) {
    header("Location: ../alumnos/examenes_solicitudes_listado.php");
    exit;
  }

  //BOTON SOLICITAR
  if (isset($_POST['submitSolicitar'])) {
    $idM = $_POST['idM'];
    $nombreM = $_POST['nombreM'];
    $nombreC = $_POST['nombreC'];
    $idDivision = $_POST['idDivision'];
    
        // Crear un formulario oculto con los datos
    $html = '<form id="formulario" method="post" action="../alumnos/examenes_solicitar.php">';
    $html .= '<input type="hidden" name="idM" value="' . $idM . '">';
    $html .= '<input type="hidden" name="nombreMateria" value="' . $nombreM . '">';
    $html .= '<input type="hidden" name="nombreCurso" value="' . $nombreC . '">';
    $html .= '<input type="hidden" name="idDivision" value="' . $idDivision . '">';
    $html .= '</form>';
  
    // Agregar JavaScript para enviar el formulario automáticamente
    $html .= '<script>document.getElementById("formulario").submit();</script>';
  
    echo $html;
    exit;

    //header("Location: ../alumnos/examenes_solicitar.php");
   // exit;
  }
}

//VARIABLES
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];
$idPlan = $_SESSION['idP'];
$nombrePlan = $_SESSION['nombreP'];
$idCicloLectivo = $_SESSION['idCiclo'];

//FUNCIONES
//LISTAR MATERIAS
$listadoMaterias = array();
$listadoMaterias = buscarMaterias($conn, $idAlumno, $idPlan);
$cantidad = count($listadoMaterias);
?>
<!DOCTYPE html>
<html lang="es">

</html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exámenes</title>
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
  <li class="breadcrumb-item"><a href="examenes_planes.php">Inscripción a exámenes</a></li>
  <li class="breadcrumb-item active">Materias</li>
</ol>

      <div class="card padding col-12">
        <h5><?php echo "Alumno: " . $nombreAlumno; ?> </h5>
        <h5><?php echo "Carrera: " . $nombrePlan; ?></h5>
        <div class="row ">
          <div class="col-12 col-md-4">
<select name="curso" id="curso" class="form-control" onchange="filtrarTabla(this.value)">
  <option value="">Todas las materias</option>
  <?php
  // Obtener los cursos posibles de la consulta que busca las materias
  $cursos = array();
  foreach ($listadoMaterias as $materia) {
    $cursos[] = $materia['Curso'];
  }
  $cursos = array_unique($cursos);
  foreach ($cursos as $curso) {
    echo '<option value="' . $curso . '">' . $curso . '</option>';
  }
  ?>
</select></div>
      </div>
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
          <input type="hidden" name="idDivision" id="idDivision">
<?php if($_SESSION['soloLecturaExam']==0){ ?>
          <table id="materias" class="table table-hover col-12">
            <thead>
              <tr class="table-primary">
                <th scope="col" style="display:none;">idMateria</th>
                <th scope="col" style="display:none;">Materia Completo</th>
                <th scope="col">Materia</th>
                <th scope="col">Curso</th>
                <th scope="col">Estado</th>
                <!-- <th scope="col">Calif. Final</th> -->
                <th scope="col">Solicitar Exámen</th>
              </tr>
            </thead>
            <tbody>
              <?php $hayRegistros = false; ?>
              <?php
              //RECORRER TABLA DE CALIFICACIONES          
              $a = 0;
              while ($a < $cantidad) {
                //LIMITAR CANTIDAD DE PALABRAS DE NOMBRE MATERIA          
                $idMateria = $listadoMaterias[$a]['idMateria'];
                $Materia = $listadoMaterias[$a]['Materia'];
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
            
                $Curso = $listadoMaterias[$a]['Curso'];
                $Estado = $listadoMaterias[$a]['Estado'];
                $CalificacionFinal = $listadoMaterias[$a]['CalificacionFinal'];
                $idDivision=$listadoMaterias[$a]['idDivision'];
                $a++;
                ?>
            
                <?php if (empty(trim($CalificacionFinal)) || $CalificacionFinal == null) { ?>
                  <?php $hayRegistros = true; ?>
                  <tr>
                    <td style="display:none;" name="idM">
                      <?php echo $idMateria ?>
                    </td>
                    <td style="display:none;" name="nombreM">
                      <?php echo $MateriaCompleto ?>
                    </td>
                    <td style="display:none;" id="idDivision" name="idDivision">
                      <?php echo $idDivision ?>
                    </td>
                    <td>
                      <?php echo $Materia ?>
                    </td>
                    <td name="nombreC">
                      <?php echo $Curso ?>
                    </td>
                    <td>
                      <?php echo $Estado ?>
                    </td>
                    
                    <!-- <td>
                    $calificacionFinal iba aca
                    </td> -->
            
                    <?php
                      //Control Existe Solicitud
                      $listadoSolicitudes = array();
                      $listadoSolicitudes = existeSolicitudExamen($conn, $idAlumno, $idMateria, $idCicloLectivo, $datosColegio[0]['idTurno']);
                      $cantidadSolicitudes = count($listadoSolicitudes);
            
                      $habilitado = true;
                      $b = 0;
                      while ($b < $cantidadSolicitudes) {
                        $Estado = $listadoSolicitudes[$b]['Estado'];
                        if ($Estado == "Pendiente" || $Estado == "Aprobada") {
                          $habilitado = false;
                        }
                      $b++;
                      }
                      
                      //No existe solicitud
                      if ($habilitado == true){
                        if (empty(trim($CalificacionFinal)) || $CalificacionFinal == null) { ?>
                          <td><button type="submit" name="submitSolicitar" class="btn btn-primary ver-btn">Solicitar</button></td>
                        <?php } else { ?>                        
                          <td><button type="submit" name="submitSolicitar" style="display:none;" class="btn btn-primary ver-btn">Solicitar</button></td>
                        <?php } } else { ?>
                          <td><button type="submit" name="submitSolicitar" class="btn btn-primary ver-btn">Ver Solicitudes</button></td>
                        <?php } ?>
                      </tr>                  
                    <?php } } ?>
              <?php if (!$hayRegistros) { ?>
                <tr>
                  <td colspan="6">No hay registros</td>
                </tr>
              <?php } ?>
            </tbody>
          </table> <?php } else { echo "El período para generar solicitudes de inscripción a examen ha finalizado."; } ?>
        </form>
      </div>
    </div>
  </div>
    <script src="../funciones/sessionControl.js"></script>
  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.slim.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

  <script>
    //SCRIPT PARA SELECCIONAR DATOS DE LA MATERIA A SOLICITAR
    document.addEventListener("DOMContentLoaded", function () {
 // Seleccionar el curso más alto por defecto
 var select = document.getElementById('curso');
  select.value = select.options[select.options.length - 1].value;
  filtrarTabla(select.value); // Llamar a la función filtrarTabla después de seleccionar el curso

      // Agregar un evento de clic a todos los botones con la clase 'ver-btn'
      var botones = document.querySelectorAll('.ver-btn');
      botones.forEach(function (boton) {
        boton.addEventListener('click', function () {
          // Obtener los datos de la fila seleccionada
          var fila = this.closest('tr');
         var idMateriaSeleccionada = fila.querySelector("td[name='idM']").innerText;
var nombreMateriaCompleto = fila.querySelector("td[name='nombreM']").innerText;
var nombreCursoCompleto = fila.querySelector("td[name='nombreC']").innerText;
var idDivision = fila.querySelector("td[name='idDivision']").innerText;

          // Cargar Datos
          document.getElementById("idM").value = idMateriaSeleccionada;
          document.getElementById("nombreM").value = nombreMateriaCompleto;
          document.getElementById("nombreC").value = nombreCursoCompleto;
          document.getElementById("idDivision").value = idDivision;
          // Enviar el formulario
          document.getElementById("envio").submit();
        });
      });
    });

    function filtrarTabla(curso) {
      var tabla = document.getElementById('materias');
      var filas = tabla.getElementsByTagName('tr');
      for (var i = 1; i < filas.length; i++) {
        var fila = filas[i];
        var celdaCurso = fila.querySelector('td[name="nombreC"]');
        if (celdaCurso.textContent.trim().toLowerCase() == curso.trim().toLowerCase() || curso == '') {
          fila.style.display = '';
        } else {
          fila.style.display = 'none';
        }
      }
    }
  </script>


  <?php include '../funciones/footer.html'; ?>

</body>

</html>