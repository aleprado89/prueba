<?php
ob_start();
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

// Obtener variables de la URL o POST
$idMateria = $_POST['idMateria'] ?? null;
$curso = $_POST['curso'] ?? null;
$ciclolectivo = $_POST['ciclolectivo'] ?? null;
$ciclolectivo_id = $_POST['ciclolectivo_id'] ?? null;
$plan = $_POST['plan'] ?? null;
$plan_id = $_POST['plan_id'] ?? null;
$materia = $_POST['materia'] ?? null;

// --- Manejo de peticiones AJAX (POST) para guardar datos ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['idCalificacion']) || isset($_POST['abandono']) || isset($_POST['get_condiciones_cursado']))) {

    // Petición AJAX para obtener las condiciones de cursado
    if (isset($_POST['get_condiciones_cursado'])) {
        $condiciones = obtenerCondicionesCursado($conn);
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $condiciones]);
        exit;
    }

    // Si la petición es para actualizar el estado de abandono (marcar o desmarcar)
    if (isset($_POST['idAlumno']) && isset($_POST['abandono'])) {
        $idAlumno = $_POST['idAlumno'];
        $abandono = $_POST['abandono']; // 'true' o 'false'
        $estado = ''; // Estado por defecto si se desmarca
        
        if ($abandono == 'true') {
            $estado = 'Abandonó Cursado'; // Si se marca el abandono
        } else {
            // Si se desmarca, se espera un nuevo estado del select
            $estado = $_POST['nuevo_estado'] ?? ''; 
        }

        $resultado = actualizarAbandonoCursado($conn, $idAlumno, $idMateria, $estado); // idMateria es del POST

        ob_clean(); // Limpia cualquier salida anterior
        header('Content-Type: application/json'); // Asegura que se envía JSON
        // Devolver un flag para que JS sepa si fue éxito
        echo json_encode(array('success' => true, 'message' => 'Estado de abandono actualizado correctamente!', 'new_state' => $estado, 'db_response' => $resultado));
        exit;
    }

    // Si la petición es para actualizar una calificación
    if (isset($_POST['idCalificacion']) && isset($_POST['columna']) && isset($_POST['nuevoValor']) && isset($_POST['idAlumno'])) {
        $idCalificacion = $_POST['idCalificacion'];
        $columna = $_POST['columna'];
        $nuevoValor = trim($_POST['nuevoValor']);
        $idAlumno = $_POST['idAlumno'];
        $idMateria_ajax = $_POST['idMateria_ajax']; // Capturar idMateria enviado desde JS

        $allowedColumns = ['n1', 'n2', 'n3', 'n4', 'n5', 'n6', 'n7', 'n8', 'r1', 'r2', 'r3', 'r4', 'r5', 'r6', 'r7', 'r8', 'examenIntegrador'];
        if (!in_array($columna, $allowedColumns)) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(array('respuesta' => 'error', 'mensaje' => 'Columna no permitida para actualización.'));
            exit;
        }

        $respuesta = actualizarCalifDocente($conn, $idCalificacion, $columna, $nuevoValor);
        $resultado_analisis = iniciarAnalisis($conn, $idMateria_ajax, $idAlumno, $idCalificacion);

        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(array('respuesta' => $respuesta, 'resultado' => $resultado_analisis));
        exit;
    }
}
// --- Fin del manejo de peticiones AJAX ---

// Si la página se carga vía GET o si ningún bloque POST AJAX anterior se ejecutó
// y no se reciben los datos necesarios, redirigir al listado de materias
if (empty($idMateria) || empty($ciclolectivo_id) || empty($plan_id)) {
    $_SESSION['login_message'] = "Datos de materia incompletos para cargar calificaciones.";
    header('Location: materiaxsecretario.php?action=calificaciones');
    exit;
}

