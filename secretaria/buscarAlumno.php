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

$apellido_busqueda = $_GET['apellido_busqueda'] ?? '';
$nombre_busqueda = $_GET['nombre_busqueda'] ?? '';

// Captura el parámetro 'origin' si viene en la URL. Por defecto, será 'legajo'.
$redirect_origin = $_GET['origin'] ?? 'legajo'; 

$alumnos = [];

// MODIFICACIÓN 2: Buscar todos los alumnos si no hay filtros o si los filtros están vacíos
// Si no se proporcionaron parámetros de búsqueda al cargar la página, o si se envió el formulario con campos vacíos.
if (empty($apellido_busqueda) && empty($nombre_busqueda) && !isset($_GET['search_submitted'])) {
    $alumnos = buscarAlumnos($conn, '', ''); // Carga todos los alumnos al inicio
} elseif (isset($_GET['search_submitted'])) {
    // Si el formulario fue enviado (incluso si los campos de filtro están vacíos)
    $alumnos = buscarAlumnos($conn, $apellido_busqueda, $nombre_busqueda);
    if (empty($alumnos) && (empty($apellido_busqueda) && empty($nombre_busqueda))) {
        // Si no se encontraron resultados y la búsqueda fue vacía, muestra todos.
        // Esto cubre el caso de "Buscar" sin filtros.
        $alumnos = buscarAlumnos($conn, '', '');
    } elseif (empty($alumnos)) {
        $message = "No se encontraron alumnos con los criterios de búsqueda.";
        $message_type = "warning";
    }
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
</head>
<body>
    <?php include '../funciones/menu_secretaria.php'; // Asegúrate de que esta ruta sea correcta ?>

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

            <!-- MODIFICACIÓN 4: Ajuste de márgenes y padding en el card principal -->
            <div class="card p-4 mb-4"> <!-- Añadido p-4 para padding interno, mb-4 para margin-bottom -->
                <h5>Buscar Alumno</h5>
                <br>
                <form method="GET" action="buscarAlumno.php">
                    <!-- MODIFICACIÓN 3: Preservar el parámetro 'origin' en el formulario -->
                    <input type="hidden" name="origin" value="<?php echo htmlspecialchars($redirect_origin); ?>">
                    <input type="hidden" name="search_submitted" value="1"> <!-- Indica que el formulario fue enviado -->

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
                                            <?php if ($redirect_origin == 'legajo'): ?>
                                                <a href="legajoAlu.php?idAlumno=<?php echo htmlspecialchars($alumno['idAlumno']); ?>" class="btn btn-primary btn-sm">Ver Legajo</a>
                                            <?php elseif ($redirect_origin == 'matriculacion'): ?>
                                                <a href="matriculacion.php?idAlumno=<?php echo htmlspecialchars($alumno['idAlumno']); ?>" class="btn btn-primary btn-sm">Matricular</a>
                                            <?php else: // Comportamiento por defecto si 'origin' es desconocido o nulo ?>
                                                <a href="legajoAlu.php?idAlumno=<?php echo htmlspecialchars($alumno['idAlumno']); ?>" class="btn btn-primary btn-sm">Ver Detalle</a>
                                            <?php endif; ?>
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

    <?php include '../funciones/footer.html'; // Asegúrate de que esta ruta sea correcta ?>
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../funciones/sessionControl.js"></script>
</body>
</html>