<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<?php
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
include '../funciones/verificarSesion.php';

$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];


//FUNCIONES
//LISTAR PLANES
$listadoPlanes = array();
$listadoPlanes = buscarPlanes($conn, $idAlumno);
$cantidad = count($listadoPlanes);
$cursadosFinalizados=selectCursadoFinalizadoByIdPlan($conn,$idAlumno,$listadoPlanes[0]['idPlan']);
$_SESSION['idP']=$listadoPlanes[0]['idPlan'];
$anio=$datosColegio[0]['anioautoweb'];//toma el primer registro de colegio para sacar el anioautoweb
$_SESSION['anio']=$anio;
$idCiclo=buscarIdCiclo($conn, $anio);//date("Y") en vez de $anio eso seria para elegir el año actual
//la siguiente consulta insertarcursadofinalizado verifica que no tenga 
//ninguna otra intencion ese año y ese plan antes de agregar

function boton($idPlan,$idCiclo){
    include '../inicio/conexion.php';
        insertarCursadoFinalizado($conn,idAlumno: $_SESSION['alu_idAlumno'],idPlan: $idPlan,idCicloLectivo: $idCiclo,intencion: "SI");
}
// Verificamos si el boton ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['intencionExamen'])) {
    // Llamamos a la función
    $idPlan = $_POST['plan']; // Obtener el idPlan del select
    $_SESSION['idP']= $idPlan;
    boton($idPlan, $idCiclo);}
// Obtener los datos del cursado finalizado por defecto
 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['intencionExamen'])) {
    $intencionExamen = $_POST['intencionExamen']; // Obtener el nuevo valor de intención de examen
    $idPlanTabla=buscarIdPlan($conn,$_POST['plan']);
    $_SESSION['idP']=$idPlanTabla;
    $idCiclo=buscarIdCiclo($conn,$_POST['anio']);
    updateCursadoFinalizado(conexion: $conn, idAlumno: $idAlumno,idPlan: $idPlanTabla ,idCicloLectivo: $idCiclo,intencionExamen: $intencionExamen); // Llamar a la función para actualizar
    $cursadosFinalizados = selectCursadoFinalizadoByIdPlan($conn, $idAlumno);

}
if (isset($idPlan)) {
    $cursadosFinalizados = selectCursadoFinalizadoByIdPlan($conn, $idAlumno);
    $_SESSION['idP']=$idPlan;
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
            <div class="card padding">
                <h5><?php echo  "Alumno: " . $nombreAlumno; ?> </h5>
                <div class="row">
                    <div class="col-md-6">
                    <form method="post" id="boton">
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
                    </div><br><br>
                    <?php
        if (empty($cursadosFinalizados)) {
            // Mostrar el botón solo si no hay cursados finalizados
        ?>
                    <div>
                        <button type="submit" name="boton" class="btn btn-primary">Establecer intención de examen</button>
                        </form>
                    </div>
                    <?php
        }
        ?>
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
                            echo "<form id='select' method='post' >"; // Asegúrate de que el action sea correcto
                            echo "<input type='hidden' name='plan'  value='" . htmlspecialchars($cursado['plan']) . "' />"; // Suponiendo que tienes un idCursado
                            echo "<input type='hidden' name='anio' value='" . htmlspecialchars($cursado['anio']) . "' />"; // Suponiendo que tienes un idCursado
                            echo "<select name='intencionExamen' class='form-control' onchange='this.form.submit()'>";
                            echo "<option value='SI'" . ($cursado['intencionExamen'] == 'SI' ? ' selected' : '') . ">SI</option>";
                            echo "<option value='NO'" . ($cursado['intencionExamen'] == 'NO' ? ' selected' : '') . ">NO</option>";
                            echo "</select>";
                            echo "</form>";
                            echo "</td>";
                            echo "<td class='text-center'>";

                             // Verifica si la intención de examen es "NO"
            if ($cursado['intencionExamen'] == 'NO') {
                // Si es "NO", no mostrar el hipervínculo
                echo "No disponible"; // Puedes mostrar un mensaje o dejarlo vacío
            } else {
                // Si es "SI", mostrar el hipervínculo
                echo "<a href='../reportes/PDFaluDebeFinal.php' target='_blank'>";
                echo "<i class='bi bi-file-pdf' style='color:black;font-size: 1.5rem;'></i>"; // Bootstrap Icons
                echo "</a>";
            }
                            echo "</td>";  
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
        <script src="../funciones/sessionControl.js"></script>
    <!-- Bootstrap JS y jQuery (necesario para el modal) -->
    <script src="../js/jquery-3.7.1.slim.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <?php include '../funciones/footer.html'; ?>

    </body>
</html>