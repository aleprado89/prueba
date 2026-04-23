<?php
/**
 * Consulta de contrasenas de acceso web (docentes y alumnos) por DNI.
 * Solo personal de secretaria autenticado.
 */
include '../funciones/verificarSesion.php';
include '../funciones/requerirSecretaria.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';
define('ID_FORMULARIO_SECRETARIA', 41);
require_once '../funciones/requerirPermisoFormulario.php';
include '../funciones/password_web.php';
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

$clave = '';
$dniPost = '';
$mensaje = '';
$claveEsHash = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dni'])) {
    $dniPost = trim((string) $_POST['dni']);
    if ($dniPost === '') {
        $mensaje = 'Ingrese un DNI.';
    } else {
        $claveRaw = '';
        $sqlAlu = 'SELECT pass.password FROM passwords_alumnos pass
            INNER JOIN alumnosterciario a ON pass.idAlumno = a.idAlumno
            INNER JOIN persona p ON a.idPersona = p.idPersona
            WHERE p.dni = ? LIMIT 1';
        $stmt = $conn->prepare($sqlAlu);
        if ($stmt) {
            $stmt->bind_param('s', $dniPost);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                $claveRaw = (string) $row['password'];
            }
            $stmt->close();
        }
        if ($claveRaw === '') {
            $sqlDoc = 'SELECT pass.password FROM passwords pass
                INNER JOIN personal ON pass.legajo = personal.legajo
                INNER JOIN persona ON personal.idPersona = persona.idPersona
                WHERE persona.dni = ? LIMIT 1';
            $stmt2 = $conn->prepare($sqlDoc);
            if ($stmt2) {
                $stmt2->bind_param('s', $dniPost);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                if ($res2 && $row2 = $res2->fetch_assoc()) {
                    $claveRaw = (string) $row2['password'];
                }
                $stmt2->close();
            }
        }
        if ($claveRaw === '') {
            $mensaje = 'No se encontro clave de alumno ni docente para ese DNI.';
        } elseif (password_web_is_hashed($claveRaw)) {
            $claveEsHash = true;
            $clave = '';
            $mensaje = 'La contrasena esta guardada de forma segura (hash). No se puede mostrar el texto. Indique al usuario que use Cambiar clave en el portal o la recuperacion por correo.';
        } else {
            $clave = $claveRaw;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Consultar claves de acceso</title>
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="icon" type="image/png" href="../img/icon.png">
</head>
<body>
<?php include '../funciones/menu_secretaria.php'; ?>

<div class="container-fluid fondo">
  <div class="container mt-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Consultar claves</li>
      </ol>
    </nav>

    <div class="card shadow-sm">
      <div class="card-header card-header-custom">
        <h5 class="mb-0"><i class="bi bi-key-fill me-2"></i>Consulta de claves (alumnos y docentes)</h5>
      </div>
      <div class="card-body">
        <p class="text-muted small">Uso interno de secretaría. Busca primero en alumnos y luego en docentes por DNI.</p>
        <?php if ($mensaje !== ''): ?>
          <div class="alert alert-info"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
          <div class="mb-3">
            <label for="dni" class="form-label">DNI</label>
            <input type="text" class="form-control" id="dni" name="dni" maxlength="20"
                   value="<?php echo htmlspecialchars($dniPost, ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div class="mb-3">
            <label for="clave" class="form-label">Contraseña registrada (acceso web)</label>
            <input type="text" class="form-control font-monospace" id="clave" readonly
                   value="<?php echo htmlspecialchars($clave, ENT_QUOTES, 'UTF-8'); ?>"
                   placeholder="<?php echo $claveEsHash ? 'No disponible (hash)' : ''; ?>">
          </div>
          <button type="submit" class="btn btn-primary">Buscar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="../js/jquery-3.7.1.js"></script>
<script src="../js/bootstrap.bundle.js"></script>
<?php include '../funciones/footer.html'; ?>
</body>
</html>
