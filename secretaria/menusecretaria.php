<?php
// HABILITAR REPORTE DE ERRORES para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../funciones/verificarSesion.php';
//session_start();

if (!isset($_SESSION['sec_nombreUsuario'])) {
    header('Location: loginAdmin.php');
    exit;
}

include '../inicio/conexion.php'; // Asegúrate de que $conn esté definida y sea una conexión activa
// Verifica si $conn es válida. Si no, algo salió mal en conexion.php
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Error crítico: Conexión a la base de datos no establecida o inválida.");
}

include '../funciones/parametrosWeb.php'; // Esto debe poblar $datosColegio usando $conn

$parametros = [];
if (!empty($datosColegio)) {
    $parametros = $datosColegio[0]; // Usamos la primera entrada
    $_SESSION['nombreColegio'] = $parametros['nombreColegio'] ?? 'Instituto Superior';
} else {
    // Valores predeterminados si no se encuentran parámetros para codnivel = 6
    $_SESSION['nombreColegio'] = $_SESSION['nombreColegio'] ?? 'Instituto Superior';
    $parametros = [
        'anioautoweb' => date('Y'),
        'examenDesde' => '', 'examenLectDesde' => '', 'examenHasta' => '',
        'cursadoDesde' => '', 'cursadoLectDesde' => '', 'cursadoHasta' => '',
        'anioCargaNotas' => date('Y'),
        'actaDesde' => '', 'actaHasta' => '',
        'nombreColegio' => $_SESSION['nombreColegio']
    ];
    // Opcional: registrar una advertencia si no se encontraron parámetros
    error_log("Advertencia: No se encontraron parámetros de colegio para codnivel=6 en parametrosWeb.php");
}

$nombreColegio = $parametros['nombreColegio'];

// Función para determinar el estado de inscripción (exámenes/cursado)
function getInscripcionStatus($currentDateTime, $desde, $lectura, $hasta) {
    $now = new DateTime($currentDateTime);
    $start = !empty($desde) ? new DateTime($desde) : null;
    $readOnly = !empty($lectura) ? new DateTime($lectura) : null;
    $end = !empty($hasta) ? new DateTime($hasta) : null;

    $status = 'cerrada';
    $colorClass = 'status-cerrada'; // Gris muy claro para cerrada

    if ($start instanceof DateTime && $end instanceof DateTime) {
        if ($now < $start || $now > $end) {
            $status = 'cerrada';
            $colorClass = 'status-cerrada';
        } elseif ($readOnly instanceof DateTime && $now >= $readOnly && $now <= $end) {
            $status = 'solo lectura';
            $colorClass = 'status-solo-lectura'; // Color #C1D7DA
        } elseif ($now >= $start && $now <= $end) { // Si estamos dentro del rango y no es solo lectura
            $status = 'abierta';
            $colorClass = 'status-abierta'; // Color #739FA5
        }
    }
    return ['status' => $status, 'colorClass' => $colorClass];
}

// Función para determinar el estado de carga de actas
function getActasStatus($currentDateTime, $desde, $hasta) {
    $now = new DateTime($currentDateTime);
    $start = !empty($desde) ? new DateTime($desde) : null;
    $end = !empty($hasta) ? new DateTime($hasta) : null;

    $status = 'cerrada';
    $colorClass = 'status-cerrada-btn-normal'; // Color para el estado "cerrada" de carga de actas

    if ($start instanceof DateTime && $end instanceof DateTime) {
        if ($now >= $start && $now <= $end) {
            $status = 'abierta';
            $colorClass = 'status-abierta';
        } else {
            $status = 'cerrada';
            $colorClass = 'status-cerrada-btn-normal';
        }
    }
    return ['status' => $status, 'colorClass' => $colorClass];
}

$currentDateTime = date('Y-m-d H:i:s');

