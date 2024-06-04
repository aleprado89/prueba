<?php session_start();?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Formulario de Login</title>
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
      <img src="../img/logo merce.jpg" class="img-fluid mx-auto" alt="logo" style="display: block; max-width: 35%; height: auto; margin-top: 5px;  margin-bottom: 5px">
      </div>
      <div class=" col-12 col-md-8">
      <h1 style="font-style: normal; color: #333333; margin-top: 2%; ">ISFD Nuestra Madre de la Merced</h1>
      </div>
      <div class="col-12 col-md-2" >
      <!-- <i class="bi bi-power"></i> -->
      <a class="nav-link" href="#" style="display: flex; flex-direction: column; align-items: center;" onmouseover="this.style.color='#2e8b97'" onmouseleave="this.style.color='#646261'"><i class="bi bi-power" ></i>Cerrar Sesión</a>

      </div>
      </div>
      </div>
    </div>

    <div class="container-fluid text-center fondo padding-bottom" >
    
<div class="container" style="margin-top: 5%; ">
<br>
    <div class="row">
        <div class="col-md-4">
            <!-- Columna en la mitad izquierda de la pantalla -->
            <div class="card margenbottom" style="background-color: #739FA5; ">
              <div class="card-body text-center" style="margin-left:8%; margin-right:8%;">
                
                <h4 class="card-title" style="color: #fff; margin-top: 20%;">Hola<?php echo " ".$_SESSION['alu_nombre']." ".$_SESSION["alu_apellido"]; ?></h4>
                <br>
                <a href="#" class="card-link-act">

                  <i class="bi bi-person-gear icono" style="font-size: 4rem"></i>
                  <h6 >Actualiza tus datos personales</h6>
                </a>
                <br>
                <br>
                <p class="card-text" style="margin-bottom: 20%;">Aquí puedes consultar tus calificaciones y asistencias,
                  actualizar tus datos y realizar inscripciones. 
                  Las inscripciones se abren en períodos de fechas asignados por secretaría.</p>
                <br>
                <br>
              </div>
            </div>
                  </div>

                  

        <div class="card col-md-7 custom-card ">
        <div class="col-md-10 offset-md-1">
            <!-- Columna en la mitad derecha de la pantalla -->
            <h3 class="text-center"style="margin-top:5%;margin-bottom:1%;"><?php echo "Ciclo Lectivo: ".$_SESSION['anioPlataformaAlu']; ?></h3>
           <br> <div class="row elemento" >
                <div class="col-md-6">
                    <!-- Primera columna de la fila superior -->
                    <div class="card mx-auto" style="background-color: #739FA5;margin-bottom: 2%; ">
                      <a href="calificaciones_planes.php" class="card-link">
                      <div class="card-header"></div>
                      <div class="card-body">
                        <h4 class="card-title text-center">Consulta de calificaciones</h4>
                        <div class="card-header text-center">
                          <i class="bi bi-award icono" style="font-size: 2.8rem; text-shadow: none;"></i>
                        </div>
                      </div></a>
                  </div>                </div>
                <div class="col-md-6">
                    <!-- Segunda columna de la fila superior -->
                    <div class="card mx-auto" style="background-color: #739FA5;margin-bottom: 2%;">
                      <a href="#" onclick="verificarFechaInscripcionExamen()" class="card-link">

                      <div class="card-header"></div>
                      <div class="card-body">
                        <h4 class="card-title text-center">Inscripciones a exámenes</h4>
                        <div class="card-header text-center">
                          <i class="bi bi-calendar-week icono" style="font-size: 2.8rem; text-shadow: none;"></i>
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

                      <div class="card-header"></div>
                      <div class="card-body">
                        <h4 class="card-title text-center">Inscripciones a cursado de materias</h4>
                        <div class="card-header text-center">
                          <i class="bi bi-book icono" style="font-size: 2.8rem ; text-shadow: none;"></i>
                        </div>
                      </div>
                      </a>
                  </div>              
                  </div>
          <div class="col-md-6">

<?php
//consulta para saber si se debe mostrar el boton de alumnos que solo deben finales
//........
$aluDebeFinal=0;
if($aluDebeFinal==1)
{
  echo '<div class="card mx-auto" style="background-color: #739FA5; margin-bottom: 2%;">';
echo '<a href="#" class="card-link">';
echo '<div class="card-header"></div>';
echo '<div class="card-body">';
echo '<h4 class="card-title text-center">Alumnos que solo adeudan finales</h4>';
echo '<div class="card-header text-center">';
echo '<i class="bi bi-mortarboard icono" style="font-size: 2.8rem; text-shadow: none;"></i>';
echo '</div>';
echo '</div>';
echo '</a>';
echo '</div>';

}
else 
{
  echo '<div class="card mx-auto" style="background-color: #7A7A7A; margin-bottom: 2%;">';
  echo '<div class="card-header"></div>';
  echo '<div class="card-body">';
  echo '<h4 class="card-title text-center">Alumnos que solo adeudan finales</h4>';
  echo '<div class="card-header text-center">';
  echo '<i class="bi bi-mortarboard icono" style="font-size: 2.8rem; text-shadow: none;"></i>';
  echo '</div>';
  echo '</div>';
  echo '</div>';
}
?>
                    
                
                
                </div>
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

<!--        Pie de página        -->


<footer>
  <div class="container-fluid">

    <p> Desarrollado por  <a target="_blank"  style="text-decoration: none;color:#646261;" href="https://web.sistemasescolares.com.ar"  onmouseover="this.style.color='#2e8b97'" onmouseleave="this.style.color='#646261'">Sistemas escolares</a></p>
  
  </div>
</footer>


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
$sql = "SELECT inscExamDesde,inscExamHasta FROM colegio WHERE codnivel = 6"; 
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
$sql = "SELECT inscCursDesde,inscCursHasta FROM colegio WHERE codnivel = 6"; 
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
          $('#mensajeModal').text("Las inscripciones estan abiertas." ); // Cambiar el contenido del modal con el mensaje
          $('#inscModal').modal('show');
        }
        else {
            // Código para abrir el modal que dice inscripcion cerrada
          $('#mensajeModal').text("Las inscripciones estan cerradas. Los períodos de inscripción son definidos por secretaria."); // Cambiar el contenido del modal con el mensaje
          $('#inscModal').modal('show');
        }
      }
    </script>


    
</body>




</html>





<!-- Iconos bootstrap para usar: 
mortarboard 
mortarboard-fill (son sombreritos de egresado)

calendar-check
calendar-event
card-checklist (calif)

adward (calif)
award-fill

person-gear
person-fill-gear (actualizacion de datos)




-->