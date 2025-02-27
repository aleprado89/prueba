<?php session_start();?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menú de alumnos</title>
    <!-- Bootswatch Material theme -->
    <!-- <link rel="stylesheet" href="../css/estilo-prebootstrap.css"> -->
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
      <a class="nav-link" href="../funciones/cerrarsesion.php" style="display: flex; flex-direction: column; align-items: center;" onmouseover="this.style.color='#2e8b97'" onmouseleave="this.style.color='#646261'"><i class="bi bi-power" ></i>Cerrar Sesión</a>
      </div>
      </div>
      </div>
    </div>

    <div class="container-fluid text-center fondo padding-bottom margenbottom2" >
    
<div class="container" style="margin-top: 5%; ">

<br>
    <div class="row">
        <div class="col-md-4 ">
            <!-- Columna en la mitad izquierda de la pantalla -->
            <div class="card margenbottom barralateralmenu" style="background-color: #739FA5; ">
              <div class="card-body text-center" style="margin-left:8%; margin-right:8%;">
                
                <h4 class="card-title" style="color: #fff; margin-top: 20%;">Hola<?php echo " ".$_SESSION['alu_nombre']." ".$_SESSION["alu_apellido"]; ?></h4>
                <br>      
                
                <div class="alert alert-dismissible alert-danger">
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  <h4 style="color:#333333;">Inscripciones:</h4>
  <p class="mb-0">Las inscripciones a exámenes y cursado están cerradas.</a></p>
</div> <br>
<p class="card-text" style="margin-bottom: 20%;">Aquí puedes consultar tus calificaciones y asistencias,
                  actualizar tus datos personales y realizar inscripciones. 
                  Las inscripciones se abren en períodos de fechas asignados por secretaría.</p>
               
                </div>
            </div>
                  </div>

                  
            <!-- Columna en la mitad derecha de la pantalla -->

        <div class="card col-md-7 custom-card ">
        <div class="col-md-10 offset-md-1">
            <h3 class="text-center"style="margin-top:5%;margin-bottom:1%;"><?php echo "Ciclo Lectivo: ".$_SESSION['anioPlataformaAlu']; ?></h3>
           <br> <br><div class="row elemento" >
                <div class="col-md-6">
                    <!-- Primera columna de la fila superior -->
                    <div class="card mx-auto" style="background-color: #739FA5;margin-bottom: 2%; ">
                      <a href="calificaciones_planes.php" class="card-link">
                      <div class="card-body">
                        <h4 class="card-title text-center">Consulta de calificaciones</h4>
                        <div class="text-center">
                          <i class="bi bi-award icono" style="font-size: 3.2rem; text-shadow: none;"></i>
                        </div>
                      </div></a>
                  </div>                </div>
                <div class="col-md-6">
                    <!-- Segunda columna de la fila superior -->
                    <div class="card mx-auto" style="background-color: #739FA5;margin-bottom: 2%;">
                      <a href="#" onclick="verificarFechaInscripcionExamen()" class="card-link">
                      <div class="card-body">
                        <h4 class="card-title text-center">Inscripciones a exámenes</h4>
                        <div class="text-center">
                          <i class="bi bi-calendar-week icono" style="font-size: 3.2rem; text-shadow: none;"></i>
                        </div>
                      </div>
                      </a>
                  </div>                 
                 </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <!-- Primera columna de la fila inferior -->
                    <div class="card mx-auto" style="background-color: #739FA5;margin-bottom: 2%;">
                      <a href="#" onclick="verificarFechaInscripcionCursado()" class="card-link">
                      <div class="card-body">
                        <h4 class="card-title text-center">Inscripciones a cursado de materias</h4>
                        <div class="text-center">
                          <i class="bi bi-book icono" style="font-size: 3.2rem ; text-shadow: none;"></i>
                        </div>
                      </div>
                      </a>
                  </div>              
                  </div>
          <div class="col-md-6">
          <!-- Segunda columna de la fila inferior -->
                    <div class="card mx-auto" style="background-color: #739FA5;margin-bottom: 2%;">
                    <a href="actuaDatosAlu.php" class="card-link">
                    <div class="card-body">
                        <h4 class="card-title text-center">Actualizar datos personales</h4>
                        <div class="text-center">
                        <i class="bi-person-gear icono" style="font-size: 3.2rem; text-shadow: none;"></i>
                        </div>
                      </div>
                      </a>
                  </div>              
                </div>

