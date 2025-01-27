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
// echo $idMateria;

// actualizarCalifDocente($conn, 106045, 'n1', 4);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

  $idCalificacion = $_POST['idCalificacion'];
  $columna = $_POST['columna'];
  $nuevoValor = $_POST['nuevoValor'];

  // Actualizar la base de datos con el nuevo valor
  $respuesta = actualizarCalifDocente($conn, $idCalificacion, $columna, $nuevoValor);
  echo $nuevoValor;

  exit;
}


$alumnosCalif = obtenerCalificacionesMateria($conn, $idMateria);
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

        <p><small>* Las calificaciones se guardan automaticamente en cada modificación. Puede verificar la carga haciendo click en imprimir calificaciones.
          <br>Valores permitidos:1-10(notas), A(ausente), a(ausente), AP(aprobado), ap(aprobado), NA(no aprobado), na(no aprobado).
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

 // Validación de caracteres permitidos
var valoresPermitidos = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'A', 'a', 'AP', 'ap', 'NA', 'na'];

celda.onblur = function() {
  var nuevoValor = celda.textContent.trim();
  if (valoresPermitidos.indexOf(nuevoValor) === -1) {
    if (nuevoValor.toUpperCase() === 'N') {
      alert("Por favor, complete el valor con 'a' para 'na' o 'A' para 'NA'.");
      celda.textContent = ''; // Borrar contenido de la celda
      return;
    } else {
      alert("Valor no permitido. Solo se permiten números del 1 al 10, 'A', 'a', 'AP', 'ap', 'NA', 'na'.");
      celda.textContent = ''; // Borrar contenido de la celda
      return;
    }
  }
}

$.ajax({
  type: "POST",
  url: "carga_calif.php",
  data: {
    idCalificacion: idCalificacion,
    columna: columna,
    nuevoValor: nuevoValor
  },
  success: function(respuesta) {}
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
           <a href="../reportes/calificacionesDocPDF.php" target="_blank">
        <button class="btn btn-primary">Imprimir Calificaciones</button>
    </a>
      </div>
      <br>
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
            </tr>
          </thead>
          <tbody>
            <?php if (isset($alumnosCalif)) { ?>
              <?php foreach ($alumnosCalif as $listado) { ?>
                <tr>
                  <td><?php echo $listado['apellido'] . " " . $listado['nombre']; ?></td>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n1'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'n1')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['n1']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['n1']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n2'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'n2')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['n2']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['n2']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n3'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'n3')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['n3']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['n3']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n4'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'n4')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['n4']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['n4']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n5'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'n5')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['n5']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['n5']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n6'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'n6')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['n6']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['n6']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n7'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'n7')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['n7']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['n7']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n8'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'n8')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['n8']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['n8']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r1'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'r1')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r1']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['r1']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r2'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'r2')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r2']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['r2']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r3'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'r3')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r3']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['r3']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r4'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'r4')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r4']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['r4']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r5'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'r5')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r5']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['r5']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r6'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'r6')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r6']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['r6']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r7'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'r7')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r7']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['r7']; ?>
                    </td>
                  <?php endif; ?>
                  <?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['r8'])): ?>
                    <td contenteditable="true" oninput="actualizarCalif(this, 'r8')" data-id="<?php echo $listado['idCalificacion']; ?>">
                      <?php echo $listado['r8']; ?>
                    </td>
                  <?php else: ?>
                    <td>
                      <?php echo $listado['r8']; ?>
                    </td>
                  <?php endif; ?>
                  <td><?php echo $listado['estadoCursado']; ?></td>
                  <td><?php echo $listado['asistencia']; ?></td>
                </tr>
              <?php } ?>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  </div>



  <?php include '../funciones/footer.html'; ?>
</body>

</html>