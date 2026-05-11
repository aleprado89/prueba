<?php
/**
 * Acceso al portal de autogestion como alumno o docente (solo superadmin tipoPermiso 7).
 */
include '../funciones/verificarSesion.php';
include '../funciones/requerirSecretaria.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';

if ((int) ($_SESSION['sec_tipoPermiso'] ?? 0) !== 7) {
    header('Location: menusecretaria.php?permiso=denegado');
    exit;
}

$mensaje = '';
$tipoMensaje = 'info';
$apellido = trim((string) ($_POST['apellido'] ?? $_GET['apellido'] ?? ''));
$nombre = trim((string) ($_POST['nombre'] ?? $_GET['nombre'] ?? ''));
$resultados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'buscar') {
        if ($apellido === '' && $nombre === '') {
            $mensaje = 'Ingrese al menos apellido o nombre para buscar.';
            $tipoMensaje = 'warning';
        } else {
            $resultados = listarPersonasTerciarioParaImpersonacion($conn, $apellido, $nombre);
            if (empty($resultados)) {
                $mensaje = 'No se encontraron personas con rol alumno y/o docente terciario.';
                $tipoMensaje = 'info';
            }
        }
    } elseif ($accion === 'acceder') {
        $tipo = $_POST['tipo'] ?? '';
        $anios = obtenerAniosPlataformaColegio($conn, 6);
        $docMod = obtenerDocenteModificaColegio($conn, 6);

        if ($tipo === 'alu') {
            $idAlumno = (int) ($_POST['id_alumno'] ?? 0);
            if ($idAlumno <= 0) {
                $mensaje = 'Alumno invalido.';
                $tipoMensaje = 'danger';
            } else {
                $data = obtenerDatosAlumno($conn, $idAlumno);
                if ($data === null) {
                    $mensaje = 'No se encontro el alumno.';
                    $tipoMensaje = 'danger';
                } else {
                    $_SESSION['_sec_backup'] = [
                        'sec_id' => $_SESSION['sec_id'],
                        'sec_nombreUsuario' => $_SESSION['sec_nombreUsuario'],
                        'sec_tipoPermiso' => $_SESSION['sec_tipoPermiso'],
                        'sec_idnivel' => $_SESSION['sec_idnivel'],
                        'login_success' => $_SESSION['login_success'] ?? true,
                    ];
                    foreach (array_keys($_SESSION) as $k) {
                        if (strpos($k, 'sec_') === 0) {
                            unset($_SESSION[$k]);
                        }
                    }
                    foreach (array_keys($_SESSION) as $k) {
                        if (strpos($k, 'alu_') === 0 || strpos($k, 'doc_') === 0) {
                            unset($_SESSION[$k]);
                        }
                    }
                    unset($_SESSION['es_alumno_y_docente'], $_SESSION['cambiarClave']);

                    $_SESSION['impersonando'] = true;
                    $_SESSION['impersonacion_label'] = $data['apellido'] . ', ' . $data['nombre'] . ' (Alumno)';

                    $_SESSION['alu_nombre'] = $data['nombre'];
                    $_SESSION['alu_apellido'] = $data['apellido'];
                    $_SESSION['alu_dni'] = $data['dni'];
                    $_SESSION['alu_idAlumno'] = (int) $data['idAlumno'];
                    $_SESSION['alu_idPersona'] = (int) $data['idPersona'];
                    $_SESSION['anioPlataformaAlu'] = $anios['anioautoweb'];

                    $_SESSION['active_user_identifier'] = 'alu_' . (int) $data['idAlumno'];
                    $_SESSION['redirect_after_logout'] = '../inicio/login.php';

                    session_regenerate_id(true);
                    require_once __DIR__ . '/../inicio/variablesParticulares.php';

                    $stmtN = $conn->prepare('SELECT nombreColegio FROM colegio WHERE codnivel = 6 LIMIT 1');
                    if ($stmtN && $stmtN->execute()) {
                        $r = $stmtN->get_result()->fetch_assoc();
                        $_SESSION['nombreColegio'] = $r['nombreColegio'] ?? 'Institución';
                        $stmtN->close();
                    }

                    $destino = '../alumnos/menualumnos.php';
                    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
                    echo '<script>localStorage.removeItem("usuario_sesion_activa_global");window.location.replace(' . json_encode($destino) . ');</script>';
                    echo '</body></html>';
                    exit;
                }
            }
        } elseif ($tipo === 'doc') {
            $legajo = (int) ($_POST['legajo'] ?? 0);
            if ($legajo <= 0) {
                $mensaje = 'Docente invalido.';
                $tipoMensaje = 'danger';
            } else {
                $data = obtenerDatosPersonal($conn, $legajo);
                if ($data === null || (int) ($data['nivel'] ?? 0) !== 6) {
                    $mensaje = 'No se encontro docente terciario con ese legajo.';
                    $tipoMensaje = 'danger';
                } else {
                    $_SESSION['_sec_backup'] = [
                        'sec_id' => $_SESSION['sec_id'],
                        'sec_nombreUsuario' => $_SESSION['sec_nombreUsuario'],
                        'sec_tipoPermiso' => $_SESSION['sec_tipoPermiso'],
                        'sec_idnivel' => $_SESSION['sec_idnivel'],
                        'login_success' => $_SESSION['login_success'] ?? true,
                    ];
                    foreach (array_keys($_SESSION) as $k) {
                        if (strpos($k, 'sec_') === 0) {
                            unset($_SESSION[$k]);
                        }
                    }
                    foreach (array_keys($_SESSION) as $k) {
                        if (strpos($k, 'alu_') === 0 || strpos($k, 'doc_') === 0) {
                            unset($_SESSION[$k]);
                        }
                    }
                    unset($_SESSION['es_alumno_y_docente'], $_SESSION['cambiarClave']);

                    $_SESSION['impersonando'] = true;
                    $_SESSION['impersonacion_label'] = $data['apellido'] . ', ' . $data['nombre'] . ' (Docente)';

                    $_SESSION['doc_nombre'] = $data['nombre'];
                    $_SESSION['doc_apellido'] = $data['apellido'];
                    $_SESSION['doc_dni'] = $data['dni'];
                    $_SESSION['doc_legajo'] = (int) $data['legajo'];
                    $_SESSION['doc_idPersona'] = (int) $data['idPersona'];
                    $_SESSION['anioPlataformaDoc'] = $anios['anioCargaNotas'];
                    $_SESSION['profeModCalifAsis'] = $docMod;

                    $_SESSION['active_user_identifier'] = 'doc_' . (int) $data['legajo'];
                    $_SESSION['redirect_after_logout'] = '../inicio/login.php';

                    session_regenerate_id(true);
                    require_once __DIR__ . '/../inicio/variablesParticulares.php';

                    $stmtN = $conn->prepare('SELECT nombreColegio FROM colegio WHERE codnivel = 6 LIMIT 1');
                    if ($stmtN && $stmtN->execute()) {
                        $r = $stmtN->get_result()->fetch_assoc();
                        $_SESSION['nombreColegio'] = $r['nombreColegio'] ?? 'Institución';
                        $stmtN->close();
                    }

                    $destino = '../docentes/menudocentes.php';
                    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
                    echo '<script>localStorage.removeItem("usuario_sesion_activa_global");window.location.replace(' . json_encode($destino) . ');</script>';
                    echo '</body></html>';
                    exit;
                }
            }
        } else {
            $mensaje = 'Tipo de acceso no valido.';
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
    <title>Acceder como usuario</title>
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
                <li class="breadcrumb-item active" aria-current="page">Acceder como usuario</li>
            </ol>
        </nav>

        <div class="card shadow-sm">
            <div class="card-header card-header-custom">
                <h5 class="mb-0"><i class="bi bi-person-fill-gear me-2"></i>Acceder al portal de autogestion</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">Solo administradores (tipo permiso 7). Se abre la sesion del alumno o docente seleccionado; use &quot;Volver a Secretaría&quot; en el portal o Cerrar sesión para recuperar su sesión de secretaría.</p>

                <?php if ($mensaje !== ''): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($tipoMensaje, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="post" action="accederComoUsuario.php" class="row g-3 mb-4">
                    <input type="hidden" name="accion" value="buscar">
                    <div class="col-md-5">
                        <label class="form-label" for="apellido">Apellido</label>
                        <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo htmlspecialchars($apellido, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Buscar</button>
                    </div>
                </form>

                <?php if (!empty($resultados)): ?>
                    <h6 class="mb-3">Resultados</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Apellido</th>
                                    <th>Nombre</th>
                                    <th>DNI</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultados as $row):
                                    $idAlu = isset($row['idAlumno']) ? (int) $row['idAlumno'] : 0;
                                    $legDoc = isset($row['legajo_docente']) ? (int) $row['legajo_docente'] : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string) $row['apellido'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $row['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $row['dni'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="d-flex flex-wrap gap-2">
                                            <?php if ($idAlu > 0): ?>
                                                <form method="post" action="accederComoUsuario.php" class="d-inline">
                                                    <input type="hidden" name="accion" value="acceder">
                                                    <input type="hidden" name="tipo" value="alu">
                                                    <input type="hidden" name="id_alumno" value="<?php echo $idAlu; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Acceder como alumno</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($legDoc > 0): ?>
                                                <form method="post" action="accederComoUsuario.php" class="d-inline">
                                                    <input type="hidden" name="accion" value="acceder">
                                                    <input type="hidden" name="tipo" value="doc">
                                                    <input type="hidden" name="legajo" value="<?php echo $legDoc; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Acceder como docente</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
