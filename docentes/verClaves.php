<?php
session_start();
include "../inicio/conexion.php";


// Verifica si se recibió el DNI
if (isset($_POST["dni"])) {
  $dni = $_POST["dni"];

  // Busca la clave en la base de datos
  $queryAlumnos = "SELECT pass.password FROM passwords_alumnos pass INNER JOIN alumnosterciario a ON pass.idAlumno=a.idAlumno INNER JOIN persona p ON a.idPersona=p.idPersona WHERE p.dni='$dni'";
  $queryDocentes = "SELECT pass.password FROM passwords pass INNER JOIN personal ON pass.legajo=personal.legajo INNER JOIN persona ON personal.idPersona=persona.idPersona WHERE persona.dni='$dni'";

  $resultAlumnos = mysqli_query($conn, $queryAlumnos);
  $resultDocentes = mysqli_query($conn, $queryDocentes);

  // Si se encontró la clave, muestra el resultado
  if (mysqli_num_rows($resultAlumnos) > 0) {
    $row = mysqli_fetch_assoc($resultAlumnos);
    $clave = $row["password"];
  } elseif (mysqli_num_rows($resultDocentes) > 0) {
    $row = mysqli_fetch_assoc($resultDocentes);
    $clave = $row["password"];
  } else {
    $clave = "";
  }
}

?>

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
  <link rel="stylesheet" href="[https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
        <div class="col-md-6 offset-md-3">
          <div class="card">
          <div class="card-body">
              <h5 class="card-title">Buscar Clave</h5>
              <form id="buscar-clave-form" method="post">
  <div class="form-group">
    <label for="dni">Ingrese DNI a buscar:</label>
    <input type="text" class="form-control" id="dni" name="dni" value="<?php echo $_POST['dni']; ?>">
  </div>
  <div class="form-group">
    <label for="clave">Contraseña:</label>
    <input type="text" class="form-control" id="clave" name="clave" readonly value="<?php echo $clave; ?>">
  </div>
  <button type="submit" class="btn btn-primary">Buscar</button>
</form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>