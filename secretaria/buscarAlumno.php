<?php
// Incluir el script de verificación de sesión
include '../funciones/verificarSesion.php';

// Habilitar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir la conexión a la base de datos y consultas
include '../inicio/conexion.php';
include '../funciones/consultas.php'; // Asegúrate de que esta ruta sea correcta

$message = '';
$message_type = '';

// 1. Sanitización de entradas (¡Bien hecho con el null coalescing!)
$apellido_busqueda = $_GET['apellido_busqueda'] ?? '';
$nombre_busqueda = $_GET['nombre_busqueda'] ?? '';

// Captura el parámetro 'origin' si viene en la URL. Por defecto, será 'legajo'.
$redirect_origin = $_GET['origin'] ?? 'legajo';

// 2. Inicializar $alumnos como un array vacío.
$alumnos = [];

// 3. LÓGICA DE BÚSQUEDA OPTIMIZADA
// Solo ejecutamos la búsqueda si el formulario fue explícitamente enviado.
if (isset($_GET['search_submitted'])) {

    // 4. Validamos que al menos uno de los campos de búsqueda tenga contenido.
    // Esto previene que el usuario liste a TODOS los alumnos si presiona "Buscar" con campos vacíos.
    if (!empty($apellido_busqueda) || !empty($nombre_busqueda)) {
        
        $alumnos = buscarAlumnos($conn, $apellido_busqueda, $nombre_busqueda);
        
        if (empty($alumnos)) {
            $message = "No se encontraron alumnos con los criterios de búsqueda.";
            $message_type = "warning";
        }
        
    } else {
        // El usuario presionó "Buscar" pero no ingresó filtros.
        $message = "Por favor, ingrese un apellido o nombre para realizar la búsqueda.";
        $message_type = "info";
        // $alumnos sigue siendo un array vacío, lo cual es correcto.
    }
    
} else {
    // 5. Carga inicial de la página (primera visita, sin 'search_submitted').
    // No ejecutamos ninguna consulta. La página cargará instantáneamente.
    // Opcionalmente, mostramos un mensaje de bienvenida/instrucción.
    $message = "Ingrese filtros y presione 'Buscar' para ver resultados.";
    $message_type = "secondary";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Alumno - Secretaría</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/material/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        #loader {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1050;
        }
        /* Oculta el contenido principal por defecto */
        .content-wrapper {
            display: none;
        }
    </style>
