<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/verificarSesion.php';
include '../funciones/analisisestado.php'; 

// --- NUEVO: Consultar permiso directamente a la base de datos ---
// Se ejecuta al inicio para estar disponible tanto en el render HTML como en las llamadas AJAX
$docenteModifica = 0; 
$sqlPermiso = "SELECT docenteModifica FROM colegio LIMIT 1";
if ($resultadoPermiso = mysqli_query($conn, $sqlPermiso)) {
    if ($filaPermiso = mysqli_fetch_assoc($resultadoPermiso)) {
        $docenteModifica = (int)$filaPermiso['docenteModifica'];
    }
}
// ----------------------------------------------------------------

$doc_legajo = '';
$nombreDoc = '';
$idMateria = '';
$ciclolectivo = ''; 
$idCiclo = '';     
$plan = '';
$materia = '';
$curso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idMateria']) && !isset($_POST['actualizarTabla']) && !isset($_POST['idAlumno'])) {
    $doc_legajo = $_SESSION['doc_legajo'];
    $nombreDoc = $_SESSION['doc_apellido'] . ", " . $_SESSION['doc_nombre'];
    $idMateria = $_POST['idMateria'];
    $ciclolectivo = $_POST['ciclolectivo']; 
    $idCiclo = buscarIdCiclo($conn, $ciclolectivo); 
    $plan = $_POST['plan'];
    $materia = $_POST['materia'];
    $curso = $_POST['curso'];
}

