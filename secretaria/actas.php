<?php
// actas.php - Programador de SistemasEscolares
session_start();

// Inclusiones de seguridad y arquitectura
include '../funciones/verificarSesion.php';
include '../inicio/conexion.php'; 
include '../funciones/consultas.php';

// Habilitar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Validar que el usuario sea de secretaría/administración (Ejemplo: si usa el mismo sistema de login que admin)
if (!isset($_SESSION['sec_nombreUsuario'])) {
    header('Location: loginAdmin.php');
    exit;
}

// ==================================================================
// BLOQUE 1: MANEJADOR DE PETICIONES AJAX (Actualización de datos)
// Metodología POST para la actualización (más seguro).
// ==================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["accion"]) && $_POST["accion"] == "actualizarActa") {
    header('Content-Type: text/plain');

    // 1. Sanitización estricta de parámetros POST
    $idAlumno = filter_input(INPUT_POST, 'idAlumno', FILTER_VALIDATE_INT);
    $columna = filter_input(INPUT_POST, 'columna', FILTER_SANITIZE_SPECIAL_CHARS); // Se valida contra lista blanca en consultas.php
    $valor = filter_input(INPUT_POST, 'valor', FILTER_UNSAFE_RAW); // Se valida en JS y se normaliza aquí
    $idFechaExamen = filter_input(INPUT_POST, 'idFechaExamen', FILTER_VALIDATE_INT);

    if ($idAlumno === false || $idFechaExamen === false || empty($columna)) {
        echo "Error: Parámetros de actualización inválidos o faltantes (IDs).";
        exit;
    }
    
    // Normalización/Validación de valor (simulando lógica de cargaActas.php)
    $valorValidado = trim(strtoupper($valor));
    
    // 2. Llamada a la función encapsulada en consultas.php
    try {
        // La validación de columna se hace en la función de consulta por seguridad, pero el valor se trata aquí.
        // Las validaciones de negocio (notas válidas 1-10, AP, NA, etc.) se hacen en el JS para feedback inmediato, 
        // pero el valor es normalizado/filtrado para la BD.
        $respuesta = actualizarActaExamen($conn, $idFechaExamen, $idAlumno, $columna, $valorValidado); 
        echo $respuesta; // Debe retornar "actualizado" o un error
    } catch (Exception $e) {
        error_log("Excepción al actualizar acta: " . $e->getMessage());
        echo "Error: " . $e->getMessage();
    }
    exit;
}

