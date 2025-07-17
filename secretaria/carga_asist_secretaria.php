<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Redirigir al login si el usuario no está autenticado como secretario
if (!isset($_SESSION['sec_nombreUsuario'])) {
    header('Location: loginAdmin.php');
    exit;
}
include '../funciones/verificarSesion.php';
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/analisisestado.php';

$sec_nombre = $_SESSION['sec_nombreUsuario'];

// Variables principales de contexto de la materia/curso/ciclo/plan
$idMateria = $_POST['idMateria'] ?? null;
$curso = $_POST['curso'] ?? null;
$ciclolectivo_anio_str = $_POST['ciclolectivo'] ?? null;
$ciclolectivo_id = $_POST['ciclolectivo_id'] ?? null;
$plan = $_POST['plan'] ?? null;
$plan_id = $_POST['plan_id'] ?? null;
$materia = $_POST['materia'] ?? null;

// --- Manejo de peticiones AJAX (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Si la petición es para actualizar la tabla de asistencia por día
    if (isset($_POST['actualizarTabla'])) {
        $anio_ajax = $_POST['anio'];
        $mes_ajax = $_POST['mes'];
        $dia_columna = $_POST['dia'];
        $idMateria_ajax = $_POST['idMateria'];
        $ciclolectivo_id_ajax = $_POST['ciclolectivo_id'];

        // *** CAMBIO CLAVE AQUÍ: Usar la nueva función optimizada ***
        $alumnosAsist = obtenerAsistenciaMateriaSecretaria($conn, $idMateria_ajax, $mes_ajax, $dia_columna, $ciclolectivo_id_ajax);

        // Formatear fecha para el encabezado
        $display_dia = substr($dia_columna, 1);
        $fecha_display = $display_dia . '/' . $mes_ajax . '/' . $anio_ajax;

        $tabla = '<thead><tr class="table-primary"><th>Estudiante</th><th>' . htmlspecialchars($fecha_display) . '</th></tr></thead><tbody>';

        if (empty($alumnosAsist)) {
            $tabla .= '<tr><td colspan="2">Sin registros</td></tr>';
        } else {
            foreach ($alumnosAsist as $alumno) {
                $fila_clase = '';
                $editable_attr = 'contenteditable="true"';
                $background_style = '';

                // Ahora, el estado viene directamente en el array $alumno
                if (($alumno['estado'] ?? '') == 'Abandonó Cursado') { // Se usa ?? '' por si 'estado' no viene definido
                    $fila_clase = ' class="table-secondary"';
                    $editable_attr = 'contenteditable="false"';
                    $background_style = ' style="background-color: #f0f0f0;"';
                }

                $tabla .= '<tr' . $fila_clase . '>';
                $tabla .= '<td>' . htmlspecialchars($alumno['apellido'] ?? '') . ' ' . htmlspecialchars($alumno['nombre'] ?? '') . '</td>';
                $tabla .= '<td class="border" ' . $editable_attr . $background_style . ' data-id="' . htmlspecialchars($alumno['idAlumno'] ?? '') . '">' . htmlspecialchars($alumno['dia'] ?? '') . '</td>';
                $tabla .= '</tr>';
            }
        }
        $tabla .= '</tbody>***Guardado automático de todas las celdas modificadas que se encuentren en verde';
        echo $tabla;
        exit();
    }
    // Si la petición es para actualizar una celda de asistencia individual
    elseif (isset($_POST['idAlumno'])) {
        $idAlumno = $_POST['idAlumno'];
        $anio_actual = $_POST['anio'];
        $mes_actual_num = $_POST['mes'];
        $dia_columna_actual = $_POST['dia'];
        $valor = $_POST['valor'];
        $idMateria_ajax = $_POST['idMateria'];
        $ciclolectivo_id_ajax = $_POST['ciclolectivo_id'];

        try {
            $respuesta_update_dia = actualizarAsistxDocentes($conn, $idAlumno, $ciclolectivo_id_ajax, $mes_actual_num, $dia_columna_actual, $valor, $idMateria_ajax);

            $asistencia_data = obtenerAsistencia($conn, $idAlumno, $idMateria_ajax, $ciclolectivo_id_ajax);
            $porcentaje_asistencia = porcentaje($asistencia_data);
            actualizarAsistencia($conn, $idAlumno, $idMateria_ajax, $porcentaje_asistencia);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Actualizado: ' . $respuesta_update_dia]);
            exit();

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
            exit();
        }
    }
}

