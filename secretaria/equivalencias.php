<?php
// HABILITAR REPORTE DE ERRORES
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../funciones/verificarSesion.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';

// --- MANEJO DE SOLICITUDES AJAX PARA DROPDOWNS ---
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_GET['ajax'] == 'get_cursos' && isset($_GET['idPlan'])) {
        $idPlan = intval($_GET['idPlan']);
        $cursos = getCursosPorPlan($conn, $idPlan); // Usamos la nueva función
        echo json_encode($cursos);
        exit;
    }
    
    if ($_GET['ajax'] == 'get_materias' && isset($_GET['idCurso'])) {
        $idCurso = intval($_GET['idCurso']);
        $materias = getMateriasPorCurso($conn, $idCurso); // Usamos la nueva función
        echo json_encode($materias);
        exit;
    }
    
    // Fallback por si la solicitud AJAX no es reconocida
    echo json_encode(['error' => 'Solicitud no válida']);
    exit;
}

// --- LÓGICA PRINCIPAL DE LA PÁGINA ---

$message = '';
$message_type = '';

// 1. Obtener el idAlumno
if (!isset($_GET['idAlumno']) || empty($_GET['idAlumno'])) {
    die("Error: Alumno no especificado.");
}
$idAlumno = intval($_GET['idAlumno']);

// 2. Obtener datos del alumno para mostrar
$alumno = obtenerDatosBasicosAlumno($conn, $idAlumno);
if (!$alumno) {
    die("Error: No se encontró al alumno con ID " . $idAlumno);
}
$nombreCompletoAlumno = htmlspecialchars($alumno['apellido'] . ', ' . $alumno['nombre']);

