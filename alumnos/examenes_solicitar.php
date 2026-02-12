<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); 

include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
include '../funciones/verificarSesion.php';

// -------------------------------------------------------------
// 1. CAPTURA ESTRICTA Y SANITIZACIÓN DE VARIABLES
// -------------------------------------------------------------
$cicloLectivo   = (int)($datosColegio[0]['anioautoweb'] ?? 0);
$idCicloLectivo = (int)buscarIdCiclo($conn, $cicloLectivo);
$idAlumno       = (int)($_SESSION['alu_idAlumno'] ?? 0);
$nombreAlumno   = htmlspecialchars(($_SESSION['alu_apellido'] ?? '') . ", " . ($_SESSION['alu_nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
$idPlan         = (int)($_SESSION['idP'] ?? 0);
$nombrePlan     = htmlspecialchars($_SESSION['nombreP'] ?? '', ENT_QUOTES, 'UTF-8');
$idTurno        = (int)($datosColegio[0]['idTurno'] ?? 0);

// Es crucial verificar que $_POST['idDivision'] realmente contenga datos de la página anterior
$idMateria      = isset($_POST['idM']) ? (int)$_POST['idM'] : 0;
$idDivision     = isset($_POST['idDivision']) ? (int)$_POST['idDivision'] : 0;
$nombreMateria  = htmlspecialchars($_POST['nombreMateria'] ?? '', ENT_QUOTES, 'UTF-8');
$nombreCurso    = htmlspecialchars($_POST['nombreCurso'] ?? '', ENT_QUOTES, 'UTF-8');


// -------------------------------------------------------------
// 2. LÓGICA DE EVENTOS POST
// -------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST['submitVolver'])) {
        header("Location: ../alumnos/examenes_materias.php");
        exit;
    }

    if (isset($_POST['idInscripcionWeb'])) {
        $idInscripcionWeb = (int)$_POST["idInscripcionWeb"];    
        cancelarExamen($conn, $idInscripcionWeb);
    }

    if (isset($_POST["fechaExamen"])) {
        $idFechaSeleccionada = (int)$_POST["fechaExamen"];
        // Reasignamos el ID validado para la solicitud
        $idMateriaPost = (int)obtenerIdMateriaPorFechaExamen($conn, $idFechaSeleccionada); 
        solicitarExamen($conn, $idAlumno, $idMateriaPost, $idCicloLectivo, $idFechaSeleccionada);
    }
}

// -------------------------------------------------------------
// 3. CONSULTA DE ESTADOS Y FECHAS
// -------------------------------------------------------------
$listadoSolicitudes = existeSolicitudExamen($conn, $idAlumno, $idMateria, $idCicloLectivo, $idTurno) ?: [];

$habilitado = true;
foreach ($listadoSolicitudes as $solicitud) {
    $estado = $solicitud['Estado'] ?? '';
    if ($estado === "Pendiente" || $estado === "Aprobada") {
        $habilitado = false;
        break;
    }
}

$listadoFechasExamenes = buscarFechasExamenTurno($conn, $idMateria, $idCicloLectivo, $idTurno, $idDivision);
$fechaHoraActual = new DateTime();
?>
<!DOCTYPE html>
<html lang="es"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar inscripción</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/material/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="../img/icon.png">
</head>

<body>
    <?php include '../funciones/menu.php'; ?>

    <div class="container-fluid fondo">
        <br>
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="menualumnos.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="examenes_planes.php">Inscripción a exámenes</a></li>
                    <li class="breadcrumb-item"><a href="examenes_materias.php">Materias</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Solicitar examen</li>
                </ol>
            </nav>

            <div class="card padding col-12 shadow-sm mb-4">
                <h5><?php echo $nombreAlumno; ?></h5>
                <h5>Carrera: <?php echo $nombrePlan; ?></h5>
                <h5>Materia: <?php echo $nombreMateria; ?></h5>
                <h5>Curso: <?php echo $nombreCurso; ?></h5>
                <h5>Turno: <?php echo htmlspecialchars($datosColegio[0]['nombreTurno'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h5>
                <br>

                <div class="row col-12" <?php echo !$habilitado ? 'style="display:none;"' : ''; ?>>
                    <div class="col-12 col-md-6">
                        <h5 class="padding">Fechas Disponibles</h5>
                        <form id="formulario" action="../alumnos/examenes_solicitar.php" method="POST">
                            <select class="form-select margenes padding" name="fechaExamen" id="fechaExamen" required>
                                <?php
                                $fechasDisponibles = 0;

                                foreach ($listadoFechasExamenes as $fechaInfo) {
                                    $idFechaExamen = (int)$fechaInfo['idFechaExamen'];
                                    $fechaStr = $fechaInfo['Fecha'];
                                    $horaStr = $fechaInfo['Hora'];

                                    // Instanciación robusta
                                    $fechaHoraExamen = DateTime::createFromFormat('Y-m-d H:i:s', $fechaStr . ' ' . $horaStr);
                                    if (!$fechaHoraExamen) {
                                        $fechaHoraExamen = DateTime::createFromFormat('Y-m-d H:i', $fechaStr . ' ' . $horaStr);
                                    }

                                    // PREVENCIÓN DE ERROR FATAL: Solo procesamos si el DateTime se creó con éxito
                                    if ($fechaHoraExamen && $fechaHoraExamen >= $fechaHoraActual) {
                                        $fechaFormateada = $fechaHoraExamen->format('d-m-Y');
                                        echo "<option value=\"{$idFechaExamen}\">{$fechaFormateada} {$horaStr}</option>";
                                        $fechasDisponibles++;
                                    }
                                }

                                if ($fechasDisponibles === 0) {
                                    echo '<option value="" disabled selected>No hay fechas disponibles</option>';
                                }
                                ?>
                            </select>
                    </div>
                    
                    <div class="col-12 col-md-6 mt-4">
                        <input type="hidden" name="idM" value="<?php echo $idMateria; ?>" />
                        <input type="hidden" name="idDivision" value="<?php echo $idDivision; ?>" />
                        <input type="hidden" name="nombreMateria" value="<?php echo $nombreMateria; ?>" />
                        <input type="hidden" name="nombreCurso" value="<?php echo $nombreCurso; ?>" />
                        
                        <?php if ($habilitado) { ?>
                            <button type="button" onclick="abrirModal()" class="btn btn-primary">Solicitar</button>
                        <?php } else { ?>
                            <div class="alert alert-warning">Ya hay solicitudes en proceso o aprobadas.</div>
                        <?php } ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="container mt-5">
                <form id="cancelar" action="../alumnos/examenes_solicitar.php" method="post">
                    <input type="hidden" name="idInscripcionWeb" id="idInscripcionWeb">
                    <input type="hidden" name="idM" value="<?php echo $idMateria; ?>" />
                    <input type="hidden" name="nombreMateria" value="<?php echo $nombreMateria; ?>" />
                    <input type="hidden" name="nombreCurso" value="<?php echo $nombreCurso; ?>" />
                    <input type="hidden" name="idDivision" value="<?php echo $idDivision; ?>" />

                    <div class="table-responsive">
                        <table class="table table-hover shadow-sm">
                            <caption class="pt-2">Solicitudes Generadas</caption>
                            <thead class="table-primary">
                                <tr>
                                    <th scope="col" class="d-none">ID</th>
                                    <th scope="col">Materia</th>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col">Observaciones</th>
                                    <th scope="col">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (!empty($listadoSolicitudes)) {
                                    foreach ($listadoSolicitudes as $solic) {
                                        $idInscripWeb = htmlspecialchars($solic['idInscripcionWeb'] ?? '', ENT_QUOTES, 'UTF-8');
                                        $materiaStr   = htmlspecialchars($solic['Materia'] ?? '', ENT_QUOTES, 'UTF-8');
                                        $estadoStr    = htmlspecialchars($solic['Estado'] ?? '', ENT_QUOTES, 'UTF-8');
                                        $observStr    = htmlspecialchars($solic['Observaciones'] ?? '', ENT_QUOTES, 'UTF-8');
                                        
                                        $fechaFormato = DateTime::createFromFormat('Y-m-d', $solic['Fecha'] ?? '');
                                        $fechaM       = $fechaFormato ? $fechaFormato->format('d/m/Y') : ($solic['Fecha'] ?? '');
                                        $horaM        = htmlspecialchars($solic['Hora'] ?? '', ENT_QUOTES, 'UTF-8');
                                ?>
                                    <tr>
                                        <td class="d-none"><?php echo $idInscripWeb; ?></td>
                                        <td><?php echo $materiaStr; ?></td>
                                        <td><?php echo "{$fechaM} {$horaM}"; ?></td>
                                        <td><span class="badge bg-<?php echo ($estadoStr === 'Aprobada') ? 'success' : (($estadoStr === 'Pendiente') ? 'warning text-dark' : 'secondary'); ?>"><?php echo $estadoStr; ?></span></td>
                                        <td><?php echo $observStr; ?></td>
                                        <td>
                                            <?php if ($estadoStr === "Pendiente") { ?>
                                                <button type="button" class="btn btn-danger btn-sm cancelar-btn" data-id="<?php echo $idInscripWeb; ?>">Cancelar</button>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php 
                                    } 
                                } else { 
                                ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No hay registros de solicitudes</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            <div class="modal fade" id="confirmarSolicitudModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirmar Solicitud</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>¿Estás seguro de que deseas solicitar el examen para esta fecha?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" id="confirmarSolicitudBtn" class="btn btn-primary">Confirmar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="confirmarCancelacionModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirmar Cancelación</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <p>¿Estás seguro de que deseas cancelar esta solicitud?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                            <button type="button" id="confirmarCancelacionBtn" class="btn btn-danger">Sí, Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include '../funciones/footer.html'; ?>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../funciones/sessionControl.js"></script>
    <script>
        let idInscripcionSeleccionado = null;

        document.querySelectorAll('.cancelar-btn').forEach(boton => {
            boton.addEventListener('click', function (e) {
                e.preventDefault();
                // Extracción moderna mediante el atributo dataset HTML5
                idInscripcionSeleccionado = this.dataset.id;
                
                // Uso de modal vanilla Bootstrap sin JQuery
                const modalCancelacion = new bootstrap.Modal(document.getElementById('confirmarCancelacionModal'));
                modalCancelacion.show();
            });
        });

        document.getElementById("confirmarCancelacionBtn").addEventListener("click", function () {
            if (idInscripcionSeleccionado) {
                document.getElementById("idInscripcionWeb").value = idInscripcionSeleccionado;
                document.getElementById("cancelar").submit();
            }
        });

        function abrirModal() {
            const dateSelect = document.getElementById("fechaExamen");
            if(dateSelect.value === "") {
                alert("Por favor, seleccione una fecha válida.");
                return;
            }
            const modalConfirmacion = new bootstrap.Modal(document.getElementById('confirmarSolicitudModal'));
            modalConfirmacion.show();
        }
        
        document.getElementById("confirmarSolicitudBtn").addEventListener("click", function() {
            document.getElementById("formulario").submit();
        });
    </script>
</body>
</html>