// Carga de datos para la visualización inicial de la tabla
$alumnosCalif = obtenerCalificacionesMateria($conn, $idMateria);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cargar calificaciones (Secretaría)</title>
<!-- Bootstrap CSS -->
<link rel="stylesheet" href="../css/bootstrap.min.css">
<!-- Bootstrap CSS from material folder -->
<link rel="stylesheet" href="../css/material/bootstrap.min.css">
<link rel="stylesheet" href="../css/estilos.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
/* Estilo para centrar el contenido de la celda de la cabecera de Prom */
.prom-header-content {
    display: flex;
    justify-content: center; /* Centra horizontalmente */
    align-items: center;    /* Centra verticalmente */
    height: 100%;           /* Asegura que el contenedor ocupe toda la altura */
    text-align: center;     /* Asegura que el texto dentro del flexbox también se centre si es multilínea */
}
/* Asegurarse que la columna también se centre */
th.text-center {
    text-align: center !important;
}
</style>
<link rel="icon" type="image/png" href="../img/icon.png">

</head>
<body>
<?php include '../funciones/menu_secretaria.php'; // Incluimos el menú de secretaría ?>
<div class="container-fluid fondo">
<br>
<div class="container">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
<li class="breadcrumb-item"><a href="materiaxsecretario.php?action=calificaciones">Selección de Materia</a></li>
<li class="breadcrumb-item active">Carga de calificaciones</li>
</ol>
<div class="card padding col-12">
<h5><?php echo "Usuario: " . htmlspecialchars($sec_nombre ?? ''); ?> </h5>
<h5><?php echo "Ciclo lectivo: " . htmlspecialchars($ciclolectivo ?? ''); ?> </h5>
<h5><?php echo "Carrera: " . htmlspecialchars($plan ?? ''); ?> </h5>
<h5><?php echo "Curso: " . htmlspecialchars($curso ?? ''); ?> </h5>
<h5><?php echo "Materia: " . htmlspecialchars($materia ?? ''); ?> </h5><br>
<p><small>* Las calificaciones se guardan automaticamente en cada modificación. La celda se pinta de verde cuando la calificacion se ha guardado exitosamente. Si no se pinta de verde revise su conexión a internet.
<br>Valores permitidos:1-10(notas), A(ausente), AP(aprobado), NA(no aprobado).
<br>* Las celdas de "Prom" están deshabilitadas por defecto. Marque el casillero encima de "Prom" para habilitar la edición.
</small></p>

