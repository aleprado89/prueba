<!DOCTYPE html>
<html lang="es">

<?php
session_start(); 
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
include '../funciones/verificarSesion.php';

// --- SANITIZACIÓN Y OBTENCIÓN DE VARIABLES PRINCIPALES ---
$cicloLectivo = $datosColegio[0]['anioautoweb'];
$idCicloLectivo = buscarIdCiclo($conn, $cicloLectivo);
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = htmlspecialchars($_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'], ENT_QUOTES, 'UTF-8');
$idPlan = $_SESSION['idP'];
$nombrePlan = htmlspecialchars($_SESSION['nombreP'], ENT_QUOTES, 'UTF-8');

// Capturamos de POST o de GET (El GET es necesario para cuando hagamos la redirección PRG)
$idMateria = $_POST['idMateria'] ?? $_GET['idMateria'] ?? '';
$nombreMateriaRaw = $_POST['nombreMateria'] ?? $_GET['nombreMateria'] ?? '';
$nombreCursoRaw = $_POST['nombreCurso'] ?? $_GET['nombreCurso'] ?? '';

// Variables sanitizadas exclusivamente para imprimir en el HTML
$nombreMateria = htmlspecialchars($nombreMateriaRaw, ENT_QUOTES, 'UTF-8');
$nombreCurso = htmlspecialchars($nombreCursoRaw, ENT_QUOTES, 'UTF-8');

// --- PROCESAMIENTO DEL FORMULARIO POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // BOTON SOLICITAR
    if(isset($_POST['solicitarSubmit']) && !empty($_POST['idMateria']) && !empty($_POST['condicionCursado'])) {
        $idMateriaPost = $_POST['idMateria'];
        $condicionSeleccionada = htmlspecialchars($_POST['condicionCursado'], ENT_QUOTES, 'UTF-8');
        
        // 1. SEGURIDAD BACKEND: Comprobamos si ya existe la solicitud antes de insertar (Evita doble clic)
        $solicitudesPrevias = existeSolicitudMateria($conn, $idAlumno, $idMateriaPost, $idCicloLectivo);
        $yaExiste = false;
        
        if (is_array($solicitudesPrevias)) {
            foreach ($solicitudesPrevias as $sol) {
                if ($sol['Estado'] == "Pendiente" || $sol['Estado'] == "Aprobada") {
                    $yaExiste = true;
                    break;
                }
            }
        }
        
        // Solo insertamos si realmente no existe
        if (!$yaExiste) {
            solicitarCursado($conn, $idAlumno, $idMateriaPost, $idCicloLectivo, $condicionSeleccionada);
        }

        // 2. PATRÓN PRG: Redirigimos por GET para limpiar el historial de peticiones y evitar el F5
        $redirectUrl = "materias_solicitar.php?idMateria=" . urlencode($idMateriaPost) . 
                       "&nombreMateria=" . urlencode($nombreMateriaRaw) . 
                       "&nombreCurso=" . urlencode($nombreCursoRaw);
        header("Location: " . $redirectUrl);
        exit();
    }

    // BOTON CANCELAR
    if (isset($_POST['idMatriculacionWeb'])) {
        $idMatriculacionWeb = $_POST["idMatriculacionWeb"];    
        cancelarCursado($conn, $idMatriculacionWeb);

        // PATRÓN PRG también para la cancelación
        $redirectUrl = "materias_solicitar.php?idMateria=" . urlencode($idMateria) . 
                       "&nombreMateria=" . urlencode($nombreMateriaRaw) . 
                       "&nombreCurso=" . urlencode($nombreCursoRaw);
        header("Location: " . $redirectUrl);
        exit();
    }
}


// --- LÓGICA DE DATOS PARA LA VISTA ---

// 1. OBTENER Y FILTRAR CONDICIONES DE CURSADO
$condicionesCrudas = obtenerCondicionesCursado($conn);
$condicionesValidas = [];

// Excluimos explícitamente las condiciones que el alumno no debe poder seleccionar
$condicionesExcluidas = ['condicional', 'abandonó cursado', 'abandono cursado'];