$examenesStatus = getInscripcionStatus(
    $currentDateTime,
    $parametros['examenDesde'] ?? '',
    $parametros['examenLectDesde'] ?? '',
    $parametros['examenHasta'] ?? ''
);

$cursadoStatus = getInscripcionStatus(
    $currentDateTime,
    $parametros['cursadoDesde'] ?? '',
    $parametros['cursadoLectDesde'] ?? '',
    $parametros['cursadoHasta'] ?? ''
);

$actasStatus = getActasStatus(
    $currentDateTime,
    $parametros['actaDesde'] ?? '',
    $parametros['actaHasta'] ?? ''
);

// --- Lógica para obtener y agrupar fechas de exámenes para el calendario ---
$groupedExamDetails = []; // Almacenará los detalles agrupados por fecha
$anio_academico = $parametros['anioautoweb'] ?? date('Y');

$queryExams = "SELECT
                    fe.idFechaExamen,
                    fe.fecha,
                    fe.hora, -- *** AÑADIDA HORA AQUÍ ***
                    mt.nombre AS nombre_materia,
                    c.nombre AS nombre_curso,
                    pa.abreviatura AS plan_abreviado
                FROM
                    fechasexamenes fe
                JOIN
                    materiaterciario mt ON fe.idMateria = mt.idMateria
                JOIN
                    curso c ON mt.idCurso = c.idCurso
                JOIN
                    plandeestudio pd ON c.idPlanEstudio = pd.idPlan
                JOIN
                    planabreviado pa ON pd.idPlan = pa.idplan
                WHERE
                    YEAR(fe.fecha) = ?
                ORDER BY
                    fe.fecha, fe.hora"; // Ordenar también por hora

if ($stmtExams = $conn->prepare($queryExams)) {
    $stmtExams->bind_param("i", $anio_academico);
    if (!$stmtExams->execute()) {
        error_log("Error al ejecutar la consulta de exámenes: " . $stmtExams->error);
    } else {
        $resultExams = $stmtExams->get_result();
        if ($resultExams) {
            while ($row = $resultExams->fetch_assoc()) {
                $examDate = new DateTime($row['fecha']);
                $dateKey = $examDate->format('Y-m-d');

                if (!isset($groupedExamDetails[$dateKey])) {
                    $groupedExamDetails[$dateKey] = [];
                }
                $groupedExamDetails[$dateKey][] = [
                    'id'             => $row['idFechaExamen'],
                    'nombre_materia' => $row['nombre_materia'],
                    'nombre_curso'   => $row['nombre_curso'],
                    'plan_abreviado' => $row['plan_abreviado'],
                    'fecha_display'  => $examDate->format('d/m/Y'),
                    'hora'           => substr($row['hora'], 0, 5) // HH:MM
                ];
            }
            $resultExams->free();
        } else {
            error_log("Error: get_result() para exámenes devolvió false. Consulta: " . $queryExams);
        }
    }
    $stmtExams->close();
} else {
    error_log("Error al preparar la consulta de exámenes: " . $conn->error);
}

// Preparamos los eventos para FullCalendar (un evento por día con exámenes)
$examEventsForCalendar = [];
foreach ($groupedExamDetails as $date => $detailsForDate) {
    if (!empty($detailsForDate)) {
        $examEventsForCalendar[] = [
            'id'    => 'day-event-' . $date,
            'title' => '',
            'start' => $date,
            'allDay' => true,
            'extendedProps' => [ // Aquí guardamos todos los detalles para ese día
                'allExams' => $detailsForDate
            ]
        ];
    }
}
$examEventsJson = json_encode($examEventsForCalendar);

// JSON ENCODE PARA PASAR groupedExamDetails al JS, en lugar de solo events. Se usa para lookup
$groupedExamDetailsJson = json_encode($groupedExamDetails);

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menú Secretaría - <?php echo htmlspecialchars($nombreColegio); ?></title>
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- FullCalendar CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">


</head>
<body>

<?php include '../funciones/menu_secretaria.php'; ?>


