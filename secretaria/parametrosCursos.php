<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../funciones/verificarSesion.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';

if (!isset($_SESSION['sec_nombreUsuario'])) {
    header('Location: ../inicio/loginAdmin.php');
    exit;
}

$mensaje = '';
$tipoMensaje = 'info';
$idCicloFiltro = isset($_GET['idciclo']) ? (int)$_GET['idciclo'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $idNivel = 6; // Fijo y oculto por requerimiento.
    $error = '';

    if ($action === 'create_course') {
        $idCiclo = (int)($_POST['idciclo'] ?? 0);
        $idPlanEstudio = (int)($_POST['idPlanEstudio'] ?? 0);
        $idTurnoCurso = (int)($_POST['idTurnoCurso'] ?? 0);
        $idDivision = (int)($_POST['idDivision'] ?? 0);
        $idCursoPredeterminado = (int)($_POST['idcursopredeterminado'] ?? 0);
        $cursoPrincipal = isset($_POST['cursoPrincipal']) ? 1 : 0;

        if ($idNivel !== 6 || $idCiclo <= 0 || $idPlanEstudio <= 0 || $idTurnoCurso <= 0 || $idDivision <= 0 || $idCursoPredeterminado <= 0) {
            $mensaje = 'Complete todos los campos obligatorios para crear el curso.';
            $tipoMensaje = 'warning';
        } else {
            $ok = crearCurso($conn, $idCiclo, $idPlanEstudio, $idTurnoCurso, $idDivision, $idCursoPredeterminado, $cursoPrincipal, $error);
            if ($ok) {
                $mensaje = 'Curso creado correctamente.';
                $tipoMensaje = 'success';
            } else {
                $mensaje = ($error !== '') ? $error : 'No se pudo crear el curso.';
                $tipoMensaje = 'danger';
            }
        }
    }

    if ($action === 'update_course') {
        $idCurso = (int)($_POST['idCurso'] ?? 0);
        $idCiclo = (int)($_POST['idciclo'] ?? 0);
        $idPlanEstudio = (int)($_POST['idPlanEstudio'] ?? 0);
        $idTurnoCurso = (int)($_POST['idTurnoCurso'] ?? 0);
        $idDivision = (int)($_POST['idDivision'] ?? 0);
        $idCursoPredeterminado = (int)($_POST['idcursopredeterminado'] ?? 0);
        $cursoPrincipal = isset($_POST['cursoPrincipal']) ? 1 : 0;

        if ($idCurso <= 0 || $idNivel !== 6 || $idCiclo <= 0 || $idPlanEstudio <= 0 || $idTurnoCurso <= 0 || $idDivision <= 0 || $idCursoPredeterminado <= 0) {
            $mensaje = 'Datos invalidos para actualizar curso.';
            $tipoMensaje = 'warning';
        } else {
            $ok = actualizarCurso($conn, $idCurso, $idCiclo, $idPlanEstudio, $idTurnoCurso, $idDivision, $idCursoPredeterminado, $cursoPrincipal, $error);
            if ($ok) {
                $mensaje = 'Curso actualizado correctamente.';
                $tipoMensaje = 'success';
            } else {
                $mensaje = ($error !== '') ? $error : 'No se pudo actualizar el curso.';
                $tipoMensaje = 'danger';
            }
        }
    }

    if ($action === 'delete_course') {
        $idCurso = (int)($_POST['idCurso'] ?? 0);
        if ($idCurso <= 0) {
            $mensaje = 'ID invalido para eliminar curso.';
            $tipoMensaje = 'warning';
        } else {
            $ok = eliminarCurso($conn, $idCurso, $error);
            if ($ok) {
                $mensaje = 'Curso eliminado correctamente.';
                $tipoMensaje = 'success';
            } else {
                $mensaje = ($error !== '') ? $error : 'No se pudo eliminar el curso.';
                $tipoMensaje = 'danger';
            }
        }
    }
}