// Lógica para la actualización AJAX de la tabla
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. DIBUJAR TABLA
    if (isset($_POST['actualizarTabla'])) {
        $anio_str = $_POST['anio']; 
        $mes = $_POST['mes'];
        $dia_col = $_POST['dia']; 
        $idMateria = $_POST['idMateria'];
        $idCicloLectivo_num = $_POST['idCicloActual']; 
        
        $alumnosAsist = obtenerAsistenciaMateria($conn, $idMateria, $mes, $dia_col, $idCicloLectivo_num);

        $dia_num = str_replace('d', '', $dia_col); 
        $fecha_formateada = htmlspecialchars($dia_num . '/' . $mes . '/' . $anio_str, ENT_QUOTES, 'UTF-8');

        $tabla = '<table id="tablaAsistencia" class="table table-hover col-12">';
        $tabla .= '<thead><tr class="table-primary"><th>Estudiante</th><th>' . $fecha_formateada . '</th></tr></thead><tbody>';

        if (empty($alumnosAsist)) {
            $tabla .= '<tr><td colspan="2">Sin registros</td></tr>';
        } else {
            foreach ($alumnosAsist as $alumno) {
                // Sanitización y estado
                $valorAsistencia = trim((string)$alumno['dia']);
                $esNueva = ($valorAsistencia === '') ? '1' : '0';
                $estadoMatriculacion = $alumno['estado'];
                $isAbandoned = ($estadoMatriculacion === 'Abandonó Cursado');

                // Lógica de Renderizado y Bloqueo
                if ($isAbandoned) {
                    $rowClass = 'class="disabled-row"';
                    $celdaClass = 'class="border disabled-cell"';
                    $contentEditableAttr = 'contenteditable="false"';
                } else {
                    $rowClass = '';
                    // Es editable SI el docente tiene permisos totales, o SI la celda está vacía (nueva)
                    if ($docenteModifica == 1 || $esNueva === '1') {
                        $celdaClass = 'class="border"';
                        $contentEditableAttr = 'contenteditable="true"';
                    } else {
                        // Bloqueo visual de registros previos si no hay permisos
                        $celdaClass = 'class="border disabled-cell" title="Edición bloqueada por Secretaría"';
                        $contentEditableAttr = 'contenteditable="false"';
                    }
                }

                $nombreCompleto = htmlspecialchars($alumno['apellido'] . ' ' . $alumno['nombre'], ENT_QUOTES, 'UTF-8');
                $valorSeguro = htmlspecialchars($valorAsistencia, ENT_QUOTES, 'UTF-8');

                $tabla .= '<tr ' . $rowClass . '>';
                $tabla .= '<td>' . $nombreCompleto . '</td>';
                $tabla .= '<td ' . $celdaClass . ' data-id="' . $alumno['idAlumno'] . '" data-new="' . $esNueva . '" ' . $contentEditableAttr . '>' . $valorSeguro . '</td>';
                $tabla .= '</tr>';
            }
        }
        $tabla .= '</tbody></table>';
        echo $tabla;
        die(); 
        
    } 
    // 2. GUARDAR ASISTENCIA INDIVIDUAL
    else if (isset($_POST['idAlumno'])) { 
        $idAlumno = filter_input(INPUT_POST, 'idAlumno', FILTER_SANITIZE_NUMBER_INT);
        $anio = filter_input(INPUT_POST, 'anio', FILTER_SANITIZE_NUMBER_INT);
        $mes = filter_input(INPUT_POST, 'mes', FILTER_SANITIZE_NUMBER_INT);
        $dia = htmlspecialchars($_POST['dia'], ENT_QUOTES, 'UTF-8');
        $valor = htmlspecialchars($_POST['valor'], ENT_QUOTES, 'UTF-8');
        $idMateria = filter_input(INPUT_POST, 'idMateria', FILTER_SANITIZE_NUMBER_INT);
        
        $esNueva = $_POST['esNueva'] ?? '0';

        // SEGURIDAD BACK-END: Bloquear si se intenta modificar un dato viejo sin permisos
        if ($docenteModifica == 0 && $esNueva === '0') {
            ob_clean();
            echo "error_permiso";
            die();
        }

        $idCicloLectivo = buscarIdCiclo($conn, $anio); 
        $respuesta = actualizarAsistxDocentes($conn, $idAlumno, $idCicloLectivo, $mes, $dia, $valor, $idMateria);
        
        $asistencia = obtenerAsistencia($conn, $idAlumno, $idMateria, $idCicloLectivo);
        $porcentaje = porcentaje($asistencia); 
        actualizarAsistencia($conn, $idAlumno, $idMateria, $porcentaje); 
        
        $idCalificacion = obtenerIdCalificacion($conn, $idAlumno, $idMateria);
        iniciarAnalisis($conn, $idMateria, $idAlumno, $idCalificacion);
        
        echo $respuesta;
        die(); 
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cargar Asistencias</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="../js/bootstrap.bundle.js"></script>

  <style>
    td.disabled-cell {
        background-color: #e9ecef; 
        color: #6c757d; 
        cursor: not-allowed;
        pointer-events: none; 
    }
    .disabled-row {
        background-color: #f8f9fa; 
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
      <h5>Docente: <?= htmlspecialchars($nombreDoc, ENT_QUOTES, 'UTF-8') ?></h5>
      <h5>Ciclo lectivo: <?= htmlspecialchars($ciclolectivo, ENT_QUOTES, 'UTF-8') ?></h5> 
      <h5>Carrera: <?= htmlspecialchars($plan, ENT_QUOTES, 'UTF-8') ?></h5>
      <h5>Materia: <?= htmlspecialchars($materia, ENT_QUOTES, 'UTF-8') ?></h5>
      <h5>Curso: <?= htmlspecialchars($curso, ENT_QUOTES, 'UTF-8') ?></h5>
      <br>

      <?php if($docenteModifica == 0): ?>
          <div class="alert alert-info">
              <strong>Atención:</strong> La edición de asistencias históricas está bloqueada desde Secretaría. <b>Puede seguir cargando o corrigiendo asistencias en casilleros nuevos del día seleccionado.</b>
          </div>
      <?php endif; ?>

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
    <script src="../js/jquery-3.7.1.js"></script>
  </div>
</div>

<?php include '../funciones/footer.html'; ?>

<script>
$(document).ready(function () {
  var idMateria = '<?php echo htmlspecialchars($idMateria, ENT_QUOTES, 'UTF-8'); ?>';
  var materia = '<?php echo htmlspecialchars($materia, ENT_QUOTES, 'UTF-8'); ?>';
  var curso = '<?php echo htmlspecialchars($curso, ENT_QUOTES, 'UTF-8'); ?>';
  var plan = '<?php echo htmlspecialchars($plan, ENT_QUOTES, 'UTF-8'); ?>';
  var ciclolectivo_str = '<?php echo htmlspecialchars($ciclolectivo, ENT_QUOTES, 'UTF-8'); ?>'; 
  var idCiclo_num = '<?php echo htmlspecialchars($idCiclo, ENT_QUOTES, 'UTF-8'); ?>'; 

  $('#fecha').on('change', function () {
    $('#btn-imprimir').prop('disabled', false);

    let fecha = $(this).val();
    if (!fecha) return;

    let [anio, mes, dia] = fecha.split('-');
    mes = mes.replace(/^0+/, ''); 
    let day_col_id = 'd' + dia.replace(/^0+/, ''); 

    $.ajax({
      type: 'POST',
      url: 'carga_asist.php',
      data: {
        actualizarTabla: true,
        anio: anio, 
        mes: mes,
        dia: day_col_id, 
        idMateria: idMateria,
        idCicloActual: idCiclo_num 
      },
      success: function (respuesta) {
        $('#tablaAsistencia').html(respuesta);
      }
    });

    let url = '../reportes/asistenciaDocPDF.php?idMateria=' + idMateria +
              '&materia=' + encodeURIComponent(materia) +
              '&curso=' + encodeURIComponent(curso) +
              '&plan=' + encodeURIComponent(plan) +
              '&ciclolectivo=' + encodeURIComponent(idCiclo_num) + 
              '&mes=' + mes;

    $('#imprimir-asistencias').attr('href', url);
  });

  // Delegación de eventos para celdas editables
  $('#tablaAsistencia').on('blur', 'td[contenteditable="true"]', function () {
    let $this = $(this);
    let id = $this.data('id'); 
    let esNueva = $this.attr('data-new') || '0'; // Extraemos el estado original
    let asistencia = $this.text().trim().toUpperCase(); // Forzar mayúscula
    
    let fecha = $('#fecha').val();
    let [anio, mes, dia] = fecha.split('-');
    dia = 'd' + dia.replace(/^0+/, ''); 
    mes = mes.replace(/^0+/, '');

    if (asistencia.length > 5) {
      alert('La asistencia puede tener hasta 5 caracteres');
      asistencia = asistencia.substring(0, 5);
      $this.text(asistencia);
    }

    if (asistencia !== "" && !/^[APJM]+$/.test(asistencia)) { 
      alert('Solo se permiten letras mayúsculas: A, P, J, M');
      $this.text(''); 
      asistencia = ""; 
    }

    $.post('carga_asist.php', {
      idAlumno: id,
      anio: anio,
      mes: mes,
      dia: dia,
      idMateria: idMateria,
      valor: asistencia,
      esNueva: esNueva // Se envía el flag de estado para la validación Backend
    }, function(response) {
      if(response.trim() === 'error_permiso') {
          alert('Error de Seguridad: La edición de asistencias previas está bloqueada.');
          $this.css('background-color', 'lightcoral');
          $this.text(''); // Limpiamos para evitar confusiones
      } else {
          $this.css('background-color', 'lightgreen'); 
      }
    }).fail(function() {
      $this.css('background-color', 'lightcoral');
      alert('Error de red al guardar la asistencia.');
    });
  });

  // Manejo de navegación con flechas
  $('#tablaAsistencia').on('keydown', 'td[contenteditable="true"]', function(e) {
    let $this = $(this);
    let $currentRow = $this.closest('tr');
    
    if (e.key === 'Enter' || e.key === 'ArrowDown') {
      e.preventDefault(); 
      $this.blur(); // Dispara el guardado al presionar enter/flecha
      let $nextRow = $currentRow.next('tr');
      while ($nextRow.length > 0) {
        let $nextCell = $nextRow.find('td[contenteditable="true"]');
        if ($nextCell.length > 0) { 
            $nextCell.eq(0).focus(); 
            break; 
        }
        $nextRow = $nextRow.next('tr'); 
      }
    }
    else if (e.key === 'ArrowUp') {
      e.preventDefault(); 
      $this.blur();
      let $prevRow = $currentRow.prev('tr');
      while ($prevRow.length > 0) {
        let $prevCell = $prevRow.find('td[contenteditable="true"]');
        if ($prevCell.length > 0) { 
            $prevCell.eq(0).focus(); 
            break; 
        }
        $prevRow = $prevRow.prev('tr'); 
      }
    }
  });
});
</script>
</body>
</html>