<div class="container mt-4">
  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <div class="row align-items-center">
        <div class="col-12">
          <h3 class="mb-1 text-dark"><?php echo htmlspecialchars($nombreColegio); ?></h3>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Panel Izquierdo: Calendario e Información del día -->
    <div class="col-md-6 mb-4">
      <div class="card shadow-sm h-100">
        <div class="card-header card-header-custom d-flex align-items-center">
          <i class="bi bi-calendar-range me-2"></i> Calendario
        </div>
        <div class="card-body">
          <div id='calendar'></div>
          <hr>
          <div class="card-text d-flex align-items-center">
            <i class="bi bi-info-circle me-2"></i> Información del día
          </div>
          <div class="mt-3" id="info-del-dia-content">
            <!-- El contenido se generará dinámicamente aquí -->
            <p class="text-muted" id="no-exam-selected">Selecciona una fecha de examen del calendario para ver los detalles.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Panel Derecho: Plataforma web -->
    <div class="col-md-6 mb-4">
      <div class="card shadow-sm h-100">
        <div class="card-header card-header-custom d-flex align-items-center">
          <i class="bi bi-cloud-upload-fill me-2"></i> Plataforma web
        </div>
        <div class="card-body">

          <!-- Autogestión Alumnos -->
          <div class="mb-4">
            <h5 class="editable-param" data-bs-toggle="tooltip" title="Editar parámetros web" onclick="location.href='../secretaria/editParametrosWeb.php'">
                Autogestión Alumnos
            </h5>
            <p class="mb-2">Año: <span class="editable-param" data-bs-toggle="tooltip" title="Editar parámetros web" onclick="location.href='../secretaria/editParametrosWeb.php'"><?php echo htmlspecialchars($parametros['anioautoweb'] ?? 'N/A'); ?></span></p>

            <!-- Card para Inscripción a exámenes -->
            <div class="card border-0 mb-3">
              <div class="card-header status-card-header <?php echo $examenesStatus['colorClass']; ?>"
                   data-bs-toggle="tooltip" title="Editar parámetros web" onclick="location.href='../secretaria/editParametrosWeb.php'">
                Inscripción a exámenes: <?php echo htmlspecialchars(ucfirst($examenesStatus['status'])); ?>
              </div>
              <div class="card-body border">
                <ul class="list-unstyled mb-0">
                  <li>Inicio: <span class="editable-param" data-bs-toggle="tooltip" title="Editar parámetros web" onclick="location.href='../secretaria/editParametrosWeb.php'"><?php echo htmlspecialchars($parametros['examenDesde'] ?? 'N/A'); ?></span></li>
                  <li>Solo lectura: <span class="editable-param" data-bs-toggle="tooltip" title="Editar parámetros web" onclick="location.href='../secretaria/editParametrosWeb.php'"><?php echo htmlspecialchars($parametros['examenLectDesde'] ?? 'N/A'); ?></span></li>
                  <li>Cierre: <span class="editable-param" data-bs-toggle="tooltip" title="Editar parámetros web" onclick="location.href='../secretaria/editParametrosWeb.php'"><?php echo htmlspecialchars($parametros['examenHasta'] ?? 'N/A'); ?></span></li>
                </ul>
              </div>
            </div>

            <!-- Card para Inscripción a cursado -->
            <div class="card border-0 mb-3">
              <div class="card-header status-card-header <?php echo $cursadoStatus['colorClass']; ?>"
                   data-bs-toggle="tooltip" title="Editar parámetros web" onclick="location.href='../secretaria/editParametrosWeb.php'">
                Inscripción a cursado: <?php echo htmlspecialchars(ucfirst($cursadoStatus['status'])); ?>
              </div>
              <div class="card-body border">
                <ul class="list-unstyled mb-0">
                  <li>Inicio: <span class="editable-param" data-bs-toggle="tooltip" title="Editar parámetros web" onclick="location.href='../secretaria/editParametrosWeb.php'"><?php echo htmlspecialchars($parametros['cursadoDesde'] ?? 'N/A'); ?></span></li>
                  <li>Solo lectura: <span class="editable-param" data-bs-toggle="tooltip" title="Editar parámetros web" onclick="location.href='../secretaria/editParametrosWeb.php'"><?php echo htmlspecialchars($parametros['cursadoLectDesde'] ?? 'N/A'); ?></span></li>
                  <li>Cierre: <span class="editable-param" data-bs-toggle="tooltip" title="Editar parámetros web" onclick="location.href='../secretaria/editParametrosWeb.php'"><?php echo htmlspecialchars($parametros['cursadoHasta'] ?? 'N/A'); ?></span></li>
                </ul>
              </div>
            </div>
          </div>

          <hr class="my-4">

          <!-- Autogestión Docente -->
          <div class="mb-4">
            <h5 class="editable-param" data-bs-toggle="tooltip" title="Editar parámetros web" onclick="location.href='../secretaria/editParametrosWeb.php'">
                Autogestión Docente
            </h5>
            <p class="mb-2">Año: <span class="editable-param" data-bs-toggle="tooltip" title="Editar parámetros web" onclick="location.href='../secretaria/editParametrosWeb.php'"><?php echo htmlspecialchars($parametros['anioCargaNotas'] ?? 'N/A'); ?></span></p>

            <!-- Card para Carga de actas -->
            <div class="card border-0 mb-3">
              <div class="card-header status-card-header <?php echo $actasStatus['colorClass']; ?>"
                   data-bs-toggle="tooltip" title="Editar parámetros web" onclick="location.href='../secretaria/editParametrosWeb.php'">
                Carga de actas: <?php echo htmlspecialchars(ucfirst($actasStatus['status'])); ?>
              </div>
              <div class="card-body border">
                <ul class="list-unstyled mb-0">
                  <li>Inicio: <span class="editable-param" data-bs-toggle="tooltip" title="Editar parámetros web" onclick="location.href='../secretaria/editParametrosWeb.php'"><?php echo htmlspecialchars($parametros['actaDesde'] ?? 'N/A'); ?></span></li>
                  <li>Cierre: <span class="editable-param" data-bs-toggle="tooltip" title="Editar parámetros web" onclick="location.href='../secretaria/editParametrosWeb.php'"><?php echo htmlspecialchars($parametros['actaHasta'] ?? 'N/A'); ?></span></li>
                </ul>
              </div>
            </div>
          </div>

          <div class="d-grid gap-2 mt-4">
            <a href="../inicio/login.php" class="btn btn-lg btn-navbar-color">
              <i class="bi bi-link-45deg me-2"></i> Acceso autogestión docentes/estudiantes
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include '../funciones/footer.html';?>
<!-- Bootstrap JS y dependencias -->
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js"></script>

