<?php
session_start();

include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
include '../funciones/verificarSesion.php';

$doc_legajo = $_SESSION['doc_legajo'];
$nombreDoc = $_SESSION['doc_apellido'].", ".$_SESSION['doc_nombre'];

$CicloLectivo=$datosColegio[0]['anioCargaNotas'];
$idturno=$datosColegio[0]['cargaActaVolTurno'];
//obtener idciclo
$idCicloLectivo=buscarIdCiclo($conn,$CicloLectivo);

// Obtener el primer plan
$planes = buscarPlanesProfesorMateria($conn,$doc_legajo);
$primerPlan = $planes[0]['idPlan'];
$idPlan=$planes[0]['idPlan'];

$fechasExamen=obtenerFechasExamenProfesor($conn,$doc_legajo,$idCicloLectivo,$idturno, $idPlan);

//inicio request method
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizarAcordeones'])) {
  $idPlan = $_POST['idPlan'];
  $doc_legajo = $_SESSION['doc_legajo'];
  $idCicloLectivo = buscarIdCiclo($conn, $CicloLectivo);
  $fechasExamen = obtenerFechasExamenProfesor($conn, $doc_legajo, $idCicloLectivo, $idturno, $idPlan);

  $materias = array();
  foreach ($fechasExamen as $examen) {
      if (!isset($materias[$examen['nombreMateria']])) {
          $materias[$examen['nombreMateria']] = array();
      }
      $materias[$examen['nombreMateria']][] = $examen;
  }

  $html = '';
  foreach ($materias as $nombreMateria => $examenes) {
      $html .= '<div class="accordion-item">';
      $html .= '<h2 class="accordion-header" id="heading-' . $examenes[0]['idFechaExamen'] . '">';
      $html .= '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-' . $examenes[0]['idFechaExamen'] . '" aria-expanded="false" aria-controls="collapse-' . $examenes[0]['idFechaExamen'] . '">';
      $html .= $nombreMateria;
      $html .= '</button>';
      $html .= '</h2>';
      $html .= '<div id="collapse-' . $examenes[0]['idFechaExamen'] . '" class="accordion-collapse collapse" aria-labelledby="heading-' . $examenes[0]['idFechaExamen'] . '" data-bs-parent="#accordionExample">';
      $html .= '<div class="accordion-body">';
      foreach ($examenes as $examen) {
          $html .= '<p>' . $examen['Fecha'] . ' - ' . $examen['Hora'] . ' - ' . $examen['Curso'] . '</p>';
      }
      $html .= '</div>';
      $html .= '</div>';
      $html .= '</div>';
  }

  echo $html;
  exit;
}


?>
<!DOCTYPE html>
<html lang="es"></html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mesas de exámenes</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <!-- Bootstrap CSS -->
   <link rel="stylesheet" href="../css/material/bootstrap.min.css">
   <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> 

<!-- Bootstrap JS (necesario para el navvar) -->
<script src="../js/bootstrap.min.js"></script> 
<script src="../js/bootstrap.bundle.min.js"></script>

</head>

<body>
<?php include '../funciones/menu_docente.php';?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
  <ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="menudocentes.php">Inicio</a></li>
   <li class="breadcrumb-item active">Mesas de exámenes</li> 
</ol>

  <div class="card padding col-12">
    <h5><?php echo  "Docente: ".$nombreDoc; ?> </h5>    
<h5><label for="ciclolectivo">Ciclo lectivo: <?php echo $CicloLectivo; ?></label></h5>
  <br>
  <script>
   $(document).ready(function() {
       // Habilitar colapso de acordeones de forma individual
       $('.collapse').on('show.bs.collapse', function () {
           var $opened = $(this).closest('.accordion').find('.collapse.show');
           if ($opened.length) {
               $opened.collapse('hide');
           }
       });
   });
  var accordionState = null; // Variable global para almacenar el estado del acordeón
  
  function cargarValor(valor) {
      var idPlan = valor;
      
      // Almacena el estado del acordeón solo la primera vez
      if (accordionState === null) {
          accordionState = $('#accordionExample').html();
      }
  
      $.ajax({
          type: 'POST',
          url: 'mesasExamenProf.php',
          data: {idPlan: idPlan, actualizarAcordeones: true},
          success: function(data) {
              if (data.trim() === '') {
                  $('#accordionExample').html('<p>No hay mesas de examen de las materias del profesor.</p>');
              } else {
                  // Actualiza el contenido
                  $('#accordionExample').html(data);
                  
                  
                  // Restaura el estado del acordeón
                  if (accordionState) {
                      $('#accordionExample').html(accordionState);
                  }
              }
          }
      });
  }
</script>

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
      <div class="container">
      <?php if (empty($fechasExamen)) { ?>
    <p>No hay mesas de exámen de las materias del profesor.</p>
  <?php } else { ?>
      
      <div class="accordion" id="accordionExample">
  <?php
  $materias = array();
  foreach ($fechasExamen as $examen) {
    if (!isset($materias[$examen['nombreMateria']])) {
      $materias[$examen['nombreMateria']] = array();
    }
    $materias[$examen['nombreMateria']][] = $examen;
  }
  
  foreach ($materias as $nombreMateria => $examenes) {
  ?>
    <div class="accordion-item">
      <h2 class="accordion-header" id="heading-<?php echo $examenes[0]['idFechaExamen']; ?>">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $examenes[0]['idFechaExamen']; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $examenes[0]['idFechaExamen']; ?>">
          <?php echo $nombreMateria; ?>
        </button>
      </h2>
      <div id="collapse-<?php echo $examenes[0]['idFechaExamen']; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo $examenes[0]['idFechaExamen']; ?>" data-bs-parent="#accordionExample">
        <div class="accordion-body">
          <?php foreach ($examenes as $examen) { 
            $p = array(
              'idFechaExamen' => filter_var($examen['idFechaExamen'], FILTER_VALIDATE_INT),
              'nombreMateria' => filter_var($nombreMateria, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
              'Curso' => filter_var($examen['Curso'], FILTER_SANITIZE_FULL_SPECIAL_CHARS),
              'Fecha' => filter_var($examen['Fecha'], FILTER_SANITIZE_FULL_SPECIAL_CHARS),
              'Hora' => filter_var($examen['Hora'], FILTER_SANITIZE_FULL_SPECIAL_CHARS),
              'idMateria' => filter_var($examen['idMateria'], FILTER_VALIDATE_INT)
            );
            ?>
            <p>
  <a href="cargaActa.php?<?php echo http_build_query($p); ?>">
    <?php echo $examen['Fecha'].' - '.$examen['Hora'].' - '.$examen['Curso']; ?>
  </a>
</p>
          <?php } ?>
        </div>
      </div>
    </div>
  <?php
  }}
  ?>
</div>
    

</div>
  
        </div>
  </div>
  
      <script src="../funciones/sessionControl.js"></script>

    <!-- Bootstrap JS y jQuery (necesario para el modal) -->
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script> 
  
    <?php include '../funciones/footer.html'; ?>

    
  
  </body>
  
  </html>