<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';

$doc_legajo = $_SESSION['doc_legajo'];
$nombreDoc = $_SESSION['doc_apellido'] . ", " . $_SESSION['doc_nombre'];
$idMateria = $_SESSION['idMateria'];
$ciclolectivo = $_SESSION['ciclolectivo'];
$idCurso=buscarIdCiclo($conn,$ciclolectivo);
$plan = $_SESSION['plan'];
$materia = $_SESSION['materia'];
$curso = $_SESSION['curso'];
$alumnosAsist = obtenerCalificacionesMateria($conn, $idMateria);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['actualizarTabla'])) {
      // Obtiene los datos de la fecha
      $anio = $_POST['anio'];
      $mes = $_POST['mes'];
      $dia = $_POST['dia'];

      $idCicloLectivo = buscarIdCiclo($conn, $anio);

      // Obtiene los datos de la tabla
      $alumnosAsist = obtenerAsistenciaMateria($conn, $idMateria, $mes, $dia, $idCicloLectivo);

      //preparo variable fecha para columna de la tabla
      $dia = substr($dia, 1); // quita el primer caracter 'd' de $dia
$fecha = $dia . '/' . $mes . '/' . $anio;
       // Genera la tabla HTML
    $tabla = '<table id="tablaAsistencia" class="table table-hover col-12">';
    $tabla .= '<thead>';
    $tabla .= '<tr class="table-primary">';
    $tabla .= '<th scope="col">Estudiante</th>';
    $tabla .= '<th scope="col"> ' . $fecha. '</th>';
    $tabla .= '</tr>';
    $tabla .= '</thead>';
    $tabla .= '<tbody>';
    if (empty($alumnosAsist)) { 
      $tabla.='<tr>
        <td colspan="2">Sin registros</td>
      </tr>';
    }
    foreach ($alumnosAsist as $alumno) {
        $tabla .= '<tr>';
        $tabla .= '<td>' . $alumno['apellido'] . ' ' . $alumno['nombre'] . '</td>';
        $tabla .= '<td class="border" contenteditable="true" data-id="' . $alumno['idAlumno'] . '">' . $alumno['dia'] . '</td>';
        $tabla .= '</tr>';
    }
    $tabla .= '</tbody>***Guardado automatico de todas las celdas modificadas que se encuentren en verde';
    $tabla .= '</table>';

    // Envía la tabla como respuesta
    echo $tabla;
    die();
      } else {
    // Código para actualizar la asistencia

  $idAlumno = $_POST['idAlumno'];
  $anio = $_POST['anio'];
  $mes = $_POST['mes'];
  $dia = $_POST['dia'];
  $valor = $_POST['valor'];

  $idCicloLectivo = buscarIdCiclo($conn, $anio);

  $respuesta = actualizarAsistxDocentes($conn, $idAlumno, $idCicloLectivo, $mes, $dia, $valor);

  echo $respuesta;}
}
 //preparo fecha por defecto para el input del calendario
 $fecha_actual = date('Y-m-d');
 $dia = date('d');
 $mes = date('m');

 if (!checkdate($mes, $dia, $ciclolectivo)) {//si diaactual/mesactual/ciclolectivo no es fecha valida coloca 1/1/ciclolectivo
   $fecha_por_defecto = $ciclolectivo . '-01-01';
 } else {//si diaactual/mesactual/ciclolectivo es fecha valida coloca diaactual/mesactual/ciclolectivo
   $fecha_por_defecto = $ciclolectivo . '-' . $mes . '-' . $dia;
 }
 //lleno la tabla
 $partes_fecha = explode('-', $fecha_por_defecto);

 $anio = $partes_fecha[0];
 $mes = trim($partes_fecha[1], '0');
 $dia = 'd'.ltrim($partes_fecha[2],'0');
 $diasinD=ltrim($partes_fecha[2],'0');
 $idCicloLectivo = buscarIdCiclo($conn, $anio);