// ==================================================================
// BLOQUE 2: MANEJADOR DE PETICIONES AJAX (Selects dependientes y Carga de Acta)
// Metodología GET para obtener datos de selects.
// ==================================================================
if (isset($_GET['ajax_action'])) {
    header('Content-Type: application/json');
    
    try {
        $datos = [];
        // Se utiliza la función filter_input y la validación en las funciones de consultas.php
        switch ($_GET['ajax_action']) {
            case 'get_turnos':
                $datos = buscarTurnosDisponibles($conn);
                break;
            case 'get_planes':
                $idCiclo = filter_input(INPUT_GET, 'idCiclo', FILTER_VALIDATE_INT);
                $idTurno = filter_input(INPUT_GET, 'idTurno', FILTER_VALIDATE_INT);
                if ($idCiclo && $idTurno) {
                    $datos = buscarPlanesPorCicloTurno($conn, $idCiclo, $idTurno);
                }
                break;
            case 'get_cursos':
                $idPlan = filter_input(INPUT_GET, 'idPlan', FILTER_VALIDATE_INT);
                $idCiclo = filter_input(INPUT_GET, 'idCiclo', FILTER_VALIDATE_INT);
                if ($idPlan && $idCiclo) {
                     $datos = buscarCursosPlanCiclo($conn, $idPlan, $idCiclo);
                }
                break;
            case 'get_materias':
                $idPlan = filter_input(INPUT_GET, 'idPlan', FILTER_VALIDATE_INT);
                $idCurso = filter_input(INPUT_GET, 'idCurso', FILTER_VALIDATE_INT);
                $idCiclo = filter_input(INPUT_GET, 'idCiclo', FILTER_VALIDATE_INT);
                if ($idPlan && $idCurso && $idCiclo) {
                    $datos = buscarMateriasCursoPlanCiclo($conn, $idPlan, $idCurso, $idCiclo);
                }
                break;
            case 'get_mesas':
                $idMateria = filter_input(INPUT_GET, 'idMateria', FILTER_VALIDATE_INT);
                $idCiclo = filter_input(INPUT_GET, 'idCiclo', FILTER_VALIDATE_INT);
                if ($idMateria && $idCiclo) {
                    $datos = buscarMesasExamenPorMateriaCiclo($conn, $idMateria, $idCiclo);
                }
                break;
            case 'get_acta':
                $idFechaExamen = filter_input(INPUT_GET, 'idFechaExamen', FILTER_VALIDATE_INT);
                if ($idFechaExamen) {
                    $datos = obtenerActaSecretaria($conn, $idFechaExamen);
                }
                break;
            case 'get_condiciones':
                $datos = buscarCondicionesExamen($conn);
                break;
            default:
                http_response_code(400); 
                echo json_encode(['error' => 'Acción AJAX desconocida.']);
                exit;
        }
        echo json_encode($datos);
        
    } catch (Exception $e) {
        error_log("Error en AJAX (actas.php): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(['error' => 'Error en el servidor.']);
    }
    exit;
}

// ==================================================================
// BLOQUE 3: LÓGICA DE LA PÁGINA (Carga inicial de selects)
// ==================================================================
// La carga inicial solo necesita Ciclos Lectivos y Condiciones.
$ciclos_lectivos = buscarCiclosLectivosArray($conn);
$condiciones = buscarCondicionesExamen($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actas de Examen | Secretaría</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/material/bootstrap.min.css"> 
    <link rel="stylesheet" href="../css/estilos.css"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    
    <style>
        /* Estilos estéticos para mejorar la experiencia de edición */
        [contenteditable="true"] {
            cursor: pointer;
            padding: 5px;
            min-width: 100px; /* Asegurar ancho mínimo para editar */
            display: inline-block;
        }
        [contenteditable="true"]:focus {
            background-color: #fff9e6; /* Resaltar al editar */
            outline: 2px solid #ffc107;
        }
        .table-responsive-acta {
            overflow-x: auto;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
<?php 
// Asumo que hay un menú de secretaría/administración
include '../funciones/menu_secretaria.php'; 
?>

<div class="container-fluid fondo">
    <br>
    <div class="container">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="menusecretaria.php">Inicio</a></li>
            <li class="breadcrumb-item active">Actas de Examen</li>
        </ol>

        <div class="card p-4 shadow-sm">
            <h5>Gestión de Actas de Examen</h5>
            <hr>

            <div class="row g-3 mb-4">
                
                <div class="col-md-2">
                    <label for="selectCiclo" class="form-label">Ciclo Lectivo</label>
                    <select class="form-select" id="selectCiclo">
                        <option value="">Seleccione</option>
                        <?php foreach ($ciclos_lectivos as $ciclo): ?>
                            <option value="<?php echo htmlspecialchars($ciclo['idCicloLectivo']); ?>">
                                <?php echo htmlspecialchars($ciclo['anio']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="selectTurno" class="form-label">Turno</label>
                    <select class="form-select" id="selectTurno" disabled>
                        <option value="">Seleccione</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="selectPlan" class="form-label">Plan</label>
                    <select class="form-select" id="selectPlan" disabled>
                        <option value="">Seleccione</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="selectCurso" class="form-label">Curso</label>
                    <select class="form-select" id="selectCurso" disabled>
                        <option value="">Seleccione</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="selectMateria" class="form-label">Materia</label>
                    <select class="form-select" id="selectMateria" disabled>
                        <option value="">Seleccione</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="selectMesa" class="form-label">Mesa de Examen</label>
                    <select class="form-select" id="selectMesa" disabled>
                        <option value="">Seleccione</option>
                    </select>
                </div>

            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <div id="infoMesa" class="alert alert-info d-none p-2 mb-0" role="alert">
                        </div>
                </div>

                <div class="col-md-4">
                    <label for="filtroCondicion" class="form-label">Filtrar por Condición</label>
                    <select class="form-select" id="filtroCondicion">
                        <option value="">Todas las Condiciones</option>
                        <?php foreach ($condiciones as $condicion): ?>
                            <option value="<?php echo htmlspecialchars($condicion['idCondicion']); ?>">
                                <?php echo htmlspecialchars($condicion['condicion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="table-responsive-acta">
                <table id="tablaActa" class="table table-hover table-striped">
                    <thead>
                        <tr class="table-primary">
                            <th scope="col" style="min-width: 250px;">Alumno</th>
                            <th scope="col" style="min-width: 150px;">Condición</th>
                            <th scope="col" style="min-width: 100px;">Oral</th>
                            <th scope="col" style="min-width: 100px;">Escrito</th>
                            <th scope="col" style="min-width: 120px;">Calificación</th>
                            <th scope="col" style="min-width: 100px;">Libro</th>
                            <th scope="col" style="min-width: 100px;">Folio</th>
                        </tr>
                    </thead>
                    <tbody id="actaBody">
                        <tr><td colspan="7" class="text-center text-muted">Seleccione una Mesa de Examen para cargar el acta.</td></tr>
                    </tbody>
                </table>
            </div>

            <div id="loadingOverlay" class="d-none text-center p-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="text-primary mt-2">Cargando Acta de Examen...</p>
            </div>
            
        </div>
    </div>
</div>

<?php include '../funciones/footer.html'; ?>
</body>
</html>

<script src="../funciones/sessionControl.js"></script>

<script>
    $(document).ready(function() {
        
        // Variables globales para el estado de los filtros y caché de datos
        let idCiclo = null;
        let idTurno = null;
        let idPlan = null;
        let idCurso = null;
        let idMateria = null;
        let idFechaExamen = null;
        let actaData = []; // Cache para los datos del acta actual

        // Función de utilidad para manejar peticiones AJAX y llenar selects
        function fetchAndPopulate(selectId, action, params = {}) {
            const $select = $(`#${selectId}`);
            $select.empty().append('<option value="">Cargando...</option>').prop('disabled', true);
            
            // Si falta un parámetro clave, resetear y salir
            if (Object.values(params).some(val => val === null)) {
                $select.empty().append('<option value="">Seleccione</option>').prop('disabled', true);
                return;
            }

            $.ajax({
                url: 'actas.php',
                type: 'GET',
                data: { ajax_action: action, ...params },
                dataType: 'json',
                success: function(data) {
                    $select.empty().append('<option value="">Seleccione</option>');
                    if (data && data.length > 0) {
                        $.each(data, function(index, item) {
                            let value, text;
                            // Determinación de campos por ID
                            if (selectId === 'selectTurno') { value = item.idTurno; text = item.nombre; }
                            else if (selectId === 'selectPlan') { value = item.idPlan; text = item.nombre; }
                            else if (selectId === 'selectCurso') { value = item.idCurso; text = item.nombre; }
                            else if (selectId === 'selectMateria') { value = item.idMateria; text = item.nombre; }
                            else if (selectId === 'selectMesa') { // Mesa de Examen
                                value = item.idFechaExamen;
                                text = `Fecha: ${item.Fecha} - Hora: ${item.Hora}`;
                                $select.append(`<option value="${value}" data-fecha="${item.Fecha}" data-hora="${item.Hora}" data-libro="${item.libro}" data-folio="${item.folio}">${text}</option>`);
                                return;
                            }
                            $select.append(`<option value="${value}">${text}</option>`);
                        });
                        $select.prop('disabled', false);
                    } else {
                        $select.append('<option value="">No hay datos</option>').prop('disabled', true);
                    }
                },
                error: function() {
                    $select.empty().append('<option value="">Error al cargar</option>').prop('disabled', true);
                }
            });
        }
        
        // ==================================================================
        // Manejadores de Eventos (Cascada de Selects)
        // ==================================================================

        function resetAndChain(resetSelects) {
             $('#actaBody').html('<tr><td colspan="7" class="text-center text-muted">Seleccione una Mesa de Examen para cargar el acta.</td></tr>');
             $('#infoMesa').addClass('d-none');
             actaData = [];
             
             resetSelects.forEach(id => {
                 $(`#${id}`).empty().append('<option value="">Seleccione</option>').prop('disabled', true);
             });
        }

        // 1. Ciclo Lectivo -> Turno
        $('#selectCiclo').on('change', function() {
            idCiclo = $(this).val();
            idTurno = idPlan = idCurso = idMateria = idFechaExamen = null;
            resetAndChain(['selectTurno', 'selectPlan', 'selectCurso', 'selectMateria', 'selectMesa']);
            if (idCiclo) fetchAndPopulate('selectTurno', 'get_turnos');
        });

        // 2. Turno -> Plan
        $('#selectTurno').on('change', function() {
            idTurno = $(this).val();
            idPlan = idCurso = idMateria = idFechaExamen = null;
            resetAndChain(['selectPlan', 'selectCurso', 'selectMateria', 'selectMesa']);
            if (idTurno && idCiclo) fetchAndPopulate('selectPlan', 'get_planes', { idCiclo: idCiclo, idTurno: idTurno });
        });

        // 3. Plan -> Curso
        $('#selectPlan').on('change', function() {
            idPlan = $(this).val();
            idCurso = idMateria = idFechaExamen = null;
            resetAndChain(['selectCurso', 'selectMateria', 'selectMesa']);
            if (idPlan && idCiclo) fetchAndPopulate('selectCurso', 'get_cursos', { idPlan: idPlan, idCiclo: idCiclo });
        });

        // 4. Curso -> Materia
        $('#selectCurso').on('change', function() {
            idCurso = $(this).val();
            idMateria = idFechaExamen = null;
            resetAndChain(['selectMateria', 'selectMesa']);
            if (idCurso && idPlan && idCiclo) fetchAndPopulate('selectMateria', 'get_materias', { idPlan: idPlan, idCurso: idCurso, idCiclo: idCiclo });
        });

        // 5. Materia -> Mesa de Examen
        $('#selectMateria').on('change', function() {
            idMateria = $(this).val();
            idFechaExamen = null;
            resetAndChain(['selectMesa']);
            if (idMateria && idCiclo) fetchAndPopulate('selectMesa', 'get_mesas', { idMateria: idMateria, idCiclo: idCiclo });
        });

        // 6. Mesa de Examen -> Cargar Acta y metadata
        $('#selectMesa').on('change', function() {
            idFechaExamen = $(this).val();
            $('#infoMesa').addClass('d-none');
            $('#actaBody').html('');
            actaData = [];

            if (idFechaExamen) {
                const $selectedMesa = $(this).find('option:selected');
                const nombreMateria = $('#selectMateria option:selected').text();
                const nombreCurso = $('#selectCurso option:selected').text();
                const fecha = $selectedMesa.data('fecha');
                const hora = $selectedMesa.data('hora');
                const libro = $selectedMesa.data('libro');
                const folio = $selectedMesa.data('folio');

                // Mostrar información de la mesa
                $('#infoMesa').removeClass('d-none').html(`
                    <strong>Materia:</strong> ${nombreMateria} | 
                    <strong>Curso:</strong> ${nombreCurso} | 
                    <strong>Fecha:</strong> ${fecha} - ${hora} |
                    <strong>Libro/Folio:</strong> ${libro || 'N/A'}/${folio || 'N/A'}
                `);
                
                loadActa(idFechaExamen);
            } else {
                $('#actaBody').html('<tr><td colspan="7" class="text-center text-muted">Seleccione una Mesa de Examen para cargar el acta.</td></tr>');
            }
        });
        
        // 7. Filtro por Condición -> Recargar Tabla (usa la caché actaData)
        $('#filtroCondicion').on('change', function() {
            const idCondicion = $(this).val();
            renderActaTable(actaData, idCondicion);
        });


        // ==================================================================
        // Funciones de Acta (Carga y Renderizado)
        // ==================================================================

        function loadActa(idMesa) {
            $('#loadingOverlay').removeClass('d-none');
            $('#tablaActa').css('opacity', 0.5);

            $.ajax({
                url: 'actas.php',
                type: 'GET',
                data: { ajax_action: 'get_acta', idFechaExamen: idMesa },
                dataType: 'json',
                success: function(data) {
                    $('#loadingOverlay').addClass('d-none');
                    $('#tablaActa').css('opacity', 1);
                    actaData = data; 
                    if (data.length > 0) {
                        const idCondicionFiltro = $('#filtroCondicion').val();
                        renderActaTable(data, idCondicionFiltro);
                    } else {
                        $('#actaBody').html('<tr><td colspan="7" class="text-center text-info">No hay alumnos inscriptos en esta mesa.</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#loadingOverlay').addClass('d-none');
                    $('#tablaActa').css('opacity', 1);
                    $('#actaBody').html(`<tr><td colspan="7" class="text-center text-danger">Error al cargar el acta: ${error}</td></tr>`);
                    console.error("Error al cargar acta:", error);
                }
            });
        }

        // Renderiza la tabla de forma dinámica, aplicando el filtro
        function renderActaTable(data, idCondicionFiltro) {
            let html = '';
            let filteredData = data;

            if (idCondicionFiltro && idCondicionFiltro !== "") {
                filteredData = data.filter(alumno => alumno.idCondicion == idCondicionFiltro);
            }

            if (filteredData.length === 0) {
                 $('#actaBody').html(`<tr><td colspan="7" class="text-center text-info">No hay alumnos con la condición seleccionada.</td></tr>`);
                 return;
            }

            $.each(filteredData, function(i, alumno) {
                // Al renderizar, guardamos el valor actual como data-original-value en cada celda
                html += `<tr data-id-alumno="${alumno.idAlumno}">
                            <td contenteditable="false">${alumno.apellido}, ${alumno.nombre}</td>
                            <td contenteditable="false" data-condicion-id="${alumno.idCondicion}" class="condicion-celda">${alumno.condicion}</td>
                            <td contenteditable="true" data-columna="oral" data-original-value="${alumno.oral || ''}" onblur="actualizarCelda(${alumno.idAlumno}, 'oral', this)">${alumno.oral || ''}</td>
                            <td contenteditable="true" data-columna="escrito" data-original-value="${alumno.escrito || ''}" onblur="actualizarCelda(${alumno.idAlumno}, 'escrito', this)">${alumno.escrito || ''}</td>
                            <td contenteditable="true" data-columna="calificacion" data-original-value="${alumno.calificacion || ''}" onblur="actualizarCelda(${alumno.idAlumno}, 'calificacion', this)">${alumno.calificacion || ''}</td>
                            <td contenteditable="true" data-columna="libro" data-original-value="${alumno.libro || ''}" onblur="actualizarCelda(${alumno.idAlumno}, 'libro', this)">${alumno.libro || ''}</td>
                            <td contenteditable="true" data-columna="folio" data-original-value="${alumno.folio || ''}" onblur="actualizarCelda(${alumno.idAlumno}, 'folio', this)">${alumno.folio || ''}</td>
                        </tr>`;
            });
            $('#actaBody').html(html);
        }

        // ==================================================================
        // Función de Actualización de Celda (Validación y Almacenamiento)
        // ==================================================================
        window.actualizarCelda = function(idAlumno, columna, element) {
            const $cell = $(element);
            let valor = $cell.text().trim();
            const originalValue = $cell.data('original-value') || ''; 
            
            if (valor === originalValue) return;

            // 1. Validación de cliente (Feedback inmediato)
            let valorValidado = valor.toUpperCase();
            let isValid = true;
            let finalValue = valor; // Valor a enviar al servidor

            if (['oral', 'escrito', 'calificacion'].includes(columna)) {
                // Validación estricta para notas, similar a cargaActas.php
                const valoresValidos = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'A','AP','APTO','NA','APT','']; 
                
                // Normalización de valores para consistencia en la BD
                if (valorValidado === 'APT' || valorValidado === 'APTO') finalValue = 'AP';
                else finalValue = valorValidado;

                if (!valoresValidos.includes(valorValidado) && !valoresValidos.includes(finalValue)) {
                    alert("⚠️ Dato inválido. Ingrese una nota (1-10), 'A', 'AP', 'NA' o déjelo vacío.");
                    $cell.text(originalValue); 
                    isValid = false;
                }
            } else if (['libro', 'folio'].includes(columna)) {
                // Validación de longitud para texto
                if (valor.length > 10) {
                    alert("⚠️ Máximo 10 caracteres permitidos para Libro/Folio.");
                    $cell.text(originalValue); 
                    isValid = false;
                }
                finalValue = valorValidado; // El valor final es el texto en mayúsculas
            }
            
            if (!isValid) return;

            // 2. Ejecutar AJAX (Almacenamiento de Datos)
            $cell.text(finalValue); // Actualizar DOM con el valor normalizado/validado
            $cell.data('original-value', finalValue); // Actualizar caché

            var datos = {
                accion: "actualizarActa",
                idAlumno: idAlumno,
                columna: columna,
                valor: finalValue, // Se envía el valor validado y normalizado al PHP
                idFechaExamen: idFechaExamen 
            };
            
            $cell.css('background-color', 'yellow'); // Indicador de "Guardando..."

            $.ajax({
                type: "POST",
                url: "actas.php",
                data: datos,
                success: function(respuesta) {
                    console.log("Respuesta del servidor:", respuesta);
                    if (respuesta.trim() == "actualizado") {
                        // Animación de éxito
                        $cell.css('background-color', 'lightgreen').animate({ backgroundColor: 'transparent' }, 1000);
                        
                        // Actualizar la caché de datos global (actaData) para que el filtro funcione con el nuevo dato
                        const alumnoIndex = actaData.findIndex(a => a.idAlumno == idAlumno);
                        if (alumnoIndex !== -1) {
                            actaData[alumnoIndex][columna] = finalValue;
                        }

                    } else {
                        // Error de servidor (ej. error en BD)
                        alert("❌ Error al guardar (Servidor): " + respuesta);
                        $cell.css('background-color', 'red').animate({ backgroundColor: 'transparent' }, 1500);
                        $cell.text(originalValue); // Revertir al valor original en caso de error
                        $cell.data('original-value', originalValue); // Restaurar caché
                    }
                },
                error: function(xhr, status, error) {
                    console.log("Error en la petición AJAX:", error);
                    alert("❌ Error de comunicación con el servidor.");
                    $cell.css('background-color', 'red').animate({ backgroundColor: 'transparent' }, 1500);
                    $cell.text(originalValue); // Revertir al valor original en caso de error
                    $cell.data('original-value', originalValue); // Restaurar caché
                }
            });
        }
    });
</script>