<?php
//consulta para saber si se debe mostrar el boton de alumnos que solo deben finales
//........
$aluDebeFinal=$_SESSION['aluDebeFinal'];
if($aluDebeFinal==1)
{
echo '<div class="col-md-12">';
echo '<div class="card mx-auto" style="background-color: #739FA5; ">';
echo '<a href="aluDebeFinal.php" class="card-link">';
echo '<div class="card-body">';
echo '<h4 class="card-title text-center">Alumnos que solo adeudan finales</h4>';
echo '<div class="text-center">';
echo '<i class="bi bi-mortarboard icono" style="font-size: 3.2rem; text-shadow: none;"></i>';
echo '</div>';
echo '</div>';
echo '</a>';
echo '</div>';
echo '</div>';
}
?>
            </div>
        </div>
      </div>
    </div>
</div>
<br>



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
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
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
        /////COMIENZA FUNCION PARA VERIFICAR SI ESTA ABIERTA INSCRIPCION CURSADO
          function verificarFechaInscripcionExamen() {
 <?php 
include '../inicio/conexion.php';

// Realizar la consulta para obtener la fecha de inscripción DE EXAMEN
$sql = "SELECT inscExamDesde,inscExamHasta FROM colegio WHERE idColegio =".$_SESSION['idColegio']; 
$resultado = $conn->query($sql);

if ($resultado->num_rows > 0) {
  $fila = $resultado->fetch_assoc();
  $fechaInscExamDesde = $fila['inscExamDesde'];
  $fechaInscExamHasta = $fila['inscExamHasta'];

} else {
  $fechaInscExamDesde = null;
  $fechaInscExamHasta = null;
}
$conn->close();
                 ?>//TERMINA EL PHP - SIGUE  EL SCRIPT QUE ABRIRÁ EL MODAL  

        var inscExamDesde = new Date("<?php echo $fechaInscExamDesde; ?>");
        var inscExamHasta = new Date("<?php echo $fechaInscExamHasta; ?>");
        var fechaActual = new Date();
        
        if (inscExamDesde <= fechaActual && inscExamHasta>= fechaActual) {
          //codigo para ingresar al formulario de la inscripcion
          window.location.href = 'examenes_planes.php';
          //$('#mensajeModal').text("Las inscripciones estan abiertas." ); // Cambiar el contenido del modal con el mensaje
          //$('#inscModal').modal('show');
        }
        else {
            // Código para abrir el modal que dice inscripcion cerrada
          $('#mensajeModal').text("Las inscripciones estan cerradas. Los períodos de inscripción son definidos por secretaria."); // Cambiar el contenido del modal con el mensaje
          $('#inscModal').modal('show');
        }
      }



      /////COMIENZA FUNCION PARA VERIFICAR SI ESTA ABIERTA INSCRIPCION CURSADO
      function verificarFechaInscripcionCursado() {
 <?php 
include '../inicio/conexion.php';

// Realizar la consulta para obtener la fecha de inscripción DE EXAMEN
$sql = "SELECT inscCursDesde,inscCursHasta FROM colegio WHERE idColegio =".$_SESSION['idColegio']; 
$resultado = $conn->query($sql);

if ($resultado->num_rows > 0) {
  $fila = $resultado->fetch_assoc();
  $fechaInscCursDesde = $fila['inscCursDesde'];
  $fechaInscCursHasta = $fila['inscCursHasta'];

} else {
  $fechaInscCursDesde = null;
  $fechaInscCursHasta = null;
}
$conn->close();
                 ?>//TERMINA EL PHP - SIGUE  EL SCRIPT QUE ABRIRÁ EL MODAL  

        var inscCursDesde = new Date("<?php echo $fechaInscCursDesde; ?>");
        var inscCursHasta = new Date("<?php echo $fechaInscCursHasta; ?>");
        var fechaActual = new Date();
        
        if (inscCursDesde <= fechaActual && inscCursHasta>= fechaActual) {
          //codigo para ingresar al formulario de la inscripcion
          window.location.href = 'materias_planes.php';
        //  $('#mensajeModal').text("Las inscripciones estan abiertas." ); // Cambiar el contenido del modal con el mensaje
         // $('#inscModal').modal('show');
        }
        else {
            // Código para abrir el modal que dice inscripcion cerrada
          $('#mensajeModal').text("Las inscripciones estan cerradas. Los períodos de inscripción son definidos por secretaria."); // Cambiar el contenido del modal con el mensaje
          $('#inscModal').modal('show');
        }
      }
    </script>