</div>
<script src="../funciones/sessionControl.js"></script>
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script>
var idMateria_js = "<?php echo htmlspecialchars($idMateria ?? ''); ?>";
</script>
<script>
function actualizarCalif(celda, columna) {
    var idCalificacion = celda.getAttribute('data-id');
    var nuevoValor = celda.textContent.trim();

    var trElement = celda.closest('tr[data-idAlumno]');
    var idAlumno = trElement.getAttribute('data-idAlumno');

    var valoresNumericosPermitidos = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
    var valoresStringPermitidos = ['A', 'a']; // Para Ausente
    var valoresMultiplesPermitidos = ['AP', 'ap', 'NA', 'na']; // Para Aprobado/No Aprobado (promoción, no aprobado)

    var isValid = false;

    if (nuevoValor === '') {
        isValid = true;
    } else if (valoresNumericosPermitidos.includes(nuevoValor)) {
        isValid = true;
    } else if (valoresStringPermitidos.includes(nuevoValor.toUpperCase())) {
        isValid = true;
        celda.textContent = nuevoValor.toUpperCase();
    } else {
        for (let i = 0; i < valoresMultiplesPermitidos.length; i++) {
            if (nuevoValor.toUpperCase() === valoresMultiplesPermitidos[i].toUpperCase()) {
                isValid = true;
                celda.textContent = valoresMultiplesPermitidos[i].toUpperCase();
                break;
            }
        }
    }

    if (!isValid) {
        alert("Valor no permitido. Solo se permiten números del 1 al 10, 'A' (Ausente), 'AP' (Aprobado), 'NA' (No Aprobado) o dejar vacío.");
        celda.textContent = '';
        celda.style.setProperty('background-color', 'lightcoral', 'important');
        return;
    }

    $.ajax({
        type: "POST",
        url: "carga_calif_secretaria.php",
        data: {
            idCalificacion: idCalificacion,
            columna: columna,
            nuevoValor: nuevoValor,
            idAlumno: idAlumno,
            idMateria_ajax: idMateria_js
        },
        dataType: 'json',
        success: function(response) {
            console.log('Response:', response);
            if (response.respuesta === 'actualizado') {
                celda.style.setProperty('background-color', 'lightgreen', 'important');
                setTimeout(function() {
                     celda.style.removeProperty('background-color');
                }, 1500);

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
            console.log("Response Text:", jqXHR.responseText);
            alert("Error de conexión al guardar la calificación. Revise su conexión a internet.");
            celda.style.setProperty('background-color', 'lightcoral', 'important');
        }
    });
}
</script>
<br>
<div class="text-center">
<a href="../reportes/calificacionesDocPDF.php?idMateria=<?php echo htmlspecialchars($idMateria ?? ''); ?>&curso=<?php echo htmlspecialchars($curso ?? ''); ?>&ciclolectivo=<?php echo htmlspecialchars($ciclolectivo_id ?? ''); ?>&plan=<?php echo htmlspecialchars($plan ?? ''); ?>&materia=<?php echo htmlspecialchars($materia ?? ''); ?>" target="_blank">
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
<th scope="col" class="text-center" title="Esta columna es solo para la nota de promoción si corresponde">
    <div class="prom-header-content text-center">
        <input type="checkbox" id="enablePromEdit">    </div>

        <span class="prom-text">Prom</span>
</th>
<th scope="col">Estado Parcial</th>
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
<?php foreach ($alumnosCalif as $listado) { ?>
<tr data-idAlumno="<?php echo htmlspecialchars($listado['idAlumno'] ?? ''); ?>">
<td class="border"><?php echo htmlspecialchars($listado['apellido'] ?? '') . " " . htmlspecialchars($listado['nombre'] ?? ''); ?></td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n1')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['n1'] ?? ''); ?>
</td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n2')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['n2'] ?? ''); ?>
</td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n3')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['n3'] ?? ''); ?>
</td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n4')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['n4'] ?? ''); ?>
</td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n5')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['n5'] ?? ''); ?>
</td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n6')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['n6'] ?? ''); ?>
</td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n7')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['n7'] ?? ''); ?>
</td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'n8')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['n8'] ?? ''); ?>
</td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r1')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['r1'] ?? ''); ?>
</td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r2')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['r2'] ?? ''); ?>
</td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r3')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['r3'] ?? ''); ?>
</td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r4')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['r4'] ?? ''); ?>
</td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r5')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['r5'] ?? ''); ?>
</td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r6')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['r6'] ?? ''); ?>
</td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r7')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['r7'] ?? ''); ?>
</td>
<td class="border" contenteditable="true" oninput="actualizarCalif(this, 'r8')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['r8'] ?? ''); ?>
</td>
<!-- Columna de Promoción: editable condicionalmente por el checkbox -->
<td class="border prom-cell" contenteditable="false" oninput="actualizarCalif(this, 'examenIntegrador')" data-id="<?php echo htmlspecialchars($listado['idCalificacion'] ?? ''); ?>">
<?php echo htmlspecialchars($listado['examenIntegrador'] ?? ''); ?>
</td>
<td class="border" id="estadoCursado" ><?php echo htmlspecialchars($listado['estadoCursado'] ?? ''); ?></td>
<td class="border"><?php echo htmlspecialchars($listado['asistencia'] ?? ''); ?></td>
<td class="border text-center">
<?php
$idAlumnoHTML = htmlspecialchars($listado['idAlumno'] ?? '');
// El checkbox ahora siempre es editable, la lógica de habilitar/deshabilitar la edición de celdas la maneja JS.
$checked_attr = (($listado['estado'] ?? '') == 'Abandonó Cursado') ? 'checked' : '';
echo '<input type="checkbox" id="abandono-'.$idAlumnoHTML.'" data-id-alumno="'.$idAlumnoHTML.'" '.$checked_attr.'>';
?>
</td>
</tr>
<?php } ?>
<?php } ?>
</tbody>
</table>
</div>
</div>
</div>
<?php include '../funciones/footer.html'; ?>

<!-- Modal para Confirmar Abandono (Cuando el checkbox se MARCA) -->
<div class="modal fade" id="modalConfirmAbandono" tabindex="-1" role="dialog" aria-labelledby="modalConfirmAbandonoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmAbandonoLabel">Confirmar Abandono de Materia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Al marcar esta casilla, se bloquea la carga de calificaciones y asistencia del alumno y se lo quita de los reportes.</p>
                <p>¿Desea continuar?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmarAbandonoBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Revertir Abandono (Cuando el checkbox se DESMARCA) -->
