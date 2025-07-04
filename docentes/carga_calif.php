<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../inicio/conexion.php';
// die("1. Llegó después de conexion.php"); // Prueba de depuración 1 (comenta después de usar)

include '../funciones/consultas.php';
// die("2. Llegó después de consultas.php"); // Prueba de depuración 2

include '../funciones/analisisestado.php';
// die("3. Llegó después de analisisestado.php"); // Prueba de depuración 3

include '../funciones/verificarSesion.php';
// die("4. Llegó después de verificarSesion.php"); // Prueba de depuración 4

$doc_legajo = $_SESSION['doc_legajo'] ?? null;
$nombreDoc = $_SESSION['doc_apellido'] . ", " . $_SESSION['doc_nombre'];

// Inicializa estas variables con valores seguros para evitar Notices en la carga inicial GET
$idMateria = $_POST['idMateria'] ?? $_GET['idMateria'] ?? null;
$curso = $_POST['curso'] ?? $_GET['curso'] ?? null;
$ciclolectivo = $_POST['ciclolectivo'] ?? $_GET['ciclolectivo'] ?? null;
$plan = $_POST['plan'] ?? $_GET['plan'] ?? null;
$materia = $_POST['materia'] ?? $_GET['materia'] ?? null;

$idCiclo=buscarIdCiclo($conn,$ciclolectivo); 
$i=0;

// Esta sección se ejecuta SÓLO en peticiones POST (AJAX)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // die("5. Entró en el bloque POST."); // Prueba de depuración 5

    // Reasigna las variables desde POST para asegurar que se usan los datos de AJAX
    $idMateria = $_POST['idMateria'] ?? null;
    $curso = $_POST['curso'] ?? null;
    $ciclolectivo = $_POST['ciclolectivo'] ?? null;
    $plan = $_POST['plan'] ?? null;
    $materia = $_POST['materia'] ?? null;

    if (isset($_POST['idAlumno']) && isset($_POST['abandono'])) {
        // die("6. Entró en el bloque de abandono."); // Prueba de depuración 6
        $idAlumno = $_POST['idAlumno'];
        $abandono = $_POST['abandono'];
        $estado = ($abandono == 'true') ? 'Abandonó Cursado' : '';
        $resultado = actualizarAbandonoCursado($conn, $idAlumno, $idMateria, $estado);

        ob_clean();
        echo json_encode(array('success' => true, 'message' => 'Estado de abandono actualizado correctamente!', 'new_state' => $estado));
        exit;
    } else { // Este es el bloque para la actualización de calificaciones (tu caso de prueba)
        if (
            isset($_POST['idCalificacion']) &&
            isset($_POST['columna']) &&
            isset($_POST['nuevoValor']) &&
            isset($_POST['idAlumno'])
        ) {
            // die("7. Entró en el bloque de actualización de calificación."); // Prueba de depuración 7
            $idCalificacion = $_POST['idCalificacion'];
            $columna = $_POST['columna'];
            $nuevoValor = trim($_POST['nuevoValor']);

            // die("8. Antes de la validación de columna. Columna: " . $columna . ", NuevoValor: " . $nuevoValor); // Prueba de depuración 8
            $allowedColumns = ['n1', 'n2', 'n3', 'n4', 'n5', 'n6', 'n7', 'n8', 'r1', 'r2', 'r3', 'r4', 'r5', 'r6', 'r7', 'r8', 'examenIntegrador'];
            if (!in_array($columna, $allowedColumns)) {
                ob_clean();
                echo json_encode(array('respuesta' => 'error', 'mensaje' => 'Columna no permitida para actualización.'));
                exit;
            }
            // die("9. Después de la validación de columna."); // Prueba de depuración 9

            // Validación de valores (tu lógica actual)
            // ... (no la modificamos aquí, ya la tienes)
            // die("10. Después de la validación de valores."); // Prueba de depuración 10

            // die("11. Antes de llamar a actualizarCalifDocente. Conn válido: " . (is_object($conn) ? "Sí" : "No o no es objeto")); // Prueba de depuración 11
            $respuesta = actualizarCalifDocente($conn, $idCalificacion, $columna, $nuevoValor);
            // die("12. Después de actualizarCalifDocente. Respuesta: " . $respuesta); // Prueba de depuración 12

            // die("13. Antes de llamar a iniciarAnalisis."); // Prueba de depuración 13
            $idAlumno = $_POST['idAlumno'];
            $resultado = iniciarAnalisis($conn, $idMateria, $idAlumno, $idCalificacion);
            // die("14. Después de iniciarAnalisis. Resultado: " . $resultado); // Prueba de depuración 14

            ob_clean();
            echo json_encode(array('respuesta' => $respuesta, 'resultado' => $resultado));
            exit;
        }
    }
}

