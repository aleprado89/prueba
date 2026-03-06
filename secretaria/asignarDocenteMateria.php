<?php
include '../funciones/verificarSesion.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function valorEntero($valor, $default = 0) {
    return isset($valor) && $valor !== '' ? (int)$valor : (int)$default;
}

function buildUrlAsignacion($params = []) {
    return 'asignarDocenteMateria.php?' . http_build_query($params);
}

function validarReglasAsignacion($asignaciones, $tipo, $idPersonal, $idProfXMatEditar = 0) {
    $hayTitular = false;
    $haySuplente = false;
    $hayEquipo = false;

    foreach ($asignaciones as $asignacion) {
        $idActual = (int)$asignacion['idProfXMat'];
        if ($idProfXMatEditar > 0 && $idActual === $idProfXMatEditar) {
            continue;
        }

        if ((int)$asignacion['idPersonal'] === $idPersonal && $asignacion['tipo'] === $tipo) {
            return 'Ese docente ya esta asignado con el mismo tipo para la materia seleccionada.';
        }

        if ($asignacion['tipo'] === 'Titular') {
            $hayTitular = true;
        } elseif ($asignacion['tipo'] === 'Suplente') {
            $haySuplente = true;
        } elseif ($asignacion['tipo'] === 'Equipo Docente') {
            $hayEquipo = true;
        }
    }

    if ($tipo === 'Titular') {
        if ($hayTitular) {
            return 'La materia ya tiene un docente titular asignado.';
        }
        if ($hayEquipo) {
            return 'No se puede asignar titular si ya hay equipo docente.';
        }
    }

    if ($tipo === 'Suplente') {
        if ($haySuplente) {
            return 'La materia ya tiene un docente suplente asignado.';
        }
        if ($hayEquipo) {
            return 'No se puede asignar suplente si ya hay equipo docente.';
        }
    }

    if ($tipo === 'Equipo Docente' && ($hayTitular || $haySuplente)) {
        return 'No se puede asignar equipo docente si la materia tiene titular y/o suplente.';
    }

    return null;
}

$tiposValidos = ['Titular', 'Suplente', 'Equipo Docente'];

$message = '';
$messageType = '';
if (isset($_SESSION['message_asignacion_docente'])) {
    $message = $_SESSION['message_asignacion_docente']['text'];
    $messageType = $_SESSION['message_asignacion_docente']['type'];
    unset($_SESSION['message_asignacion_docente']);
}

$ciclos = levantarCiclosLectivos($conn);
$planes = buscarTodosPlanes($conn);
$docentesActivos = obtenerPersonalActivoLegajo($conn);

$anioDefault = $datosColegio[0]['anioCargaNotas'] ?? date('Y');
$idCicloDefault = buscarIdCiclo($conn, $anioDefault);
if (!$idCicloDefault && !empty($ciclos)) {
    $idCicloDefault = (int)$ciclos[0]['idCicloLectivo'];
}

$idPlanDefault = !empty($planes) ? (int)$planes[0]['idPlan'] : 0;

$idCicloLectivo = valorEntero($_REQUEST['idCicloLectivo'] ?? null, $idCicloDefault);
$idPlan = valorEntero($_REQUEST['idPlan'] ?? null, $idPlanDefault);

$cursos = ($idPlan > 0 && $idCicloLectivo > 0) ? buscarCursosPlanCiclo($conn, $idPlan, $idCicloLectivo) : [];
$idCursoDefault = !empty($cursos) ? (int)$cursos[0]['idCurso'] : 0;
$idCurso = valorEntero($_REQUEST['idCurso'] ?? null, $idCursoDefault);
if (!empty($cursos)) {
    $cursoValido = false;
    foreach ($cursos as $cursoRow) {
        if ((int)$cursoRow['idCurso'] === $idCurso) {
            $cursoValido = true;
            break;
        }
    }
    if (!$cursoValido) {
        $idCurso = $idCursoDefault;
    }
} else {
    $idCurso = 0;
}