$alumnosAsist=obtenerAsistenciaMateria($conn, $idMateria, $mes, $dia, $idCicloLectivo);
?>
<!DOCTYPE html>
<html lang="es">

</html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cargar Asistencias</title>
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
        <li class="breadcrumb-item active">Carga de asistencias</li>
      </ol>

      <div class="card padding col-12">
        <h5><?php echo  "Docente: " . $nombreDoc; ?> </h5>
        <h5><?php echo  "Ciclo lectivo: " . $ciclolectivo; ?> </h5>
        <h5><?php echo  "Carrera: " . $plan; ?> </h5>
        <h5><?php echo  "Materia: " . $materia; ?> </h5>
        <h5><?php echo  "Curso: " . $curso; ?> </h5><br>
        <div class="col-md-6>
        <label for="fecha">Seleccione fecha:</label>
        <input type="date" id="fecha" name="fecha" value="<?php echo $ciclolectivo . '-' . date('m-d'); ?>" min="<?php echo $ciclolectivo; ?>-01-01" max="<?php echo $ciclolectivo; ?>-12-31">  
        </div>
         
        <br><p><small>* Las asistencias se guardan automaticamente en cada modificación. La celda se pinta de verde cuando la calificacion se ha guardado exitosamente. Si no se pinta de verde revise su conexion a internet y/o dispositivo.
          <br>Valores permitidos:P ó p(presente), A ó a(ausente). Se puede cargar 1 asistencia por día y hasta 5 asistencias por día por alumno(5 horas). 
          </small></p></div>
          <br>
          <div class="text-center">
  <a id="imprimir-asistencias" target="_blank">
    <button class="btn btn-primary">Imprimir Asistencias</button>
  </a>
