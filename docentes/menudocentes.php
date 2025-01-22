<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menú de Docentes</title>
    <!-- Bootswatch Material theme -->
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">

  <!-- <link rel="stylesheet" href="../css/bootstrap.min.css"> -->
  <!-- Font Awesome CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> 

</head>
<body>
<div class="container-fluid header" >
<div class="container header">
    <div class="row header" >
      <div class="col-12 col-md-2"  >
      <img src="<?php echo $_SESSION['logo']; ?>" class="img-fluid mx-auto" alt="logo" style="display: block; max-width: 35%; height: auto; margin-top: 5px;  margin-bottom: 5px">
      </div>
      <div class=" col-12 col-md-8">
      <h1 style="font-style: normal; color: #333333; margin-top: 2%; "><?php echo $_SESSION['nombreColegio']?></h1>
      </div>
      <div class="col-12 col-md-2" >
      <!-- <i class="bi bi-power"></i> -->
      <a class="icon-link" href="../funciones/cerrarsesion.php" style="margin-top: 10%; display: flex; flex-direction: column; align-items: center; text-decoration: none;">
  <i class="bi bi-power"></i>
  Cerrar Sesión
</a>
      </div>
      </div>
      </div>
    </div>

    <div class="container-fluid text-center fondo" >
<div class="container" style="margin-top: 5%;">
<br>
    <div class="row">
        <div class="col-md-4">
            <!-- Columna en la mitad izquierda de la pantalla -->
            <div class="card carddoc " style="background-color: #739FA5;margin-bottom: 2%; ">
              <div class="card-body text-center" style="margin-left:8%; margin-right:8%;">
                
                <h4 class="card-title" style="color: #fff; margin-top: 20%;">Hola <?php echo $_SESSION['doc_nombre']." ".$_SESSION["doc_apellido"]; ?></h4>
                <br>
                <div class="alert alert-dismissible alert-danger">
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  <h4 style="color:#333333;">Actas:</h4>
  <p class="mb-0">El módulo de carga de actas de exámenes están cerradas.</a></p>
</div>
                <br>
                <p class="card-text" style="margin-bottom: 20%;">Aquí puedes cargar calificaciones y asistencias y
                  actualizar tus datos personales. Si se utiliza la carga de Actas desde esta plataforma, 
                  se abrirá solo en los períodos de fechas que secretaría habilite.
                  </p>
                
              </div>
            </div>
                  </div>

                 

        <div class="card carddoc col-md-7 custom-card">
        <div class="col-md-10 offset-md-1">
            <!-- Columna en la mitad derecha de la pantalla -->
            <h3 class="text-center"style="margin-top:1%;margin-bottom:10%;"><?php echo "Ciclo Lectivo: ".$_SESSION['anioPlataformaDoc']; ?></h3>
            <div class="row elemento" >
                <div class="col-md-6">
                    <!-- Primera columna de la fila superior -->
                    <div class="card  mx-auto" style="background-color: #739FA5;margin-bottom: 2%; ">
                      <a href="materiaxdocente.php" class="card-link ">
                      <div class="card-header"></div>
                      <div class="card-body">
                        <h4 class="card-title text-center">Carga de Calificaciones Parciales</h4>
                        <div class="card-header text-center">
                          <i class="bi bi-award icono" style="font-size: 2.8rem; text-shadow: none;"></i>
                        </div>
                      </div></a>
                  </div>                </div>
                <div class="col-md-6">
                    <!-- Segunda columna de la fila superior -->
                    <div class="card  mx-auto" style="background-color: #739FA5;margin-bottom: 2%;">
                      <a href="#" class="card-link ">

                      <div class="card-header"></div>
                      <div class="card-body">
                        <h4 class="card-title text-center">Carga de Asistencias</h4>
                        <div class="card-header text-center">
                          <i class="bi bi-mortarboard icono" style="font-size: 2.8rem; text-shadow: none;"></i>
                        </div>
                      </div>
                      </a>
                  </div>               
                 </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                     <!-- Primera columna de la fila inferior  -->
                    <div class="card mx-auto" style="background-color: #739FA5;margin-bottom: 2%;">
                    <a href="#" onclick="verificarFechaActasVolantes()" class="card-link ">

                      <div class="card-header"></div>
                      <div class="card-body">
                        <h4 class="card-title text-center">Carga de Actas de Exámenes Finales</h4>
                        <div class="card-header text-center">
                          <i class="bi bi-book icono" style="font-size: 2.8rem ; text-shadow: none;"></i>
                        </div>
                      </div>
                      </a>
                  </div>              
                  </div>
                <div class="col-md-6">
                     <!-- Segunda columna de la fila inferior  -->
                    <!-- <div class="card mx-auto" style="background-color: #739FA5;margin-bottom: 2%;">
                      <a href="#" onclick="verificarFechaInscripcionExamen()" class="card-link">

                      <div class="card-header"></div>
                      <div class="card-body">
                        <h4 class="card-title text-center">Inscripciones a exámenes</h4>
                        <div class="card-header text-center">
                          <i class="bi bi-calendar-week icono" style="font-size: 2.8rem; text-shadow: none;"></i>
                        </div>
                      </div>
                      </a>
                  </div> -->              
                 </div> 
            </div>
        </div>
      </div>
    </div>
