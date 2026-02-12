<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/verificarSesion.php';
include '../funciones/analisisestado.php'; // Asegúrate de que este archivo existe y sus funciones son correctas

// Variables para la primera carga de la página (viene de POST desde otra página)
$doc_legajo = '';
$nombreDoc = '';
$idMateria = '';
$ciclolectivo = ''; // Esto contendrá el AÑO del ciclo (string, ej. "2023")
$idCiclo = '';     // Esto contendrá el ID NUMÉRICO del ciclo
$plan = '';
$materia = '';
$curso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idMateria']) && !isset($_POST['actualizarTabla']) && !isset($_POST['idAlumno'])) {
    $doc_legajo = $_SESSION['doc_legajo'];
    $nombreDoc = $_SESSION['doc_apellido'] . ", " . $_SESSION['doc_nombre'];
    $idMateria = $_POST['idMateria'];
    $ciclolectivo = $_POST['ciclolectivo']; // Año string
    $idCiclo = buscarIdCiclo($conn, $ciclolectivo); // Obtenemos el ID numérico del ciclo
    $plan = $_POST['plan'];
    $materia = $_POST['materia'];
    $curso = $_POST['curso'];
}

// Lógica para la actualización AJAX de la tabla (cuando se selecciona una fecha)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['actualizarTabla'])) {
        $anio_str = $_POST['anio']; // Año como string (ej. "2023")
        $mes = $_POST['mes'];
        $dia_col = $_POST['dia']; // 'd1', 'd2', etc.
        $idMateria = $_POST['idMateria'];
        $idCicloLectivo_num = $_POST['idCicloActual']; // <--- OPTIMIZACIÓN: ID numérico del ciclolectivo
        
        // Usar la función obtenerAsistenciaMateria que ahora incluye el estado de matriculación
        $alumnosAsist = obtenerAsistenciaMateria($conn, $idMateria, $mes, $dia_col, $idCicloLectivo_num);

        $dia_num = str_replace('d', '', $dia_col); // Extraer solo el número del día
        $fecha_formateada = $dia_num . '/' . $mes . '/' . $anio_str;

        $tabla = '<table id="tablaAsistencia" class="table table-hover col-12">';
        $tabla .= '<thead><tr class="table-primary"><th>Estudiante</th><th>' . $fecha_formateada . '</th></tr></thead><tbody>';

        if (empty($alumnosAsist)) {
            $tabla .= '<tr><td colspan="2">Sin registros</td></tr>';
        } else {
            foreach ($alumnosAsist as $alumno) {
                $estadoMatriculacion = $alumno['estado'];
                $isAbandoned = ($estadoMatriculacion === 'Abandonó Cursado');

                $rowClass = $isAbandoned ? 'class="disabled-row"' : '';
                $celdaClass = $isAbandoned ? 'class="border disabled-cell"' : 'class="border"';
                $contentEditableAttr = $isAbandoned ? 'contenteditable="false"' : 'contenteditable="true"';

                $tabla .= '<tr ' . $rowClass . '>';
                $tabla .= '<td>' . $alumno['apellido'] . ' ' . $alumno['nombre'] . '</td>';
                // La celda de asistencia
                // Eliminamos el input y usamos directamente la TD, la tabla ya está contenteditable
                // Añado un data-original-content para que, si es editable, pueda manipular el contenido sin afectar el "real" para el value
                $tabla .= '<td ' . $celdaClass . ' data-id="' . $alumno['idAlumno'] . '" ' . $contentEditableAttr . '>' . $alumno['dia'] . '</td>';
                $tabla .= '</tr>';
            }
        }
        $tabla .= '</tbody>***Guardado automático de todas las celdas modificadas que se encuentren en verde';
        $tabla .= '</table>';
        echo $tabla;
        die(); // Terminar la ejecución ya que esta es una respuesta AJAX
    } else if (isset($_POST['idAlumno'])) { // Lógica para guardar la asistencia individual (POST AJAX)
        $idAlumno = $_POST['idAlumno'];
        $anio = $_POST['anio'];
        $mes = $_POST['mes'];
        $dia = $_POST['dia'];
        $valor = $_POST['valor'];
        $idMateria = $_POST['idMateria'];
        $idCicloLectivo = buscarIdCiclo($conn, $anio); // Aquí también podrías enviar el idCicloActual si lo necesitas en el JS
        $respuesta = actualizarAsistxDocentes($conn, $idAlumno, $idCicloLectivo, $mes, $dia, $valor, $idMateria);
        
        // NOTA: Asegúrate de que las funciones a continuación obtengan el contexto de asistencia completo si lo necesitan para porcentajes
        $asistencia = obtenerAsistencia($conn, $idAlumno, $idMateria, $idCicloLectivo);
        $porcentaje = porcentaje($asistencia); // Asumiendo 'porcentaje' es una función que calcula el porcentaje
        actualizarAsistencia($conn, $idAlumno, $idMateria, $porcentaje); // Asumiendo que esta actualiza el porcentaje en calificacionesterciario
        
        $idCalificacion = obtenerIdCalificacion($conn, $idAlumno, $idMateria);
        iniciarAnalisis($conn, $idMateria, $idAlumno, $idCalificacion);
        echo $respuesta;
        die(); // Terminar la ejecución
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cargar Asistencias</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="../js/bootstrap.min.js"></script>

  <style>
    /* Estilos para celdas y filas deshabilitadas */
    /* *** CAMBIO CLAVE AQUÍ: td.disabled-cell en lugar de solo .disabled-cell *** */
    td.disabled-cell {
        background-color: #e9ecef; /* Un gris claro, similar al disabled de Bootstrap */
        color: #6c757d; /* Color de texto más tenue */
        cursor: not-allowed;
        pointer-events: none; /* Esto evita que el usuario haga clic o seleccione el texto */
    }

    .disabled-row {
        background-color: #f8f9fa; /* Un fondo gris muy claro para la fila entera */
    }
  </style>

<link rel="icon" type="image/png" href="../img/icon.png">

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
      <h5>Docente: <?= $nombreDoc ?></h5>
      <h5>Ciclo lectivo: <?= $ciclolectivo ?></h5> <!-- Este es el año string -->
      <h5>Carrera: <?= $plan ?></h5>
      <h5>Materia: <?= $materia ?></h5>
      <h5>Curso: <?= $curso ?></h5>
      <br>

      <div class="col-md-6">
        <label style="font-weight: bold;" for="fecha">SELECCIONE FECHA:</label>
        <input type="date" id="fecha" name="fecha" min="<?= $ciclolectivo . '-01-01' ?>" max="<?= $ciclolectivo . '-12-31' ?>">
      </div>

      <br>
      <p><small>* Las asistencias se guardan automáticamente en cada modificación. La celda se pinta de verde cuando se guarda correctamente.<br>
        Valores permitidos: P (presente), A (ausente), J (justificado), M (media falta).
      </small></p>
    </div>

    <br>
    <div class="text-center">
      <a id="imprimir-asistencias" target="_blank">
        <button class="btn btn-primary" id="btn-imprimir" disabled>Imprimir Asistencias</button>
      </a>
    </div>
    <br>

    <div>
      <table id="tablaAsistencia" class="table table-hover col-12">
        <thead>
          <tr class="table-primary">
            <th>Estudiante</th>
            <th>Asistencia</th>
          </tr>
        </thead>
        <tbody>
          <tr><td colspan="2">Seleccione una fecha para ver asistencias.</td></tr>
        </tbody>
      </table>
    </div>

    <script src="../funciones/sessionControl.js"></script>
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
  </div>
</div>

<?php include '../funciones/footer.html'; ?>

<script>
$(document).ready(function () {
  var idMateria = '<?php echo $idMateria; ?>';
  var materia = '<?php echo $materia; ?>';
  var curso = '<?php echo $curso; ?>';
  var plan = '<?php echo $plan; ?>';
  var ciclolectivo_str = '<?php echo $ciclolectivo; ?>'; // Año como string (ej. "2023")
  var idCiclo_num = '<?php echo $idCiclo; ?>'; // ID numérico del ciclolectivo

  $('#fecha').on('change', function () {
    $('#btn-imprimir').prop('disabled', false);

    let fecha = $(this).val();
    if (!fecha) return;

    let [anio, mes, dia] = fecha.split('-');
    mes = mes.replace(/^0+/, ''); // Eliminar ceros iniciales
    day_col_id = 'd' + dia.replace(/^0+/, ''); // 'd' + día_número (ej. d15)

    // AJAX para actualizar la tabla de asistencia
    $.ajax({
      type: 'POST',
      url: 'carga_asist.php',
      data: {
        actualizarTabla: true,
        anio: anio, // Año para el encabezado (string)
        mes: mes,
        dia: day_col_id, // 'd' + el día del mes
        idMateria: idMateria,
        idCicloActual: idCiclo_num // <--- OPTIMIZACIÓN: Pasar el ID numérico del ciclo
      },
      success: function (respuesta) {
        // La respuesta ya es la tabla completa en HTML
        $('#tablaAsistencia').html(respuesta);
      }
    });

    // Construcción de la URL para el botón de imprimir
    // asistenciaDocPDF.php espera el año, que es 'ciclolectivo_str'
    let url = '../reportes/asistenciaDocPDF.php?idMateria=' + idMateria +
              '&materia=' + encodeURIComponent(materia) +
              '&curso=' + encodeURIComponent(curso) +
              '&plan=' + encodeURIComponent(plan) +
              '&ciclolectivo=' + encodeURIComponent(idCiclo_num) + 
              '&mes=' + mes;

    $('#imprimir-asistencias').attr('href', url);
  });

  // Delegación de eventos para celdas editables
  $('#tablaAsistencia').on('input', 'td[contenteditable="true"]', function () {
    let $this = $(this);
    let id = $this.data('id'); // idAlumno
    let asistencia = $this.text().trim(); // Mantener mayúsculas/minúsculas como vienen
    let fecha = $('#fecha').val();
    let [anio, mes, dia] = fecha.split('-');
    dia = 'd' + dia.replace(/^0+/, ''); // 'd' + día_número
    mes = mes.replace(/^0+/, '');

    // *** Validaciones revertidas a tu código original ***
    if (asistencia.length > 5) {
      alert('La asistencia puede tener hasta 5 caracteres');
      asistencia = asistencia.substring(0, 5);
      $this.text(asistencia);
    }

    if (!/^[APJM]*$/.test(asistencia)) { // Validar solo letras permitidas
      alert('Solo se permiten letras mayúsculas: A, P, J, M');
      $this.text(''); // Limpiar si no es válido
      asistencia = ""; // Asegurarse de que el valor enviado sea vacío para el backend si es inválido
      // Se debe ejecutar el POST incluso si es inválido para borrar el dato
      $.post('carga_asist.php', {
        idAlumno: id,
        anio: anio,
        mes: mes,
        dia: dia,
        idMateria: idMateria,
        valor: asistencia // Ahora 'asistencia' es "" si es inválido
      }, function(response) {
         console.log(response); // La respuesta del servidor (ej. "actualizado")
      });
    } else {
      // Si la validación pasa, enviar el dato
      $this.css('background-color', 'lightgreen'); // Marcar en verde temporalmente
      $.post('carga_asist.php', {
        idAlumno: id,
        anio: anio,
        mes: mes,
        dia: dia,
        idMateria: idMateria,
        valor: asistencia
      }, function(response) {
         console.log(response); // La respuesta del servidor (ej. "actualizado")
      });
    }
  });


  // Manejo de navegación con flechas
  $('#tablaAsistencia').on('keydown', 'td[contenteditable="true"]', function(e) {
    let $this = $(this);
    let $currentRow = $this.closest('tr');
    
    // Enter o ArrowDown
    if (e.key === 'Enter' || e.key === 'ArrowDown') {
      e.preventDefault(); // Evitar salto de línea en contenteditable y scrolling por defecto
      let $nextRow = $currentRow.next('tr');
      // Buscar la siguiente celda editable, solo si existe una siguiente fila
      while ($nextRow.length > 0) {
        let $nextCell = $nextRow.find('td[contenteditable="true"]');
        if ($nextCell.length > 0) { // Si hay al menos una celda editable en la siguiente fila
            $nextCell.eq(0).focus(); // Enfocar la primera celda editable de la próxima fila
            break; // Salir del bucle
        }
        $nextRow = $nextRow.next('tr'); // Si no hay celdas editables en esta fila, ir a la siguiente
      }
    }
    // ArrowUp
    else if (e.key === 'ArrowUp') {
      e.preventDefault(); // Evitar scrolling por defecto
      let $prevRow = $currentRow.prev('tr');
       // Buscar la celda editable anterior, solo si existe una fila anterior
      while ($prevRow.length > 0) {
        let $prevCell = $prevRow.find('td[contenteditable="true"]');
        if ($prevCell.length > 0) { // Si hay al menos una celda editable en la fila anterior
            $prevCell.eq(0).focus(); // Enfocar la primera celda editable de la fila anterior
            break; // Salir del bucle
        }
        $prevRow = $prevRow.prev('tr'); // Si no hay celdas editables en esta fila, ir a la anterior
      }
    }
  });
});
</script>
</body>
</html>