<?php
/**
 * Fragmento de navegacion para el area de alumnos (navbar + modal + scripts).
 * Incluir dentro de <body> despues de abrir la etiqueta. No incluir DOCTYPE ni <html>.
 * Requiere sesion de alumno activa (la pagina llamante debe incluir verificarSesion.php antes).
 */
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../inicio/conexion.php';
}
require_once __DIR__ . '/consultas.php';

$fechaActual = date('Y-m-d');
$abiertoExam = false;
$abiertoCurs = false;
$_SESSION['soloLecturaExam'] = $_SESSION['soloLecturaExam'] ?? 0;
$_SESSION['cursSoloLectura'] = $_SESSION['cursSoloLectura'] ?? 0;

$params = obtenerParametrosColegio($conn, 6);
if ($params) {
    if ($fechaActual >= $params['examenDesde'] && $fechaActual <= $params['examenHasta']) {
        $abiertoExam = true;
        if ($fechaActual >= $params['examenLectDesde'] && $fechaActual < $params['examenHasta']) {
            $_SESSION['soloLecturaExam'] = 1;
        }
    }
    if ($fechaActual >= $params['cursadoDesde'] && $fechaActual <= $params['cursadoHasta']) {
        $abiertoCurs = true;
        if ($fechaActual >= $params['cursadoLectDesde'] && $fechaActual < $params['cursadoHasta']) {
            $_SESSION['cursSoloLectura'] = 1;
        }
    }
}
?>
<?php if (!empty($_SESSION['impersonando'])):
    $lblImp = isset($_SESSION['impersonacion_label']) ? (string) $_SESSION['impersonacion_label'] : 'Usuario';
    ?>
<div class="alert alert-warning text-center m-0 rounded-0 py-2 mb-0 border-0">
  <i class="bi bi-eye-fill"></i>
  Vista desde Secretaría como <strong><?php echo htmlspecialchars($lblImp, ENT_QUOTES, 'UTF-8'); ?></strong>
  &nbsp;·&nbsp;
  <a href="../secretaria/finalizarImpersonacion.php" class="alert-link fw-semibold">
    <i class="bi bi-box-arrow-left"></i> Volver a Secretaría
  </a>
</div>
<?php endif; ?>
<nav class="navbar navbar-expand-lg" data-bs-theme="light">
    <div class="container">
      <div class="d-flex flex-column align-items-center centrarlogomenu">
        <img src="<?php echo htmlspecialchars($_SESSION['logo'] ?? '../img/icon.png', ENT_QUOTES, 'UTF-8'); ?>" class="est-logo img-fluid mx-auto" alt="logo">
      </div>

      <button class="navbar-toggler margenbottom ms-auto" type="button" data-bs-toggle="collapse"
        data-bs-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false"
        aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarColor01">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <a class="nav-link" href="menualumnos.php">Inicio</a>
          </li>
          <li class="nav-item active">
            <a class="nav-link" href="calificaciones_planes.php">Calificaciones
              <span class="visually-hidden">(current)</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#" onclick="onClickInscCurs(); return false;">Insc. Cursado</a>
          </li>
          <li class="nav-item">
             <a class="nav-link" href="#" onclick="onClickInscExam(); return false;">Insc. Examen</a>
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
              onmouseover="this.style.color='#2e8b97'" onmouseleave="this.style.color='#646261'">
              <i class="bi bi-power"></i> Cerrar Sesión
            </a>
          </li>
        </ul>
      </div>
    </div>
</nav>

<div class="modal fade" id="inscModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Aviso de Inscripción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

<script src="../js/jquery-3.7.1.js"></script>
<script src="../js/bootstrap.bundle.js"></script>
<script>
  const inscripcionExamenAbierta = <?php echo $abiertoExam ? 'true' : 'false'; ?>;
  const inscripcionCursadoAbierta = <?php echo $abiertoCurs ? 'true' : 'false'; ?>;

  function onClickInscExam() {
    if (inscripcionExamenAbierta) {
      window.location.href = 'examenes_planes.php';
    } else {
      document.getElementById('mensajeModal').textContent = 'La inscripción a exámenes está cerrada. Los períodos de inscripción están definidos por secretaría.';
      if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        new bootstrap.Modal(document.getElementById('inscModal')).show();
      }
    }
  }

  function onClickInscCurs() {
    if (inscripcionCursadoAbierta) {
      window.location.href = 'materias_planes.php';
    } else {
      document.getElementById('mensajeModal').textContent = 'La inscripción a materias está cerrada. Los períodos de inscripción están definidos por secretaría.';
      if (typeof bootstrap.Modal !== 'undefined' && bootstrap.Modal) {
        new bootstrap.Modal(document.getElementById('inscModal')).show();
      }
    }
  }
</script>
