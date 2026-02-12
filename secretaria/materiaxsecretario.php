<?php
session_start();

// Redirigir al login si el usuario no está autenticado como secretario
if (!isset($_SESSION['sec_nombreUsuario'])) {
    header('Location: loginAdmin.php');
    exit;
}
include '../funciones/verificarSesion.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';

$sec_nombre = $_SESSION['sec_nombreUsuario'];

// Determinar el tipo de acción (calificaciones o asistencias)
$action_type = $_GET['action'] ?? null;
if (!in_array($action_type, ['calificaciones', 'asistencias'])) {
    $_SESSION['login_message'] = "Acción no especificada para la selección de materia.";
    header('Location: menusecretaria.php');
    exit;
}

$page_title_suffix = ($action_type == 'calificaciones') ? "Carga de calificaciones" : "Carga de asistencias";
$url_target_page = ($action_type == 'calificaciones') ? 'carga_calif_secretaria.php' : 'carga_asist_secretaria.php';

// Obtener el ciclo lectivo por defecto
$primerCicloLectivoAnio = $datosColegio[0]['anioCargaNotas'] ?? date('Y');
$primerCicloLectivoId = buscarIdCiclo($conn, $primerCicloLectivoAnio);

// Obtener todos los planes (carreras)
$planes = buscarTodosPlanes($conn); // Usar buscarTodosPlanes para secretaría
$primerPlanId = null;
$primerPlanNombre = "No hay planes disponibles";
if (!empty($planes)) {
    $primerPlanId = $planes[0]['idPlan'];
    $primerPlanNombre = $planes[0]['nombre'];
}


// --- Manejo de la petición AJAX ---

// Petición AJAX para cargar cursos (cuando cambian Ciclo o Plan)
// Se usa 'is_ajax_courses' para diferenciarla de la petición para cargar materias
if (isset($_POST['is_ajax_courses']) && $_POST['is_ajax_courses'] == '1') {
    $ciclolectivo_id_ajax = $_POST['ciclolectivo_id'];
    $plan_id_ajax = $_POST['plan_id'];

    $cursos_filtrados = buscarCursosPlanCiclo($conn, $plan_id_ajax, $ciclolectivo_id_ajax);

    $output = ''; // No iniciar con "Todos los Cursos"
    if (!empty($cursos_filtrados)) {
        foreach ($cursos_filtrados as $index => $curso) {
            $selected = ($index === 0) ? 'selected' : ''; // Seleccionar el primer curso
            $output .= '<option value="' . htmlspecialchars($curso['idCurso']) . '" ' . $selected . '>' . htmlspecialchars($curso['nombre']) . '</option>';
        }
    } else {
        $output .= '<option value="">Sin cursos</option>'; // Opción si no hay cursos para ese plan/ciclo
    }
    echo $output;
    exit; // Terminar la ejecución para la llamada AJAX
}


// Petición AJAX para cargar materias (cuando cambian Ciclo, Plan o Curso)
if (isset($_POST['is_ajax_materias']) && $_POST['is_ajax_materias'] == '1') {
    $ciclolectivo_id_ajax = $_POST['ciclolectivo_id'];
    $plan_id_ajax = $_POST['plan_id'];
    $curso_id_ajax = $_POST['curso_id'] ?? null; // Obtener el ID del curso seleccionado del filtro

    // Asegurarse de que si curso_id_ajax es "Sin Cursos", se trate como nulo para el filtro
    if ($curso_id_ajax === '') {
        $curso_id_ajax = null;
    }

    $materias_filtradas = obtenerTodasLasMaterias($conn, $ciclolectivo_id_ajax, $plan_id_ajax, $curso_id_ajax);

    // Devolver solo el tbody de la tabla de materias en formato HTML
    $output = '';
    if (empty($materias_filtradas)) {
        $output .= '<tr><td colspan="3">Sin registros</td></tr>';
    } else {
        foreach ($materias_filtradas as $materia) {
            // Asegurarse de que los nombres de las materias y cursos estén correctamente escapados para JS
            $materia_js = htmlspecialchars(addslashes($materia['Materia']));
            $curso_js = htmlspecialchars(addslashes($materia['Curso']));
            $output .= '<tr>
                <td><a href="#" onclick="setMateria(' . htmlspecialchars($materia['idMateria']) . ', \'' . $materia_js . '\', \'' . $curso_js . '\', \'' . htmlspecialchars($materia['idCicloLectivo']) . '\', \'' . htmlspecialchars($materia['idPlan']) . '\', \'' . htmlspecialchars($url_target_page) . '\')">' . htmlspecialchars($materia['Materia']) . '</a></td>
                <td>' . htmlspecialchars($materia['Curso']) . '</td>
            </tr>';
        }
    }
    echo $output;
    exit; // Terminar la ejecución para la llamada AJAX
}
// --- Fin del manejo AJAX ---


