<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<?php
include '../inicio/conexion.php';
include '../funciones/consultas.php';
//include '../funciones/pruebaSession.php';

$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];

//FUNCIONES
//LISTAR PLANES
$listadoPlanes = array();
$listadoPlanes = buscarPlanes($conn, $idAlumno);
$cantidad = count($listadoPlanes);
$cursadosFinalizados=selectCursadoFinalizadoByIdPlan($conn,$idAlumno,$listadoPlanes[0]['idPlan']);

function boton($idPlan){
    include '../inicio/conexion.php';
    $idCiclo=buscarIdCiclo($conn, date("Y"));
    insertarCursadoFinalizado($conn,$_SESSION['alu_idAlumno'],$idPlan,$idCiclo,"SI");
}
// Verificamos si el boton ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['intencionExamen'])) {
    // Llamamos a la función
    $idPlan = $_POST['plan']; // Obtener el idPlan del select
    boton($idPlan);}
// Obtener los datos del cursado finalizado por defecto
if (isset($idPlan)) {
    $cursadosFinalizados = selectCursadoFinalizadoByIdPlan($conn, $idAlumno,$idPlan);
} 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['intencionExamen'])) {
    $intencionExamen = $_POST['intencionExamen']; // Obtener el nuevo valor de intención de examen
    $idPlanTabla=buscarIdPlan($conn,$_POST['plan']);
    updateCursadoFinalizado($conn, $idAlumno,$idPlanTabla ,$_POST['anio'],$intencionExamen); // Llamar a la función para actualizar
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumnos que solo adeudan finales</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../css/material/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Bootstrap JS (necesario para el navvar) -->
    <script src="../js/bootstrap.min.js"></script>
</head>

<body>
    <?php include '../funciones/menu.php'; ?>

    <div class="container-fluid fondo">
        <br>
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="menualumnos.php">Inicio</a></li>
                <li class="breadcrumb-item active">Alumnos que solo adeudan finales</li>
            </ol>
            <div class="card padding col-12">
                <h5><?php echo  "Alumno: " . $nombreAlumno; ?> </h5>
                <div class="row">
                    <div class="col-6">
                    <form method="post">
                        <select class="form-control" id="plan" name="plan">
                            <?php                    //RECORRO ARRAY PLANES PARA LLENAR SELECT
                            $a = 0;
                            while ($a < $cantidad) {
                                $idPlan = $listadoPlanes[$a]['idPlan'];
                                $Plan = $listadoPlanes[$a]['Plan'];
                            ?>
                                <option type="submit" value="<?php echo $idPlan; ?>">
                                    <?php echo $Plan; ?>
                                </option>
                            <?php
                                $a++;
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <button type="submit" name="boton" class="btn btn-primary">Establecer intención de examen</button>
                        </form>

                    </div>
                </div>
            </div>
            <br>
            <div>
                <table class="table table-hover col-12">
                    <thead>
                        <tr class="table-primary">
                            <th scope="col" class="text-center">Plan de Estudio</th>
                            <th scope="col" class="text-center">Ciclo lectivo</th>
                            <th scope="col" class="text-center">Intención de examen</th>
                            <th scope="col" class="text-center">Comprobante</th>

                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if (!empty($cursadosFinalizados)) {
                        foreach ($cursadosFinalizados as $cursado) {
                            echo "<tr>";
                            echo "<td class='text-center'>" . htmlspecialchars($cursado['plan']) . "</td>";
                            echo "<td class='text-center'>" . htmlspecialchars($cursado['anio']) . "</td>";
                            echo "<td class='text-center'>" ;
                            echo "<form method='post' >"; // Asegúrate de que el action sea correcto
                            echo "<input type='hidden' name='plan'  value='" . htmlspecialchars($cursado['plan']) . "' />"; // Suponiendo que tienes un idCursado
                            echo "<input type='hidden' name='anio' value='" . htmlspecialchars($cursado['anio']) . "' />"; // Suponiendo que tienes un idCursado

                            echo "<select name='intencionExamen' class='form-control' onchange='this.form.submit()'>";
                            echo "<option value='SI'" . ($cursado['intencionExamen'] == 'SI' ? ' selected' : '') . ">SI</option>";
                            echo "<option value='NO'" . ($cursado['intencionExamen'] == 'NO' ? ' selected' : '') . ">NO</option>";
                            echo "</select>";
                            echo "</form>";
                            echo "</td>";
                            echo "<td class='text-center'>"  . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center'>No hay datos disponibles</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS y jQuery (necesario para el modal) -->
    <script src="../js/jquery-3.7.1.slim.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <?php include '../funciones/footer.html'; ?>

    </body>
</html>