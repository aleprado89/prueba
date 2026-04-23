<?php
include '../funciones/verificarSesion.php';
include '../funciones/requerirSecretaria.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';
define('ID_FORMULARIO_SECRETARIA', 22);
require_once '../funciones/requerirPermisoFormulario.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listados de personal - Secretaria</title>
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
            <li class="breadcrumb-item active">Listados de personal</li>
        </ol>

        <div class="card p-4">
            <h5>Generar listado de personal</h5>
            <p>Seleccione el alcance y el formato para generar el reporte.</p>

            <form id="formListadoPersonal" method="GET" target="_blank">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="tipoListado" class="form-label">Personal a incluir <span class="text-danger">*</span></label>
                        <select class="form-select" id="tipoListado" name="tipoListado" required>
                            <option value="activos" selected>Solo personal activo</option>
                            <option value="todos">Todo el personal</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <h6>Seleccione el formato del reporte:</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="formato" id="formatoRenglon" value="renglon" checked>
                        <label class="form-check-label" for="formatoRenglon">Con renglon</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="formato" id="formatoCuadriculado" value="cuadriculado">
                        <label class="form-check-label" for="formatoCuadriculado">Con cuadriculado</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="formato" id="formatoDni" value="dni">
                        <label class="form-check-label" for="formatoDni">Con DNI</label>
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

<script src="../js/jquery-3.7.1.js"></script>
<script src="../js/bootstrap.bundle.js"></script>
<script src="../funciones/sessionControl.js"></script>
<script>
$(document).ready(function() {
    $('#formListadoPersonal').on('submit', function() {
        var formato = $('input[name="formato"]:checked').val();
        if (formato === 'renglon') {
            $(this).attr('action', '../reportes/listadoPersonalRenglonPDF.php');
        } else if (formato === 'cuadriculado') {
            $(this).attr('action', '../reportes/listadoPersonalCuadriculadoPDF.php');
        } else if (formato === 'dni') {
            $(this).attr('action', '../reportes/listadoPersonalDniPDF.php');
        }
    });
});
</script>
</body>
</html>
