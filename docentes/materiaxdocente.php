<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';

$doc_legajo = $_SESSION['doc_legajo'];
$nombreDoc = $_SESSION['doc_apellido'].", ".$_SESSION['doc_nombre'];

//si ya seleccionó una materia, la guardo en la variable de sesión
if (isset($_POST['idMateria'])) {
  $_SESSION['idMateria'] = $_POST['idMateria'];
  $_SESSION['materia'] = $_POST['materia'];
  $_SESSION['ciclolectivo'] = $_POST['ciclolectivo'];
  $_SESSION['plan'] = $_POST['plan'];
  header('Location: carga_calif.php');
  exit;
}

// Obtener el  ciclolectivo de tabla colegios para tomar predeterminado
$primerCiclolectivo=$datosColegio[0]['anioCargaNotas'];
// $ciclolectivos = levantarCiclosLectivos(conexion: $conn);
// $primerCiclolectivo = $ciclolectivos[0]['idCicloLectivo'];

// Obtener el primer plan
$planes = buscarPlanesProfesorMateria($conn,$doc_legajo);
$primerPlan = $planes[0]['idPlan'];

// Cargar la variable de sesión con el primer ciclolectivo y plan
if (!isset($_SESSION['valorSeleccionado'])) {
  $_SESSION['valorSeleccionado'] = $primerCiclolectivo;
  $_SESSION['planSeleccionado'] = $primerPlan;
}
if (!isset($materiasAsignadas)) {
  $materiasAsignadas = obtenerMateriasxProfesor($conn,$doc_legajo,$primerCiclolectivo,$primerPlan);
}


if (isset($_POST['valor'])) {
  $valorSeleccionado = $_POST['valor'];
  $ciclolectivo = $_POST['ciclolectivo'];
  $plan = $_POST['plan'];
  // Llamar a la función con el valor seleccionado
  $materiasAsignadas = obtenerMateriasxProfesor($conn,$doc_legajo,$ciclolectivo,$plan);
  
  // Devolver los datos de la tabla en formato HTML
  echo '<table id="tablaMaterias" class="table table-hover col-12">
    <thead>
      <tr class="table-primary">
        <th scope="col">Materias asignadas al docente</th>
      </tr>
    </thead>
    <tbody>';
  foreach ($materiasAsignadas as $materia) {
    echo '<tr>
      <td><a href="#" onclick="setMateria('.$materia['idMateria'].', \''.$materia['Materia'].'\')">'.$materia['Materia'].'</a></td>
    </tr>';
  }
  echo '</tbody>
  </table><br>';
}

if (isset($_SESSION['valorSeleccionado']) && isset($_SESSION['planSeleccionado'])) {
  $valorSeleccionado = $_SESSION['valorSeleccionado'];
  $planSeleccionado = $_SESSION['planSeleccionado'];
  // Llamar a la función con el valor seleccionado
  $materiasAsignadas = obtenerMateriasxProfesor($conn,$doc_legajo,$valorSeleccionado,$planSeleccionado);
}
?>
<!DOCTYPE html>
<html lang="es"></html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ver calificaciones</title>
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
<?php include '../funciones/menu_docente.php'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
  <ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="menudocentes.php">Inicio</a></li>
    <li class="breadcrumb-item active">Carga de calificaciones parciales</li>
</ol>

  <div class="card padding col-12">
    <h5><?php echo  "Docente: ".$nombreDoc; ?> </h5>
        <?php
    $primerCicloLectivo = $datosColegio[0]['anioCargaNotas'];
    ?>
<select name="ciclolectivo" class="form-select" id="ciclolectivo" onchange="cargarValor(this.value)" <?php if ($_SESSION['profeModCiclo'] == 0) { echo 'disabled'; } ?>>
<?php
        $ciclolectivos = levantarCiclosLectivos(conexion: $conn); // Llamar a la función levantarCiclosLectivos
        $ciclolectivo_seleccionado = null;
        foreach ($ciclolectivos as $ciclolectivo) {
          if ($ciclolectivo['anio'] == $primerCicloLectivo) {
            $ciclolectivo_seleccionado = $ciclolectivo;
            break;
          }
        }
        echo '<option value="' . $ciclolectivo_seleccionado['idCicloLectivo'] . '" selected>' . $ciclolectivo_seleccionado['anio'] . '</option>';
        foreach ($ciclolectivos as $ciclolectivo) {
          if ($ciclolectivo['idCicloLectivo'] != $ciclolectivo_seleccionado['idCicloLectivo']) {
            echo '<option value="' . $ciclolectivo['idCicloLectivo'] . '">' . $ciclolectivo['anio'] . '</option>';
          }
        }
      ?>
    </select>
  <br>
  <select name="plan" class="form-select" id="plan" onchange="cargarValor(this.value)">
    <?php
        $planes = buscarPlanesProfesorMateria($conn,$doc_legajo); // Llamar a la función buscarPlanesProfesorMateria
        foreach ($planes as $plan) {
          echo '<option value="' . $plan['idPlan'] . '">' . $plan['nombrePlan'] . '</option>';
        }
        ?>
      </select>
  </div>
  
  
    <br>
    <div>
      <table id="tablaMaterias" class="table table-hover col-12">
        <thead>
          <tr class="table-primary">
            <th scope="col">Materias asignadas al docente</th>
          </tr>
        </thead>
        <tbody>     
        <?php
          $materiasAsignadas = obtenerMateriasxProfesor($conn, $doc_legajo, $primerCicloLectivo, $primerPlan);
          foreach ($materiasAsignadas as $materia) { ?>
            <tr>
              <td>
                <a href="#" onclick="setMateria(<?php echo $materia['idMateria']; ?>, '<?php echo $materia['Materia']; ?>')">
                  <?php echo $materia['Materia']; ?>
                </a>
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
      $.ajax({
        type: 'POST',
        url: 'materiaxdocente.php',
        data: {valor: valor, ciclolectivo: ciclolectivo, plan: plan},
        success: function(data) {
          console.log('Respuesta del servidor:', data);
          // Reemplazar solo el contenido de la tabla
          var tabla = $(data).filter('#tablaMaterias');
          if (tabla.length > 0) {
            $('#tablaMaterias').html(tabla.html());
          }
        }
      });
    }
    //funcion para pasar idmateria,ciclolectivo y plan a la pagina carga_calif.php
    function setMateria(idMateria, materia) {
      var ciclolectivo = $('#ciclolectivo').find('option:selected').text();
      var plan = $('#plan').find('option:selected').text();
      $.ajax({
        type: 'POST',
        url: '<?php echo $_SERVER['PHP_SELF']; ?>',
        data: {idMateria: idMateria, materia: materia, ciclolectivo: ciclolectivo, plan: plan},
        success: function(data) {
          window.location.href = 'carga_calif.php';
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