<script>
// Inicializar tooltips de Bootstrap
$(function () {
  $('[data-bs-toggle="tooltip"]').tooltip();
});

// Función para mostrar los detalles del examen (ahora maneja múltiple exámenes)
function displayExamDetails(examsInfoArray) {
    const infoContentDiv = document.getElementById('info-del-dia-content');
    infoContentDiv.innerHTML = ''; // Limpiar contenido anterior

    if (examsInfoArray && examsInfoArray.length > 0) {
        // Mostrar la fecha para la que se listan los exámenes
        const dateHeader = document.createElement('p');
        dateHeader.classList.add('fw-bold');
        dateHeader.innerHTML = 'Exámenes del día ' + examsInfoArray[0].fecha_display + ':';
        infoContentDiv.appendChild(dateHeader);

        // Crear una lista para cada examen
        const ul = document.createElement('ul');
        ul.classList.add('list-unstyled', 'ms-3', 'mb-0'); // Añadir 'mb-0' para que no haya margen extra abajo
        examsInfoArray.forEach(exam => {
            const li = document.createElement('li');
            li.innerHTML = `<strong>${exam.nombre_materia}</strong><br>
                            ${exam.plan_abreviado} - ${exam.nombre_curso} - ${exam.hora}hs`;
            ul.appendChild(li);
        });
        infoContentDiv.appendChild(ul);
    } else {
        resetInfoDelDia();
    }
    // Asegurarse de que el mensaje predeterminado esté oculto si hay contenido
    document.getElementById('no-exam-selected').style.display = 'none';
}