</div>


<!-- Modal -->
<div class="modal" id="inscModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Inscripciones Cerradas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true"></span>
        </button>
      </div>
      <div class="modal-body">
      <p id="mensajeModal"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>


       <!--           FUNCIONES     y SCRIPTS        -->

<!-- Bootstrap JS y jQuery (necesario para el modal) -->
<script src="../js/jquery-3.7.1.slim.min.js"></script>
 <script src="../js/bootstrap.min.js"></script> 
 <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>

<script>
        /////COMIENZA FUNCION PARA VERIFICAR SI ESTA ABIERTA CARGA DE ACTAS
          function verificarFechaActasVolantes() {
 <?php 
include '../inicio/conexion.php';

// Realizar la consulta para obtener la fecha de ACTAS
$sql = "SELECT cargaActaVolDesde,cargaActaVolHasta FROM colegio WHERE idColegio =" . $_SESSION['idColegio']; 
$resultado = $conn->query($sql);

if ($resultado->num_rows > 0) {
  $fila = $resultado->fetch_assoc();
  $fechaCargaActaDesde = $fila['cargaActaVolDesde'];
  $fechaCargaActaHasta = $fila['cargaActaVolHasta'];

} else {
  $fechaCargaActaDesde = null;
  $fechaCargaActaHasta = null;
}
$conn->close();
                 ?>//TERMINA EL PHP - SIGUE  EL SCRIPT QUE ABRIRÁ EL MODAL  

        var cargaActaVolDesde = new Date("<?php echo $fechaCargaActaDesde; ?>");
        var cargaActaVolHasta = new Date("<?php echo $fechaCargaActaHasta; ?>");
        var fechaActual = new Date();
        
        if (cargaActaVolDesde <= fechaActual && cargaActaVolHasta>= fechaActual) {
          //codigo para ingresar al formulario de la carga de actas
          $('#mensajeModal').text("La carga de actas está abierta." ); // Cambiar el contenido del modal con el mensaje
          $('#inscModal').modal('show');
        }
        else {
            // Código para abrir el modal que dice inscripcion cerrada
          $('#mensajeModal').text("La carga de actas está cerrada. Los períodos carga de actas son definidos por secretaria."); // Cambiar el contenido del modal con el mensaje
          $('#inscModal').modal('show');
        }
      }
      </script>
      <script>


               <?php
    include '../inicio/conexion.php';

    // Realiza tu consulta MySQL para obtener un valor
    $sql = "SELECT  cargaActaVolDesde,cargaActaVolHasta FROM colegio WHERE idColegio =" . $_SESSION['idColegio'];
    $resultado = $conn->query($sql);
    if ($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        $fechaCargaActaDesde = $fila['cargaActaVolDesde'];
        $fechaCargaActaHasta = $fila['cargaActaVolHasta'];
    } else {
      $fechaCargaActaDesde = null;
      $fechaCargaActaHasta = null;
    }
    $conn->close();
    $fechaActual = date('Y-m-d H:i:s');
    
     
    if ($fechaCargaActaDesde <= $fechaActual && $fechaCargaActaHasta >= $fechaActual) {
      
        echo 'var alertDiv = document.querySelector(".alert");'; // Selecciona el elemento <div> con la clase alert
        echo 'alertDiv.classList.remove("alert-danger");'; // Elimina la clase alert-secondary
        echo 'alertDiv.classList.add("alert-success");'; // Agrega la clase alert-danger
        echo 'alertDiv.querySelector("h4").textContent = "¡Atención!";'; // Modifica el texto del título
        echo 'alertDiv.querySelector("p").textContent = "La carga de actas de exámenes está abierta";'; // Modifica el texto del contenido
    }
    ?>
  
    </script>
    <?php include '../funciones/footer.html'; ?>
</body>
</html>