// Redirigir si faltan datos esenciales para la materia
if (empty($idMateria) || empty($ciclolectivo_id) || empty($plan_id)) {
    $_SESSION['login_message'] = "Datos de materia incompletos para cargar asistencias.";
    header('Location: materiaxsecretario.php?action=asistencias');
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cargar Asistencias (Secretaría)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<?php include '../funciones/menu_secretaria.php'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
      <li class="breadcrumb-item"><a href="materiaxsecretario.php?action=asistencias">Selección de Materia</a></li>
      <li class="breadcrumb-item active">Carga de asistencias</li>
    </ol>

    <div class="card padding col-12">
      <h5>Usuario: <?= htmlspecialchars($sec_nombre ?? '') ?></h5>
      <h5>Ciclo lectivo: <?= htmlspecialchars($ciclolectivo_anio_str ?? '') ?></h5>
      <h5>Carrera: <?= htmlspecialchars($plan ?? '') ?></h5>
      <h5>Materia: <?= htmlspecialchars($materia ?? '') ?></h5>
      <h5>Curso: <?= htmlspecialchars($curso ?? '') ?></h5>
      <br>

      <div class="col-md-6">
        <label style="font-weight: bold;" for="fecha">SELECCIONE FECHA:</label>
        <input type="date" id="fecha" name="fecha"
               min="<?= (int)($ciclolectivo_anio_str ?? date('Y')) . '-01-01' ?>"
               max="<?= (int)($ciclolectivo_anio_str ?? date('Y')) . '-12-31' ?>">
      </div>

      <br>
      <p><small>* Las asistencias se guardan automáticamente en cada modificación. La celda se pinta de verde cuando se guarda correctamente.<br>
        Valores permitidos: P/p (presente), A/a (ausente), J/j (justificado), M/m (media falta).
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

  </div>
</div>

<?php include '../funciones/footer.html'; ?>

<script src="../funciones/sessionControl.js"></script>
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>

<script>
$(document).ready(function () {
  var idMateria_js = '<?php echo htmlspecialchars($idMateria ?? ''); ?>';
  var materia_js = '<?php echo htmlspecialchars(addslashes($materia ?? '')); ?>';
  var curso_js = '<?php echo htmlspecialchars(addslashes($curso ?? '')); ?>';
  var plan_js = '<?php echo htmlspecialchars(addslashes($plan ?? '')); ?>';
  var ciclolectivo_anio_str_js = '<?php echo htmlspecialchars($ciclolectivo_anio_str ?? ''); ?>';
  var ciclolectivo_id_js = '<?php echo htmlspecialchars($ciclolectivo_id ?? ''); ?>';

  $('#fecha').on('change', function () {
    $('#btn-imprimir').prop('disabled', false);

    let fecha_seleccionada = $(this).val();
    if (!fecha_seleccionada) return;

    let [anio_val, mes_val, dia_val] = fecha_seleccionada.split('-');
    mes_val = mes_val.replace(/^0+/, '');
    let dia_columna = 'd' + dia_val.replace(/^0+/, '');

    $.ajax({
      type: 'POST',
      url: 'carga_asist_secretaria.php',
      data: {
        actualizarTabla: true,
        anio: anio_val,
        mes: mes_val,
        dia: dia_columna,
        idMateria: idMateria_js,
        ciclolectivo_id: ciclolectivo_id_js
      },
      success: function (respuesta) {
        $('#tablaAsistencia').html(respuesta);
      },
      error: function(jqXHR, textStatus, errorThrown) {
        console.error("Error AJAX al cargar la tabla: " + textStatus, errorThrown);
        console.log("Response Text:", jqXHR.responseText);
        alert("Error al cargar la tabla de asistencia. Revise su conexión.");
      }
    });

    let url_imprimir = '../reportes/asistenciaDocPDF.php?idMateria=' + idMateria_js +
                       '&materia=' + encodeURIComponent(materia_js) +
                       '&curso=' + encodeURIComponent(curso_js) +
                       '&plan=' + encodeURIComponent(plan_js) +
                       '&ciclolectivo=' + ciclolectivo_id_js +
                       '&mes=' + mes_val;
    $('#imprimir-asistencias').attr('href', url_imprimir);
  });

  $('#tablaAsistencia').on('input', 'td[contenteditable="true"]', function () {
    let $this = $(this);
    let idAlumno = $this.data('id');
    let asistencia_valor = $this.text().trim();
    let fecha_actual = $('#fecha').val();

    if (!fecha_actual) {
        alert("Por favor, seleccione una fecha primero.");
        $this.text('');
        return;
    }

    let [anio_actual, mes_actual_num, dia_actual_str] = fecha_actual.split('-');
    mes_actual_num = mes_actual_num.replace(/^0+/, '');
    let dia_columna_actual = 'd' + dia_actual_str.replace(/^0+/, '');

    let valid_chars_regex = /^[PAJMpajm]*$/;
    if (!valid_chars_regex.test(asistencia_valor)) {
      alert('Solo se permiten letras P, p, A, a, J, j, M, m');
      $this.text('');
      $.post('carga_asist_secretaria.php', {
        idAlumno: idAlumno,
        anio: anio_actual,
        mes: mes_actual_num,
        dia: dia_columna_actual,
        idMateria: idMateria_js,
        ciclolectivo_id: ciclolectivo_id_js,
        valor: ""
      }).done(function(response) { console.log('Entrada inválida limpiada:', response); })
        .fail(function(jqXHR, textStatus, errorThrown) {
           console.error("Error al limpiar valor: " + textStatus, errorThrown);
           console.log("Response text:", jqXHR.responseText);
        });
      $this.css('background-color', 'lightcoral');
      return;
    }

    $this.text(asistencia_valor.toUpperCase());
    asistencia_valor = asistencia_valor.toUpperCase();


    $.ajax({
       type: 'POST',
       url: 'carga_asist_secretaria.php',
       data: {
         idAlumno: idAlumno,
         anio: anio_actual,
         mes: mes_actual_num,
         dia: dia_columna_actual,
         idMateria: idMateria_js,
         ciclolectivo_id: ciclolectivo_id_js,
         valor: asistencia_valor
       },
       dataType: 'json',
       success: function(response) {
         console.log('Update response:', response);
         if (response.success) {
           $this.css('background-color', 'lightgreen');
           // *** CAMBIO: ELIMINADO setTimeout para que el color quede ***
           // setTimeout(function() {
           //    $this.css('background-color', '');
           // }, 1500);
         } else {
           alert('Error al guardar asistencia: ' + (response.message || 'Respuesta inesperada.'));
           $this.css('background-color', 'lightcoral');
         }
       },
       error: function(jqXHR, textStatus, errorThrown) {
         console.error("Error AJAX al guardar asistencia: " + textStatus, errorThrown);
         console.log("Response Text:", jqXHR.responseText);
         alert("Error al guardar la asistencia. Revise su conexión a internet.");
         $this.css('background-color', 'lightcoral');
       }
    });
  });
});
</script>

<script>
$('#tablaAsistencia').on('keydown', 'td[contenteditable="true"]', function(e) {
  let $this = $(this);

  if (e.key === 'Enter' || e.key === 'ArrowDown') {
    e.preventDefault();
    let $nextCell = $this.closest('tr').next('tr').find('td[contenteditable="true"]');
    if ($nextCell.length > 0) {
      $nextCell.focus();
    }
  }

  if (e.key === 'ArrowUp') {
    e.preventDefault();
    let $prevCell = $this.closest('tr').prev('tr').find('td[contenteditable="true"]');
    if ($prevCell.length > 0) {
      $prevCell.focus();
    }
  }
});
</script>
</body>
</html>