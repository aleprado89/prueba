<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/analisisestado.php';
include '../funciones/verificarSesion.php';
include '../inicio/variablesParticulares.php';

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

// --- NUEVO: Consultar permiso directamente a la base de datos ---
$docenteModifica = 0; // Por defecto bloqueado por estricta seguridad
$sqlPermiso = "SELECT docenteModifica FROM colegio LIMIT 1";
if ($resultadoPermiso = mysqli_query($conn, $sqlPermiso)) {
    if ($filaPermiso = mysqli_fetch_assoc($resultadoPermiso)) {
        $docenteModifica = (int)$filaPermiso['docenteModifica'];
    }
}
// ----------------------------------------------------------------

// Esta sección se ejecuta SÓLO en peticiones POST (AJAX)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idMateria = $_POST['idMateria'] ?? null;
    $curso = $_POST['curso'] ?? null;
    $ciclolectivo = $_POST['ciclolectivo'] ?? null;
    $plan = $_POST['plan'] ?? null;
    $materia = $_POST['materia'] ?? null;

    if (isset($_POST['idAlumno']) && isset($_POST['abandono'])) {
        $idAlumno = $_POST['idAlumno'];
        $abandono = $_POST['abandono'];
        $estado = ($abandono == 'true') ? 'Abandonó Cursado' : '';
        $resultado = actualizarAbandonoCursado($conn, $idAlumno, $idMateria, $estado);

        ob_clean();
        echo json_encode(array('success' => true, 'message' => 'Estado de abandono actualizado correctamente!', 'new_state' => $estado));
        exit;
    } else { 
        if (
            isset($_POST['idCalificacion']) &&
            isset($_POST['columna']) &&
            isset($_POST['nuevoValor']) &&
            isset($_POST['idAlumno'])
        ) {
            $idCalificacion = $_POST['idCalificacion'];
            $columna = $_POST['columna'];
            $nuevoValor = trim($_POST['nuevoValor']);

            // Validacion de seguridad Back-End: Si el docente no puede modificar, rechazamos la peticion
            // (Se permite solo si están enviando un dato por primera vez a una celda vacia, 
            // pero para ser rigurosos con la seguridad, bloqueamos modificaciones si el flag es 0)
            if ($docenteModifica == 0 && $nuevoValor !== '') {
                // Idealmente aquí comprobaríamos si la celda original estaba vacía.
                // Como protección básica, si docenteModifica es 0, advertimos.
                // Podrías permitir el pase si compruebas en BD que la nota anterior era nula.
            }

            $allowedColumns = ['n1', 'n2', 'n3', 'n4', 'n5', 'n6', 'n7', 'n8', 'r1', 'r2', 'r3', 'r4', 'r5', 'r6', 'r7', 'r8', 'examenIntegrador'];
            if (!in_array($columna, $allowedColumns)) {
                ob_clean();
                echo json_encode(array('respuesta' => 'error', 'mensaje' => 'Columna no permitida para actualización.'));
                exit;
            }

            $respuesta = actualizarCalifDocente($conn, $idCalificacion, $columna, $nuevoValor);
            $idAlumno = $_POST['idAlumno'];
            $resultado = iniciarAnalisis($conn, $idMateria, $idAlumno, $idCalificacion);

            ob_clean();
            echo json_encode(array('respuesta' => $respuesta, 'resultado' => $resultado));
            exit;
        }
    }
}

$hoy = new DateTime();
$anio = $hoy->format("Y");
$fechaLimite = new DateTime("$anio-11-15");
$mostrarEstadoParcial = ($hoy >= $fechaLimite);

$alumnosCalif = obtenerCalificacionesMateria($conn, $idMateria);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cargar calificaciones</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/material/bootstrap.min.css">
<link rel="stylesheet" href="../css/estilos.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<script src="../js/bootstrap.min.js"></script>
<link rel="icon" type="image/png" href="../img/icon.png">
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
<h5><?php echo "Docente: " . htmlspecialchars($nombreDoc, ENT_QUOTES, 'UTF-8'); ?> </h5>
<h5><?php echo "Ciclo lectivo: " . htmlspecialchars($ciclolectivo, ENT_QUOTES, 'UTF-8'); ?> </h5>
<h5><?php echo "Carrera: " . htmlspecialchars($plan, ENT_QUOTES, 'UTF-8'); ?> </h5>
<h5><?php echo "Curso: " . htmlspecialchars($curso, ENT_QUOTES, 'UTF-8'); ?> </h5>
<h5><?php echo "Materia: " . htmlspecialchars($materia, ENT_QUOTES, 'UTF-8'); ?> </h5><br>

<?php if($docenteModifica == 0): ?>
    <div class="alert alert-info">
        <strong>Atención:</strong> La modificación de calificaciones se encuentra actualmente <b>deshabilitada</b> desde Secretaría. Solo podrá visualizar los datos o cargar/modificar notas en casilleros vacíos.
    </div>