$materias = ($idPlan > 0 && $idCicloLectivo > 0)
    ? obtenerTodasLasMaterias($conn, $idCicloLectivo, $idPlan, $idCurso > 0 ? $idCurso : null)
    : [];

$idMateriaDefault = !empty($materias) ? (int)$materias[0]['idMateria'] : 0;
$idMateria = valorEntero($_REQUEST['idMateria'] ?? null, $idMateriaDefault);
if (!empty($materias)) {
    $materiaValida = false;
    foreach ($materias as $materiaRow) {
        if ((int)$materiaRow['idMateria'] === $idMateria) {
            $materiaValida = true;
            break;
        }
    }
    if (!$materiaValida) {
        $idMateria = $idMateriaDefault;
    }
} else {
    $idMateria = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
    $idProfXMat = valorEntero($_POST['idProfXMat'] ?? null, 0);
    $idMateriaPost = valorEntero($_POST['idMateria'] ?? null, 0);
    $idPersonalPost = valorEntero($_POST['idPersonal'] ?? null, 0);
    $tipoPost = trim($_POST['tipo'] ?? '');

    $baseParams = [
        'idCicloLectivo' => valorEntero($_POST['idCicloLectivo'] ?? null, $idCicloLectivo),
        'idPlan' => valorEntero($_POST['idPlan'] ?? null, $idPlan),
        'idCurso' => valorEntero($_POST['idCurso'] ?? null, $idCurso),
        'idMateria' => $idMateriaPost
    ];

    if ($idMateriaPost <= 0) {
        $_SESSION['message_asignacion_docente'] = ['text' => 'Debe seleccionar una materia.', 'type' => 'danger'];
        header('Location: ' . buildUrlAsignacion($baseParams));
        exit;
    }

    if ($action === 'delete') {
        if ($idProfXMat <= 0) {
            $_SESSION['message_asignacion_docente'] = ['text' => 'Asignacion no valida para eliminar.', 'type' => 'danger'];
            header('Location: ' . buildUrlAsignacion($baseParams));
            exit;
        }

        $okDelete = deleteProfesorMateria($conn, $idProfXMat);
        $_SESSION['message_asignacion_docente'] = $okDelete
            ? ['text' => 'Asignacion eliminada correctamente.', 'type' => 'success']
            : ['text' => 'No se pudo eliminar la asignacion.', 'type' => 'danger'];
        header('Location: ' . buildUrlAsignacion($baseParams));
        exit;
    }

    if ($action === 'save') {
        if ($idPersonalPost <= 0 || !in_array($tipoPost, $tiposValidos, true)) {
            $_SESSION['message_asignacion_docente'] = ['text' => 'Debe seleccionar docente y tipo de asignacion.', 'type' => 'danger'];
            $baseParams['edit'] = $idProfXMat > 0 ? $idProfXMat : null;
            header('Location: ' . buildUrlAsignacion(array_filter($baseParams, static function ($v) { return $v !== null; })));
            exit;
        }

        $asignacionesActuales = obtenerAsignacionesProfesorMateria($conn, $idMateriaPost);
        $errorRegla = validarReglasAsignacion($asignacionesActuales, $tipoPost, $idPersonalPost, $idProfXMat);
        if ($errorRegla !== null) {
            $_SESSION['message_asignacion_docente'] = ['text' => $errorRegla, 'type' => 'danger'];
            $baseParams['edit'] = $idProfXMat > 0 ? $idProfXMat : null;
            header('Location: ' . buildUrlAsignacion(array_filter($baseParams, static function ($v) { return $v !== null; })));
            exit;
        }

        if ($idProfXMat > 0) {
            $ok = updateProfesorMateria($conn, $idProfXMat, $idPersonalPost, $tipoPost);
            $_SESSION['message_asignacion_docente'] = $ok
                ? ['text' => 'Asignacion actualizada correctamente.', 'type' => 'success']
                : ['text' => 'No se pudo actualizar la asignacion.', 'type' => 'danger'];
        } else {
            $ok = insertProfesorMateria($conn, $idMateriaPost, $idPersonalPost, $tipoPost);
            $_SESSION['message_asignacion_docente'] = $ok
                ? ['text' => 'Asignacion creada correctamente.', 'type' => 'success']
                : ['text' => 'No se pudo crear la asignacion.', 'type' => 'danger'];
        }

        header('Location: ' . buildUrlAsignacion($baseParams));
        exit;
    }
}

