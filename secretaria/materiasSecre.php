<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
 // Verifica si la cookie existe y establece la variable de sesión con su valor
if (isset($_COOKIE['parametro'])) {
  $_SESSION['parametro'] = $_COOKIE['parametro'];
  $urlForm = $_SESSION['parametro'];
} else {
  // Si la cookie no existe, establece un valor por defecto para la variable de sesión
  $_SESSION['parametro'] = '';
  $urlForm = '';
}
}

include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';

$doc_legajo = $_SESSION['doc_legajo'];
$nombreDoc = $_SESSION['doc_apellido'].", ".$_SESSION['doc_nombre'];

//$urlForm='carga_calif.php';
$urlForm = $_SESSION['parametro'];

//si ya seleccionó una materia, la guardo en la variable de sesión
if (isset($_POST['idMateria'])) {
  $_SESSION['idMateria'] = $_POST['idMateria'];
  $_SESSION['materia'] = $_POST['materia'];
  $_SESSION['ciclolectivo'] = $_POST['ciclolectivo'];
  $_SESSION['plan'] = $_POST['plan'];
  $_SESSION['curso'] = $_POST['curso'];
  $url=$_POST['urlForm'];
  header('Location: '.$url);
  exit;
}

// Obtener el  ciclolectivo de tabla colegios para tomar predeterminado
$primerCiclolectivo='2025';
// $ciclolectivos = levantarCiclosLectivos(conexion: $conn);
// $primerCiclolectivo = $ciclolectivos[0]['idCicloLectivo'];

// Obtener el primer plan
$planes = buscarTodosPlanes($conn);
$primerPlan = $planes[0]['idPlan'];
$nombrePlan=$planes[0]['nombrePlan'];
//obtener el primer curso
$cursos = buscarCursosPlanCiclo($conn,$primerPlan,$primerCiclolectivo);
$primerCurso = $cursos[0]['idCurso'];
$nombreCurso=$cursos[0]['nombreCurso'];



// Cargar la variable de sesión con el primer ciclolectivo y plan
if (!isset($_SESSION['valorSeleccionado'])) {
  $_SESSION['valorSeleccionado'] = $primerCiclolectivo;
  $_SESSION['planSeleccionado'] = $primerPlan;
  $_SESSION['cursoSeleccionado'] = $primerCurso;
}
if (!isset($materiasAsignadas)) {
  $materiasAsignadas = materiasPlanCurso($conn,$primerPlan,$primerCurso);
}

if (isset($_POST['plan']) || isset($_POST['valor'])) {
  $plan = $_POST['plan'];
  $cursos = buscarCursosPlanCiclo($conn, $plan, $primeridCiclo);
  echo '<select name="curso" class="form-select margenes padding" id="curso">';
  echo '<option value="">Seleccione un curso</option>';
  foreach ($cursos as $curso) {
    echo '<option value="' . $curso['idCurso'] . '">' . $curso['nombre'] . '</option>';
  }
  echo '</select>';
  exit;
}
if (isset($_POST['valor'])) {
  $valorSeleccionado = $_POST['valor'];
  $ciclolectivo = $_POST['ciclolectivo'];
  $plan = $_POST['plan'];
  $curso = $_POST['curso'];
  $nombrePlan=buscarNombrePlan($conn,$plan);

  // Llamar a la función con el valor seleccionado
  $materiasAsignadas = materiasPlanCurso($conn,$primerPlan,$primerCurso);

  function getMes($i) {
    $meses = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    return $meses[$i-1];
  }
  // Devolver los datos de la tabla en formato HTML
  echo '<table id="tablaMaterias" class="table table-hover col-12">
    <thead>
      <tr class="table-primary">
        <th scope="col">Materias asignadas al docente</th>
        <th scope="col">Curso</th>
        <th scope="col">Imprimir</th>
      </tr>
    </thead>
    <tbody>';
    if (empty($materiasAsignadas)) {
      echo '<tr>
      <td colspan="3">Sin registros</td>
    </tr>';
    }
  foreach ($materiasAsignadas as $materia) {
    echo '<tr>
<td><a href="#" onclick="setMateria('.$materia['idMateria'].', \''.$materia['Materia'].'\', \''.$materia['Curso'].'\', \''.$_SESSION['parametro'].'\')">'.$materia['Materia'].'</a></td>
     <td>'.$materia['Curso'].' </td>
     <td class="text-center">';

     if ($urlForm == 'carga_calif.php') {
echo '<a href="../reportes/calificacionesDocPDF.php?idMateria=' . htmlspecialchars($materia['idMateria']) . '&materia=' . htmlspecialchars($materia['Materia']) . '&curso=' . htmlspecialchars($materia['Curso']) . '&plan=' . htmlspecialchars($nombrePlan) . '&ciclolectivo=' . htmlspecialchars($ciclolectivo) . '" target="_blank"><i class="bi bi-printer"></i></a>';
             } else if ($urlForm == 'carga_asist.php') {              
              $url = '../reportes/asistenciaDocPDF.php?idMateria='.htmlspecialchars(string: $materia['idMateria']).'&materia='.htmlspecialchars($materia['Materia']).'&curso='.htmlspecialchars($materia['Curso']).'&plan='.htmlspecialchars($nombrePlan).'&ciclolectivo='.htmlspecialchars($ciclolectivo).'&mes=';
echo '<select onchange="window.open(\''.$url.'\'+this.value, \'_blank\')">';    

              echo '<option value="">Mes</option>';
      for ($i = 1; $i <= 12; $i++) {
        echo '<option value="'.$i.'">'.getMes($i).'</option>';
      }
      echo '</select>';
     }
     echo '</td>
   </tr>';
  }
  echo '</tbody>
  </table><br>';
}