foreach ($condicionesCrudas as $condicion) {
    // Normalizamos el string protegiendo la codificación UTF-8 (tildes)
    $nombreCondicionNormalizado = trim(mb_strtolower($condicion['condicion'], 'UTF-8'));
    
    if (!in_array($nombreCondicionNormalizado, $condicionesExcluidas)) {
        $condicionesValidas[] = $condicion;
    }
}

// 2. LISTAR SOLICITUDES Y VERIFICAR HABILITACIÓN
$listadoSolicitudes = existeSolicitudMateria($conn, $idAlumno, $idMateria, $idCicloLectivo);
$cantidadSolicitudes = is_array($listadoSolicitudes) ? count($listadoSolicitudes) : 0;

$habilitado = true;

// Solo evaluamos inhabilitar si realmente hay solicitudes y tenemos un ID de materia válido
if ($cantidadSolicitudes > 0 && !empty($idMateria)) {
    foreach ($listadoSolicitudes as $solicitud) {
        if (isset($solicitud['Estado'])) {
            $Estado = $solicitud['Estado'];
            if ($Estado == "Pendiente" || $Estado == "Aprobada") {
                $habilitado = false;
                break; // Optimizamos saliendo del bucle
            }
        }
    }
}
?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitar Inscripción</title>
  
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <script src="../js/bootstrap.min.js"></script> 
  <link rel="icon" type="image/png" href="../img/icon.png">
</head>

<body>

