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
$cursadosValidos = ['Cuatrimestral', 'Anual', 'Semestral', 'Trimestral'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $idNivel = 6; // Fijo por requerimiento (no visible para el usuario).

    if ($action === 'create_plan') {
        $numero = trim($_POST['numero'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $cursado = trim($_POST['cursado'] ?? '');
        $resolucion = trim($_POST['resolucion'] ?? '');
        $resolucion = ($resolucion === '') ? null : $resolucion;

        if ($numero === '' || $nombre === '') {
            $mensaje = 'Complete numero de orden y nombre.';
            $tipoMensaje = 'warning';
        } elseif (!in_array($cursado, $cursadosValidos, true)) {
            $mensaje = 'El valor de cursado no es valido.';
            $tipoMensaje = 'warning';
        } else {
            $ok = crearPlanDeEstudio($conn, $numero, $nombre, $cursado, $resolucion, $idNivel);
            if ($ok) {
                $mensaje = 'Plan de estudio creado correctamente.';
                $tipoMensaje = 'success';
            } else {
                $mensaje = 'No se pudo crear el plan de estudio.';
                $tipoMensaje = 'danger';
            }
        }
    }

    if ($action === 'update_plan') {
        $idPlan = (int)($_POST['idPlan'] ?? 0);
        $numero = trim($_POST['numero'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $cursado = trim($_POST['cursado'] ?? '');
        $resolucion = trim($_POST['resolucion'] ?? '');
        $resolucion = ($resolucion === '') ? null : $resolucion;

        if ($idPlan <= 0 || $numero === '' || $nombre === '') {
            $mensaje = 'Datos invalidos para actualizar.';
            $tipoMensaje = 'warning';
        } elseif (!in_array($cursado, $cursadosValidos, true)) {
            $mensaje = 'El valor de cursado no es valido.';
            $tipoMensaje = 'warning';
        } else {
            $ok = actualizarPlanDeEstudio($conn, $idPlan, $numero, $nombre, $cursado, $resolucion, $idNivel);
            if ($ok) {
                $mensaje = 'Plan de estudio actualizado correctamente.';
                $tipoMensaje = 'success';
            } else {
                $mensaje = 'No se pudo actualizar el plan de estudio.';
                $tipoMensaje = 'danger';
            }
        }
    }

    if ($action === 'delete_plan') {
        $idPlan = (int)($_POST['idPlan'] ?? 0);
        if ($idPlan <= 0) {
            $mensaje = 'ID invalido para eliminar.';
            $tipoMensaje = 'warning';
        } elseif (planDeEstudioTieneCursos($conn, $idPlan)) {
            $mensaje = 'No se puede eliminar el plan porque tiene cursos creados.';
            $tipoMensaje = 'warning';
        } else {
            $ok = eliminarPlanDeEstudio($conn, $idPlan);
            if ($ok) {
                $mensaje = 'Plan de estudio eliminado correctamente.';
                $tipoMensaje = 'success';
            } else {
                $mensaje = 'No se pudo eliminar el plan de estudio.';
                $tipoMensaje = 'danger';
            }
        }
    }
}

$planes = obtenerPlanesDeEstudioPorNivel($conn, 6);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parametros - Planes de estudio</title>
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
        <li class="breadcrumb-item active">Planes de estudio</li>
    </ol>

    <h3 class="mb-3">Planes de Estudio</h3>

    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($tipoMensaje); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm p-4 mb-4">
        <h5 class="mb-3">Alta de plan de estudio</h5>
        <form method="POST" class="row g-3">
            <input type="hidden" name="action" value="create_plan">
            <input type="hidden" name="idNivel" value="6">

            <div class="col-md-3">
                <label for="numero" class="form-label">Numero de orden</label>
                <input type="number" min="1" step="1" class="form-control" id="numero" name="numero" required>
            </div>

            <div class="col-md-5">
                <label for="nombre" class="form-label">Nombre del plan</label>
                <input type="text" class="form-control" id="nombre" name="nombre" maxlength="100" required>
            </div>

            <div class="col-md-4">
                <label for="cursado" class="form-label">Cursado</label>
                <select class="form-select" id="cursado" name="cursado" required>
                    <option value="Cuatrimestral">Cuatrimestral</option>
                    <option value="Anual">Anual</option>
                    <option value="Semestral">Semestral</option>
                    <option value="Trimestral">Trimestral</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="resolucion" class="form-label">Resolucion (opcional)</label>
                <input type="text" class="form-control" id="resolucion" name="resolucion" maxlength="50">
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Crear plan
                </button>
            </div>
        </form>
    </div>

    <div class="card shadow-sm p-4">
        <h5 class="mb-3">Listado de planes</h5>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Numero</th>
                    <th>Nombre</th>
                    <th>Cursado</th>
                    <th>Resolucion</th>
                    <th class="text-center">Reg. Nuevo</th>
                    <th class="text-center">Reg. Modif.</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($planes)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No hay planes cargados para nivel 6.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($planes as $plan): ?>
                    <tr>
                        <td><?php echo (int)$plan['idPlan']; ?></td>
                        <td><?php echo htmlspecialchars($plan['numero']); ?></td>
                        <td><?php echo htmlspecialchars($plan['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($plan['cursado']); ?></td>
                        <td><?php echo htmlspecialchars($plan['resolucion'] ?? ''); ?></td>
                        <td class="text-center"><?php echo (int)$plan['registroNuevo']; ?></td>
                        <td class="text-center"><?php echo (int)$plan['registroModificacion']; ?></td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editPlanModal"
                                data-idplan="<?php echo (int)$plan['idPlan']; ?>"
                                data-numero="<?php echo htmlspecialchars($plan['numero']); ?>"
                                data-nombre="<?php echo htmlspecialchars($plan['nombre']); ?>"
                                data-cursado="<?php echo htmlspecialchars($plan['cursado']); ?>"
                                data-resolucion="<?php echo htmlspecialchars($plan['resolucion'] ?? ''); ?>">
                                <i class="bi bi-pencil"></i>
                            </button>

                            <form method="POST" class="d-inline" onsubmit="return confirm('Confirma eliminar este plan de estudio?');">
                                <input type="hidden" name="action" value="delete_plan">
                                <input type="hidden" name="idPlan" value="<?php echo (int)$plan['idPlan']; ?>">
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

<div class="modal fade" id="editPlanModal" tabindex="-1" aria-labelledby="editPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPlanModalLabel">Editar plan de estudio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_plan">
                    <input type="hidden" name="idPlan" id="edit_idPlan">
                    <input type="hidden" name="idNivel" value="6">

                    <div class="mb-3">
                        <label for="edit_numero" class="form-label">Numero de orden</label>
                        <input type="number" min="1" step="1" class="form-control" id="edit_numero" name="numero" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_nombre" class="form-label">Nombre del plan</label>
                        <input type="text" class="form-control" id="edit_nombre" name="nombre" maxlength="100" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_cursado" class="form-label">Cursado</label>
                        <select class="form-select" id="edit_cursado" name="cursado" required>
                            <option value="Cuatrimestral">Cuatrimestral</option>
                            <option value="Anual">Anual</option>
                            <option value="Semestral">Semestral</option>
                            <option value="Trimestral">Trimestral</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_resolucion" class="form-label">Resolucion (opcional)</label>
                        <input type="text" class="form-control" id="edit_resolucion" name="resolucion" maxlength="50">
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
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('editPlanModal');
    if (!modalEl) {
        return;
    }

    modalEl.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) {
            return;
        }

        document.getElementById('edit_idPlan').value = button.getAttribute('data-idplan') || '';
        document.getElementById('edit_numero').value = button.getAttribute('data-numero') || '';
        document.getElementById('edit_nombre').value = button.getAttribute('data-nombre') || '';
        document.getElementById('edit_cursado').value = button.getAttribute('data-cursado') || 'Cuatrimestral';
        document.getElementById('edit_resolucion').value = button.getAttribute('data-resolucion') || '';
    });
});
</script>
</body>
</html>