// Función para limpiar la sección de información del día
function resetInfoDelDia() {
    const infoContentDiv = document.getElementById('info-del-dia-content');
    // Limpiar todos los hijos excepto el mensaje "no Exam selected" si ya existe
    let noExamMessage = document.getElementById('no-exam-selected');
    if (noExamMessage) {
        infoContentDiv.innerHTML = ''; // Limpiar todo lo demás
        infoContentDiv.appendChild(noExamMessage); // Volver a añadir el mensaje
        noExamMessage.style.display = 'block'; // Asegurarse de que sea visible
    } else {
        // Si el mensaje no existe (primera carga o error), crearlo y añadirlo
        infoContentDiv.innerHTML = '';
        noExamMessage = document.createElement('p');
        noExamMessage.classList.add('text-muted');
        noExamMessage.id = 'no-exam-selected';
        noExamMessage.innerHTML = 'Selecciona una fecha de examen del calendario para ver los detalles.';
        infoContentDiv.appendChild(noExamMessage);
    }
}


// Importante: Pasar la información AGRUPADA al JS para facilitar la búsqueda
const groupedExams = <?php echo $groupedExamDetailsJson; ?>;

// Inicializar FullCalendar
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        height: 'auto', /* Permite que el calendario ajuste su altura */
        contentHeight: 'auto', /* Ayuda a que el contenido del calendario se ajuste */
        aspectRatio: 1.35, /* Un aspecto que hace que el calendario sea más o menos cuadrado */
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: '' // ¡Eliminado el botón 'dayGridMonth' (Mes)
        },
        initialDate: '<?php echo date("Y-m-d"); ?>',
        events: <?php echo $examEventsJson; ?>,

        eventContent: function(arg) {
            let arrayOfDomNodes = [];
            let iconEl = document.createElement('i');
            iconEl.classList.add('bi', 'bi-info-circle-fill');
            iconEl.title = 'Haga clic para ver los exámenes de este día';
            arrayOfDomNodes.push(iconEl);
            return { domNodes: arrayOfDomNodes };
        },

        eventClick: function(info) {
            // Usa los extendedProps guardados para mostrar los detalles
            displayExamDetails(info.event.extendedProps.allExams);
            info.jsEvent.preventDefault();
        },

        // Manejador de evento al hacer clic en cualquier día del calendario
        dateClick: function(info) {
            const clickedDate = info.dateStr; // Formato YYYY-MM-DD
            if (groupedExams[clickedDate]) {
                displayExamDetails(groupedExams[clickedDate]);
            } else {
                resetInfoDelDia(); // Si no hay exámenes en la fecha, limpiar la info
            }
        },
        // Opcional: Esto hace que las celdas del día sean más compactas
        dayCellContent: function(arg) {
            return {
                html: '<div style="font-size: 0.9em;">' + arg.dayNumberText + '</div>'
            };
        }
    });
    calendar.render();

    // Lógica para mostrar automáticamente los exámenes de hoy al cargar la página
    const today = new Date();
    const todayDateStr = today.toISOString().slice(0, 10);

    if (groupedExams[todayDateStr]) {
        displayExamDetails(groupedExams[todayDateStr]);
    } else { // Si hoy no hay exámenes, resetear la sección de info del día
        resetInfoDelDia();
    }
});
</script>
<script src="../funciones/sessionControl.js"></script>
</body>
</html>