if (isset($_SESSION['valorSeleccionado']) && isset($_SESSION['planSeleccionado'])) {
  $valorSeleccionado = $_SESSION['valorSeleccionado'];
  $planSeleccionado = $_SESSION['planSeleccionado'];
  $cursoSeleccionado = $_SESSION['cursoSeleccionado'];
  // Llamar a la función con el valor seleccionado
  $materiasAsignadas = materiasPlanCurso($conn,$primerPlan,$primerCurso);
}
?>
<!DOCTYPE html>
<html lang="es"></html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seleccionar Materia</title>
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
<?php include '../funciones/menu_secretaria.php';?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
  <ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="menudocentes.php">Inicio</a></li>
  <?php if ($urlForm=='carga_calif.php') { 
    echo '<li class="breadcrumb-item active">Calificaciones por materia</li>'; } else
    if ($urlForm=='carga_asist.php') {
    echo '<li class="breadcrumb-item active">Asistencias</li>'; } ?>
</ol>

  <div class="card padding col-12">
    <h5><?php echo  "Docente: ".$nombreDoc; ?> </h5>
        <?php
    ?>
    <br>
      <div class="row col-12 col-md-6">
      <div class="col-auto "> 
<label>Ciclo Lectivo:</label></div>
<div class="col-12 col-md-8 ">
<select name="ciclolectivo" class="form-select margenes padding" id="ciclolectivo" onchange="cargarValor(this.value)">
  <?php
  $ciclolectivos = levantarCiclosLectivos(conexion: $conn); // Llamar a la función levantarCiclosLectivos
  $ultimoCiclolectivo = max(array_column($ciclolectivos, 'anio'));
  foreach ($ciclolectivos as $ciclolectivo) {
    if ($ciclolectivo['idCicloLectivo'] == $ultimoCiclolectivo) {
      echo '<option value="' . $ciclolectivo['idCicloLectivo'] . '" selected>' . $ciclolectivo['anio'] . '</option>';
    } else {
      echo '<option value="' . $ciclolectivo['idCicloLectivo'] . '">' . $ciclolectivo['anio'] . '</option>';
    }
  }
  ?>