<?php include '../funciones/menu.php'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="menualumnos.php">Inicio</a></li>
      <li class="breadcrumb-item"><a href="materias_planes.php">Inscripción a cursado</a></li>
      <li class="breadcrumb-item"><a href="materias_materias.php">Materias</a></li>
      <li class="breadcrumb-item active">Solicitar cursado</li>
    </ol>

    <div class="card padding col-12 margenbottom">
      <h5><?php echo $nombreAlumno; ?></h5>
      <h5>Materia: <?php echo $nombreMateria; ?></h5>
      <h5>Curso: <?php echo $nombreCurso; ?></h5>

      <div class="row col-12 mt-4">
        
        <form id="formSolicitar" action="../alumnos/materias_solicitar.php" method="POST">      
          <div class="col-12 col-md-6">
            
            <input type="hidden" name="idMateria" value="<?php echo $idMateria; ?>" />
            <input type="hidden" name="nombreMateria" value="<?php echo $nombreMateria; ?>" />
            <input type="hidden" name="nombreCurso" value="<?php echo $nombreCurso; ?>" />
            <input type="hidden" name="solicitarSubmit" value="1" />

            <?php if ($habilitado == true): ?>
              
              <div class="form-group mb-3">
                <label for="condicionCursado" class="form-label">Condición de Cursado <span class="text-danger">*</span></label>
                <select name="condicionCursado" id="condicionCursado" class="form-select" required>
                  <option value="" disabled selected>Seleccione una condición...</option>
                  <?php foreach ($condicionesValidas as $cond): ?>
                    <option value="<?php echo htmlspecialchars($cond['condicion'], ENT_QUOTES, 'UTF-8'); ?>">
                      <?php echo htmlspecialchars($cond['condicion'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Por favor, seleccione una condición válida.</div>
              </div>

              <button type="button" id="btnSolicitar" onclick="abrirModal()" class="btn btn-primary">Solicitar</button>
            <?php else: ?>
              <div class="alert alert-success">Tienes una solicitud generada para esta materia.</div>
            <?php endif; ?>        
          </div>
        </form>

      </div>
    </div>

    <div class="container mt-5">
      <form id="cancelar" action="../alumnos/materias_solicitar.php" method="post">
        <input type="hidden" name="idMatriculacionWeb" id="idMatriculacionWeb">
        <input type="hidden" name="idMateria" value="<?php echo $idMateria; ?>" />
        <input type="hidden" name="nombreMateria" value="<?php echo $nombreMateria; ?>" />
        <input type="hidden" name="nombreCurso" value="<?php echo $nombreCurso; ?>" />

        <caption>Solicitudes Generadas</caption>
        <div class="table-responsive">
          <table class="table table-hover">        
            <thead>
              <tr class="table-primary">
                <th scope="col" style="display:none;">idMatriculacionWeb</th>
                <th scope="col">Materia</th>
                <th scope="col">Estado</th>
                <th scope="col">Observaciones</th>
                <th scope="col"></th>
              </tr>
            </thead>
            <tbody>
              <?php
              // RECORRER TABLA DE SOLICITUDES        
              if ($cantidadSolicitudes > 0) {
                  for ($a = 0; $a < $cantidadSolicitudes; $a++) {
                      // El uso de ?? '' previene el error deprecado "Passing null to parameter #1"
                      $idMatriculacionWebRow = $listadoSolicitudes[$a]['idMatriculacionWeb'] ?? '';
                      $MateriaRow = htmlspecialchars($listadoSolicitudes[$a]['Materia'] ?? '', ENT_QUOTES, 'UTF-8');
                      $EstadoRow = htmlspecialchars($listadoSolicitudes[$a]['Estado'] ?? '', ENT_QUOTES, 'UTF-8');
                      $ObservacionesRow = htmlspecialchars($listadoSolicitudes[$a]['Observaciones'] ?? '', ENT_QUOTES, 'UTF-8');
                      
                      // Prevenimos imprimir filas vacías en caso de fallos de la base de datos
                      if(empty($idMatriculacionWebRow)) continue;
                      ?>
                      <tr>
                        <td style="display:none;"><?php echo $idMatriculacionWebRow; ?></td>
                        <td><?php echo $MateriaRow; ?></td>
                        <td><?php echo $EstadoRow; ?></td>
                        <td><?php echo $ObservacionesRow; ?></td>
                        <td>
                          <?php if ($EstadoRow == "Pendiente"): ?>
                            <button type="submit" class="btn btn-danger cancelar-btn">Cancelar</button>
                          <?php endif; ?>
                        </td>
                      </tr>
                      <?php
                  }
              } 
              
              if ($cantidadSolicitudes == 0 || !isset($idMatriculacionWebRow)) {
                  ?>
                  <tr>
                    <td colspan="4" class="text-center">No hay registros</td>
                  </tr>
                  <?php
              }
              ?>
            </tbody>
          </table>
        </div>
      </form>
    </div>

  </div> </div> <div class="modal fade" id="confirmarSolicitudModal" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar Solicitud</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>¿Estás seguro de que deseas confirmar la solicitud bajo la condición seleccionada?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="confirmarSolicitud()">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="confirmarCancelacionModal" tabindex="-1" aria-labelledby="cancelarModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cancelarModalLabel">Confirmar Cancelación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        ¿Estás seguro de que querés cancelar la solicitud de esta materia?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        <button type="button" class="btn btn-danger" id="btnConfirmarCancelacion">Sí, Cancelar</button>
      </div>
    </div>
  </div>
</div>

<script src="../funciones/sessionControl.js"></script>
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const botones = document.querySelectorAll('.cancelar-btn');
  const form = document.getElementById("cancelar");
  const inputId = form.querySelector("#idMatriculacionWeb");
  let idMatriculacionTmp = null;

  botones.forEach(boton => {
    boton.addEventListener('click', function (e) {
      e.preventDefault();
      const fila = this.closest('tr');
      idMatriculacionTmp = fila.querySelector("td:nth-child(1)").innerText.trim();
      const modal = new bootstrap.Modal(document.getElementById('confirmarCancelacionModal'));
      modal.show();
    });
  });

  document.getElementById("btnConfirmarCancelacion").addEventListener("click", function () {
    if (idMatriculacionTmp) {
      inputId.value = idMatriculacionTmp;
      form.requestSubmit(); 
    }
  });
});
</script>

<script>
  // Control riguroso del Front-End: Aseguramos que la UI respete el requerimiento de la DB
  function abrirModal() {
    const form = document.getElementById('formSolicitar');
    
    if (form.reportValidity()) {
        $('#confirmarSolicitudModal').modal('show');
    }
  }

  function confirmarSolicitud() {
    document.getElementById('formSolicitar').submit();
  }
</script>

<?php include '../funciones/footer.html'; ?>

</body>
</html>