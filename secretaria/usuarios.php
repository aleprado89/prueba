<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../funciones/verificarSesion.php';
include '../funciones/requerirSecretaria.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';
require_once '../funciones/formulariosSecretaria.php';

$mensaje = '';
$tipoMensaje = 'info';
$mostrarToastGuardado = false;
$selectedUserId = isset($_GET['idusuario']) ? (int)$_GET['idusuario'] : 0;

if ((int)($_SESSION['sec_tipoPermiso'] ?? 0) !== 7) {
    $mensaje = 'Solo el usuario administrador (tipoPermiso 7) puede gestionar usuarios y permisos.';
    $tipoMensaje = 'danger';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (int)($_SESSION['sec_tipoPermiso'] ?? 0) === 7) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_user') {
        $nombreUsuario = trim($_POST['nombreUsuario'] ?? '');
        $clave = trim($_POST['clave'] ?? '');

        if ($nombreUsuario === '' || $clave === '') {
            $mensaje = 'Debe completar usuario y clave.';
            $tipoMensaje = 'warning';
        } elseif (existeNombreUsuarioAdmin($conn, $nombreUsuario)) {
            $mensaje = 'El nombre de usuario ya existe.';
            $tipoMensaje = 'warning';
        } else {
            $nuevoId = crearUsuarioAdmin($conn, $nombreUsuario, $clave);
            if ($nuevoId !== false) {
                $mensaje = 'Usuario creado correctamente.';
                $tipoMensaje = 'success';
                $selectedUserId = (int)$nuevoId;
            } else {
                $mensaje = 'No se pudo crear el usuario.';
                $tipoMensaje = 'danger';
            }
        }
    }

    if ($action === 'update_user') {
        $idUsuario = (int)($_POST['idusuario'] ?? 0);
        $nombreUsuario = trim($_POST['nombreUsuario'] ?? '');
        $nuevaClave = trim($_POST['clave'] ?? '');
        $selectedUserId = $idUsuario;

        if ($idUsuario <= 0 || $nombreUsuario === '') {
            $mensaje = 'Datos invalidos para editar usuario.';
            $tipoMensaje = 'warning';
        } elseif (existeNombreUsuarioAdmin($conn, $nombreUsuario, $idUsuario)) {
            $mensaje = 'El nombre de usuario ya existe.';
            $tipoMensaje = 'warning';
        } else {
            $ok = actualizarUsuarioAdmin($conn, $idUsuario, $nombreUsuario, $nuevaClave);
            if ($ok) {
                $mensaje = 'Usuario actualizado correctamente.';
                $tipoMensaje = 'success';
            } else {
                $mensaje = 'No se pudo actualizar el usuario.';
                $tipoMensaje = 'danger';
            }
        }
    }

    if ($action === 'delete_user') {
        $idUsuario = (int)($_POST['idusuario'] ?? 0);
        $selectedUserId = 0;

        if ($idUsuario <= 0) {
            $mensaje = 'Usuario invalido para eliminar.';
            $tipoMensaje = 'warning';
        } elseif ($idUsuario === (int)($_SESSION['sec_id'] ?? 0)) {
            $mensaje = 'No puede eliminar el usuario con sesion activa.';
            $tipoMensaje = 'warning';
        } else {
            $usuario = obtenerUsuarioAdminPorId($conn, $idUsuario);
            if (!$usuario) {
                $mensaje = 'El usuario no existe.';
                $tipoMensaje = 'warning';
            } elseif ((int)$usuario['tipoPermiso'] === 7) {
                $mensaje = 'No se puede eliminar el usuario administrador.';
                $tipoMensaje = 'warning';
            } else {
                $ok = eliminarUsuarioAdmin($conn, $idUsuario);
                if ($ok) {
                    $mensaje = 'Usuario eliminado correctamente.';
                    $tipoMensaje = 'success';
                } else {
                    $mensaje = 'No se pudo eliminar el usuario.';
                    $tipoMensaje = 'danger';
                }
            }
        }
    }

    if ($action === 'save_permissions_all') {
        $idUsuario = (int)($_POST['idusuario'] ?? 0);
        $modosPorFormulario = $_POST['idllavegral'] ?? [];
        $llavesPorFormulario = $_POST['idllave'] ?? [];
        $selectedUserId = $idUsuario;

        $usuario = obtenerUsuarioAdminPorId($conn, $idUsuario);
        if (!$usuario) {
            $mensaje = 'El usuario seleccionado no existe.';
            $tipoMensaje = 'warning';
        } elseif ((int)$usuario['tipoPermiso'] === 7) {
            $mensaje = 'El administrador (tipoPermiso=7) tiene acceso total. No se editan sus permisos.';
            $tipoMensaje = 'warning';
        } elseif (empty($modosPorFormulario) || !is_array($modosPorFormulario)) {
            $mensaje = 'No se recibieron permisos para guardar.';
            $tipoMensaje = 'warning';
        } else {
            $todoOk = true;
            $idsPermitidos = formulariosSecretariaV3Ids();
            foreach ($modosPorFormulario as $idFormulario => $idLlaveGralRaw) {
                $idFormulario = (int)$idFormulario;
                if (!in_array($idFormulario, $idsPermitidos, true)) {
                    continue;
                }
                $idLlaveGral = (int)$idLlaveGralRaw;

                if ($idFormulario <= 0 || !in_array($idLlaveGral, [0, 1, 2, 3], true)) {
                    $todoOk = false;
                    break;
                }

                $idsLlaves = [];
                if ($idLlaveGral === 3 && isset($llavesPorFormulario[$idFormulario]) && is_array($llavesPorFormulario[$idFormulario])) {
                    $idsLlaves = array_map('intval', $llavesPorFormulario[$idFormulario]);
                }

                if (!guardarPermisosUsuarioFormulario($conn, $idUsuario, $idFormulario, $idLlaveGral, $idsLlaves)) {
                    $todoOk = false;
                    break;
                }
            }

            if ($todoOk) {
                $mensaje = 'Permisos guardados correctamente.';
                $tipoMensaje = 'success';
                $mostrarToastGuardado = true;
            } else {
                $mensaje = 'No se pudieron guardar todos los permisos.';
                $tipoMensaje = 'danger';
            }
        }
    }
}