$ciclos = obtenerCiclosLectivos($conn);
$planes = obtenerPlanesDeEstudioPorNivel($conn, 6);
$turnosCursos = obtenerTurnosCursos($conn);
$divisiones = obtenerDivisiones($conn);
$cursosPredeterminados = obtenerCursosPredeterminadosPorNivel($conn, 6);
$cursos = obtenerCursosPorNivel($conn, 6, $idCicloFiltro);
$referentesPorPlanPred = [];
foreach ($cursos as $curso) {
    if ((int)$curso['cursoPrincipal'] === 1) {
        $clave = (int)$curso['idPlanEstudio'] . '_' . (int)$curso['idcursopredeterminado'];
        $referentesPorPlanPred[$clave] = (int)$curso['idCurso'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parametros - Cursos</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
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
        <li class="breadcrumb-item">Instituto</li>
        <li class="breadcrumb-item">Parametros</li>
        <li class="breadcrumb-item active">Cursos</li>
    </ol>

    <h3 class="mb-3">Cursos</h3>

    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($tipoMensaje); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm p-4 mb-4">
        <h5 class="mb-3">Alta de curso</h5>
        <form method="POST" class="row g-3">
            <input type="hidden" name="action" value="create_course">
            <input type="hidden" name="idNivel" value="6">

            <div class="col-md-3">
                <label for="idciclo" class="form-label">Ciclo lectivo</label>
                <select class="form-select" id="idciclo" name="idciclo" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($ciclos as $ciclo): ?>
                        <option value="<?php echo (int)$ciclo['idciclolectivo']; ?>">
                            <?php echo htmlspecialchars((string)$ciclo['anio']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-5">
                <label for="idPlanEstudio" class="form-label">Plan de estudio</label>
                <select class="form-select" id="idPlanEstudio" name="idPlanEstudio" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($planes as $plan): ?>
                        <option value="<?php echo (int)$plan['idPlan']; ?>">
                            <?php echo htmlspecialchars($plan['nombre'] . ' (Nro ' . $plan['numero'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="idcursopredeterminado" class="form-label">Curso predeterminado</label>
                <select class="form-select curso-pred" id="idcursopredeterminado" name="idcursopredeterminado" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($cursosPredeterminados as $cursoPred): ?>
                        <option value="<?php echo (int)$cursoPred['idcursopredeterminado']; ?>">
                            <?php echo htmlspecialchars($cursoPred['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="idDivision" class="form-label">Division</label>
                <select class="form-select division" id="idDivision" name="idDivision" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($divisiones as $division): ?>
                        <option value="<?php echo (int)$division['idDivision']; ?>">
                            <?php echo htmlspecialchars($division['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Si la division es <strong>U</strong>, significa division unica.</div>
            </div>

            <div class="col-md-4">
                <label for="idTurnoCurso" class="form-label">Turno</label>
                <select class="form-select turno" id="idTurnoCurso" name="idTurnoCurso" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($turnosCursos as $turno): ?>
                        <option value="<?php echo (int)$turno['idTurnoCurso']; ?>">
                            <?php echo htmlspecialchars($turno['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="cursoPrincipal" name="cursoPrincipal" value="1">
                    <label class="form-check-label" for="cursoPrincipal">
                        Curso referente (principal)
                    </label>
                    <div class="form-text">Solo puede existir un referente por Plan + Curso predeterminado. El referente permite que otros cursos del mismo plan hereden su configuracion.</div>
                </div>
            </div>

            <div class="col-12">
                <div class="alert alert-light border mb-0">
                    Nombre autogenerado: <strong id="previewNombreCurso">-</strong>
                    <div class="small text-muted">Formato: NombrePredeterminado + NombreDivision + NombreTurno.</div>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Crear curso
                </button>
            </div>
        </form>
    </div>

    <div class="card shadow-sm p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
            <h5 class="mb-0">Listado de cursos</h5>
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label for="filtro_idciclo" class="form-label mb-0">Filtrar por ciclo lectivo</label>
                    <select class="form-select" id="filtro_idciclo" name="idciclo">
                        <option value="0">Todos</option>
                        <?php foreach ($ciclos as $ciclo): ?>
                            <option value="<?php echo (int)$ciclo['idciclolectivo']; ?>" <?php echo ($idCicloFiltro === (int)$ciclo['idciclolectivo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$ciclo['anio']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">Aplicar</button>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Ciclo</th>
                        <th>Plan</th>
                        <th>Predeterminado</th>
                        <th>Division</th>
                        <th>Turno</th>
                        <th>Nombre</th>
                        <th>Referente</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($cursos)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No hay cursos para el filtro seleccionado.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($cursos as $curso): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$curso['anioCiclo']); ?></td>
                        <td><?php echo htmlspecialchars($curso['nombrePlan']); ?></td>
                        <td><?php echo htmlspecialchars($curso['nombreCursoPredeterminado']); ?></td>
                        <td><?php echo htmlspecialchars($curso['nombreDivision']); ?></td>
                        <td><?php echo htmlspecialchars($curso['nombreTurno']); ?></td>
                        <td><?php echo htmlspecialchars($curso['nombre']); ?></td>
                        <td>
                            <?php if ((int)$curso['cursoPrincipal'] === 1): ?>
                                <span class="badge bg-success">Si</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">No</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editCursoModal"
                                data-idcurso="<?php echo (int)$curso['idCurso']; ?>"
                                data-idciclo="<?php echo (int)$curso['idciclo']; ?>"
                                data-idplanestudio="<?php echo (int)$curso['idPlanEstudio']; ?>"
                                data-idturnocurso="<?php echo (int)$curso['idTurnoCurso']; ?>"
                                data-iddivision="<?php echo (int)$curso['idDivision']; ?>"
                                data-idcursopredeterminado="<?php echo (int)$curso['idcursopredeterminado']; ?>"
                                data-cursoprincipal="<?php echo (int)$curso['cursoPrincipal']; ?>">
                                <i class="bi bi-pencil"></i>
                            </button>

                            <form method="POST" class="d-inline" onsubmit="return confirm('Confirma eliminar este curso?');">
                                <input type="hidden" name="action" value="delete_course">
                                <input type="hidden" name="idCurso" value="<?php echo (int)$curso['idCurso']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editCursoModal" tabindex="-1" aria-labelledby="editCursoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCursoModalLabel">Editar curso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_course">
                    <input type="hidden" name="idCurso" id="edit_idCurso">
                    <input type="hidden" name="idNivel" value="6">

                    <div class="mb-3">
                        <label for="edit_idciclo" class="form-label">Ciclo lectivo</label>
                        <select class="form-select" id="edit_idciclo" name="idciclo" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($ciclos as $ciclo): ?>
                                <option value="<?php echo (int)$ciclo['idciclolectivo']; ?>">
                                    <?php echo htmlspecialchars((string)$ciclo['anio']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_idPlanEstudio" class="form-label">Plan de estudio</label>
                        <select class="form-select" id="edit_idPlanEstudio" name="idPlanEstudio" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($planes as $plan): ?>
                                <option value="<?php echo (int)$plan['idPlan']; ?>">
                                    <?php echo htmlspecialchars($plan['nombre'] . ' (Nro ' . $plan['numero'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_idcursopredeterminado" class="form-label">Curso predeterminado</label>
                        <select class="form-select curso-pred" id="edit_idcursopredeterminado" name="idcursopredeterminado" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($cursosPredeterminados as $cursoPred): ?>
                                <option value="<?php echo (int)$cursoPred['idcursopredeterminado']; ?>">
                                    <?php echo htmlspecialchars($cursoPred['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_idDivision" class="form-label">Division</label>
                        <select class="form-select division" id="edit_idDivision" name="idDivision" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($divisiones as $division): ?>
                                <option value="<?php echo (int)$division['idDivision']; ?>">
                                    <?php echo htmlspecialchars($division['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Si la division es <strong>U</strong>, significa division unica.</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_idTurnoCurso" class="form-label">Turno</label>
                        <select class="form-select turno" id="edit_idTurnoCurso" name="idTurnoCurso" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($turnosCursos as $turno): ?>
                                <option value="<?php echo (int)$turno['idTurnoCurso']; ?>">
                                    <?php echo htmlspecialchars($turno['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_cursoPrincipal" name="cursoPrincipal" value="1">
                        <label class="form-check-label" for="edit_cursoPrincipal">Curso referente (principal)</label>
                        <div class="form-text">Solo puede existir un referente por Plan + Curso predeterminado. El referente permite heredar configuracion a otros cursos del mismo plan.</div>
                    </div>

                    <div class="alert alert-light border mb-0">
                        Nombre autogenerado: <strong id="previewNombreCursoEdit">-</strong>
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

<?php include '../funciones/footer.html'; ?>
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../funciones/sessionControl.js"></script>
<script>
const referentesPorPlanPred = <?php echo json_encode($referentesPorPlanPred, JSON_UNESCAPED_UNICODE); ?>;

function actualizarPreviewNombreCurso(prefix, targetId) {
    const pred = document.getElementById(prefix + 'idcursopredeterminado');
    const divi = document.getElementById(prefix + 'idDivision');
    const turno = document.getElementById(prefix + 'idTurnoCurso');
    const target = document.getElementById(targetId);
    if (!pred || !divi || !turno || !target) {
        return;
    }

    const predText = pred.options[pred.selectedIndex] ? pred.options[pred.selectedIndex].text.trim() : '';
    const divText = divi.options[divi.selectedIndex] ? divi.options[divi.selectedIndex].text.trim() : '';
    const turnoText = turno.options[turno.selectedIndex] ? turno.options[turno.selectedIndex].text.trim() : '';

    if (!predText || !divText || !turnoText || pred.value === '' || divi.value === '' || turno.value === '') {
        target.textContent = '-';
        return;
    }

    target.textContent = (predText + ' ' + divText + ' ' + turnoText).trim();
}

function validarSeleccionReferente(planId, cursoPredId, cursoIdActual, checkEl) {
    if (!checkEl || !checkEl.checked) {
        return true;
    }

    if (!planId || !cursoPredId) {
        return true;
    }

    const clave = String(planId) + '_' + String(cursoPredId);
    if (!Object.prototype.hasOwnProperty.call(referentesPorPlanPred, clave)) {
        return true;
    }

    const idExistente = parseInt(referentesPorPlanPred[clave], 10);
    const idActual = parseInt(cursoIdActual || 0, 10);
    if (idExistente > 0 && idExistente !== idActual) {
        checkEl.checked = false;
        alert('Ya existe un curso referente para ese plan y curso predeterminado.');
        return false;
    }

    return true;
}

document.addEventListener('DOMContentLoaded', function () {
    ['idcursopredeterminado', 'idDivision', 'idTurnoCurso'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', function () {
                actualizarPreviewNombreCurso('', 'previewNombreCurso');
            });
        }
    });

    const createPlan = document.getElementById('idPlanEstudio');
    const createPred = document.getElementById('idcursopredeterminado');
    const createPrincipal = document.getElementById('cursoPrincipal');
    if (createPrincipal && createPlan && createPred) {
        createPrincipal.addEventListener('change', function () {
            validarSeleccionReferente(createPlan.value, createPred.value, 0, createPrincipal);
        });
        createPlan.addEventListener('change', function () {
            validarSeleccionReferente(createPlan.value, createPred.value, 0, createPrincipal);
        });
        createPred.addEventListener('change', function () {
            validarSeleccionReferente(createPlan.value, createPred.value, 0, createPrincipal);
        });
    }

    const editModal = document.getElementById('editCursoModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) {
                return;
            }

            document.getElementById('edit_idCurso').value = button.getAttribute('data-idcurso') || '';
            document.getElementById('edit_idciclo').value = button.getAttribute('data-idciclo') || '';
            document.getElementById('edit_idPlanEstudio').value = button.getAttribute('data-idplanestudio') || '';
            document.getElementById('edit_idTurnoCurso').value = button.getAttribute('data-idturnocurso') || '';
            document.getElementById('edit_idDivision').value = button.getAttribute('data-iddivision') || '';
            document.getElementById('edit_idcursopredeterminado').value = button.getAttribute('data-idcursopredeterminado') || '';
            document.getElementById('edit_cursoPrincipal').checked = (button.getAttribute('data-cursoprincipal') || '0') === '1';

            actualizarPreviewNombreCurso('edit_', 'previewNombreCursoEdit');
        });

        ['edit_idcursopredeterminado', 'edit_idDivision', 'edit_idTurnoCurso'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', function () {
                    actualizarPreviewNombreCurso('edit_', 'previewNombreCursoEdit');
                });
            }
        });

        const editPlan = document.getElementById('edit_idPlanEstudio');
        const editPred = document.getElementById('edit_idcursopredeterminado');
        const editPrincipal = document.getElementById('edit_cursoPrincipal');
        const editIdCurso = document.getElementById('edit_idCurso');
        if (editPlan && editPred && editPrincipal && editIdCurso) {
            const validarEdit = function () {
                validarSeleccionReferente(editPlan.value, editPred.value, editIdCurso.value, editPrincipal);
            };
            editPrincipal.addEventListener('change', validarEdit);
            editPlan.addEventListener('change', validarEdit);
            editPred.addEventListener('change', validarEdit);
        }
    }
});
</script>
</body>
</html>
