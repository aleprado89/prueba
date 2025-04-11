<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/analisisestado.php';


$doc_legajo = $_SESSION['doc_legajo'];
$nombreDoc = $_SESSION['doc_apellido'] . ", " . $_SESSION['doc_nombre'];
$idMateria = $_SESSION['idMateria'];
$curso= $_SESSION['curso'];
$ciclolectivo = $_SESSION['ciclolectivo'];
$idCiclo=buscarIdCiclo($conn,$ciclolectivo);
$plan = $_SESSION['plan'];
$materia = $_SESSION['materia'];
$i=0;


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (isset($_POST['idAlumno']) && isset($_POST['abandono'])) {
    // Actualizar la propiedad cursando en la base de datos
    $idAlumno = $_POST['idAlumno'];
    $abandono = $_POST['abandono'];
    if ($abandono == 'true') {
      $estado = 'Abandonó Cursado';
    }
   
    // Aquí debes agregar la consulta SQL para actualizar la propiedad cursando
$resultado=actualizarAbandonoCursado($conn, $idAlumno, $idMateria, $estado);
  } else {
  // Obtiene los datos de la solicitud
  $idCalificacion = $_POST['idCalificacion'];
  $columna = $_POST['columna'];
  $nuevoValor = $_POST['nuevoValor'];
  $idAlumno = $_POST['idAlumno'];

  // Verifica si el valor contiene caracteres no permitidos
  $combinacionesPermitidas = ['AP', 'ap', 'NA', 'na'];
if (in_array($nuevoValor, $combinacionesPermitidas)) {
  // El valor es permitido
} else {
  // El valor no es permitido
  $caracteresPermitidos = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0', 'A', 'a', ' ', ''];
  foreach (str_split($nuevoValor) as $caracter) {
    if (!in_array($caracter, $caracteresPermitidos)) {
      echo json_encode(array('respuesta' => 'error', 'mensaje' => 'Valor no permitido'));
      exit;
    }
  }
}

  // Llama a la función actualizarCalifDocente
  $respuesta = actualizarCalifDocente($conn, $idCalificacion, $columna, $nuevoValor);

  // Llama a la función iniciarAnalisis
  $resultado = iniciarAnalisis($conn, $idMateria, $idAlumno, $idCalificacion);

  // Devuelve el resultado
  echo json_encode(array('respuesta' => $respuesta, 'resultado' => $resultado));
  exit;
}
}

$alumnosCalif = obtenerCalificacionesMateria($conn, $idMateria);
?>
<!DOCTYPE html>
<html lang="es">

</html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cargar calificaciones</title>
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
        <h5><?php echo  "Curso: " . $curso; ?> </h5>
        <h5><?php echo  "Materia: " . $materia; ?> </h5><br>

        <p><small>* Las calificaciones se guardan automaticamente en cada modificación. La celda se pinta de verde cuando la calificacion se ha guardado exitosamente. Si no se pinta de verde revise su conexion a internet.
          <br>Valores permitidos:1-10(notas), A(ausente), AP(aprobado), NA(no aprobado).
          </small></p>
    
      </div>


      <!-- Bootstrap JS y jQuery (necesario para el modal) -->
      <script src="../js/jquery-3.7.1.min.js"></script>
      <script src="../js/popper.min.js"></script>
      <script src="../js/bootstrap.min.js"></script>

      <script>

        function actualizarCalif(celda, columna) {

          var idCalificacion = celda.getAttribute('data-id');
          var nuevoValor = celda.textContent.trim(); // Agrega el método trim() aquí
          var filaPadre = celda.parentNode.parentNode;
          var trElement = celda.closest('tr[data-idAlumno]');
          var idAlumno = trElement.getAttribute('data-idAlumno');
         
 // Validación de caracteres permitidos
var valoresPermitidos = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'A', 'a',''];
var valoresPermitidosMultiples = ['AP', 'ap', 'NA', 'na'];