$usuarios = obtenerResumenPermisosUsuarios($conn);
$selectedUser = null;
if ($selectedUserId > 0) {
    $selectedUser = obtenerUsuarioAdminPorId($conn, $selectedUserId);
}

if (!$selectedUser && !empty($usuarios)) {
    $selectedUserId = (int)$usuarios[0]['idusuarios'];
    $selectedUser = obtenerUsuarioAdminPorId($conn, $selectedUserId);
}

$formularios = obtenerFormulariosPermisosV3($conn);
$llavesGenerales = obtenerLlavesGenerales($conn);
$llavesGeneralesPorId = [];
foreach ($llavesGenerales as $llaveGeneral) {
    $llavesGeneralesPorId[(int)$llaveGeneral['idllavegral']] = $llaveGeneral['llavesgenerales'];
}

$llavesPorFormulario = [];
$permisosActuales = [];
if ($selectedUser) {
    foreach ($formularios as $formulario) {
        $idFormulario = (int)$formulario['idformulario'];
        $llavesPorFormulario[$idFormulario] = obtenerLlavesPorFormulario($conn, $idFormulario);
        $permisosActuales[$idFormulario] = obtenerPermisosUsuarioFormulario($conn, (int)$selectedUser['idusuarios'], $idFormulario);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Usuarios y Permisos</title>
    <link rel="stylesheet" href="../css/material/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="../img/icon.png">
</head>
<body>
<?php include '../funciones/menu_secretaria.php'; ?>

<div class="container mt-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
        <li class="breadcrumb-item active">Usuarios y Permisos</li>
    </ol>

    <h3 class="mb-3">Gestion de Usuarios y Permisos</h3>

    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($tipoMensaje); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ((int)($_SESSION['sec_tipoPermiso'] ?? 0) !== 7): ?>
        <div class="alert alert-danger">
            No tiene permisos para administrar usuarios.
        </div>
    <?php else: ?>
        <div class="card p-4 mb-4 shadow-sm">
            <h5 class="mb-3">Alta de Usuario</h5>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="create_user">
                <div class="col-md-4">
                    <label for="nombreUsuario" class="form-label">Nombre de usuario</label>
                    <input type="text" class="form-control" id="nombreUsuario" name="nombreUsuario" required>
                </div>
                <div class="col-md-4">
                    <label for="clave" class="form-label">Clave</label>
                    <input type="text" class="form-control" id="clave" name="clave" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Crear usuario
                    </button>
                </div>
            </form>
        </div>

        <div class="card p-4 mb-4 shadow-sm">
            <h5 class="mb-3">Listado de Usuarios</h5>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Resumen</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">No hay usuarios cargados.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($usuarios as $usuario): ?>
                        <?php
                        $idU = (int)$usuario['idusuarios'];
                        $esAdmin = ((int)$usuario['tipoPermiso'] === 7);
                        $resumen = $esAdmin ? 'Administrador (acceso total)' : ($usuario['resumenPermisos'] ?? 'Sin permisos configurados');
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['nombreUsuario']); ?></td>
                            <td>
                                <a href="#" class="text-primary" data-bs-toggle="modal" data-bs-target="#resumenModal<?php echo $idU; ?>" title="Ver resumen">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                            </td>
                            <td class="text-end">
                                <a href="usuarios.php?idusuario=<?php echo $idU; ?>#gestion-permisos" class="text-primary me-3" title="Ir a permisos">
                                    <i class="bi bi-key-fill"></i>
                                </a>
                                <a href="#" class="text-primary me-3" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $idU; ?>" title="Editar usuario">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a href="#" class="text-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?php echo $idU; ?>" title="Eliminar usuario">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </td>
                        </tr>

                        <div class="modal fade" id="resumenModal<?php echo $idU; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Resumen de permisos</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="mb-1"><strong>Usuario:</strong> <?php echo htmlspecialchars($usuario['nombreUsuario']); ?></p>
                                        <hr>
                                        <?php if ($esAdmin): ?>
                                            <p class="mb-0"><?php echo htmlspecialchars($resumen); ?></p>
                                        <?php else: ?>
                                            <?php $itemsResumen = array_filter(array_map('trim', explode('|', (string)$resumen))); ?>
                                            <?php if (empty($itemsResumen) || $resumen === 'Sin permisos configurados'): ?>
                                                <p class="mb-0 text-muted">Sin permisos configurados.</p>
                                            <?php else: ?>
                                                <ul class="list-group">
                                                    <?php foreach ($itemsResumen as $itemResumen): ?>
                                                        <?php
                                                        $partes = explode(':', $itemResumen, 2);
                                                        $nombreForm = trim($partes[0] ?? '');
                                                        $modoForm = trim($partes[1] ?? '');
                                                        ?>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                            <span><?php echo htmlspecialchars($nombreForm); ?></span>
                                                            <span class="badge bg-primary"><?php echo htmlspecialchars($modoForm); ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="editUserModal<?php echo $idU; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Editar usuario</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="update_user">
                                            <input type="hidden" name="idusuario" value="<?php echo $idU; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Nombre de usuario</label>
                                                <input type="text" class="form-control" name="nombreUsuario" value="<?php echo htmlspecialchars($usuario['nombreUsuario']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Nueva clave (opcional)</label>
                                                <input type="text" class="form-control" name="clave" placeholder="Dejar vacio para no cambiar">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="deleteUserModal<?php echo $idU; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Eliminar usuario</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="idusuario" value="<?php echo $idU; ?>">
                                            <p>
                                                Va a eliminar al usuario <strong><?php echo htmlspecialchars($usuario['nombreUsuario']); ?></strong>.
                                            </p>
                                            <p class="text-danger mb-0">Tambien se eliminaran sus permisos por formulario.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-danger">Eliminar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-4 mb-4 shadow-sm" id="gestion-permisos">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Asignacion de Permisos por Formulario</h5>
                <?php if ($selectedUser): ?>
                    <span class="badge bg-primary">Usuario seleccionado: <?php echo htmlspecialchars($selectedUser['nombreUsuario']); ?></span>
                <?php endif; ?>
            </div>

            <?php if (!$selectedUser): ?>
                <p class="text-muted mb-0">Seleccione un usuario para editar permisos.</p>
            <?php elseif ((int)$selectedUser['tipoPermiso'] === 7): ?>
                <div class="alert alert-info mb-0">
                    El usuario <strong><?php echo htmlspecialchars($selectedUser['nombreUsuario']); ?></strong> es administrador
                    (<strong>tipoPermiso = 7</strong>) y tiene acceso total. No se le editan permisos.
                </div>
            <?php else: ?>
                <div class="bg-primary text-white py-2 px-3 rounded mb-3">
                    Editando permisos de: <strong><?php echo htmlspecialchars($selectedUser['nombreUsuario']); ?></strong>
                </div>
                <div class="mb-3 d-flex gap-2">
                    <button type="button" id="setAllTotal" class="btn btn-primary btn-sm">Marcar todo Acceso Total</button>
                    <button type="button" id="setAllLectura" class="btn btn-primary btn-sm">Marcar todo Solo Lectura</button>
                </div>
                <form method="POST" id="formPermisosGlobal">
                    <input type="hidden" name="action" value="save_permissions_all">
                    <input type="hidden" name="idusuario" value="<?php echo (int)$selectedUser['idusuarios']; ?>">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                        <tr>
                            <th style="width: 22%;">Formulario</th>
                            <th style="width: 20%;">Modalidad</th>
                            <th>Llaves especificas (si modalidad = Especifico)</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($formularios)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">No hay formularios en la tabla formularios.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($formularios as $formulario): ?>
                            <?php
                            $idFormulario = (int)$formulario['idformulario'];
                            $permiso = $permisosActuales[$idFormulario] ?? ['idllavegral' => 0, 'llaves' => []];
                            $modoActual = (int)$permiso['idllavegral'];
                            $llavesSeleccionadas = $permiso['llaves'] ?? [];
                            $llavesFormulario = $llavesPorFormulario[$idFormulario] ?? [];
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($formulario['formulario']); ?></strong><br>
                                    <small class="text-muted">ID: <?php echo $idFormulario; ?></small>
                                </td>
                                <td>
                                    <select name="idllavegral[<?php echo $idFormulario; ?>]" class="form-select modo-select" data-form-id="<?php echo $idFormulario; ?>">
                                        <option value="0" <?php echo ($modoActual === 0) ? 'selected' : ''; ?>>Sin asignar (sin permiso)</option>
                                        <option value="1" <?php echo ($modoActual === 1) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($llavesGeneralesPorId[1] ?? 'Acceso Total'); ?>
                                        </option>
                                        <option value="2" <?php echo ($modoActual === 2) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($llavesGeneralesPorId[2] ?? 'Solo Lectura'); ?>
                                        </option>
                                        <option value="3" <?php echo ($modoActual === 3) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($llavesGeneralesPorId[3] ?? 'Especifico'); ?>
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <div class="specific-keys" data-form-id="<?php echo $idFormulario; ?>" style="<?php echo ($modoActual === 3) ? '' : 'display:none;'; ?>">
                                        <?php if (empty($llavesFormulario)): ?>
                                            <span class="text-muted">No hay llaves definidas para este formulario.</span>
                                        <?php else: ?>
                                            <div class="row g-2">
                                                <?php foreach ($llavesFormulario as $llave): ?>
                                                    <?php $idLlave = (int)$llave['idllave']; ?>
                                                    <div class="col-md-6">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="idllave[<?php echo $idFormulario; ?>][]"
                                                                   id="llave_<?php echo $idFormulario . '_' . $idLlave; ?>"
                                                                   value="<?php echo $idLlave; ?>"
                                                                <?php echo in_array($idLlave, $llavesSeleccionadas, true) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="llave_<?php echo $idFormulario . '_' . $idLlave; ?>">
                                                                <?php echo htmlspecialchars($llave['llave']); ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($modoActual === 1): ?>
                                        <span class="badge bg-success">Total</span>
                                    <?php elseif ($modoActual === 2): ?>
                                        <span class="badge bg-primary">Lectura</span>
                                    <?php elseif ($modoActual === 3): ?>
                                        <span class="badge bg-warning text-dark">Especifico</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar todos los permisos
                    </button>
                </div>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../funciones/footer.html'; ?>
<script src="../js/jquery-3.7.1.js"></script>
<script src="../js/bootstrap.bundle.js"></script>
<script src="../funciones/sessionControl.js"></script>
<script>
    $(document).on('change', '.modo-select', function () {
        const formId = $(this).data('form-id');
        const modo = parseInt($(this).val(), 10);
        const specificBlock = $('.specific-keys[data-form-id="' + formId + '"]');

        if (modo === 3) {
            specificBlock.show();
        } else {
            specificBlock.hide();
            specificBlock.find('input[type="checkbox"]').prop('checked', false);
        }
    });

    $('#setAllTotal').on('click', function () {
        $('.modo-select').val('1').trigger('change');
    });

    $('#setAllLectura').on('click', function () {
        $('.modo-select').val('2').trigger('change');
    });

    if (window.location.hash === '#gestion-permisos') {
        const target = document.getElementById('gestion-permisos');
        if (target) {
            target.scrollIntoView({behavior: 'smooth', block: 'start'});
        }
    }

    <?php if ($mostrarToastGuardado): ?>
    const toastPermisos = document.getElementById('toastPermisosGuardados');
    if (toastPermisos) {
        const toast = new bootstrap.Toast(toastPermisos, { delay: 2500 });
        toast.show();
    }
    <?php endif; ?>
</script>

<div class="position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <div id="toastPermisosGuardados" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                Permisos guardados con exito.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
</body>
</html>