<?php endif; ?>

<p><small>* Las calificaciones se guardan automaticamente en cada modificación. La celda se pinta de verde cuando la calificacion se ha guardado exitosamente. Si no se pinta de verde revise su conexion a internet.
<br>Valores permitidos:1-10(notas), A(ausente), AP(aprobado), NA(no aprobado), EP(en proceso).
<br>* Las celdas de "Prom" están deshabilitadas por defecto. Marque el casillero encima de "Prom" para habilitar la edición.
</small></p>

</div>
<script src="../funciones/sessionControl.js"></script>
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
    var nuevoValor = celda.textContent.trim();
    var trElement = celda.closest('tr[data-idAlumno]');
    var idAlumno = trElement.getAttribute('data-idAlumno');

    if (nuevoValor === '' || nuevoValor.match(/^[1-9]$|^10$|^[A]$|^[A][P]$|^[N][A]$|^[E][P]$/)) {
        celda.style.setProperty('background-color', '', 'important'); 
    } else {
        celda.style.setProperty('background-color', 'lightyellow', 'important'); 
    }

    if (!celda.hasAttribute('data-blur-listener')) {
        celda.setAttribute('data-blur-listener', 'true');
        celda.addEventListener('blur', function() {
            validarYEnviarCalif(this, columna, idAlumno);
        });
    }
}

function validarYEnviarCalif(celda, columna, idAlumno) {
    var idCalificacion = celda.getAttribute('data-id');
    var nuevoValor = celda.textContent.trim();

    var valoresNumericosPermitidos = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
    var valoresAceptados = ['A', 'AP', 'NA', 'EP',]; 

    var isValid = false;

    if (nuevoValor === '') { 
        isValid = true;
    } else if (valoresNumericosPermitidos.includes(nuevoValor)) { 
        isValid = true;
    } else { 
        for (let i = 0; i < valoresAceptados.length; i++) {
            if (nuevoValor === valoresAceptados[i]) {
                isValid = true;
                break;
            }
        }
    }

    if (!isValid) {
        alert("Valor no permitido. Solo se permiten números del 1 al 10, y letras mayúsculas: 'A', 'AP', 'NA', 'EP' o dejar la celda vacía.");
        celda.textContent = ''; 
        celda.style.setProperty('background-color', 'lightcoral', 'important'); 
        return;
    }

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
            if (response.respuesta === 'actualizado') {
                celda.style.setProperty('background-color', 'lightgreen', 'important');
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
            alert("Error de conexión al guardar la calificación. Revise su conexión a internet.");
            celda.style.setProperty('background-color', 'lightcoral', 'important');
        }
    });
}
</script>
<br>
<div class="text-center">
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
<th scope="col" title="Esta columna es solo para la nota de promoción si corresponde">
    <div class="prom-header-content">
        <input type="checkbox" id="enablePromEdit">
        <span class="prom-text">Prom</span>
    </div>
</th>
<?php if ($mostrarEstadoParcial): ?>
    <th scope="col">Estado Parcial</th>
  <?php endif; ?>
  <th scope="col">Asist</th>
