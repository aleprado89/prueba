<?php
/**
 * Blanqueo de contrasenas web (docentes y alumnos) por DNI.
 * Solo personal de secretaria autenticado.
 */
include '../funciones/verificarSesion.php';
include '../funciones/requerirSecretaria.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';
define('ID_FORMULARIO_SECRETARIA', 41);
require_once '../funciones/requerirPermisoFormulario.php';
include '../inicio/variablesParticulares.php';
include '../funciones/parametrosWeb.php';

if (empty($_SESSION['nombreColegio']) && !empty($datosColegio[0]['nombreColegio'])) {
    $_SESSION['nombreColegio'] = $datosColegio[0]['nombreColegio'];
} elseif (empty($_SESSION['nombreColegio'])) {
    $stmtN = $conn->prepare('SELECT nombreColegio FROM colegio WHERE codnivel = 6 LIMIT 1');
    if ($stmtN && $stmtN->execute()) {
        $r = $stmtN->get_result()->fetch_assoc();
        $_SESSION['nombreColegio'] = $r['nombreColegio'] ?? 'Institución';
        $stmtN->close();
    } else {
        $_SESSION['nombreColegio'] = 'Institución';
    }
}

$dniPost = '';
$mensaje = '';
$tipoMensaje = 'info';
$resultadoBusqueda = null;
$claveDocenteDefecto = (string) ($_SESSION['claveDocente'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'buscar') {
        $dniPost = trim((string) ($_POST['dni'] ?? ''));
        if ($dniPost === '') {
            $mensaje = 'Ingrese un DNI.';
            $tipoMensaje = 'warning';
        } else {
            $alu = obtenerAlumnoWebPasswordPorDni($conn, $dniPost);
            if ($alu !== null) {
                $dniAlu = (string) $alu['dni'];
                $resultadoBusqueda = [
                    'rol' => 'alumno',
                    'idAlumno' => (int) $alu['idAlumno'],
                    'apellido' => $alu['apellido'],
                    'nombre' => $alu['nombre'],
                    'dni' => $dniAlu,
                    'clave_quedara' => $dniAlu,
                ];
            } else {
                $doc = obtenerDocenteWebPasswordPorDni($conn, $dniPost);
                if ($doc !== null) {
                    $resultadoBusqueda = [
                        'rol' => 'docente',
                        'legajo' => (int) $doc['legajo'],
                        'apellido' => $doc['apellido'],
                        'nombre' => $doc['nombre'],
                        'dni' => (string) $doc['dni'],
                        'clave_quedara' => $claveDocenteDefecto,
                    ];
                } else {
                    $mensaje = 'No se encontro alumno ni docente terciario con ese DNI.';
                    $tipoMensaje = 'warning';
                }
            }
        }
    } elseif ($accion === 'blanquear') {
        $rol = $_POST['rol'] ?? '';
        $dniPost = trim((string) ($_POST['dni'] ?? ''));
        $usuarioLog = (string) ($_SESSION['sec_nombreUsuario'] ?? 'sec');

        if ($rol === 'alumno') {
            $idAlumno = (int) ($_POST['id_alumno'] ?? 0);
            if ($idAlumno <= 0 || $dniPost === '') {
                $mensaje = 'Datos invalidos para blanquear alumno.';
                $tipoMensaje = 'danger';
            } else {
                $alu = obtenerAlumnoWebPasswordPorDni($conn, $dniPost);
                if ($alu === null || (int) $alu['idAlumno'] !== $idAlumno || (string) $alu['dni'] !== $dniPost) {
                    $mensaje = 'No coincide el alumno con el DNI indicado. Vuelva a buscar.';
                    $tipoMensaje = 'danger';
                } elseif (blanquearClaveAlumno($conn, $idAlumno, $dniPost)) {
                    registrarLogBlanqueo($conn, $idAlumno, $usuarioLog, 'Blanqueo clave web alumno');
                    $mensaje = 'Clave blanqueada. El alumno debe iniciar sesión con el DNI como contraseña: <strong>' . htmlspecialchars($dniPost, ENT_QUOTES, 'UTF-8') . '</strong>.';
                    $tipoMensaje = 'success';
                } else {
                    $mensaje = 'No se pudo blanquear la clave del alumno.';
                    $tipoMensaje = 'danger';
                }
            }
        } elseif ($rol === 'docente') {
            $legajo = (int) ($_POST['legajo'] ?? 0);
            if ($legajo <= 0 || $claveDocenteDefecto === '') {
                $mensaje = 'No está configurada la contraseña por defecto para docentes. Revise la configuración del instituto o datos inválidos.';
                $tipoMensaje = 'danger';
            } else {
                $doc = obtenerDocenteWebPasswordPorDni($conn, $dniPost);
                if ($doc === null || (int) $doc['legajo'] !== $legajo || (string) $doc['dni'] !== $dniPost) {
                    $mensaje = 'No coincide el docente con el DNI indicado. Vuelva a buscar.';
                    $tipoMensaje = 'danger';
                } elseif (blanquearClaveDocente($conn, $legajo, $claveDocenteDefecto)) {
                    registrarLogBlanqueo($conn, null, $usuarioLog, 'Blanqueo clave web doc L' . $legajo);
                    $mensaje = 'Clave blanqueada. El docente debe iniciar sesión con la contraseña por defecto del instituto: <strong>' . htmlspecialchars($claveDocenteDefecto, ENT_QUOTES, 'UTF-8') . '</strong>.';
                    $tipoMensaje = 'success';
                } else {
                    $mensaje = 'No se pudo blanquear la clave del docente.';
                    $tipoMensaje = 'danger';
                }
            }
        } else {
            $mensaje = 'Accion no reconocida.';
            $tipoMensaje = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blanqueo de claves de acceso</title>
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="icon" type="image/png" href="../img/icon.png">
</head>
<body>
<?php include '../funciones/menu_secretaria.php'; ?>

<div class="container-fluid fondo pt-4">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Blanqueo de claves</li>
      </ol>
    </nav>

    <div class="card shadow-sm">
      <div class="card-header card-header-custom">
        <h5 class="mb-0"><i class="bi bi-key-fill me-2"></i>Blanqueo de claves (alumnos y docentes)</h5>
      </div>
      <div class="card-body">
        <p class="text-muted small">Ingrese el DNI y pulse Buscar. Si existe como alumno o como docente, verá los datos y qué contraseña quedará tras el blanqueo.</p>
        <?php if ($mensaje !== ''): ?>
          <div class="alert alert-<?php echo htmlspecialchars($tipoMensaje, ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($tipoMensaje === 'success'): ?>
              <?php echo $mensaje; ?>
            <?php else: ?>
              <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" class="mb-4">
          <input type="hidden" name="accion" value="buscar">
          <div class="mb-3">
            <label for="dni" class="form-label">DNI</label>
            <input type="text" class="form-control" id="dni" name="dni" maxlength="20"
                   value="<?php echo htmlspecialchars($dniPost, ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Buscar</button>
        </form>

        <?php if ($resultadoBusqueda !== null): ?>
          <div class="card border-secondary">
            <div class="card-body">
              <h6 class="card-title mb-3">Persona encontrada</h6>
              <p class="mb-1"><strong><?php echo htmlspecialchars($resultadoBusqueda['apellido'] . ', ' . $resultadoBusqueda['nombre'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
              <p class="mb-1">DNI: <?php echo htmlspecialchars((string) $resultadoBusqueda['dni'], ENT_QUOTES, 'UTF-8'); ?></p>
              <p class="mb-1">Rol: <?php echo $resultadoBusqueda['rol'] === 'alumno' ? 'Alumno' : 'Docente'; ?></p>
              <?php if ($resultadoBusqueda['rol'] === 'docente'): ?>
                <p class="mb-1">Legajo: <?php echo (int) $resultadoBusqueda['legajo']; ?></p>
              <?php endif; ?>

              <div class="alert alert-light border mt-3 mb-3">
                <p class="mb-0 small mb-1"><strong>Tras blanquear</strong>, en el portal web deberá iniciar sesión con:</p>
                <?php if ($resultadoBusqueda['rol'] === 'alumno'): ?>
                  <p class="mb-0"><span class="text-muted">Usuario:</span> DNI · <span class="text-muted">Contraseña:</span> <strong><?php echo htmlspecialchars((string) $resultadoBusqueda['clave_quedara'], ENT_QUOTES, 'UTF-8'); ?></strong> (el mismo DNI)</p>
                <?php elseif ($resultadoBusqueda['clave_quedara'] !== ''): ?>
                  <p class="mb-0"><span class="text-muted">Usuario:</span> DNI · <span class="text-muted">Contraseña:</span> <strong><?php echo htmlspecialchars((string) $resultadoBusqueda['clave_quedara'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                <?php else: ?>
                  <p class="mb-0 text-warning">No hay contraseña por defecto de docentes configurada; no podrá blanquear hasta que esté definida.</p>
                <?php endif; ?>
              </div>

              <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalBlanqueo"
                <?php echo ($resultadoBusqueda['rol'] === 'docente' && $resultadoBusqueda['clave_quedara'] === '') ? 'disabled' : ''; ?>>
                <i class="bi bi-arrow-counterclockwise me-1"></i>Blanquear clave
              </button>

              <div class="modal fade" id="modalBlanqueo" tabindex="-1" aria-labelledby="modalBlanqueoLabel" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalBlanqueoLabel">Confirmar blanqueo</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                      <?php if ($resultadoBusqueda['rol'] === 'alumno'): ?>
                        <p>Quedará habilitado para ingresar al portal con el <strong>DNI</strong> como usuario y contraseña.</p>
                      <?php else: ?>
                        <p>Quedará habilitado para ingresar al portal con el <strong>DNI</strong> como usuario y la <strong>contraseña por defecto de docentes</strong> del instituto.</p>
                      <?php endif; ?>
                      <p class="mb-0 text-danger small">La contraseña que usaba hasta ahora dejará de ser válida.</p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                      <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="d-inline">
                        <input type="hidden" name="accion" value="blanquear">
                        <input type="hidden" name="rol" value="<?php echo htmlspecialchars($resultadoBusqueda['rol'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="dni" value="<?php echo htmlspecialchars((string) $resultadoBusqueda['dni'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($resultadoBusqueda['rol'] === 'alumno'): ?>
                          <input type="hidden" name="id_alumno" value="<?php echo (int) $resultadoBusqueda['idAlumno']; ?>">
                        <?php else: ?>
                          <input type="hidden" name="legajo" value="<?php echo (int) $resultadoBusqueda['legajo']; ?>">
                        <?php endif; ?>
                        <button type="submit" class="btn btn-warning">Confirmar blanqueo</button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="../js/jquery-3.7.1.js"></script>
<script src="../js/bootstrap.bundle.js"></script>
<?php include '../funciones/footer.html'; ?>
</body>
</html>