celda.onblur = function() {
  var nuevoValor = celda.textContent.trim();
  if (valoresPermitidos.indexOf(nuevoValor) === -1) {
    var encontrado = false;
    for (var i = 0; i < valoresPermitidosMultiples.length; i++) {
      if (nuevoValor.toUpperCase() === valoresPermitidosMultiples[i].toUpperCase()) {
        encontrado = true;
        break;
      }
    }
    if (!encontrado) {
      alert("Valor no permitido. Solo se permiten números del 1 al 10, 'A', 'AP', 'NA' ó vacio si no hay calificacion.");
      celda.textContent = ''; // Borrar contenido de la celda
      celda.dispatchEvent(new Event('input')); // Desencadenar el evento input
      return;
    }
  }
}
console.log(nuevoValor);
$.ajax({
  
    type: "POST",
    url: "carga_calif.php",
    data: {
      idCalificacion: idCalificacion,
      columna: columna,
      nuevoValor: nuevoValor,
      idAlumno: idAlumno

    },
    dataType: 'json',
    success: function(respuesta) {
 var filaPadre = celda.parentNode;
          var rowId = filaPadre.rowIndex;
      if (respuesta.respuesta === "actualizado") {
        var filaActual = document.querySelectorAll('tr')[rowId];  
        if (filaActual) {
          var estadoParcialCorrecto = filaActual.querySelector('#estadoCursado');
          if (estadoParcialCorrecto) {
            celda.style.backgroundColor = 'lightgreen';
            estadoParcialCorrecto.innerHTML = respuesta.resultado;
          } else {
            console.log('No se encontró la celda estadoCursado');
          }
        } else {
          console.log('No se encontró la fila con el atributo data-row igual a ' + rowId);
        }

      } else {
        celda.style.backgroundColor = '';
      }

    },
  });
}

        // Agregar evento de teclado a las celdas de la tabla
$('.table td[contenteditable="true"]').on('keydown', function(e) {
  var charCode = e.which;
  var regex = /^[1-9]|10|a|A|AP|ap|Ap|NA|na$/;

  // Cancelar la inserción de caracteres no permitidos
  if (!regex.test(String.fromCharCode(charCode))) {
    e.preventDefault();
    $(this).text(''); // Borrar contenido de la celda
  }
});


      </script>
<br>
<div class="text-center">
   <!-- BOTON VER LISTADO CALIFICACIONES -->
           <a href="../reportes/calificacionesDocPDF.php?idMateria=<?php echo htmlspecialchars($idMateria); ?>&curso=<?php echo htmlspecialchars($curso); ?>&ciclolectivo=<?php echo htmlspecialchars($idCiclo); ?>&plan=<?php echo htmlspecialchars($plan); ?>&materia=<?php echo htmlspecialchars($materia); ?>" target="_blank">
               <button class="btn btn-primary">Imprimir Calificaciones</button>
           </a>
      </div>
      <br>
      <div class="d-block d-sm-none text-center">
  ------>scroll a la derecha para imprimir------>
</div>
      <div>
        <table id="tablaMaterias" class="table table-hover col-12">
          <thead>
            <tr class="table-primary">
              <th scope="col">Estudiante</th>
              <th scope="col">P1</th>
              <th scope="col">P2</th>
              <th scope="col">P3</th>
              <th scope="col">P4</th>
              <th scope="col">P5</th>
              <th scope="col">P6</th>
              <th scope="col">P7</th>
              <th scope="col">IEFI</th>
              <th scope="col">RP1</th>
              <th scope="col">RP2</th>
              <th scope="col">RP3</th>
              <th scope="col">RP4</th>
              <th scope="col">RP5</th>
              <th scope="col">RP6</th>
              <th scope="col">RP7</th>
              <th scope="col">RIEFI</th>
              <th scope="col">Estado Parcial</th>
              <th scope="col">Asist</th>
              <th scope="col">Abandonó Cursado</th>          
              </tr>
          </thead>
          <tbody>
          <?php if (empty($alumnosCalif)) { ?>
        <tr>
          <td colspan="20">Sin registros</td>
        </tr>
      <?php } else { ?>
            <?php if (isset($alumnosCalif)) { ?>
              <?php foreach ($alumnosCalif as $listado) {  ?>
                <tr data-id="<?php echo $i; ?>" data-row="<?php echo $i; ?>" data-idAlumno="<?php echo $listado['idAlumno']; ?>">
                <td class="border"><?php echo $listado['apellido'] . " " . $listado['nombre']; ?></td>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n1'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n1')" data-id="<?php echo $listado['idCalificacion']; ?>" data-idAlumno="<?php echo $listado['idAlumno']; ?>">
                      <?php echo $listado['n1']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['n1']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n2'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n2')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['n2']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['n2']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n3'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n3')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['n3']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['n3']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n4'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n4')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['n4']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['n4']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n5'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n5')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['n5']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['n5']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n6'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n6')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['n6']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['n6']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n7'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n7')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['n7']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['n7']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n8'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n8')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['n8']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['n8']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r1'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r1')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r1']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['r1']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r2'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r2')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r2']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['r2']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r3'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r3')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r3']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['r3']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r4'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r4')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r4']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['r4']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r5'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r5')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r5']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['r5']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r6'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r6')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r6']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['r6']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r7'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r7')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r7']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['r7']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r8'])): ?>
                    <td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r8')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r8']; ?>
                    </td>
                  <?php else: ?>
                    <td class="border">
                      <?php echo $listado['r8']; ?>
                    </td>
                  <?php endif; ?>
                  <td class="border" id="estadoCursado" ><?php echo $listado['estadoCursado']; ?></td>
                  <td class="border"><?php echo $listado['asistencia']; ?></td>
                  <td class="border text-center">
                    <?php 
                    if ($listado['estado'] == 'Abandonó Cursado') {
                      echo '<input type="checkbox" id="abandono-'.$listado['idAlumno'].'" checked>';
                    }
                    else {
                      echo '<input type="checkbox" id="abandono-'.$listado['idAlumno'].'">';
                    }
                    ?>
                  </td>
                </tr>
              <?php $i++;} ?>
            <?php } } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  </div>
  <?php include '../funciones/footer.html'; ?>

   <!-- Ventana modal con confirmación -->
