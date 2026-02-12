<?php
session_start();

include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
include '../funciones/verificarSesion.php';

$doc_legajo = $_SESSION['doc_legajo'];
$nombreDoc = $_SESSION['doc_apellido'].", ".$_SESSION['doc_nombre'];

$CicloLectivo = $datosColegio[0]['anioCargaNotas'];
$idturno = $datosColegio[0]['cargaActaVolTurno'];
$idCicloLectivo = buscarIdCiclo($conn, $CicloLectivo);

$planes = buscarPlanesProfesorMateria($conn, $doc_legajo);
$primerPlan = $planes[0]['idPlan'];
$idPlan = $primerPlan;

$fechasExamen = obtenerFechasExamenProfesor($conn, $_SESSION['doc_idPersona'], $idCicloLectivo, $idturno, $idPlan);

// Función para renderizar acordeones
function generarAcordeonesHTML($fechasExamen) {
    if (empty($fechasExamen)) {
        return '<p>No hay mesas de examen de las materias del profesor.</p>';
    }

    $materias = array();
    foreach ($fechasExamen as $examen) {
        if (!isset($materias[$examen['nombreMateria']])) {
            $materias[$examen['nombreMateria']] = array();
        }
        $materias[$examen['nombreMateria']][] = $examen;
    }

    $html = '<div class="accordion" id="accordionExample">';
    $index = 0;

    foreach ($materias as $nombreMateria => $examenes) {
        $html .= '<div class="accordion-item">';
        $html .= '<h2 class="accordion-header" id="heading-' . $index . '">';
        $html .= '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-' . $index . '" aria-expanded="false" aria-controls="collapse-' . $index . '">';
        $html .= htmlspecialchars($nombreMateria);
        $html .= '</button>';
        $html .= '</h2>';
        $html .= '<div id="collapse-' . $index . '" class="accordion-collapse collapse" aria-labelledby="heading-' . $index . '" data-bs-parent="#accordionExample">';
        $html .= '<div class="accordion-body">';

        foreach ($examenes as $examen) {
            $p = array(
                'idFechaExamen' => filter_var($examen['idFechaExamen'], FILTER_VALIDATE_INT),
                'nombreMateria' => filter_var($nombreMateria, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'Curso' => filter_var($examen['Curso'], FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'Fecha' => filter_var($examen['Fecha'], FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'Hora' => filter_var($examen['Hora'], FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'idMateria' => filter_var($examen['idMateria'], FILTER_VALIDATE_INT)
            );

            $html .= '<p><a href="cargaActa.php?' . http_build_query($p) . '">'
                   . htmlspecialchars($examen['Fecha'] . ' - ' . $examen['Hora'] . ' - ' . $examen['Curso'])
                   . '</a></p>';
        }

        $html .= '</div></div></div>';
        $index++;
    }

    $html .= '</div>';
    return $html;
}

// Manejo de petición AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizarAcordeones'])) {
    $idPlan = $_POST['idPlan'];
    $idCicloLectivo = buscarIdCiclo($conn, $CicloLectivo);

    $fechasExamen = obtenerFechasExamenProfesor($conn, $_SESSION['doc_idPersona'], $idCicloLectivo, $idturno, $idPlan);
    echo generarAcordeonesHTML($fechasExamen);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mesas de exámenes</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- jQuery primero -->
  <script src="../js/jquery-3.7.1.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.bundle.min.js"></script>
<link rel="icon" type="image/png" href="../img/icon.png">

</head>
<body>

<?php include '../funciones/menu_docente.php'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="menudocentes.php">Inicio</a></li>
      <li class="breadcrumb-item active">Mesas de exámenes</li>
    </ol>

    <div class="card padding col-12">
      <h5><?php echo "Docente: " . $nombreDoc; ?></h5>
      <h5><label for="ciclolectivo">Ciclo lectivo: <?php echo $CicloLectivo; ?></label></h5>
      <br>

      <select name="plan" class="form-select" id="plan" onchange="cargarValor(this.value)">
        <?php
        foreach ($planes as $plan) {
          echo '<option value="' . $plan['idPlan'] . '">' . htmlspecialchars($plan['nombrePlan']) . '</option>';
        }
        ?>
      </select>
    </div>

    <br>
    <div class="container" id="contenedorAcordeon">
      <?php echo generarAcordeonesHTML($fechasExamen); ?>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  $('.collapse').on('show.bs.collapse', function () {
    var $opened = $(this).closest('.accordion').find('.collapse.show');
    if ($opened.length) {
        $opened.collapse('hide');
    }
  });
});

function cargarValor(idPlan) {
  $.ajax({
    type: 'POST',
    url: 'mesasExamenProf.php',
    data: { idPlan: idPlan, actualizarAcordeones: true },
    dataType: 'html',
    success: function(data) {
      $('#contenedorAcordeon').html(data);
    },
    error: function(xhr, status, error) {
      console.error('Error en AJAX:', status, error);
    }
  });
}
</script>

<script src="../funciones/sessionControl.js"></script>
<?php include '../funciones/footer.html'; ?>

</body>
</html>