// Este código se ejecuta cuando la página se carga vía GET (o si ninguna de las condiciones POST se cumplen)
$alumnosCalif = obtenerCalificacionesMateria($conn, $idMateria);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cargar calificaciones</title>
<!-- Bootstrap CSS -->
<link rel="stylesheet" href="../css/bootstrap.min.css">
<!-- Bootstrap CSS from material folder -->
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
<h5><?php echo "Docente: " . $nombreDoc; ?> </h5>
<h5><?php echo "Ciclo lectivo: " . $ciclolectivo; ?> </h5>
<h5><?php echo "Carrera: " . $plan; ?> </h5>
<h5><?php echo "Curso: " . $curso; ?> </h5>
<h5><?php echo "Materia: " . $materia; ?> </h5><br>
<p><small>* Las calificaciones se guardan automaticamente en cada modificación. La celda se pinta de verde cuando la calificacion se ha guardado exitosamente. Si no se pinta de verde revise su conexion a internet.
<br>Valores permitidos:1-10(notas), A(ausente), AP(aprobado), NA(no aprobado).
<br>* Las celdas de "Prom" están deshabilitadas por defecto. Marque el casillero encima de "Prom" para habilitar la edición.
</small></p>

</div>
<script src="../funciones/sessionControl.js"></script>
<!-- Bootstrap JS y jQuery (necesario para el modal) -->
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script>
var idMateria = "<?php echo htmlspecialchars($idMateria); ?>";
var curso = "<?php echo htmlspecialchars($curso); ?>";
var ciclolectivo = "<?php echo htmlspecialchars($ciclolectivo); ?>";
var plan = "<?php echo htmlspecialchars($plan); ?>";
var materia = "<?php echo htmlspecialchars($materia); ?>";
</script>
<script>
function actualizarCalif(celda, columna) {
    var idCalificacion = celda.getAttribute('data-id');
    var nuevoValor = celda.textContent.trim(); // Trim whitespace

    var trElement = celda.closest('tr[data-idAlumno]');
    var idAlumno = trElement.getAttribute('data-idAlumno');

    // Validation for allowed values
    var valoresNumericosPermitidos = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
    var valoresStringPermitidos = ['A', 'a','n']; // For Ausente
    var valoresMultiplesPermitidos = ['AP', 'ap', 'NA', 'na']; // For Aprobado/No Aprobado

    var isValid = false;

    if (nuevoValor === '') { // Empty value is allowed
        isValid = true;
    } else if (valoresNumericosPermitidos.includes(nuevoValor)) { // Numeric values 1-10
        isValid = true;
    } else if (valoresStringPermitidos.includes(nuevoValor)) { // 'A' or 'a'
        isValid = true;
    } else { // Check for 'AP', 'NA' etc. (case-insensitive)
        for (let i = 0; i < valoresMultiplesPermitidos.length; i++) {
            if (nuevoValor.toUpperCase() === valoresMultiplesPermitidos[i].toUpperCase()) {
                isValid = true;
                break;
            }
        }
    }

    if (!isValid) {
        alert("Valor no permitido. Solo se permiten números del 1 al 10, 'A' (Ausente), 'AP' (Aprobado), 'NA' (No Aprobado) o dejar vacío.");
        celda.textContent = ''; // Clear invalid content
        // Do not proceed with AJAX call for invalid value
        celda.style.setProperty('background-color', 'lightcoral', 'important'); // Visual feedback for error
        return;
    }

    console.log(`Sending AJAX for ${columna}, value: ${nuevoValor}`);
    $.ajax({
        type: "POST",
        url: "carga_calif.php",
        data: {
            idCalificacion: idCalificacion,
            columna: columna,
            nuevoValor: nuevoValor,
            idAlumno: idAlumno,
            idMateria: idMateria,
            ciclolectivo: ciclolectivo,
            plan: plan,
            materia: materia,
            curso: curso
        },
        dataType: 'json',
        success: function(response) {
            console.log('AJAX request successful.');
            console.log('Response:', response);

            if (response.respuesta === 'actualizado') {
                celda.style.setProperty('background-color', 'lightgreen', 'important');
                // Auto-clear background color after a short delay
               // setTimeout(function() {
                //    celda.style.removeProperty('background-color');
             //   }, 1500);

                var filaActual = celda.closest('tr');
                var estadoParcialElement = filaActual.querySelector('#estadoCursado');
                if (estadoParcialElement && response.resultado) {
                    estadoParcialElement.innerHTML = response.resultado;
                }
            } else {
                alert('Error al guardar calificación: ' + (response.mensaje || 'Respuesta inesperada.'));
                celda.style.setProperty('background-color', 'lightcoral', 'important');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX Error: " + textStatus, errorThrown);
            alert("Error de conexión al guardar la calificación. Revise su conexión a internet.");
            celda.style.setProperty('background-color', 'lightcoral', 'important');
        }
    });
}

// Remove the keydown event listener. The onblur validation is generally sufficient.
// If you still want a keydown filter, it needs to be more complex to allow for multi-char inputs like 'AP'.
// For now, removing it simplifies the logic and relies on the onblur/oninput validation.
/*
$('.table td[contenteditable="true"]').on('keydown', function(e) {
    var charCode = e.which;
    // This regex is problematic for keydown for multi-character inputs like AP/NA
    // It's better to validate on blur or input.
    // var regex = /^[0-9Aap] $/i; // Loosened to allow numbers, A, P
    // if (!(charCode >= 48 && charCode <= 57) && charCode != 65 && charCode != 80 && charCode != 78 && charCode !=97 && charCode != 110 && charCode != 112 && charCode != 8 && charCode != 46 && charCode != 37 && charCode != 39) {
    //     e.preventDefault();
    // }
    // No specific keydown filtering, rely on `actualizarCalif`'s validation after content is entered.
});
*/
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
------>scroll a la derecha------>
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
<!-- NEW COLUMN FOR PROM -->
<th scope="col" title="Esta columna es solo para la nota de promoción si corresponde">
    <div class="prom-header-content">
        <input type="checkbox" id="enablePromEdit">
        <span class="prom-text">Prom</span>
    </div>
</th>
<!-- END NEW COLUMN -->
<th scope="col">Estado Parcial</th>
<th scope="col">Asist</th>
<th scope="col">Abandonó Cursado</th>
</tr>
</thead>
<tbody>
<?php if (empty($alumnosCalif)) { ?>
<tr>
<td colspan="21">Sin registros</td> <!-- Updated colspan to reflect new column -->
</tr>
<?php } else { ?>
<?php if (isset($alumnosCalif)) { ?>
<?php foreach ($alumnosCalif as $listado) { ?>
<tr data-id="<?php echo $i; ?>" data-row="<?php echo $i; ?>" data-idAlumno="<?php echo $listado['idAlumno']; ?>">
<td class="border"><?php echo $listado['apellido'] . " " . $listado['nombre']; ?></td>
<?php if ($_SESSION['profeModCalifAsis'] == 1 || empty($listado['n1'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n1')" data-id="<?php echo $listado['idCalificacion']; ?>">
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
<!-- NEW BODY CELL FOR PROM -->
<td class="border prom-cell" contenteditable="false" oninput="actualizarCalif(this, 'examenIntegrador')" data-id="<?php echo $listado['idCalificacion']; ?>">
<?php echo $listado['examenIntegrador']; ?>
</td>
<!-- END NEW BODY CELL -->
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
    var idMateria = '<?php echo $idMateria; ?>';
    var materia = '<?php echo $materia; ?>';
    var curso = '<?php echo $curso; ?>';
    var plan = '<?php echo $plan; ?>';
    var ciclolectivo = '<?php echo $ciclolectivo; ?>';

    // --- Logic for "Prom" column editability ---
    const enablePromEditCheckbox = $('#enablePromEdit');
    const promCells = $('.prom-cell');

    // Initial state: Prom column is not editable
    promCells.attr('contenteditable', 'false');
    promCells.css('background-color', '#f0f0f0'); // Grey background for non-editable state

    enablePromEditCheckbox.on('change', function() {
        if ($(this).is(':checked')) {
            promCells.attr('contenteditable', 'true');
            promCells.css('background-color', ''); // Remove grey background, make it look editable
            // You might want to add a light yellow or white background here
        } else {
            promCells.attr('contenteditable', 'false');
            promCells.css('background-color', '#f0f0f0'); // Set back to grey
        }
    });

    // --- Existing Abandonment Logic ---

    // Buscar los checkboxes que estén marcados como "Abandonó Cursado"
    $('input[type="checkbox"][id^="abandono-"]').each(function() {
        if ($(this).is(':checked')) {
            var fila = $(this).closest('tr');
            fila.find('td').css('background-color', '#ccc'); // Pintar la fila de gris
            fila.find('td[contenteditable="true"]').attr('contenteditable', 'false'); // Bloquear las celdas
             // Additionally, block the new 'prom-cell' if it's there
            fila.find('.prom-cell').attr('contenteditable', 'false');
            $(this).attr('disabled', true); // Deshabilitar el checkbox
        }
    });

    //boton confirmar (for abandonment modal)
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
                abandono: abandono,
                idMateria: idMateria,
                materia: materia,
                curso: curso,
                plan: plan,
                ciclolectivo: ciclolectivo
            },
            success: function(response) {
                console.log('Abandono status update response:', response);
                checkbox.prop('checked', true); // Marca el checkbox
                fila.find('td').css('background-color', '#ccc'); // Pintar la fila de gris
                fila.find('td[contenteditable="true"]').attr('contenteditable', 'false'); // Bloquear las celdas
                fila.find('.prom-cell').attr('contenteditable', 'false'); // Ensure 'prom-cell' is also blocked
                checkbox.attr('disabled', true); // Deshabilitar el checkbox
                $('#confirmarAbandono').modal('hide'); // Ocultar ventana modal
            },
            error: function(jqXHR, textStatus, errorThrown) {
                 console.error("AJAX Error updating abandonment: " + textStatus, errorThrown);
                 alert("Error de conexión al actualizar abandono.");
            }
        });
    });

    //abre el modal (for abandonment)
    $(document).on('mousedown', 'input[type="checkbox"][id^="abandono-"]', function(event) {
        // Only show modal if the checkbox is not already checked/disabled
        if (!$(this).is(':checked') && !$(this).is(':disabled')) {
            event.preventDefault(); // Evita que se marque el checkbox al hacer click
            $('#confirmarAbandono').data('checkbox', $(this)); // Almacena el checkbox en la ventana modal
            $('#confirmarAbandono').modal('show'); // Muestra la ventana modal
        }
    });

    //boton cancelar del modal (for abandonment)
    $(document).on('click', '#confirmarAbandono .btn-default', function() { // Target specific modal's cancel button
        var checkbox = $('#confirmarAbandono').data('checkbox');
        // No need to change its state or enable/disable, as it was never checked initially
        // by the user due to preventDefault(). Just hide modal.
        $('#confirmarAbandono').modal('hide'); // Ocultar ventana modal
    });
});
</script>
</body>
</html>