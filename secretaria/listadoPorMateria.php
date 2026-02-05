<?php
// Incluir el script de verificación de sesión
include '../funciones/verificarSesion.php';

// Habilitar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir la conexión a la base de datos y consultas
include '../inicio/conexion.php';
include '../funciones/consultas.php';

// Obtener datos para los selectores
$ciclosLectivos = levantarCiclosLectivos($conn);
$planes = buscarTodosPlanes($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listados por materia - Secretaría</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/material/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <?php include '../funciones/menu_secretaria.php'; ?>

    <div class="container-fluid fondo">
        <br>
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
                <li class="breadcrumb-item active">Listados por materia</li>
            </ol>

            <div class="card p-4">
                <h5>Generar Listado de alumnos por Mmteria</h5>
                <p>Seleccione los filtros y el formato para generar el reporte.</p>

                <form id="formListadoMateria" method="GET" target="_blank">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="cicloLectivo" class="form-label">Ciclo lectivo <span class="text-danger">*</span></label>
                            <select class="form-select" id="cicloLectivo" name="idCicloLectivo" required>
                                <option value="">Seleccione un ciclo</option>
                                <?php foreach ($ciclosLectivos as $ciclo): ?>
                                    <option value="<?php echo $ciclo['idCicloLectivo']; ?>"><?php echo $ciclo['anio']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="planEstudio" class="form-label">Plan de estudio <span class="text-danger">*</span></label>
                            <select class="form-select" id="planEstudio" name="idPlan" required>
                                <option value="">Seleccione un plan</option>
                                <?php foreach ($planes as $plan): ?>
                                    <option value="<?php echo $plan['idPlan']; ?>"><?php echo $plan['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="curso" class="form-label">Curso <span class="text-danger">*</span></label>
                            <select class="form-select" id="curso" name="idCurso" required disabled>
                                <option value="">Seleccione plan y ciclo</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="materia" class="form-label">Materia <span class="text-danger">*</span></label>
                            <select class="form-select" id="materia" name="idMateria" required disabled>
                                <option value="">Seleccione un curso</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6>Seleccione el formato del reporte:</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="formato" id="formatoRenglon" value="renglon" checked>
                            <label class="form-check-label" for="formatoRenglon">
                                Con renglón
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="formato" id="formatoCuadriculado" value="cuadriculado">
                            <label class="form-check-label" for="formatoCuadriculado">
                                Con cuadriculado
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="formato" id="formatoDni" value="dni">
                            <label class="form-check-label" for="formatoDni">
                                Con DNI
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-4">
                        <i class="bi bi-printer"></i> Imprimir
                    </button>
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
    $(document).ready(function() {
        function cargarCursos() {
            var idPlan = $('#planEstudio').val();
            var idCicloLectivo = $('#cicloLectivo').val();
            var cursoSelect = $('#curso');
            var materiaSelect = $('#materia');

            materiaSelect.prop('disabled', true).html('<option value="">Seleccione un curso</option>'); // Resetear materia

            if (idPlan && idCicloLectivo) {
                cursoSelect.prop('disabled', false).html('<option value="">Cargando...</option>');
                $.ajax({
                    url: '../secretaria/inscripcionMateria.php',
                    type: 'GET',
                    data: {
                        ajax_action: 'get_cursos',
                        idPlan: idPlan,
                        anio: $('#cicloLectivo option:selected').text()
                    },
                    dataType: 'json',
                    success: function(cursos) {
                        cursoSelect.empty().append('<option value="">Seleccione un curso</option>');
                        if (cursos.length > 0) {
                            $.each(cursos, function(i, curso) {
                                cursoSelect.append('<option value="' + curso.idCurso + '">' + curso.nombre + '</option>');
                            });
                        } else {
                            cursoSelect.html('<option value="">No hay cursos</option>');
                        }
                    },
                    error: function() {
                        cursoSelect.prop('disabled', true).html('<option value="">Error</option>');
                    }
                });
            } else {
                cursoSelect.prop('disabled', true).html('<option value="">Seleccione plan y ciclo</option>');
            }
        }
        
        function cargarMaterias() {
            var idCurso = $('#curso').val();
            var idPlan = $('#planEstudio').val();
            var materiaSelect = $('#materia');
            
            if (idCurso && idPlan) {
                materiaSelect.prop('disabled', false).html('<option value="">Cargando...</option>');
                $.ajax({
                    url: '../secretaria/inscripcionMateria.php',
                    type: 'GET',
                    data: {
                        ajax_action: 'get_materias',
                        idPlan: idPlan,
                        idCurso: idCurso
                    },
                    dataType: 'json',
                    success: function(materias) {
                        materiaSelect.empty().append('<option value="">Seleccione una materia</option>');
                        if (materias.length > 0) {
                            $.each(materias, function(i, materia) {
                                materiaSelect.append('<option value="' + materia.idMateria + '">' + materia.nombreMateria + '</option>');
                            });
                        } else {
                            materiaSelect.html('<option value="">No hay materias</option>');
                        }
                    },
                    error: function() {
                        materiaSelect.prop('disabled', true).html('<option value="">Error</option>');
                    }
                });
            } else {
                 materiaSelect.prop('disabled', true).html('<option value="">Seleccione un curso</option>');
            }
        }

        $('#planEstudio, #cicloLectivo').on('change', cargarCursos);
        $('#curso').on('change', cargarMaterias);
        
        $('#formListadoMateria').on('submit', function(e) {
            var formato = $('input[name="formato"]:checked').val();
            if(formato === 'renglon') {
                $(this).attr('action', '../reportes/listadoMateriaRenglonPDF.php');
            } else if (formato === 'cuadriculado') {
                $(this).attr('action', '../reportes/listadoMateriaCuadriculadoPDF.php');
            } else if (formato === 'dni') {
                $(this).attr('action', '../reportes/listadoMateriaDniPDF.php');
            }
        });
    });
    </script>
</body>
</html>