</select></div></div>
  <br>
  <div class="row col-12 col-md-6 ">
    <div class="col-auto ">  <label>Carrera:</label>
    </div>
    <div class="col-12 col-md-9 ">
    <select name="plan" class="form-select margenes padding" id="plan" onchange="cargarValor(this.value)">
      <?php
          $planes = buscarTodosPlanes($conn); 
          foreach ($planes as $plan) {
            echo '<option value="' . $plan['idPlan'] . '">' . $plan['nombre'] . '</option>';
          }
          ?>
    </select></div></div>
    <br>
    <div class="row col-12 col-md-6 ">
      <div class="col-auto ">  <label>Curso:</label>
      </div>
      <div class="col-12 col-md-9 ">
        <select name="curso" class="form-select margenes padding" id="curso" onchange="cargarValor(this.value)">
          <?php
          $primeridCiclo=buscarIdCiclo($conn,$ultimoCiclolectivo);
          $cursos = buscarCursosPlanCiclo($conn, $primerPlan,$primeridCiclo); // Llamar a la función buscarCursosPlan
          foreach ($cursos as $curso) {
            echo '<option value="' . $curso['idCurso'] . '">' . $curso['nombre'] . '</option>';
          }
          ?>
        </select>
      </div>
    </div></div>
      <br>
    <div>
    <table id="tablaMaterias" class="table table-hover col-12">
  <thead>
    <tr class="table-primary">
      <th scope="col">Materias asignadas al docente</th>
      <th scope="col">Curso</th>
      <th scope="col">Imprimir</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($materiasAsignadas as $materia) { ?>
    <tr>
<td><a href="#" onclick="setMateria(<?php echo $materia['idMateria']; ?>, '<?php echo $materia['Materia']; ?>', '<?php echo $materia['Curso']; ?>', '<?php echo $_SESSION['parametro']; ?>')"><?php echo $materia['Materia']; ?></a></td>
      <td><?php echo $materia['Curso']; ?> </td>
      <td class="text-center">
      <?php if ($urlForm == 'carga_calif.php') { ?>
<a href="../reportes/calificacionesDocPDF.php?idMateria=<?php echo htmlspecialchars($materia['idMateria']); ?>&materia=<?php echo htmlspecialchars($materia['Materia']); ?>&curso=<?php echo htmlspecialchars($materia['Curso']); ?>&plan=<?php echo htmlspecialchars($primerPlan); ?>&ciclolectivo=<?php echo htmlspecialchars($primerCicloLectivo); ?>" target="_blank"><i class="bi bi-printer"></i></a>
          <?php } else if ($urlForm == 'carga_asist.php') { ?>
          <select onchange="window.open('../reportes/asistenciaDocPDF.php?idMateria=<?php echo $materia['idMateria']; ?>&mes='+this.value, '_blank')">
            <?php for ($i = 1; $i <= 12; $i++) { ?>
              <option value="<?php echo $i; ?>"><?php echo getMes($i); ?></option>
            <?php } ?>
          </select>
        <?php } ?>
      </td>
    </tr>
  <?php } ?>
  </tbody>     
      </table>
    </div>
  
        </div>
  </div>
  
  
    <!-- Bootstrap JS y jQuery (necesario para el modal) -->
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>

    <!-- obtengo el valor seleccionado del select y actualizo la tabla html de materias-->
  <script>
   function cargarValor(valor) {
     var ciclolectivo = $('#ciclolectivo').val();
     var plan = $('#plan').val();
     var curso = $('#curso').val();
     $.ajax({
       type: 'POST',
       url: 'materiaxdocente.php',
       data: {valor: valor, ciclolectivo: ciclolectivo, plan: plan, curso: curso},
       success: function(data) {
         console.log('Respuesta del servidor:', data);
         // Reemplazar solo el contenido de la tabla
         var tabla = $(data).filter('#tablaMaterias');
         if (tabla.length > 0) {
           $('#tablaMaterias').html(tabla.html());
         }
         // Reemplazar el select de cursos
      $('#curso').replaceWith(data);
    }
  });
}
    //funcion para pasar idmateria,ciclolectivo y plan a la pagina carga_calif.php
    function setMateria(idMateria, materia,curso, urlForm) {
      var ciclolectivo = $('#ciclolectivo').find('option:selected').text();
      var plan = $('#plan').find('option:selected').text();
      $.ajax({
        type: 'POST',
        url: '<?php echo $_SERVER['PHP_SELF']; ?>',
        data: {idMateria: idMateria, materia: materia, ciclolectivo: ciclolectivo, plan: plan,curso: curso ,urlForm: urlForm},
        success: function(data) {
          window.location.href = urlForm;
        }
      });
    }
  </script>
  <script>
    $(document).ready(function() {
      var ciclolectivo = $('#ciclolectivo').val();
      var plan = $('#plan').val();
      $.ajax({
        type: 'POST',
        url: 'materiaxdocente.php',
        data: {valor: '', ciclolectivo: ciclolectivo, plan: plan},
        success: function(data) {
          console.log('Respuesta del servidor:', data);
          // Reemplazar solo el contenido de la tabla
          var tabla = $(data).filter('#tablaMaterias');
          if (tabla.length > 0) {
            $('#tablaMaterias').html(tabla.html());
          }
        }
      });
    });
  </script>
  
    <?php include '../funciones/footer.html'; ?>
  
  </body>
  
  </html>