<div id="confirmarAbandono" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Confirmar abandono de materia</h4>
      </div>
      <div class="modal-body">
        <p>Al marcar esta casilla, se bloquea la carga de calificaciones y asistencia del alumno. Esta opción solo se puede revertir desde secretaria ¿Desea continuar?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="confirmarAbandonoBtn">Confirmar</button>
      </div>
    </div>
  </div>
</div>


<script>

$(document).ready(function() {
  // Buscar los checkboxes que estén marcados como "Abandonó Cursado"
  $('input[type="checkbox"][id^="abandono-"]').each(function() {
    if ($(this).is(':checked')) {
      var fila = $(this).closest('tr');
      fila.find('td').css('background-color', '#ccc'); // Pintar la fila de gris
      fila.find('td[contenteditable="true"]').attr('contenteditable', 'false'); // Bloquear las celdas
      $(this).attr('disabled', true); // Deshabilitar el checkbox

    }
  });
});
//boton confirmar
$(document).on('click', '#confirmarAbandonoBtn', function() {
  var checkbox = $('#confirmarAbandono').data('checkbox');
  var fila = checkbox.closest('tr');
  var idAlumno = fila.attr('data-idAlumno');
  var abandono = true; // El checkbox ya está marcado

  // Actualizar la propiedad abandono en la base de datos
  $.ajax({
    type: "POST",
    url: "carga_calif.php",
    data: {
      idAlumno: idAlumno,
      abandono: abandono
    },
    success: function() {
      console.log(abandono);
    }
  });

  checkbox.prop('checked', true); // Marca el checkbox
  fila.find('td').css('background-color', '#ccc'); // Pintar la fila de gris
  fila.find('td[contenteditable="true"]').attr('contenteditable', 'false'); // Bloquear las celdas
  checkbox.attr('disabled', true); // Deshabilitar el checkbox
  $('#confirmarAbandono').modal('hide'); // Ocultar ventana modal
});
//abre el modal
$(document).on('mousedown', 'input[type="checkbox"][id^="abandono-"]', function(event) {
  event.preventDefault(); // Evita que se marque el checkbox
  $('#confirmarAbandono').data('checkbox', $(this)); // Almacena el checkbox en la ventana modal
  $('#confirmarAbandono').modal('show'); // Muestra la ventana modal
});
//boton cancelar del modal
$(document).on('click', '.btn-default', function() {
  var checkbox = $('#confirmarAbandono').data('checkbox');
  checkbox.prop('checked', false); // Desmarca el checkbox
  checkbox.removeAttr('disabled'); // Habilita el checkbox
  checkbox.closest('tr').find('td[contenteditable="true"]').attr('contenteditable', 'true'); // Desbloquea las celdas
  checkbox.closest('tr').find('td').css('background-color', ''); // Restaura el color original de la fila
  $('#confirmarAbandono').modal('hide'); // Ocultar ventana modal
});
</script>

</body>

</html>