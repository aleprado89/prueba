<?php   //session_start();
?>
<nav class="navbar navbar-expand-lg " data-bs-theme="light">
    <div class="container ">
      <div class="d-flex flex-column align-items-center centrarlogomenu ">
        <img src="<?php echo $_SESSION['logo']; ?>" class="est-logo img-fluid mx-auto" alt="logo">
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
            <a class="nav-link" href="#" onclick="onClickInscCurs()">Insc.Cursado</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#" onclick="onClickInscExam()">Insc. Examen</a>
          </li>
          <li class="nav-item active">
            <a class="nav-link" href="actuaDatosAlu.php">Datos Personales
              <span class="visually-hidden">(current)</span>
            </a>
          </li>
        </ul>
        <ul class="ms-auto" style="list-style-type: none;">
          <li class="nav-item">
            <a class="nav-link" href="../funciones/cerrarsesion.php" style="display: flex; flex-direction: column; align-items: center;"
              onmouseover="this.style.color='#2e8b97'" onmouseleave="this.style.color='#646261'"><i
                class="bi bi-power"></i>Cerrar Sesión</a>
          </li>

        </ul>
      </div>
    </div>
  </nav>

   <!-- Modal -->
<div class="modal" id="inscModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Inscripciones Cerradas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true"></span>
        </button>
      </div>
      <div class="modal-body">
      <p id="mensajeModal"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php
//obtengo fechas desde hasta de inscripcion exames
function verificarInscExam() {
  $fechaActual = date('Y-m-d');
  include '../inicio/conexion.php';
  $sql = "SELECT inscExamDesde,inscExamHasta,inscExamLectDesde FROM colegio WHERE inscExamDesde <= '$fechaActual' AND inscExamHasta >= '$fechaActual'";
  $resultado = $conn->query($sql);
  if ($resultado->num_rows > 0) {
    $abierto=true;
      $fila = $resultado->fetch_assoc();
      $inscExamLectDesde = $fila['inscExamLectDesde'];
      $inscExamHasta = $fila['inscExamHasta'];
      if ($fechaActual >= $inscExamLectDesde && $fechaActual < $inscExamHasta) {
        $_SESSION['soloLecturaExam'] = 1;
      } else {
        $_SESSION['soloLecturaExam'] = 0;
      }
  } else {
    $_SESSION['soloLecturaExam'] = 0;
    $abierto=false;
  }
  mysqli_close($conn);
  return $abierto;
}

//obtengo fechas desde hasta de inscripcion a cursado
function verificarInscCurs() {
  $fechaActual = date('Y-m-d');
  include '../inicio/conexion.php';
  $sql = "SELECT inscCursDesde,inscCursHasta,inscCursLectDesde FROM colegio WHERE inscCursDesde <= '$fechaActual' AND inscCursHasta >= '$fechaActual'";
  $resultado = $conn->query($sql);
  if ($resultado->num_rows > 0) {
    $abierto=true;
      $fila = $resultado->fetch_assoc();
      $inscCursLectDesde = $fila['inscCursLectDesde'];
      $inscCursHasta = $fila['inscCursHasta'];
      if ($fechaActual >= $inscCursLectDesde && $fechaActual < $inscCursHasta) {
        $_SESSION['cursSoloLectura'] = 1;
      } else {
        $_SESSION['cursSoloLectura'] = 0;
      }
  } else {
    $_SESSION['cursSoloLectura'] = 0;
    $abierto=false;
  }
  mysqli_close($conn);
  return $abierto;
}
?>

  <!--           FUNCIONES     y SCRIPTS        -->

<!-- Bootstrap JS y jQuery (necesario para el modal) -->
<script src="../js/jquery-3.7.1.min.js"></script>
 <script src="../js/bootstrap.min.js"></script> 
 <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
  
  <script> 
   function onClickInscExam() {
    var abierto = '<?php echo verificarInscExam(); ?>';
    if (abierto==true) {
      window.location.href = 'examenes_planes.php';
    } else {
      $('#inscModal').modal('show');
      $('#mensajeModal').text("La inscripción a exámenes está cerrada. Los períodos de inscripción están definidos por secretaria.");
    }
  }

  function onClickInscCurs() {
    var abierto = '<?php echo verificarInscCurs(); ?>';
    if (abierto == true) {
      window.location.href = 'materias_planes.php';
    } else {
      $('#inscModal').modal('show');
      $('#mensajeModal').text("La inscripción a materias está cerrada. Los períodos de inscripción están definidos por secretaria.");
    }
  }

</script>