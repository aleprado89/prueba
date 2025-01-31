<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';

$doc_legajo = $_SESSION['doc_legajo'];
$nombreDoc = $_SESSION['doc_apellido'] . ", " . $_SESSION['doc_nombre'];
$idMateria = $_SESSION['idMateria'];
$ciclolectivo = $_SESSION['ciclolectivo'];
$plan = $_SESSION['plan'];
$materia = $_SESSION['materia'];
$alumnosAsist = obtenerCalificacionesMateria($conn, $idMateria);
?>
<!DOCTYPE html>
<html lang="es">

</html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ver calificaciones</title>
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
  <?php include '../funciones/menu_docente.php'; ?>

  <div class="container-fluid fondo">
    <br>
    <div class="container">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="menudocentes.php">Inicio</a></li>
        <li class="breadcrumb-item active">Carga de calificaciones parciales</li>
      </ol>

      <div class="card padding col-12">
        <h5><?php echo  "Docente: " . $nombreDoc; ?> </h5>
        <h5><?php echo  "Ciclo lectivo: " . $ciclolectivo; ?> </h5>
        <h5><?php echo  "Carrera: " . $plan; ?> </h5>
        <h5><?php echo  "Materia: " . $materia; ?> </h5><br>
        <label for="fecha">Seleccione fecha:</label>
        <input type="date" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>">    
      </div>
      <div>
      <table id="tablaAsistencia" class="table table-hover col-12">
  <thead>
    <tr class="table-primary">
      <th scope="col">Estudiante</th>
      <th scope="col">Asistencia</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($alumnosAsist as $alumno) { ?>
      <tr>
        <td><?php echo $alumno['apellido'] . " " . $alumno['nombre']; ?></td>
        <td contenteditable="true" data-id="<?php echo $alumno['id']; ?>"></td>
      </tr>
    <?php } ?>
  </tbody>
</table></div>

      <!-- Bootstrap JS y jQuery (necesario para el modal) -->
      <script src="../js/jquery-3.7.1.min.js"></script>
      <script src="../js/popper.min.js"></script>
      <script src="../js/bootstrap.min.js"></script>
      <br>
      <div>
        
      </div>
    </div>
  </div>
  </div>



  <?php include '../funciones/footer.html'; ?>
</body>

<script>
  $(document).ready(function() {
    $('#tablaAsistencia td[contenteditable="true"]').on('input', function() {
      var $this = $(this);
      var id = $this.attr('data-id');
      var asistencia = $this.text().trim();
      if (asistencia.length > 5) {
        alert('La asistencia solo puede tener hasta 5 caracteres');
        $this.text(asistencia.substring(0, 5));
      }
      if (!/^[AaPp]+$/.test(asistencia)) {
        alert('La asistencia solo puede contener letras A, a, P, p');
        $this.text('');
      }
      // Aquí puedes agregar el código para enviar la asistencia al servidor
    });
  });
//funcion para validar la fecha del select
  function isValidDate(fecha) {
  var date = new Date(fecha);
  return !isNaN(date.getTime());
}
//funcion para mostrar alertas de que fecha esta cargando
$(document).ready(function() {
  var fecha = $('#fecha').val();
  alert('Atención, la fecha seleccionada para la carga de asistencia es: ' + fecha + '. Para cargar otra fecha seleccione la fecha deseada.');
  
  var fechaInvalidaMostrada = false;
  
  $('#fecha').on('blur', function() {
    var fecha = $(this).val();
    if (isValidDate(fecha)) {
      alert('Atención, la fecha seleccionada para la carga de asistencia es: ' + fecha + '. Para cargar otra fecha seleccione la fecha deseada.');
      fechaInvalidaMostrada = false; // Resetea la variable
    } else {
      if (!fechaInvalidaMostrada) { // Verifica si se ha mostrado el alert de fecha inválida
        alert('Error: La fecha ingresada es inválida. Por favor, ingrese una fecha válida.');
        $(this).val(''); // Borra el valor del select
        $(this).focus(); // Agrega un foco al select
        fechaInvalidaMostrada = true; // Marca que se ha mostrado el alert de fecha inválida
      }
    }
  });
});
</script>
</html>