$materiaSeleccionada = null;
foreach ($materias as $materiaRow) {
    if ((int)$materiaRow['idMateria'] === $idMateria) {
        $materiaSeleccionada = $materiaRow;
        break;
    }
}

$asignaciones = $idMateria > 0 ? obtenerAsignacionesProfesorMateria($conn, $idMateria) : [];
$editId = valorEntero($_GET['edit'] ?? null, 0);
$asignacionEdit = null;
foreach ($asignaciones as $asignacionRow) {
    if ((int)$asignacionRow['idProfXMat'] === $editId) {
        $asignacionEdit = $asignacionRow;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar docente a materia</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/material/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="icon" type="image/png" href="../img/icon.png">
</head>
<body>
<?php include '../funciones/menu_secretaria.php'; ?>

<div class="container-fluid fondo">
    <br>
    <div class="container">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
            <li class="breadcrumb-item">Datos de Personal</li>
            <li class="breadcrumb-item active">Asignar docente a materia</li>
        </ol>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Asignacion de profesores por materia</h5>
                <a href="menusecretaria.php" class="btn btn-outline-secondary text-dark">Volver</a>
            </div>

            <form method="GET" action="asignarDocenteMateria.php" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Ciclo lectivo</label>
                    <select class="form-select" name="idCicloLectivo" required>
                        <?php foreach ($ciclos as $ciclo): ?>
                            <option value="<?php echo (int)$ciclo['idCicloLectivo']; ?>" <?php echo ((int)$ciclo['idCicloLectivo'] === (int)$idCicloLectivo) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$ciclo['anio']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Plan</label>
                    <select class="form-select" name="idPlan" required>
                        <?php foreach ($planes as $plan): ?>
                            <option value="<?php echo (int)$plan['idPlan']; ?>" <?php echo ((int)$plan['idPlan'] === (int)$idPlan) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$plan['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Curso</label>
                    <select class="form-select" name="idCurso">
                        <?php if (empty($cursos)): ?>
                            <option value="0">Sin cursos</option>
                        <?php else: ?>
                            <?php foreach ($cursos as $curso): ?>
                                <option value="<?php echo (int)$curso['idCurso']; ?>" <?php echo ((int)$curso['idCurso'] === (int)$idCurso) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)$curso['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Materia</label>
                    <select class="form-select" name="idMateria">
                        <?php if (empty($materias)): ?>
                            <option value="0">Sin materias</option>
                        <?php else: ?>
                            <?php foreach ($materias as $materia): ?>
                                <option value="<?php echo (int)$materia['idMateria']; ?>" <?php echo ((int)$materia['idMateria'] === (int)$idMateria) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)$materia['Materia']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Aplicar filtros</button>
                </div>
            </form>
        </div>

        <?php if ($idMateria > 0 && $materiaSeleccionada): ?>
            <div class="card p-4 mb-4">
                <h6 class="mb-3">
                    Materia seleccionada:
                    <?php echo htmlspecialchars((string)$materiaSeleccionada['Materia']); ?>
                    <?php if (!empty($materiaSeleccionada['Curso'])): ?>
                        (<?php echo htmlspecialchars((string)$materiaSeleccionada['Curso']); ?>)
                    <?php endif; ?>
                </h6>

                <form method="POST" action="asignarDocenteMateria.php" class="row g-3 mb-4">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="idProfXMat" value="<?php echo (int)($asignacionEdit['idProfXMat'] ?? 0); ?>">
                    <input type="hidden" name="idMateria" value="<?php echo (int)$idMateria; ?>">
                    <input type="hidden" name="idCicloLectivo" value="<?php echo (int)$idCicloLectivo; ?>">
                    <input type="hidden" name="idPlan" value="<?php echo (int)$idPlan; ?>">
                    <input type="hidden" name="idCurso" value="<?php echo (int)$idCurso; ?>">

                    <div class="col-md-5">
                        <label class="form-label">Docente (personal activo)</label>
                        <select class="form-select" name="idPersonal" required>
                            <option value="">Seleccionar</option>
                            <?php foreach ($docentesActivos as $docente): ?>
                                <option value="<?php echo (int)$docente['legajo']; ?>" <?php echo ((int)$docente['legajo'] === (int)($asignacionEdit['idPersonal'] ?? 0)) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)$docente['apellido'] . ', ' . (string)$docente['nombre'] . ' - Legajo ' . (string)$docente['legajo'] . ' - DNI ' . (string)$docente['dni']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="tipo" required>
                            <option value="">Seleccionar</option>
                            <?php foreach ($tiposValidos as $tipoOption): ?>
                                <option value="<?php echo htmlspecialchars($tipoOption); ?>" <?php echo (($asignacionEdit['tipo'] ?? '') === $tipoOption) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tipoOption); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-floppy"></i> <?php echo $asignacionEdit ? 'Actualizar' : 'Agregar'; ?>
                        </button>
                        <?php if ($asignacionEdit): ?>
                            <a href="<?php echo htmlspecialchars(buildUrlAsignacion([
                                'idCicloLectivo' => $idCicloLectivo,
                                'idPlan' => $idPlan,
                                'idCurso' => $idCurso,
                                'idMateria' => $idMateria
                            ])); ?>" class="btn btn-outline-secondary text-dark">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>

                <h6 class="mb-3">Asignaciones actuales</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Docente</th>
                                <th>Legajo</th>
                                <th>DNI</th>
                                <th>Reg. Nuevo</th>
                                <th>Reg. Modificacion</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($asignaciones)): ?>
                            <tr>
                                <td colspan="7">Sin asignaciones para esta materia.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($asignaciones as $asignacion): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$asignacion['tipo']); ?></td>
                                    <td><?php echo htmlspecialchars((string)$asignacion['apellido'] . ', ' . (string)$asignacion['nombre']); ?></td>
                                    <td><?php echo (int)$asignacion['idPersonal']; ?></td>
                                    <td><?php echo htmlspecialchars((string)$asignacion['dni']); ?></td>
                                    <td><?php echo (int)$asignacion['registroNuevo']; ?></td>
                                    <td><?php echo (int)$asignacion['registroModificacion']; ?></td>
                                    <td class="d-flex gap-2">
                                        <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars(buildUrlAsignacion([
                                            'idCicloLectivo' => $idCicloLectivo,
                                            'idPlan' => $idPlan,
                                            'idCurso' => $idCurso,
                                            'idMateria' => $idMateria,
                                            'edit' => (int)$asignacion['idProfXMat']
                                        ])); ?>">
                                            Editar
                                        </a>
                                        <form method="POST" action="asignarDocenteMateria.php" onsubmit="return confirm('Confirma eliminar esta asignacion?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="idProfXMat" value="<?php echo (int)$asignacion['idProfXMat']; ?>">
                                            <input type="hidden" name="idMateria" value="<?php echo (int)$idMateria; ?>">
                                            <input type="hidden" name="idCicloLectivo" value="<?php echo (int)$idCicloLectivo; ?>">
                                            <input type="hidden" name="idPlan" value="<?php echo (int)$idPlan; ?>">
                                            <input type="hidden" name="idCurso" value="<?php echo (int)$idCurso; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../funciones/footer.html'; ?>
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../funciones/sessionControl.js"></script>
</body>
</html>