// 3. Manejo de formularios POST (ADD, EDIT, DELETE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    $conn->begin_transaction();
    try {
        switch ($_POST['action']) {
            case 'add':
                $idMateria = intval($_POST['idMateria']);
                if (checkMatriculacionMateriaExiste($conn, $idAlumno, $idMateria)) {
                    throw new Exception("Este alumno ya posee una inscripción en la materia seleccionada.");
                }
                
                $data = [
                    'idAlumno'      => $idAlumno,
                    'idMateria'    => $idMateria,
                    'fecha'        => $_POST['fecha'],
                    'tipo'         => $_POST['tipo'],
                    'resolucion'   => $_POST['resolucion'],
                    'calificacion' => $_POST['calificacion'],
                    'procedencia'  => $_POST['procedencia']
                ];
                insertarEquivalencia($conn, $data);
                $message = "Equivalencia registrada con éxito.";
                $message_type = "success";
                break;

            case 'edit':
                $data = [
                    'idMatriculacionMateria' => intval($_POST['idMatriculacionMateria']),
                    'idResolucion'           => intval($_POST['idResolucion']),
                    'idCalificacion'         => intval($_POST['idCalificacion']),
                    'idMateria'              => intval($_POST['idMateria']),
                    'fecha'                  => $_POST['fecha'],
                    'tipo'                   => $_POST['tipo'],
                    'resolucion'             => $_POST['resolucion'],
                    'calificacion'           => $_POST['calificacion'],
                    'procedencia'            => $_POST['procedencia']
                ];
                actualizarEquivalencia($conn, $data);
                $message = "Equivalencia actualizada con éxito.";
                $message_type = "success";
                break;

            case 'delete':
                $idMatriculacionMateria = intval($_POST['idMatriculacionMateria']);
                eliminarEquivalencia($conn, $idMatriculacionMateria);
                $message = "Equivalencia eliminada con éxito.";
                $message_type = "success";
                break;
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}

// 4. Obtener datos para los formularios y la tabla
$planes = buscarTodosPlanes($conn);
$equivalencias = obtenerEquivalenciasAlumno($conn, $idAlumno);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Equivalencias - Secretaría</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/material/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <?php include '../funciones/menu_secretaria.php'; ?>

    <div class="container mt-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="buscarAlumno.php?origin=equivalencias">Buscar Alumno</a></li>
            <li class="breadcrumb-item active">Equivalencias</li>
        </ol>

        <h3 class="mb-3">Gestión de Equivalencias</h3>
        <h4>Alumno: <?php echo $nombreCompletoAlumno; ?> (ID: <?php echo $idAlumno; ?>)</h4>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card p-4 mb-4 shadow-sm">
            <h5 class="mb-3">Registrar Nueva Equivalencia</h5>
            <form id="addForm" method="POST" action="equivalencias.php?idAlumno=<?php echo $idAlumno; ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="add_idPlan" class="form-label">Plan de Estudio</label>
                        <select class="form-select" id="add_idPlan" name="idPlan" required>
                            <option value="" selected>Seleccione un Plan...</option>
                            <?php foreach ($planes as $plan): ?>
                                <option value="<?php echo $plan['idPlan']; ?>"><?php echo htmlspecialchars($plan['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="add_idCurso" class="form-label">Curso</label>
                        <select class="form-select" id="add_idCurso" name="idCurso" required disabled>
                            <option value="" selected>Seleccione un Curso...</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="add_idMateria" class="form-label">Materia</label>
                        <select class="form-select" id="add_idMateria" name="idMateria" required disabled>
                            <option value="" selected>Seleccione una Materia...</option>
                        </select>
                    </div>
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label for="add_fecha" class="form-label">Fecha</label>
                        <input type="date" class="form-control" id="add_fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="add_tipo" class="form-label">Tipo</label>
                        <select class="form-select" id="add_tipo" name="tipo" required>
                            <option value="Aprobación por Equivalencia" selected>Aprobación por Equivalencia</option>
                            <option value="Aprobación por Pase">Aprobación por Pase</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="add_calificacion" class="form-label">Calificación</label>
                        <input type="text" class="form-control" id="add_calificacion" name="calificacion" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="add_resolucion" class="form-label">Resolución</label>
                        <input type="text" class="form-control" id="add_resolucion" name="resolucion" placeholder="Ej: R.D. 123/24" required>
                    </div>
                    <div class="col-md-6">
                        <label for="add_procedencia" class="form-label">Procedencia</label>
                        <input type="text" class="form-control" id="add_procedencia" name="procedencia" placeholder="Ej: Universidad Nacional de..." required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Registrar Equivalencia
                </button>
            </form>
        </div>

        <div class="card p-4 mb-4 shadow-sm">
            <h5 class="mb-3">Historial de Equivalencias</h5>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Curso</th>
                            <th>Materia</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Calific.</th>
                            <th>Resolución</th>
                            <th>Procedencia</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($equivalencias)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No hay equivalencias registradas para este alumno.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($equivalencias as $eq): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($eq['nombrePlan']); ?></td>
                            <td><?php echo htmlspecialchars($eq['nombreCurso']); ?></td>
                            <td><?php echo htmlspecialchars($eq['nombreMateria']); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($eq['fechaMatriculacion']))); ?></td>
                            <td><?php echo htmlspecialchars($eq['estado']); ?></td>
                            <td><?php echo htmlspecialchars($eq['calificacion']); ?></td>
                            <td><?php echo htmlspecialchars($eq['nombreResolucion']); ?></td>
                            <td><?php echo htmlspecialchars($eq['procedencia']); ?></td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editModal"
                                        data-idmatriculacion="<?php echo $eq['idMatriculacionMateria']; ?>"
                                        data-idresolucion="<?php echo $eq['idResolucion']; ?>"
                                        data-idcalificacion="<?php echo $eq['idCalificacion']; ?>"
                                        data-idplan="<?php echo $eq['idPlan']; ?>"
                                        data-idcurso="<?php echo $eq['idCurso']; ?>"
                                        data-idmateria="<?php echo $eq['idMateria']; ?>"
                                        data-fecha="<?php echo $eq['fechaMatriculacion']; ?>"
                                        data-tipo="<?php echo htmlspecialchars($eq['estado']); ?>"
                                        data-calificacion="<?php echo htmlspecialchars($eq['calificacion']); ?>"
                                        data-resolucion="<?php echo htmlspecialchars(str_replace(' ' . $eq['calificacion'], '', $eq['nombreResolucion'])); // Quita la calificación del nombre de resolución ?>"
                                        data-procedencia="<?php echo htmlspecialchars($eq['procedencia']); ?>"
                                        title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal"
                                        data-idmatriculacion="<?php echo $eq['idMatriculacionMateria']; ?>"
                                        data-materia="<?php echo htmlspecialchars($eq['nombreMateria']); ?>"
                                        title="Eliminar">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar la equivalencia para la materia <strong id="delete_materia_nombre"></strong>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer y eliminará los registros de las 3 tablas asociadas.</p>
                </div>
                <div class="modal-footer">
                    <form id="deleteForm" method="POST" action="equivalencias.php?idAlumno=<?php echo $idAlumno; ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="idMatriculacionMateria" id="delete_idMatriculacionMateria">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Editar Equivalencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST" action="equivalencias.php?idAlumno=<?php echo $idAlumno; ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="idMatriculacionMateria" id="edit_idMatriculacionMateria">
                        <input type="hidden" name="idResolucion" id="edit_idResolucion">
                        <input type="hidden" name="idCalificacion" id="edit_idCalificacion">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="edit_idPlan" class="form-label">Plan de Estudio</label>
                                <select class="form-select" id="edit_idPlan" name="idPlan" required>
                                    <option value="" selected>Seleccione un Plan...</option>
                                    <?php foreach ($planes as $plan): ?>
                                        <option value="<?php echo $plan['idPlan']; ?>"><?php echo htmlspecialchars($plan['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_idCurso" class="form-label">Curso</label>
                                <select class="form-select" id="edit_idCurso" name="idCurso" required>
                                    <option value="" selected>Cargando...</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_idMateria" class="form-label">Materia</label>
                                <select class="form-select" id="edit_idMateria" name="idMateria" required>
                                    <option value="" selected>Cargando...</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label for="edit_fecha" class="form-label">Fecha</label>
                                <input type="date" class="form-control" id="edit_fecha" name="fecha" required>
                            </div>
                            <div class="col-md-3">
                                <label for="edit_tipo" class="form-label">Tipo</label>
                                <select class="form-select" id="edit_tipo" name="tipo" required>
                                    <option value="Aprobación por Equivalencia">Aprobación por Equivalencia</option>
                                    <option value="Aprobación por Pase">Aprobación por Pase</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="edit_calificacion" class="form-label">Calificación</label>
                                <input type="text" class="form-control" id="edit_calificacion" name="calificacion" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="edit_resolucion" class="form-label">Resolución</label>
                                <input type="text" class="form-control" id="edit_resolucion" name="resolucion" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_procedencia" class="form-label">Procedencia</label>
                                <input type="text" class="form-control" id="edit_procedencia" name="procedencia" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="editForm" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../funciones/footer.html'; ?>

    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../funciones/sessionControl.js"></script>

    <script>
        // Función para cargar Cursos
        function cargarCursos(idPlan, selectCurso, idCursoSeleccionado = null) {
            const url = 'equivalencias.php?ajax=get_cursos&idPlan=' + idPlan;
            $(selectCurso).prop('disabled', true).html('<option value="">Cargando...</option>');
            
            $.getJSON(url, function(data) {
                $(selectCurso).prop('disabled', false).html('<option value="">Seleccione un Curso...</option>');
                $.each(data, function(key, entry) {
                    $(selectCurso).append($('<option></option>').attr('value', entry.idCurso).text(entry.nombre));
                });
                if (idCursoSeleccionado) {
                    $(selectCurso).val(idCursoSeleccionado);
                }
            }).fail(function() {
                alert('Error al cargar cursos.');
                $(selectCurso).prop('disabled', false).html('<option value="">Error</option>');
            });
        }

        // Función para cargar Materias
        function cargarMaterias(idCurso, selectMateria, idMateriaSeleccionada = null) {
            const url = 'equivalencias.php?ajax=get_materias&idCurso=' + idCurso;
            $(selectMateria).prop('disabled', true).html('<option value="">Cargando...</option>');
            
            $.getJSON(url, function(data) {
                $(selectMateria).prop('disabled', false).html('<option value="">Seleccione una Materia...</option>');
                $.each(data, function(key, entry) {
                    $(selectMateria).append($('<option></option>').attr('value', entry.idMateria).text(entry.nombre));
                });
                if (idMateriaSeleccionada) {
                    $(selectMateria).val(idMateriaSeleccionada);
                }
            }).fail(function() {
                alert('Error al cargar materias.');
                $(selectMateria).prop('disabled', false).html('<option value="">Error</option>');
            });
        }

        $(document).ready(function() {
            // --- Lógica para formulario 'ADD' ---
            $('#add_idPlan').on('change', function() {
                var idPlan = $(this).val();
                if (idPlan) {
                    cargarCursos(idPlan, '#add_idCurso');
                } else {
                    $('#add_idCurso').prop('disabled', true).html('<option value="">Seleccione un Curso...</option>');
                }
                $('#add_idMateria').prop('disabled', true).html('<option value="">Seleccione una Materia...</option>');
            });

            $('#add_idCurso').on('change', function() {
                var idCurso = $(this).val();
                if (idCurso) {
                    cargarMaterias(idCurso, '#add_idMateria');
                } else {
                    $('#add_idMateria').prop('disabled', true).html('<option value="">Seleccione una Materia...</option>');
                }
            });

            // --- Lógica para formulario 'EDIT' (en el modal) ---
            $('#edit_idPlan').on('change', function() {
                var idPlan = $(this).val();
                if (idPlan) {
                    cargarCursos(idPlan, '#edit_idCurso');
                } else {
                    $('#edit_idCurso').prop('disabled', true).html('<option value="">Seleccione un Curso...</option>');
                }
                $('#edit_idMateria').prop('disabled', true).html('<option value="">Seleccione una Materia...</option>');
            });

            $('#edit_idCurso').on('change', function() {
                var idCurso = $(this).val();
                if (idCurso) {
                    cargarMaterias(idCurso, '#edit_idMateria');
                } else {
                    $('#edit_idMateria').prop('disabled', true).html('<option value="">Seleccione una Materia...</option>');
                }
            });


            // --- Lógica para poblar modales ---
            $('#deleteModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var idMatriculacion = button.data('idmatriculacion');
                var nombreMateria = button.data('materia');

                var modal = $(this);
                modal.find('#delete_idMatriculacionMateria').val(idMatriculacion);
                modal.find('#delete_materia_nombre').text(nombreMateria);
            });

            $('#editModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                
                // Extraer todos los datos
                var idMatriculacion = button.data('idmatriculacion');
                var idResolucion = button.data('idresolucion');
                var idCalificacion = button.data('idcalificacion');
                var idPlan = button.data('idplan');
                var idCurso = button.data('idcurso');
                var idMateria = button.data('idmateria');
                var fecha = button.data('fecha');
                var tipo = button.data('tipo');
                var calificacion = button.data('calificacion');
                var resolucion = button.data('resolucion');
                var procedencia = button.data('procedencia');

                // Poblar campos del modal
                var modal = $(this);
                modal.find('#edit_idMatriculacionMateria').val(idMatriculacion);
                modal.find('#edit_idResolucion').val(idResolucion);
                modal.find('#edit_idCalificacion').val(idCalificacion);
                modal.find('#edit_fecha').val(fecha);
                modal.find('#edit_tipo').val(tipo);
                modal.find('#edit_calificacion').val(calificacion);
                modal.find('#edit_resolucion').val(resolucion);
                modal.find('#edit_procedencia').val(procedencia);

                // --- Cascada de Dropdowns para Edit ---
                modal.find('#edit_idPlan').val(idPlan);

                // 1. Cargar Cursos
                var selectCurso = '#edit_idCurso';
                const urlCursos = 'equivalencias.php?ajax=get_cursos&idPlan=' + idPlan;
                $(selectCurso).prop('disabled', true).html('<option value="">Cargando...</option>');
                
                $.getJSON(urlCursos, function(dataCursos) {
                    $(selectCurso).prop('disabled', false).html('<option value="">Seleccione un Curso...</option>');
                    $.each(dataCursos, function(key, entry) {
                        $(selectCurso).append($('<option></option>').attr('value', entry.idCurso).text(entry.nombre));
                    });
                    // Seleccionar el curso
                    $(selectCurso).val(idCurso);

                    // 2. Cargar Materias (basado en el curso ya seleccionado)
                    var selectMateria = '#edit_idMateria';
                    const urlMaterias = 'equivalencias.php?ajax=get_materias&idCurso=' + idCurso;
                    $(selectMateria).prop('disabled', true).html('<option value="">Cargando...</option>');
                    
                    $.getJSON(urlMaterias, function(dataMaterias) {
                        $(selectMateria).prop('disabled', false).html('<option value="">Seleccione una Materia...</option>');
                        $.each(dataMaterias, function(key, entry) {
                            $(selectMateria).append($('<option></option>').attr('value', entry.idMateria).text(entry.nombre));
                        });
                        // Seleccionar la materia
                        $(selectMateria).val(idMateria);

                    }).fail(function() { alert('Error al cargar materias para editar.'); });

                }).fail(function() { alert('Error al cargar cursos para editar.'); });
            });

        });
    </script>
</body>
</html>