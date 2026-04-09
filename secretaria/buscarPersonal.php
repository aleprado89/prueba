<?php
include '../funciones/verificarSesion.php';
include '../funciones/requerirSecretaria.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';
define('ID_FORMULARIO_SECRETARIA', 20);
require_once '../funciones/requerirPermisoFormulario.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = '';
$message_type = '';

$apellido_busqueda = $_GET['apellido_busqueda'] ?? '';
$nombre_busqueda = $_GET['nombre_busqueda'] ?? '';
$personal = [];

if (isset($_GET['search_submitted'])) {
    if (!empty($apellido_busqueda) || !empty($nombre_busqueda)) {
        $personal = buscarPersonal($conn, $apellido_busqueda, $nombre_busqueda);
        if (empty($personal)) {
            $message = "No se encontraron legajos con los criterios de busqueda.";
            $message_type = "warning";
        }
    } else {
        $message = "Por favor, ingrese un apellido o nombre para realizar la busqueda.";
        $message_type = "info";
    }
} else {
    $message = "Ingrese filtros y presione 'Buscar' para ver resultados.";
    $message_type = "secondary";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar personal - Secretaria</title>
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
            <li class="breadcrumb-item active">Buscar personal</li>
        </ol>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Buscar personal</h5>
                <a href="legajoPersonal.php?mode=new" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nuevo legajo
                </a>
            </div>

            <form method="GET" action="buscarPersonal.php">
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

            <?php if (!empty($personal)): ?>
                <h5 class="mt-4">Resultados de la busqueda:</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mt-3">
                        <thead>
                            <tr>
                                <th>Legajo</th>
                                <th>Apellido</th>
                                <th>Nombre</th>
                                <th>DNI</th>
                                <th>Cargo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($personal as $persona): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($persona['legajo']); ?></td>
                                    <td><?php echo htmlspecialchars($persona['apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($persona['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($persona['dni']); ?></td>
                                    <td><?php echo htmlspecialchars($persona['cargo'] ?? ''); ?></td>
                                    <td><?php echo ((int)$persona['actual'] === 1) ? 'Activo' : 'Inactivo'; ?></td>
                                    <td>
                                        <a href="legajoPersonal.php?legajo=<?php echo urlencode($persona['legajo']); ?>&mode=edit" class="btn btn-primary btn-sm">
                                            Ver legajo
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

<script src="../js/jquery-3.7.1.js"></script>
<script src="../js/bootstrap.bundle.js"></script>
<script src="../funciones/sessionControl.js"></script>
</body>
</html>