</div>
          <br>
              <div>
      <table id="tablaAsistencia" class="table table-hover col-12">
  <thead>
    <tr class="table-primary">
      <th scope="col">Estudiante</th>
      <th scope="col"><?php echo $diasinD."/".$mes."/".$anio ?></th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($alumnosAsist)) { ?>
        <tr>
          <td colspan="2">Sin registros</td>
        </tr>
      <?php } else { 
         foreach ($alumnosAsist as $alumno) { ?>
          <tr>
            <td><?php echo $alumno['apellido'] . ' ' . $alumno['nombre']; ?></td>
            <td class="border" contenteditable="true" data-id="<?php echo $alumno['idAlumno']; ?>"><?php echo $alumno['dia']; ?></td>
          </tr>
        <?php } }?>
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
  var fecha = $('#fecha').val();
  var partes = fecha.split('-');
  var mes = partes[1].replace(/^0+/, ''); // Elimina el cero adelante del mes
  var anio = '<?php echo $idCurso; ?>'; // Obtiene el año de la fecha seleccionada
  var plan = '<?php echo $plan; ?>'; // Obtiene el plan de la variable de sesión
  var materia = '<?php echo $materia; ?>'; // Obtiene la materia de la variable de sesión
  var curso = '<?php echo $curso; ?>'; // Obtiene el curso de la variable de sesión
  var idMateria = '<?php echo $idMateria; ?>';
  var url = '../reportes/asistenciaDocPDF.php?idMateria=' + encodeURIComponent(idMateria) + '&mes=' + encodeURIComponent(mes) + '&ciclolectivo=' + encodeURIComponent(anio) + '&plan=' + encodeURIComponent(plan) + '&materia=' + encodeURIComponent(materia) + '&curso=' + encodeURIComponent(curso);
  $('#imprimir-asistencias').attr('href', url);

  $('#fecha').on('change', function() {
    var fecha = $(this).val();
    var partes = fecha.split('-');
    var mes = partes[1].replace(/^0+/, ''); // Elimina el cero adelante del mes
  var anio = '<?php echo $idCurso; ?>'; // Obtiene el año de la fecha seleccionada
  var plan = '<?php echo $plan; ?>'; // Obtiene el plan de la variable de sesión
  var materia = '<?php echo $materia; ?>'; // Obtiene la materia de la variable de sesión
  var curso = '<?php echo $curso; ?>'; // Obtiene el curso de la variable de sesión
    var idMateria = '<?php echo $idMateria; ?>';
    var url = '../reportes/asistenciaDocPDF.php?idMateria=' + encodeURIComponent(idMateria) + '&mes=' + encodeURIComponent(mes) + '&ciclolectivo=' + encodeURIComponent(anio) + '&plan=' + encodeURIComponent(plan) + '&materia=' + encodeURIComponent(materia) + '&curso=' + encodeURIComponent(curso);
  $('#imprimir-asistencias').attr('href', url);
  });

  $('#tablaAsistencia td[contenteditable="true"]').on('input', function() {
    var $this = $(this);
    var id = $this.attr('data-id');
    var asistencia = $this.text().trim();
    var fecha = $('#fecha').val();
    var partes = fecha.split('-');
    var anio = fecha.split('-')[0];
    var dia = 'd' + partes[2].replace(/^0+/, '');
    var mes = partes[1].replace(/^0+/, '');

    if (asistencia.length > 5) {
      alert('La asistencia puede tener hasta 5 caracteres');
      $this.text(asistencia.substring(0, 5));
    }
    if (!/^[AaPp]*$/.test(asistencia)) {
  alert('La asistencia puede contener letras A, a, P, p');
  $this.text('');
} else {
  $this.css('background-color', 'lightgreen');
}

    // Envía la información al servidor
    $.ajax({
      type: 'POST',
      url: 'carga_asist.php',
      data: {
    idAlumno: id,
    anio: anio,
    mes: mes,
    dia: dia,
    valor: asistencia
  },
      success: function(respuesta) {
        console.log(respuesta);
      }
    });
  });

  //funcion para validar la fecha del select
  function isValidDate(fecha) {
    var date = new Date(fecha);
    return !isNaN(date.getTime());
  }

  //funcion para mostrar alertas de que fecha esta cargando
  $(document).ready(function() {
    
    $('#fecha').on('change', function() {
      // Obtiene la fecha seleccionada
        var fecha = $('#fecha').val();
        var partes = fecha.split('-');
        var anio = partes[0];
        var mes = partes[1].replace(/^0+/, ''); // Elimina el cero adelante del mes
        var dia = 'd' + partes[2].replace(/^0+/, ''); // Agrega la "d" adelante del día y elimina el cero adelante

        // Hace la llamada AJAX al mismo archivo
        $.ajax({
  type: 'POST',
  url: 'carga_asist.php',
  data: {
    actualizarTabla: true,
    anio: anio,
    mes: mes,
    dia: dia
  },
  success: function(respuesta) {
    $('#tablaAsistencia').html(respuesta);

    
        // Vuelve a aplicar la función que hace la validación y pinta la celda de verde
    $('#tablaAsistencia td[contenteditable="true"]').on('input', function() {
      var $this = $(this);
      var id = $this.attr('data-id');
      var asistencia = $this.text().trim();
      var fecha = $('#fecha').val();
      var partes = fecha.split('-');
      var anio = fecha.split('-')[0];
      var dia = 'd' + partes[2].replace(/^0+/, '');
      var mes = partes[1].replace(/^0+/, '');

      if (asistencia.length > 5) {
        alert('La asistencia puede tener hasta 5 caracteres');
        $this.text(asistencia.substring(0, 5));
      }
      if (!/^[AaPp]*$/.test(asistencia)) {
        alert('La asistencia puede contener letras A, a, P, p');
        $this.text('');
      } else {
        $this.css('background-color', 'lightgreen');
      }

      // Envía la información al servidor
      $.ajax({
        type: 'POST',
        url: 'carga_asist.php',
        data: {
          idAlumno: id,
          anio: anio,
          mes: mes,
          dia: dia,
          valor: asistencia
        },
        success: function(respuesta) {
          console.log(respuesta);
        }
      });
    });
  }
});
    });
  });
});
</script>
</html>