</head>
<body>
    <div id="loader">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>
    
    <div class="content-wrapper">
        <?php include '../funciones/menu_secretaria.php'; ?>
        <div class="container-fluid fondo">
            <br>
            <div class="container">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
                    <li class="breadcrumb-item active">Buscar Alumno</li>
                </ol>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Buscar Alumno</h5>                                     
                        <?php       if ($redirect_origin == 'legajo') { ?>

                        <a href="legajoAlu.php?mode=new" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nuevo Alumno
                        </a> <?php } ?>
                    </div>
                    <form id="searchForm" method="GET" action="buscarAlumno.php">
                        <input type="hidden" name="origin" value="<?php echo htmlspecialchars($redirect_origin); ?>">
                        <input type="hidden" name="search_submitted" value="1">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="apellido_busqueda" class="form-label">Apellido</label>
                                <input type="text" class="form-control" id="apellido_busqueda" name="apellido_busqueda" value="<?php echo htmlspecialchars($apellido_busqueda); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="nombre_busqueda" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre_busqueda" name="nombre_busqueda" value="<?php echo htmlspecialchars($nombre_busqueda); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Buscar</button>
                    </form>
                    
                    <?php if (!empty($alumnos)): ?>
                        <h5 class="mt-4">Resultados de la búsqueda:</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mt-3">
                                <thead>
                                    <tr>
                                        <th>Apellido</th>
                                        <th>Nombre</th>
                                        <th>DNI</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                   <?php foreach ($alumnos as $alumno): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($alumno['apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($alumno['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($alumno['dni']); ?></td>
                                        <td>
                                            <?php
                                            // Tu lógica de redirección está perfecta.
                                            // Solo aseguramos la sanitización en la salida.
                                            $action_link = '';
                                            $button_text = '';
                                            $target_blank = false;
                                            $id_alumno_seguro = htmlspecialchars($alumno['idAlumno']);

                                            if ($redirect_origin == 'legajo') {
                                                $action_link = 'legajoAlu.php?idAlumno=' . $id_alumno_seguro . '&mode=edit';
                                                $button_text = 'Ver Legajo';
                                            } elseif ($redirect_origin == 'matriculacion') {
                                                $action_link = 'matriculacion.php?idAlumno=' . $id_alumno_seguro;
                                                $button_text = 'Matricular';
                                            } elseif ($redirect_origin == 'inscripcionMateria') {
                                                $action_link = 'inscripcionMateria.php?idAlumno=' . $id_alumno_seguro;
                                                $button_text = 'Inscribir Materia';
                                            } elseif ($redirect_origin == 'inscribirExamen') {
                                                $action_link = 'inscripcionExamenAlumno.php?idAlumno=' . $id_alumno_seguro;
                                                $button_text = 'Inscribir a Examen';
                                            } elseif ($redirect_origin == 'califxalumno') {
                                                $action_link = 'carga_califxalumno_secretaria.php?idAlumno=' . $id_alumno_seguro;
                                                $button_text = 'Ver Calificaciones';
                                            } elseif ($redirect_origin == 'certificadoRegular') {
                                                $nombre   = rawurlencode(trim(htmlspecialchars($alumno['nombre'] ?? '', ENT_QUOTES, 'UTF-8')));
                                                $apellido = rawurlencode(trim(htmlspecialchars($alumno['apellido'] ?? '', ENT_QUOTES, 'UTF-8')));
                                                $dni      = rawurlencode(trim(htmlspecialchars($alumno['dni'] ?? '', ENT_QUOTES, 'UTF-8')));
                                                $idAlumno = intval($alumno['idAlumno'] ?? 0);
                                                $action_link = "../reportes/aluRegularPDF.php?nombre={$nombre}&apellido={$apellido}&dni={$dni}&idAlumno={$idAlumno}";
                                                $button_text = "Certificado Regular";
                                                $target_blank = true;
                                            } elseif($redirect_origin == 'equivalencias'){
                                                $action_link = 'equivalencias.php?idAlumno=' . $id_alumno_seguro;
                                                $button_text = 'Equivalencias';
                                            } elseif ($redirect_origin == 'presistema') {
                                                $action_link = 'registrosPresistema.php?idAlumno=' . $id_alumno_seguro;
                                                $button_text = 'Registros Presistema';
                                            } else {
                                                $action_link = 'legajoAlu.php?idAlumno=' . $id_alumno_seguro . '&mode=edit';
                                                $button_text = 'Ver Detalle';
                                            }
                                            ?>
                                            <a href="<?php echo $action_link; ?>"
                                               class="btn btn-primary btn-sm"
                                               <?php echo $target_blank ? 'target="_blank"' : ''; ?>>
                                                <?php echo $button_text; ?>
                                            </a>
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
        <?php include '../funciones/footer.html'; ?>
    </div> <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../funciones/sessionControl.js"></script>

    <script>
        // Ocultar el spinner y mostrar el contenido una vez que toda la página esté cargada.
        $(window).on('load', function() {
            // Tu comentario mencionaba 300ms, pero faltaba en el código. 
            // Lo agrego para asegurar que el loader sea visible al menos un instante
            // en cargas muy rápidas (que ahora será la carga inicial).
            setTimeout(function() {
                $('#loader').fadeOut('fast'); // Ocultar el spinner
                $('.content-wrapper').fadeIn('fast'); // Mostrar el contenido
            }, 300); // 300 milisegundos de retardo
        });

        // Cuando el usuario intenta enviar el formulario, mostramos el spinner de nuevo.
        // Esto está perfecto.
        $(document).ready(function() {
            $('#searchForm').on('submit', function() {
                // Ocultar el contenido actual
                $('.content-wrapper').fadeOut('fast');
                // Mostrar el spinner
                $('#loader').fadeIn('fast');
            });
        });
    </script>
</body>
</html>