<?php
   session_start();

   include '../inicio/conexion.php';
   include '../funciones/consultas.php';
   include '../funciones/parametrosWeb.php';
   
   $doc_legajo = $_SESSION['doc_legajo'];
   $nombreDoc = $_SESSION['doc_apellido'].", ".$_SESSION['doc_nombre'];
   
   //recibo parametros sanitizados
   $idFechaExamen = intval(filter_input(INPUT_GET, 'idFechaExamen', FILTER_VALIDATE_INT));
   $nombreMateria = filter_input(INPUT_GET, 'nombreMateria', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
   $Curso = filter_input(INPUT_GET, 'Curso', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
   $Fecha = filter_input(INPUT_GET, 'Fecha', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
   $Hora = filter_input(INPUT_GET, 'Hora', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
   $idMateria = filter_input(INPUT_GET, 'idMateria', FILTER_VALIDATE_INT);
   
   $CicloLectivo=$datosColegio[0]['anioCargaNotas'];
   $idturno=$datosColegio[0]['cargaActaVolTurno'];
   //obtener idciclo
   $idCicloLectivo=buscarIdCiclo($conn,$CicloLectivo);
   $turno=buscarNombreTurno($conn, $idturno);

    $acta=obtenerActa($conn,$idFechaExamen);


    if ($_POST["accion"] == "actualizarActa") {
        try {
          $idAlumno = $_POST["idAlumno"];
          $columna = $_POST["columna"];
          $valor = $_POST["valor"];       
        $idFechaExamen=$_POST["idFechaExamen"];
          $setUpdate = "$columna = '$valor'";
          $respuesta = actualizarActa($conn,$idFechaExamen, $idAlumno, $setUpdate);
          echo $respuesta;
        } catch (Exception $e) {
          echo "Error: " . $e->getMessage();
        }
        exit;
      }
    ?>
   <!DOCTYPE html>
<html lang="es"></html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mesas de exámenes</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <!-- Bootstrap CSS -->
   <link rel="stylesheet" href="../css/material/bootstrap.min.css">
   <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> 

<!-- Bootstrap JS (necesario para el navvar) -->
<script src="../js/bootstrap.min.js"></script> 
<script src="../js/bootstrap.bundle.min.js"></script>
  <!-- Bootstrap JS y jQuery (necesario para el modal) -->
  <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script> 

</head>
<body>
<?php include '../funciones/menu_docente.php';?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
  <ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="menudocentes.php">Inicio</a></li>
  <li class="breadcrumb-item"><a href="mesasExamenProf.php">Mesas de exámenes</a></li>
   <li class="breadcrumb-item active">Carga de Acta</li> 
</ol>

  <div class="card padding col-12">
    <h5><?php echo  "Docente: ".$nombreDoc; ?> </h5>    
<h5><label for="ciclolectivo">Año: <?php echo $CicloLectivo; ?></label></h5>
<h5><?php echo  "Turno: ".$turno; ?> </h5>    
<h5><?php echo  "Materia: ".$nombreMateria; ?> </h5>    
<h5><?php echo  "Curso: ".$Curso; ?> </h5>   
<h5><?php echo  "Fecha mesa de examen: ".$Fecha.' - '.$Hora; ?> </h5>    
  </div>
      <br>
      <div class="container">
      <table id="tablaActa" class="table table-hover col-12">
  <thead>
    <tr class="table-primary">
      <th scope="col">Alumno</th>
      <th scope="col">Oral</th>
      <th scope="col">Escrito</th>
      <th scope="col">Calificación</th>
      <th scope="col">Libro</th>
      <th scope="col">Folio</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($acta as $alumno) { ?>
      <tr data-id-alumno="<?php echo $alumno['idAlumno']; ?>">
        <td contenteditable="false"><?php echo $alumno['apellido'] . ', ' . $alumno['nombre']; ?></td>
        <td contenteditable="true" data-columna="oral" oninput="actualizarCelda( <?php echo $alumno['idAlumno']; ?>, 'oral', this.textContent)"><?php echo $alumno['oral']; ?></td>
<td contenteditable="true" data-columna="escrito" oninput="actualizarCelda( <?php echo $alumno['idAlumno']; ?>, 'escrito', this.textContent)"><?php echo $alumno['escrito']; ?></td>
<td contenteditable="true" data-columna="calificacion" oninput="actualizarCelda( <?php echo $alumno['idAlumno']; ?>, 'calificacion', this.textContent)"><?php echo $alumno['calificacion']; ?></td>
<td contenteditable="true" data-columna="libro" oninput="actualizarCelda(<?php echo $alumno['idAlumno']; ?>, 'libro', this.textContent)"><?php echo $alumno['libro']; ?></td>
<td contenteditable="true" data-columna="folio" oninput="actualizarCelda( <?php echo $alumno['idAlumno']; ?>, 'folio', this.textContent)"><?php echo $alumno['folio']; ?></td>
 </tr>
    <?php } ?>
  </tbody>
</table>
</div>
  
        </div>
  </div>
  
  
  
  
    <?php include '../funciones/footer.html'; ?>
</body>
</html>

<script>
    
    function actualizarCelda(idAlumno, columna, valor) {
      if (['escrito', 'oral', 'calificacion'].includes(columna)) {
    const valoresValidos = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'A', 'a', ''];
    if (!valoresValidos.includes(valor)) {
      alert("Dato inválido");
      $(`tr[data-id-alumno="${idAlumno}"] td[data-columna="${columna}"]`).text('');
      valor = "";
    }
  } else if (['libro', 'folio'].includes(columna)) {
    if (valor.length > 10) {
      alert("Máximo 10 caracteres permitidos");
      $(`tr[data-id-alumno="${idAlumno}"] td[data-columna="${columna}"]`).text('');
      valor = "";
    }
  }
  //cargo ajax
  var datos = {
    accion: "actualizarActa",
    idAlumno: idAlumno,
    columna: columna,
    valor: valor,
    idFechaExamen: <?php echo $idFechaExamen; ?>
  };
  console.log(datos);
  $.ajax({
    type: "POST",
    url: "cargaActa.php",
    data: datos,
       success: function(respuesta) {
console.log(respuesta);
      if (respuesta == "actualizado") {
        $(`tr[data-id-alumno="${idAlumno}"] td[data-columna="${columna}"]`).css('background-color', 'lightgreen');
      }     
    },
    error: function(xhr, status, error) {
      console.log("Error en la petición AJAX:", error);
    }
  });
}

</script>