<div class="modal fade" id="modalRevertAbandono" tabindex="-1" role="dialog" aria-labelledby="modalRevertAbandonoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRevertAbandonoLabel">Revertir Abandono de Materia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Por favor, seleccione el nuevo estado de cursado para el alumno:</p>
                <div class="mb-3">
                    <label for="selectNewEstado" class="form-label">Estado:</label>
                    <select class="form-select" id="selectNewEstado">
                        <option value="">Cargando estados...</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmarRevertBtn">Aceptar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var idMateriaForAjax = idMateria_js; 

    // --- Lógica para la editabilidad de la columna "Prom" ---
    const enablePromEditCheckbox = $('#enablePromEdit');
    const promCells = $('.prom-cell');

    // Estado inicial: la columna Prom no es editable y tiene un fondo gris
    promCells.attr('contenteditable', 'false');
    promCells.css('background-color', '#f0f0f0');

    enablePromEditCheckbox.on('change', function() {
        if ($(this).is(':checked')) {
            promCells.attr('contenteditable', 'true');
            promCells.css('background-color', ''); // Quita el fondo gris, se vuelve editable
        } else {
            promCells.attr('contenteditable', 'false');
            promCells.css('background-color', '#f0f0f0'); // Vuelve al gris
        }
    });

    // --- Funciones auxiliares para estado de la fila ---
    function aplicarEstiloAbandono(fila) {
        fila.find('td').css('background-color', '#ccc');
        fila.find('td[contenteditable="true"]').attr('contenteditable', 'false');
        fila.find('.prom-cell').attr('contenteditable', 'false');
    }

    function quitarEstiloAbandono(fila) {
        fila.find('td').css('background-color', ''); // Quita el gris
        fila.find('td[contenteditable="false"]').attr('contenteditable', 'true'); // Re-habilita celdas
        // El prom-cell se maneja con su propio checkbox de habilitación
    }

    // --- Lógica de Abandono de Cursado (Manejo de Checkbox y Modales) ---

    // Inicializar el estado de las filas y accesibilidad de las celdas al cargar la página
    $('input[type="checkbox"][id^="abandono-"]').each(function() {
        if ($(this).is(':checked')) {
            aplicarEstiloAbandono($(this).closest('tr'));
        }
    });

    // Event listener para el cambio del checkbox de Abandono
    $(document).on('change', 'input[type="checkbox"][id^="abandono-"]', function() {
        var checkbox = $(this);
        var fila = checkbox.closest('tr');
        var idAlumno = checkbox.data('id-alumno'); // Obtener el ID del alumno del data-attribute

        if (checkbox.is(':checked')) { // Se marcó el checkbox (va a "Abandonó Cursado")
            $('#modalConfirmAbandono').data('checkbox', checkbox); // Almacena el checkbox actual
            $('#modalConfirmAbandono').modal('show');
        } else { // Se desmarcó el checkbox (va a "Revertir Abandono")
            $('#modalRevertAbandono').data('checkbox', checkbox); // Almacena el checkbox actual
            
            // Cargar las opciones del select en el modal de revertir
            $.ajax({
                type: 'POST',
                url: 'carga_calif_secretaria.php',
                data: { get_condiciones_cursado: '1' },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        var select = $('#selectNewEstado');
                        select.empty();
                        $.each(response.data, function(i, cond) {
                            select.append($('<option></option>').val(cond.condicion).text(cond.condicion));
                        });
                        $('#modalRevertAbandono').modal('show');
                    } else {
                        alert('Error al cargar las condiciones de cursado.');
                        checkbox.prop('checked', true); // Revertir el estado del checkbox si falla
                    }
                },
                error: function() {
                    alert('Error de conexión al cargar condiciones de cursado.');
                    checkbox.prop('checked', true); // Revertir el estado del checkbox si falla
                }
            });
        }
    });

    // --- Lógica para el modal de Confirmación de Abandono (MARCAR) ---
    $(document).on('click', '#confirmarAbandonoBtn', function() {
        var checkbox = $('#modalConfirmAbandono').data('checkbox');
        var fila = checkbox.closest('tr');
        var idAlumno = checkbox.data('id-alumno');

        $.ajax({
            type: "POST",
            url: "carga_calif_secretaria.php",
            data: {
                idAlumno: idAlumno,
                abandono: 'true', // Enviamos 'true' para indicar que se abandona
                idMateria: idMateriaForAjax
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    aplicarEstiloAbandono(fila);
                    $('#modalConfirmAbandono').modal('hide');
                } else {
                    alert('Error al actualizar abandono: ' + (response.message || 'Respuesta inesperada.'));
                    checkbox.prop('checked', false); // Mantener desmarcado si falla
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                 console.error("Error AJAX al actualizar abandono: " + textStatus, errorThrown);
                 alert("Error de conexión al actualizar abandono.");
                 checkbox.prop('checked', false); // Revertir el estado del checkbox si falla
            }
        });
    });

    // Si se cancela el modal de Confirmación de Abandono, revertir el checkbox
    $('#modalConfirmAbandono').on('hidden.bs.modal', function () {
        var checkbox = $(this).data('checkbox');
        if (checkbox && checkbox.is(':checked')) { // Solo si estaba checked y se cerró sin confirmar
             // Esto se dispara si el usuario cancela o cierra el modal. Si ya se confirmó,
             // el checkbox ya estará en el estado correcto y no se revertirá.
             // Aquí lo que queremos es que si se cancela, el checkbox vuelva a su estado original.
             // Como el "change" ya lo marcó, debemos desmarcarlo.
             // Se podría guardar el estado original antes del "change", pero por ahora esto es más simple:
             // Si el modal se cierra y no fue por un "confirmar", significa que se canceló.
             // Revertir el checkbox es clave.
             var confirmed = $(this).data('confirmed'); // Usar un flag para saber si fue confirmado
             if (!confirmed) {
                 checkbox.prop('checked', false);
             }
             $(this).removeData('confirmed'); // Limpiar el flag
        }
    });
    // Set a flag when the confirm button is clicked
    $('#confirmarAbandonoBtn').on('click', function() {
        $('#modalConfirmAbandono').data('confirmed', true);
    });

    // --- Lógica para el modal de Revertir Abandono (DESMARCAR) ---
    $(document).on('click', '#confirmarRevertBtn', function() {
        var checkbox = $('#modalRevertAbandono').data('checkbox');
        var fila = checkbox.closest('tr');
        var idAlumno = checkbox.data('id-alumno');
        var nuevoEstado = $('#selectNewEstado').val(); // Nuevo estado seleccionado

        if (!nuevoEstado) {
            alert('Por favor, seleccione un estado.');
            return;
        }

        $.ajax({
            type: "POST",
            url: "carga_calif_secretaria.php",
            data: {
                idAlumno: idAlumno,
                abandono: 'false', // Enviamos 'false' para indicar que se revierte
                nuevo_estado: nuevoEstado,
                idMateria: idMateriaForAjax
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    quitarEstiloAbandono(fila);
                    // Actualizar el estado parcial en la fila si es visible
                    fila.find('#estadoCursado').text(response.new_state); // Asumiendo que `new_state` devuelve el texto del estado
                    $('#modalRevertAbandono').modal('hide');
                } else {
                    alert('Error al revertir abandono: ' + (response.message || 'Respuesta inesperada.'));
                    checkbox.prop('checked', true); // Revertir el checkbox si falla
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                 console.error("Error AJAX al revertir abandono: " + textStatus, errorThrown);
                 alert("Error de conexión al revertir abandono.");
                 checkbox.prop('checked', true); // Revertir el checkbox si falla
            }
        });
    });

    // Si se cancela el modal de Revertir Abandono, revertir el checkbox
    $('#modalRevertAbandono').on('hidden.bs.modal', function () {
        var checkbox = $(this).data('checkbox');
        var confirmed = $(this).data('confirmed');
        if (!confirmed) { // Si no se confirmó (se cerró de otra forma o se canceló)
             if (checkbox) {
                 checkbox.prop('checked', true); // Vuelve a marcar el checkbox
             }
        }
        $(this).removeData('confirmed'); // Limpiar el flag
    });
    // Set a flag when the accept button is clicked
    $('#confirmarRevertBtn').on('click', function() {
        $('#modalRevertAbandono').data('confirmed', true);
    });
});
</script>
</body>
</html>