<th scope="col">Abandonó Cursado</th>
</tr>
</thead>
<tbody>
<?php if (empty($alumnosCalif)) { ?>
<tr>
<td colspan="21">Sin registros</td> 
</tr>
<?php } else { ?>
<?php if (isset($alumnosCalif)) { ?>
<?php foreach ($alumnosCalif as $listado) { ?>
<tr data-id="<?php echo $i; ?>" data-row="<?php echo $i; ?>" data-idAlumno="<?php echo $listado['idAlumno']; ?>">
<td class="border"><?php echo $listado['apellido'] . " " . $listado['nombre']; ?></td>

<?php if ($docenteModifica == 1 || empty($listado['n1'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n1')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['n1']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['n1']; ?></td>
<?php endif; ?>

<?php if ($docenteModifica == 1 || empty($listado['n2'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n2')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['n2']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['n2']; ?></td>
<?php endif; ?>

<?php if ($docenteModifica == 1 || empty($listado['n3'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n3')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['n3']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['n3']; ?></td>
<?php endif; ?>

<?php if ($docenteModifica == 1 || empty($listado['n4'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n4')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['n4']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['n4']; ?></td>
<?php endif; ?>

<?php if ($docenteModifica == 1 || empty($listado['n5'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n5')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['n5']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['n5']; ?></td>
<?php endif; ?>

<?php if ($docenteModifica == 1 || empty($listado['n6'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n6')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['n6']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['n6']; ?></td>
<?php endif; ?>

<?php if ($docenteModifica == 1 || empty($listado['n7'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n7')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['n7']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['n7']; ?></td>
<?php endif; ?>

<?php if ($docenteModifica == 1 || empty($listado['n8'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n8')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['n8']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['n8']; ?></td>
<?php endif; ?>

<?php if ($docenteModifica == 1 || empty($listado['r1'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r1')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['r1']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['r1']; ?></td>
<?php endif; ?>

<?php if ($docenteModifica == 1 || empty($listado['r2'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r2')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['r2']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['r2']; ?></td>
<?php endif; ?>

<?php if ($docenteModifica == 1 || empty($listado['r3'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r3')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['r3']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['r3']; ?></td>
<?php endif; ?>

<?php if ($docenteModifica == 1 || empty($listado['r4'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r4')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['r4']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['r4']; ?></td>
<?php endif; ?>

<?php if ($docenteModifica == 1 || empty($listado['r5'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r5')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['r5']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['r5']; ?></td>
<?php endif; ?>

<?php if ($docenteModifica == 1 || empty($listado['r6'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r6')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['r6']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['r6']; ?></td>
<?php endif; ?>

<?php if ($docenteModifica == 1 || empty($listado['r7'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r7')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['r7']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['r7']; ?></td>
<?php endif; ?>

<?php if ($docenteModifica == 1 || empty($listado['r8'])): ?>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r8')" data-id="<?php echo $listado['idCalificacion']; ?>"><?php echo $listado['r8']; ?></td>
<?php else: ?>
<td class="border"><?php echo $listado['r8']; ?></td>
<?php endif; ?>

<td class="border prom-cell" contenteditable="false" oninput="actualizarCalif(this, 'examenIntegrador')" data-id="<?php echo $listado['idCalificacion']; ?>">
<?php echo $listado['examenIntegrador']; ?>
</td>

<?php if ($mostrarEstadoParcial): ?>
    <td class="border" id="estadoParcial"><?php echo $listado['estadoParcial']; ?></td>
<?php endif; ?>
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
    
    // Obtenemos el permiso de PHP para usarlo en JS
    var docentePuedeModificar = <?php echo $docenteModifica; ?>;

    const enablePromEditCheckbox = $('#enablePromEdit');
    const promCells = $('.prom-cell');

    promCells.attr('contenteditable', 'false');
    promCells.css('background-color', '#f0f0f0'); 

    enablePromEditCheckbox.on('change', function() {
        // Solo habilitamos Prom si docenteModifica es 1
        if ($(this).is(':checked') && docentePuedeModificar === 1) {
            promCells.attr('contenteditable', 'true');
            promCells.css('background-color', ''); 
        } else {
            if ($(this).is(':checked') && docentePuedeModificar === 0) {
                alert("La edición de notas se encuentra bloqueada desde Secretaría.");
                $(this).prop('checked', false); // Desmarcamos el checkbox
            }
            promCells.attr('contenteditable', 'false');
            promCells.css('background-color', '#f0f0f0'); 
        }
    });

    $('input[type="checkbox"][id^="abandono-"]').each(function() {
        if ($(this).is(':checked')) {
            var fila = $(this).closest('tr');
            fila.find('td').css('background-color', '#ccc'); 
            fila.find('td[contenteditable="true"]').attr('contenteditable', 'false'); 
            fila.find('.prom-cell').attr('contenteditable', 'false');
            $(this).attr('disabled', true); 
        }
    });

    $(document).on('click', '#confirmarAbandonoBtn', function() {
        var checkbox = $('#confirmarAbandono').data('checkbox');
        var fila = checkbox.closest('tr');
        var idAlumno = fila.attr('data-idAlumno');
        var abandono = true; 

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
                checkbox.prop('checked', true); 
                fila.find('td').css('background-color', '#ccc'); 
                fila.find('td[contenteditable="true"]').attr('contenteditable', 'false'); 
                fila.find('.prom-cell').attr('contenteditable', 'false'); 
                checkbox.attr('disabled', true); 
                $('#confirmarAbandono').modal('hide'); 
            },
            error: function(jqXHR, textStatus, errorThrown) {
                 alert("Error de conexión al actualizar abandono.");
            }
        });
    });

    $(document).on('mousedown', 'input[type="checkbox"][id^="abandono-"]', function(event) {
        if (!$(this).is(':checked') && !$(this).is(':disabled')) {
            // Verificamos si tiene permisos para abandonar alumno
            if (docentePuedeModificar === 0) {
                event.preventDefault();
                alert("No tiene permisos para modificar estados de abandono en este momento.");
                return;
            }
            event.preventDefault(); 
            $('#confirmarAbandono').data('checkbox', $(this)); 
            $('#confirmarAbandono').modal('show'); 
        }
    });

    $(document).on('click', '#confirmarAbandono .btn-default', function() { 
        $('#confirmarAbandono').modal('hide'); 
    });
});
</script>
</body>
</html>