<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Formulario de Login</title>
    <!-- Bootswatch Material theme -->
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <!-- <link rel="stylesheet" href="../css/bootstrap.min.css"> -->
  <!-- Font Awesome CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> 

<style>
.card-link {
  text-decoration: none; /* Para quitar el subrayado del enlace */
  text-shadow: 0px 4px 4px rgba(0, 0, 0, 0.25);
  color: #ffffff;  /*Color de texto por defecto */
  transition: all 0.3s; /* Transición suave de los estilos al pasar el mouse */
  box-shadow: 0px 4px 4px rgba(0, 0, 0, 0.25);
  border-radius: 5px;
  }
  @media  (min-width:800px) {
  .card-link {
    min-height: 280px;
  }
}
.card-link:hover {
  color: #fff; /* Color de texto al pasar el mouse */
  background-color: #40848d; /* Color de fondo al pasar el mouse */
  border-radius: 5px; /* Bordes redondeados al pasar el mouse */

}
.card-link .icono {
  color: #597c81; /* Nuevo color al hacer hover sobre la card */
}
.card-link:hover .icono {
  color: #739FA5; /* Nuevo color al hacer hover sobre la card */
}
.card-link-act{
  text-decoration: none; /* Para quitar el subrayado del enlace */
  color: #2c3236; /* Color de texto por defecto */
  transition: all 0.3s; /* Transición suave de los estilos al pasar el mouse */
}
.card-link-act:hover{
  color: #fff; /* Color de texto al pasar el mouse */
  /* background-color: #19608f; Color de fondo al pasar el mouse */
}
.card-link-act .icono {
  color: #597c81; /* Nuevo color al hacer hover sobre la card */
}
.card-link-act:hover .icono {
  color: #40848d; /* Nuevo color al hacer hover sobre la card */
}

/* Estilo para margen de cuadritos diferenciados en celulares */
.elemento {
  margin-bottom: 3%; /* Margen inferior por defecto */
}

/* Media query para dispositivos con un ancho máximo de 767px (celulares) */
@media only screen and (max-width: 767px) {
  .elemento {
    margin-bottom: 0%; /* Margen inferior para celulares */
  }
}
@media (min-width:800px)  {

.card {
    min-height: 280px;
}
}

.header {
    display: flex;
  justify-content: center; /* Centra horizontalmente */
  align-items: center; /* Centra verticalmente */
  background-color:#ffff;
  margin-top:0%;
  margin-bottom: -1%;
}

</style>


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
      <a class="icon-link" href="../funciones/cerrarsesion.php" style="margin-top:10%; ">
  Cerrar Sesión
  <i class="bi bi-power"></i>
</a>
      </div>
      </div>
      </div>
    </div>

    <div class="container-fluid text-center " style="background-color: #E6EAE8;height: calc(100vh - 100px);">
<div class="container" style="margin-top: 5%;">
    <div class="row">
        <div class="col-md-4">
            <!-- Columna en la mitad izquierda de la pantalla -->
            <div class="card " style="background-color: #739FA5;margin-bottom: 2%; ">
              <div class="card-body text-center" style="margin-left:8%; margin-right:8%;">
                
                <h4 class="card-title" style="color: #fff; margin-top: 20%;">Hola <?php session_start(); echo $_SESSION['doc_nombre']." ".$_SESSION["doc_apellido"]; ?></h4>
                <br>
                <a href="#" class="card-link-act">

                  <i class="bi bi-person-gear icono" style="font-size: 4rem"></i>
                  <h6 >Actualiza tus datos personales</h6>
                </a>
                <br>
                <p class="card-text" style="margin-bottom: 20%;">Aquí puedes cargar calificaciones y asistencias y
                  actualizar tus datos personales. Si se utiliza la carga de Actas desde esta plataforma, 
                  se abrirá solo en los períodos de fechas que secretaría habilite.
                  </p>
                
              </div>
            </div>
                  </div>

                 

        <div class="card col-md-7">
        <div class="col-md-10 offset-md-1">
            <!-- Columna en la mitad derecha de la pantalla -->
            <h3 class="text-center"style="margin-top:1%;margin-bottom:10%;"><?php echo "Ciclo Lectivo: ".$_SESSION['anioPlataformaDoc']; ?></h3>
            <div class="row elemento" >
                <div class="col-md-6">
                    <!-- Primera columna de la fila superior -->
                    <div class="card mx-auto" style="background-color: #739FA5;margin-bottom: 2%; ">
                      <a href="#" class="card-link">
                      <div class="card-header"></div>
                      <div class="card-body">
                        <h4 class="card-title text-center">Carga de Calificaciones y Asistencias</h4>
                        <div class="card-header text-center">
                          <i class="bi bi-award icono" style="font-size: 2.8rem; text-shadow: none;"></i>
                        </div>
                      </div></a>
                  </div>                </div>
                <div class="col-md-6">
                    <!-- Segunda columna de la fila superior -->
                    <div class="card mx-auto" style="background-color: #739FA5;margin-bottom: 2%;">
                      <a href="#" onclick="verificarFechaActasVolantes()" class="card-link">

                      <div class="card-header"></div>
                      <div class="card-body">
                        <h4 class="card-title text-center">Carga de Actas de Exámenes Finales</h4>
                        <div class="card-header text-center">
                          <i class="bi bi-mortarboard icono" style="font-size: 2.8rem; text-shadow: none;"></i>
                        </div>
                      </div>
                      </a>
                  </div>               
                 </div>
            </div>
            <!-- <div class="row">
                <div class="col-md-6">
                     Primera columna de la fila inferior 
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
                     Segunda columna de la fila inferior 
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
                  </div>                </div> -->
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
          function verificarFechaActasVolantes() {
 <?php 
include '../inicio/conexion.php';

// Realizar la consulta para obtener la fecha de inscripción DE EXAMEN
$sql = "SELECT cargaActaVolDesde,cargaActaVolHasta FROM colegio WHERE codnivel = 6"; 
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