// Carga inicial de materias y cursos al cargar la página (no AJAX)
$materiasAsignadas = [];
$cursosDisponibles = [];
$selected_initial_curso_id = null; // Para seleccionar el primer curso en la carga inicial

if ($primerCicloLectivoId && $primerPlanId) {
    $cursosDisponibles = buscarCursosPlanCiclo($conn, $primerPlanId, $primerCicloLectivoId);
    if (!empty($cursosDisponibles)) {
        $selected_initial_curso_id = $cursosDisponibles[0]['idCurso'];
    }
    // Cargar materias con el curso preseleccionado si hay alguno, de lo contrario, todas las del plan/ciclo
    $materiasAsignadas = obtenerTodasLasMaterias($conn, $primerCicloLectivoId, $primerPlanId, $selected_initial_curso_id);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Materias - Secretaría (<?php echo htmlspecialchars($page_title_suffix); ?>)</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .form-group-filter {
      margin-bottom: 1rem;
    }
  </style>
<link rel="icon" type="image/png" href="../img/icon.png">

</head>
<body>
<?php include '../funciones/menu_secretaria.php'; // Incluimos el menú de secretaría ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
      <li class="breadcrumb-item active"><?php echo htmlspecialchars($page_title_suffix); ?></li>
    </ol>

    <div class="card padding col-12">
      <h5><?php echo "Usuario: " . htmlspecialchars($sec_nombre); ?></h5>
      <br>
      <!-- Filtros -->
      <div class="row">
        <div class="col-12 col-md-4 form-group-filter">
          <label for="ciclolectivo_select">Ciclo lectivo:</label>
          <select name="ciclolectivo_select" class="form-select" id="ciclolectivo_select">
             <?php
                $ciclolectivos = levantarCiclosLectivos($conn); // Obtiene todos los ciclos
                foreach ($ciclolectivos as $cl) {
                    $selected = ($cl['idCicloLectivo'] == $primerCicloLectivoId) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($cl['idCicloLectivo']) . '" ' . $selected . '>' . htmlspecialchars($cl['anio']) . '</option>';
                }
            ?>
          </select>
        </div>
        <div class="col-12 col-md-4 form-group-filter">
          <label for="plan_select">Carrera:</label>
          <select name="plan_select" class="form-select" id="plan_select">
            <?php
                foreach ($planes as $plan) {
                    $selected = ($plan['idPlan'] == $primerPlanId) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($plan['idPlan']) . '" ' . $selected . '>' . htmlspecialchars($plan['nombre']) . '</option>';
                }
            ?>
          </select>
        </div>
        <div class="col-12 col-md-4 form-group-filter">
          <label for="curso_select">Curso:</label>
          <select name="curso_select" class="form-select" id="curso_select">
            <?php
                if (!empty($cursosDisponibles)) {
                    foreach ($cursosDisponibles as $curso) {
                        // Seleccionamos el primer curso como predeterminado si no hay ninguno seleccionado
                        $selected = ($curso['idCurso'] == $selected_initial_curso_id) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($curso['idCurso']) . '" ' . $selected . '>' . htmlspecialchars($curso['nombre']) . '</option>';
                    }
                } else {
                    echo '<option value="">Sin cursos</option>'; // Opción si no hay cursos
                }
            ?>
          </select>
        </div>
      </div>
    </div>
    <br>

    <div>
       <table id="tablaMaterias" class="table table-hover col-12">
           <thead>
               <tr class="table-primary">
                   <th scope="col">Materias</th>
                   <th scope="col">Curso</th>
               </tr>
           </thead>
           <tbody>
           <?php if (empty($materiasAsignadas)) { ?>
               <tr><td colspan="2">Sin registros</td></tr>
           <?php } else {
               foreach ($materiasAsignadas as $materia) {
                   // Asegurarse de que los nombres de las materias y cursos estén correctamente escapados para JS
                   $materia_js = htmlspecialchars(addslashes($materia['Materia']));
                   $curso_js = htmlspecialchars(addslashes($materia['Curso']));
                   ?>
                   <tr>
                       <!-- CORRECCIÓN DE LA COMA: Ahora los parámetros están correctamente separados -->
                       <td><a href="#" onclick="setMateria(<?php echo htmlspecialchars($materia['idMateria']); ?>, '<?php echo $materia_js; ?>', '<?php echo $curso_js; ?>', '<?php echo htmlspecialchars($materia['idCicloLectivo']); ?>', '<?php echo htmlspecialchars($materia['idPlan']); ?>', '<?php echo htmlspecialchars($url_target_page); ?>')"><?php echo htmlspecialchars($materia['Materia']); ?></a></td>
                       <td><?php echo htmlspecialchars($materia['Curso']); ?></td>
                   </tr>
               <?php }
           } ?>
           </tbody>
       </table>
    </div>

  </div>
</div>

<script src="../funciones/sessionControl.js"></script>
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>

<script>
    // Función para cargar los cursos disponibles según el ciclo y plan seleccionados
    function cargarCursos() {
        var ciclolectivo_id = $('#ciclolectivo_select').val();
        var plan_id = $('#plan_select').val();
        var action_type = '<?php echo $action_type; ?>'; // Pasa el tipo de acción al AJAX

        $.ajax({
            type: 'POST',
            url: 'materiaxsecretario.php?action=' + action_type,
            data: {
                is_ajax_courses: '1', // Indicar que es una petición AJAX para cursos
                ciclolectivo_id: ciclolectivo_id,
                plan_id: plan_id
            },
            success: function(data) {
                $('#curso_select').html(data);
                // Si la respuesta no está vacía, seleccionar la primera opción
                if (data.trim() !== '<option value="">Sin cursos</option>') { // Verifica si hay cursos reales
                    $('#curso_select option:first').prop('selected', true);
                }
                cargarMaterias(); // Una vez que los cursos se han cargado, cargar las materias
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error al cargar cursos:', textStatus, errorThrown);
                $('#curso_select').html('<option value="">Error al cargar cursos</option>');
                $('#tablaMaterias tbody').html('<tr><td colspan="3" class="text-danger">Error al cargar las materias. Inténtelo de nuevo.</td></tr>');
            }
        });
    }

    // Función para recargar la tabla de materias al cambiar los filtros (Ciclo, Plan, Curso)
    function cargarMaterias() {
        var ciclolectivo_id = $('#ciclolectivo_select').val();
        var plan_id = $('#plan_select').val();
        var curso_id = $('#curso_select').val(); // Obtener el ID del curso seleccionado

        // Si el curso seleccionado es "Sin Cursos" (value=""), se envía como cadena vacía, que `obtenerTodasLasMaterias`
        // interpretará como nulo y no filtrará por curso.

        var action_type = '<?php echo $action_type; ?>';

        $.ajax({
            type: 'POST',
            url: 'materiaxsecretario.php?action=' + action_type,
            data: {
                is_ajax_materias: '1', // Indicar que es una petición AJAX para materias
                ciclolectivo_id: ciclolectivo_id,
                plan_id: plan_id,
                curso_id: curso_id // Incluir el ID del curso
            },
            success: function(data) {
                $('#tablaMaterias tbody').html(data);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error al cargar materias:', textStatus, errorThrown);
                $('#tablaMaterias tbody').html('<tr><td colspan="3" class="text-danger">Error al cargar las materias. Inténtelo de nuevo.</td></tr>');
            }
        });
    }

    // Función para pasar datos de la materia seleccionada a la página de carga
    function setMateria(idMateria, materiaNombre, cursoNombre, cicloId, planId, urlTarget) {
        // Obtener el año del ciclo y el nombre del plan de los select para pasarlos como strings
        var ciclolectivo_anio_str = $('#ciclolectivo_select option:selected').text();
        var plan_nombre_str = $('#plan_select option:selected').text();

        // Crear un formulario temporal
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = urlTarget;
        form.style.display = 'none';

        // Añadir campos ocultos para los datos de la materia
        var fields = {
            idMateria: idMateria,
            materia: materiaNombre, // Nombre de la materia (string)
            curso: cursoNombre,     // Nombre del curso (string) - Este es el curso específico de la materia

            // Información de los filtros para contexto en la página de destino (si se necesita mostrar)
            ciclolectivo: ciclolectivo_anio_str, // Año del ciclo como string
            ciclolectivo_id: cicloId, // ID del ciclo como int
            plan: plan_nombre_str, // Nombre del plan como string
            plan_id: planId         // ID del plan como int
        };

        for (var key in fields) {
            if (fields.hasOwnProperty(key)) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            }
        }

        // Simular el envío del formulario
        document.body.appendChild(form);
        form.submit();
    }

    // Configurar los event listeners para los select
    $(document).ready(function() {
        $('#ciclolectivo_select').on('change', cargarCursos);
        $('#plan_select').on('change', cargarCursos);
        $('#curso_select').on('change', cargarMaterias); // Ahora este también desencadena la carga de materias

        // Cargar cursos y luego materias al cargar la página por primera vez
        cargarCursos();
    });
</script>

<?php include '../funciones/footer.html'; ?>
</body>
</html>