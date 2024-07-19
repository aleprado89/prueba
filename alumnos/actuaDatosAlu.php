<?php session_start();
include '../inicio/conexion.php';
//include '../funciones/consultas.php';
//include '../funciones/pruebaSession.php';
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'].", ".$_SESSION['alu_nombre'];
?>
<!DOCTYPE html>
<html lang="es"></html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Datos Personales</title>
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
  <li class="breadcrumb-item"><a href="calificaciones_planes.php">Carreras</a></li>
  <li class="breadcrumb-item"><a href="calificaciones.php">Materias</a></li>
  <li class="breadcrumb-item active">Calificaciones</li>
</ol>

  <div class="card padding col-12">
    <h5><?php echo  "Alumno: ".$nombreAlumno; ?> </h5>
  </div>
  <br>
  <div class="card padding "> 
  <h3>Identidad:</h3>
  <div class="row">
  <div class="col-md-5">
  <label for="fechaNac">Fecha de Nacimiento:</label>
            <input type="date" class="form-control" id="fechaNac" name="fechaNac">
            <label for="nacionalidad">Nacionalidad:</label>
            <input type="text" class="form-control" id="nacionalidad" name="nacionalidad">
  </div>
  <div class="col-md-5 offset-1">
            <label for="sexo">Sexo:</label>
            <select class="form-control" id="sexo" name="sexo">
                    <option value="Masculino">Masculino</option>
                    <option value="Femenino">Femenino</option>
                    <option value="Otro">Otro</option>
                </select>            <label for="fotoCarnet">Foto Carnet:</label>
            <input type="file" class="form-control" id="fotoCarnet" name="fotoCarnet">
  </div>
  </div>
                   
  </div><br>
  <div class="card padding  "> 
  <h3>Ubicacion:</h3>
  <div class="row">
  <div class="col-md-5">
            <label for="lugarNacimiento">Lugar de Nacimiento:</label>
            <input type="text" class="form-control" id="lugarNacimiento" name="lugarNacimiento">
            <label for="provincia">Provincia:</label>
            <input type="text" class="form-control" id="provincia" name="provincia">
            <label for="ciudad">Ciudad:</label>
            <input type="text" class="form-control" id="ciudad" name="ciudad">
            </div> 
            <div class="col-md-5 offset-1">
            <label for="direccion">Direccion:</label>
            <input type="text" class="form-control" id="direccion" name="direccion">
            <label for="barrio">Barrio:</label>
            <input type="text" class="form-control" id="barrio" name="barrio">
            <label for="codigoPostal">Código Postal:</label>
            <input type="text" class="form-control" id="codigoPostal" name="codigoPostal">
            </div></div>
  </div><br>
  <div class="card padding  "> 
  <h3>Contacto:</h3>
  <div class="row">
  <div class="col-md-5">
            <label for="mail">Mail:</label>
            <input type="email" class="form-control" id="mail" name="mail">
            <label for="mailInstitucional">Mail Institucional:</label>
            <input type="email" class="form-control" id="mailInstitucional" name="mailInstitucional">
            </div> 
            <div class="col-md-5 offset-1">
            <label for="telefono">Teléfono:</label>
            <input type="text" class="form-control" id="telefono" name="telefono">
            <label for="celular">Celular:</label>
            <input type="text" class="form-control" id="celular" name="celular">
            <label for="telefonoEmergencia">Teléfono de Emergencia:</label>
            <input type="text" class="form-control" id="telefonoEmergencia" name="telefonoEmergencia">          
  </div></div></div><br>
 </div>
</div>


  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.slim.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

  <?php include '../funciones/footer.html'; ?>

</body>

</html>