<!-- Agrega este script al final de tu archivo HTML, antes de cerrar el body -->
<script>
    <?php
    include '../inicio/conexion.php';

    // Realiza tu consulta MySQL para obtener un valor
    $sql = "SELECT inscCursDesde, inscCursHasta, inscExamDesde, inscExamHasta FROM colegio WHERE idColegio =" . $_SESSION['idColegio'];
    $resultado = $conn->query($sql);
    if ($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        $fechaInscCursDesde = $fila['inscCursDesde'];
        $fechaInscCursHasta = $fila['inscCursHasta'];
        $fechaInscExamDesde = $fila['inscExamDesde'];
        $fechaInscExamHasta = $fila['inscExamHasta'];
    } else {
        $fechaInscCursDesde = null;
        $fechaInscCursHasta = null;
        $fechaInscExamDesde = null;
        $fechaInscExamHasta = null;
    }
    $conn->close();
    $fechaActual = date('Y-m-d H:i:s');
    
     if ($fechaInscExamDesde <= $fechaActual && $fechaInscExamHasta >= $fechaActual && $fechaInscCursDesde <= $fechaActual && $fechaInscCursHasta >= $fechaActual) {
      echo 'var alertDiv = document.querySelector(".alert");'; // Selecciona el elemento <div> con la clase alert
       echo 'alertDiv.classList.remove("alert-danger");'; // Elimina la clase alert-secondary
       echo 'alertDiv.classList.add("alert-success");'; // Agrega la clase alert-danger
       echo 'alertDiv.querySelector("h4").textContent = "¡Atención!";'; // Modifica el texto del título
      echo 'alertDiv.querySelector("p").textContent = "Las inscripciones a exámenes y cursado están abiertas";'; // Modifica el texto del contenido
   }else
    if ($fechaInscCursDesde <= $fechaActual && $fechaInscCursHasta >= $fechaActual) {
        echo 'var alertDiv = document.querySelector(".alert");'; // Selecciona el elemento <div> con la clase alert
        echo 'alertDiv.classList.remove("alert-danger");'; // Elimina la clase alert-secondary
        echo 'alertDiv.classList.add("alert-success");'; // Agrega la clase alert-danger
        echo 'alertDiv.querySelector("h4").textContent = "¡Atención!";'; // Modifica el texto del título
        echo 'alertDiv.querySelector("p").textContent = "Las inscripciones a cursado están abiertas";'; // Modifica el texto del contenido
    
  }else    
    if ($fechaInscExamDesde <= $fechaActual && $fechaInscExamHasta >= $fechaActual) {
      
        echo 'var alertDiv = document.querySelector(".alert");'; // Selecciona el elemento <div> con la clase alert
        echo 'alertDiv.classList.remove("alert-danger");'; // Elimina la clase alert-secondary
        echo 'alertDiv.classList.add("alert-success");'; // Agrega la clase alert-danger
        echo 'alertDiv.querySelector("h4").textContent = "¡Atención!";'; // Modifica el texto del título
        echo 'alertDiv.querySelector("p").textContent = "Las inscripciones a exámenes están abiertas";'; // Modifica el texto del contenido
    }
    ?>
</script>    
<?php include '../funciones/footer.html'; ?>
</body>
</html>

