<?php session_start(); 
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
//include '../funciones/pruebaSession.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  //BOTON SELECCIONAR PLAN
  if (isset($_POST['submitVer'])) {

    $idP = $_POST['idP'];
    $nombreP = $_POST['nombreP'];
    $_SESSION['idP'] = $idP;
    $_SESSION['nombreP'] = $nombreP;

    header("Location: ../alumnos/examenes_materias.php");
    exit;
  }
}

//VARIABLES
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_nombre'] . " " . $_SESSION['alu_apellido'];
$cicloLectivo =  $datosColegio[0]['anioautoweb'];

//FUNCIONES
//LISTAR PLANES
$listadoPlanes = array();
$listadoPlanes = buscarPlanes($conn, $idAlumno);
$cantidad = count($listadoPlanes);

$idPlanGrafico = $listadoPlanes[0]['idPlan'];

$listadoCurricula = array();
$listadoCurricula = estadoPlan($conn, $idAlumno, $idPlanGrafico, $cicloLectivo);
$cantidadMaterias = count($listadoCurricula);
$cantidadAprobado = 0;
$cantidadCursando = 0;
$cantidadFaltante = 0;
$b = 0;
while ($b < $cantidadMaterias) {
  if ($listadoCurricula[$b]['idCalificacion'] != null) {
    if ($listadoCurricula[$b]['materiaAprobada'] == 1) {
      $cantidadAprobado = $cantidadAprobado + 1;
    } else {
      $cantidadCursando = $cantidadCursando + 1;
    }
  } else {
    $cantidadFaltante = $cantidadFaltante + 1;
  }
  $b++;
}
?>
<!DOCTYPE html>
<html lang="es"></html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carreras</title>
  <!-- Bootstrap CSS -->
  <!-- <link rel="stylesheet" href="../css/estilo-prebootstrap.css"> -->
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Bootstrap JS (necesario para el navvar) -->
  <script src="../js/bootstrap.min.js"></script>
  <script src="../js/chartjs/dist/chart.umd.js"></script><!-- charjs  (necesario para el grafico de torta) -->

  
</head>

<body>
<?php include '../funciones/menu.php'; ?>

  <div class="container-fluid fondo">
  <br>
  <div class="container">
  <ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="menualumnos.php">Inicio</a></li>
  <li class="breadcrumb-item active">Inscripción a exámenes</li>
</ol>
      <div class="row">


        <div class="card margenes padding col-md-3">
          <br>
          <h3>
            <?php echo $_SESSION['alu_nombre']; ?>
          </h3>
          <h4>Progreso Carrera:</h4>
          <h6> <?php echo $listadoPlanes[0]['Plan']; ?></h6>

          <!--        ESTADISTICCASSSSSSSSSSSSSSSSSSS        -->

          <div class="estadisticas"><canvas id="myPieChart"></canvas></div>

        </div>
        <div class="card col-md-8 margenes padding">
          <br>
          <h3>Seleccione la carrera:</h3>

          <!-- FORM VER PLAN -->
          <form id="envio" method="post">
            <input type="hidden" name="idP" id="idP">
            <input type="hidden" name="nombreP" id="nombreP">

            <!-- TABLA DE PLANES -->
            <table class="table tabla-calificaciones-planes table-borderless">
              <thead>
                <tr>
                  <th scope="col" style="display:none;">idPlan</th>
                  <th scope="col"></th>
                </tr>
              </thead>
              <tbody>

                <?php

                //RECORRER TABLA DE PLANES                
                $a = 0;
                while ($a < $cantidad) {
                  $idPlan = $listadoPlanes[$a]['idPlan'];
                  $Plan = $listadoPlanes[$a]['Plan'];
                  $a++;
                  ?>

                  <tr>
                    <td style="display:none;">
                      <?php echo $idPlan ?>
                    </td>
                    <td>
                      <?php echo $Plan ?>
                    </td>
                    <td><button type="submit" name="submitVer" class="btn btn-primary ver-btn">Ver
                        Materias</button></td>
                  </tr>

                  <?php
                }
                ?>

              </tbody>
            </table>
          </form>
        </div>
      </div>
    </div>

    <script>

      var cantidadAprobado = <?php echo $cantidadAprobado; ?>;
      var cantidadCursando = <?php echo $cantidadCursando; ?>;
      var cantidadFaltante = <?php echo $cantidadFaltante; ?>;

      ////////////////////////SCRIPT PARA GRAFICOS CHAR////////////////////////
      var ctx = document.getElementById('myPieChart').getContext('2d');
      var myPieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Aprobadas', 'Cursando', 'A cursar'],
          datasets: [{
            data: [cantidadAprobado, cantidadCursando, cantidadFaltante],
            backgroundColor: ['#40848d', '#333333', '#c1d7da']
          }]
        },
        options: {
          legend: {
            display: true,
            position: 'bottom' // Coloca la leyenda debajo del gráfico
          }
        }
      });


      ///////////////////////////SCRIPT PARA BOTON VER CALIFICACIONES//////////////////////////////#


      //SCRIPT PARA SELECCIONAR DATOS DEL PLAN A VER
      document.addEventListener("DOMContentLoaded", function () {
        // Agregar un evento de clic a todos los botones con la clase 'ver-btn'
        var botones = document.querySelectorAll('.ver-btn');
        botones.forEach(function (boton) {
          boton.addEventListener('click', function () {
            // Obtener los datos de la fila seleccionada
            var fila = this.closest('tr');
            var idPlanSeleccionado = fila.querySelector("td:nth-child(1)").innerText;
            var nombrePlanCompleto = fila.querySelector("td:nth-child(2)").innerText;
            // Cargar Datos
            document.getElementById("idP").value = idPlanSeleccionado;
            document.getElementById("nombreP").value = nombrePlanCompleto;
            // Enviar el formulario
            document.getElementById("envio").submit();
          });
        });
      });
    </script>
    
  </div>

  <?php include '../funciones/footer.html'; ?>

</body>

</html>