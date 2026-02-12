<?php
/**
 * menualumnos.php
 * Interfaz principal para el alumno.
 * Arquitectura: Lógica de negocio separada en consultas.php.
 * Seguridad: Validación de sesión y fechas lado servidor.
 */

// 1. Control de Sesión y Dependencias
include '../funciones/verificarSesion.php'; // Asumo que tienes este archivo
include '../inicio/conexion.php';           // Variable $conn
require_once '../funciones/consultas.php';       // Lógica de base de datos

// Configuración de errores para entorno de desarrollo (Desactivar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Lógica de Negocio (Lado Servidor)
// Obtenemos los parámetros del colegio (Fechas, turnos, etc) una sola vez.
// Asumimos nivel 6 (Terciario) según tu contexto, o puedes usar $_SESSION['idnivel'] si existe.
$params = obtenerParametrosColegio($conn, 6); 

$fechaActual = date('Y-m-d');
$abiertoExam = false;
$abiertoCurs = false;

// Inicializamos variables de sesión para control en páginas destino
$_SESSION['soloLecturaExam'] = 0;
$_SESSION['cursSoloLectura'] = 0;

if ($params) {
    // --- Lógica Inscripción Exámenes ---
    if ($fechaActual >= $params['examenDesde'] && $fechaActual <= $params['examenHasta']) {
        $abiertoExam = true;
        
        // Verificar si es período de "Solo Lectura" (modificaciones deshabilitadas)
        if ($fechaActual >= $params['examenLectDesde'] && $fechaActual < $params['examenHasta']) {
            $_SESSION['soloLecturaExam'] = 1;
        }
    }

    // --- Lógica Inscripción Cursado ---
    if ($fechaActual >= $params['cursadoDesde'] && $fechaActual <= $params['cursadoHasta']) {
        $abiertoCurs = true;

        // Verificar si es período de "Solo Lectura"
        if ($fechaActual >= $params['cursadoLectDesde'] && $fechaActual < $params['cursadoHasta']) {
            $_SESSION['cursSoloLectura'] = 1;
        }
    }
}

// Cerramos conexión aquí si no se usará más abajo (buena práctica para liberar recursos)
// mysqli_close($conn); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Alumnos - SistemasEscolares</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css"> <link rel="stylesheet" href="estilos.css">
      <link rel="stylesheet" href="../css/material/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<nav class="navbar navbar-expand-lg" data-bs-theme="light">
    <div class="container">
      <div class="d-flex flex-column align-items-center centrarlogomenu">
        <img src="<?php echo htmlspecialchars($_SESSION['logo'] ?? '../img/logo_default.png'); ?>" class="est-logo img-fluid mx-auto" alt="logo">
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
            <a class="nav-link" href="#" onclick="onClickInscCurs()">Insc. Cursado</a>
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

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
  
<script> 
  /**
   * Lógica de Control de Acceso
   * Inyectamos las variables PHP directamente como booleanos nativos de JS.
   * Esto elimina el error de comparación de tipos ("1" vs true).
   */
  const inscripcionExamenAbierta = <?php echo $abiertoExam ? 'true' : 'false'; ?>;
  const inscripcionCursadoAbierta = <?php echo $abiertoCurs ? 'true' : 'false'; ?>;

  function onClickInscExam() {
    if (inscripcionExamenAbierta) {
      window.location.href = 'examenes_planes.php';
    } else {
      $('#mensajeModal').text("La inscripción a exámenes está cerrada. Los períodos de inscripción están definidos por secretaria.");
      $('#inscModal').modal('show');
    }
  }

  function onClickInscCurs() {
    if (inscripcionCursadoAbierta) {
      window.location.href = 'materias_planes.php';
    } else {
      $('#mensajeModal').text("La inscripción a materias está cerrada. Los períodos de inscripción están definidos por secretaria.");
      $('#inscModal').modal('show');
    }
  }
</script>

</body>
</html>