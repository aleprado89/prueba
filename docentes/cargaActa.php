<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
include '../funciones/verificarSesion.php';

// --- CONSULTAR PERMISO DIRECTAMENTE A LA BASE DE DATOS ---
$docenteModifica = 0; 
$sqlPermiso = "SELECT docenteModifica FROM colegio LIMIT 1";
if ($resultadoPermiso = mysqli_query($conn, $sqlPermiso)) {
    if ($filaPermiso = mysqli_fetch_assoc($resultadoPermiso)) {
        $docenteModifica = (int)$filaPermiso['docenteModifica'];
    }
}
// ----------------------------------------------------------------

$doc_legajo = $_SESSION['doc_legajo'];
$nombreDoc = $_SESSION['doc_apellido'].", ".$_SESSION['doc_nombre'];

// --- CAPTURA DE VARIABLES Y DECODIFICACIÓN (Solución Acentos y ñ) ---
$idFechaExamen = filter_input(INPUT_GET, 'idFechaExamen', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'idFechaExamen', FILTER_VALIDATE_INT);
$Fecha = filter_input(INPUT_GET, 'Fecha', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$Hora = filter_input(INPUT_GET, 'Hora', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$idMateria = filter_input(INPUT_GET, 'idMateria', FILTER_VALIDATE_INT);

// Usamos GET directo y decodificamos las entidades HTML que provienen del sistema heredado
$nombreMateriaRaw = $_GET['nombreMateria'] ?? '';
$CursoRaw = $_GET['Curso'] ?? '';
$nombreMateria = html_entity_decode($nombreMateriaRaw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$Curso = html_entity_decode($CursoRaw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

$CicloLectivo = $datosColegio[0]['anioCargaNotas'];
$idturno = $datosColegio[0]['cargaActaVolTurno'];
$idCicloLectivo = buscarIdCiclo($conn, $CicloLectivo);
$turno = buscarNombreTurno($conn, $idturno);

// --- LÓGICA AJAX POST (GUARDADO) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "actualizarActa") {
    try {
        $idAlumno = filter_input(INPUT_POST, 'idAlumno', FILTER_VALIDATE_INT);
        $columna = $_POST["columna"];
        $valor = trim($_POST["valor"]);       
        $idFechaExamen = filter_input(INPUT_POST, 'idFechaExamen', FILTER_VALIDATE_INT);
        $esNueva = $_POST["esNueva"] ?? '0';

        // SEGURIDAD BACK-END: Bloqueo de ediciones a actas preexistentes si no hay permisos
        if ($docenteModifica == 0 && $esNueva === '0') {
            ob_clean();
            echo "error_permiso";
            exit;
        }

        // BUSCAR idInscripcion (Clave primaria requerida por actualizarNotaInscripcion)
        $idInscripcion = null;
        $sqlBuscarId = "SELECT idInscripcion FROM inscripcionexamenes WHERE idAlumno = ? AND idFechaExamen = ? LIMIT 1";
        $stmtBuscar = $conn->prepare($sqlBuscarId);
        $stmtBuscar->bind_param("ii", $idAlumno, $idFechaExamen);
        $stmtBuscar->execute();
        $resBuscar = $stmtBuscar->get_result();
        if ($rowBuscar = $resBuscar->fetch_assoc()) {
            $idInscripcion = $rowBuscar['idInscripcion'];
        }
        $stmtBuscar->close();

        if ($idInscripcion) {
            // LLAMADA A LA FUNCIÓN CORRECTA EN consultas.php
            $resultado = actualizarNotaInscripcion($conn, $idInscripcion, $columna, $valor);
            
            ob_clean();
            if ($resultado['success'] === true) {
                echo "actualizado"; // Retornamos la palabra mágica que espera el JS
            } else {
                echo $resultado['message']; // Si falla por validación, enviamos el error
            }
        } else {
            ob_clean();
            echo "Error: No se encontró el registro de inscripción.";
        }
    } catch (Exception $e) {
        ob_clean();
        echo "Error del Servidor: " . $e->getMessage();
    }
    exit;
}

// OBTENER DATOS PARA LA VISTA
$acta = obtenerActa($conn, $idFechaExamen);

// --- FUNCIÓN DE RENDERIZADO LIMPIO ---
function generarCeldaActa($valor, $columna, $idAlumno, $docenteModifica) {
    $valorStr = trim((string)$valor);
    $esNueva = ($valorStr === '') ? '1' : '0';
    $editable = ($docenteModifica == 1 || $esNueva === '1') ? 'true' : 'false';
    $htmlValor = htmlspecialchars($valorStr, ENT_QUOTES, 'UTF-8');
    
    if ($editable === 'true') {
        return "<td class=\"border\" contenteditable=\"true\" data-columna=\"$columna\" data-id-alumno=\"$idAlumno\" data-new=\"$esNueva\">$htmlValor</td>";
    } else {
        return "<td class=\"border disabled-cell\" data-columna=\"$columna\" data-id-alumno=\"$idAlumno\" data-new=\"$esNueva\" title=\"Edición bloqueada por Secretaría\">$htmlValor</td>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cargar Acta</title>
  
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> 

  <style>
    /* Estilo para las celdas bloqueadas históricas */
    td.disabled-cell {
        background-color: #e9ecef; 
        color: #6c757d; 
        cursor: not-allowed;
        pointer-events: none; 
    }
  </style>

  <script src="../js/bootstrap.min.js"></script> 
  <script src="../js/bootstrap.bundle.min.js"></script>
  <script src="../js/jquery-3.7.1.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <link rel="icon" type="image/png" href="../img/icon.png">

</head>
<body>
<?php include '../funciones/menu_docente.php';?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="menudocentes.php">Inicio</a></li>
    <li class="breadcrumb-item"><a href="mesasExamenProf.php">Mesas de exámenes</a></li>
    <li class="breadcrumb-item active">Carga de Acta</li> 
  </ol>

  <div class="card padding col-12">
    <h5><?php echo "Docente: ".htmlspecialchars($nombreDoc, ENT_QUOTES, 'UTF-8'); ?> </h5>    
    <h5><label for="ciclolectivo">Año: <?php echo htmlspecialchars($CicloLectivo, ENT_QUOTES, 'UTF-8'); ?></label></h5>
    <h5><?php echo "Turno: ".htmlspecialchars($turno, ENT_QUOTES, 'UTF-8'); ?> </h5>    
    
    <h5><?php echo "Materia: ".$nombreMateria; ?> </h5>    
    <h5><?php echo "Curso: ".$Curso; ?> </h5>   
    
    <h5><?php echo "Fecha mesa de examen: ".htmlspecialchars($Fecha.' - '.$Hora, ENT_QUOTES, 'UTF-8'); ?> </h5>    
    
    <?php if($docenteModifica == 0): ?>
        <br>
        <div class="alert alert-info">
            <strong>Atención:</strong> La modificación de actas de exámenes pasados está bloqueada desde Secretaría. <b>Puede cargar notas en los casilleros vacíos correspondientes a esta mesa.</b>
        </div>
    <?php endif; ?>
  </div>
  
  <br>
  <div class="container">
    <table id="tablaActa" class="table table-hover col-12">
      <thead>
        <tr class="table-primary">
          <th scope="col">Alumno</th>
          <th scope="col">Oral</th>
          <th scope="col">Escrito</th>
          <th scope="col">Calificación</th>
          <th scope="col">Libro</th>
          <th scope="col">Folio</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($acta as $alumno) { ?>
          <tr data-id-alumno="<?php echo $alumno['idAlumno']; ?>">
            <td class="border"><?php echo htmlspecialchars($alumno['apellido'] . ', ' . $alumno['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
            
            <?php echo generarCeldaActa($alumno['oral'], 'oral', $alumno['idAlumno'], $docenteModifica); ?>
            <?php echo generarCeldaActa($alumno['escrito'], 'escrito', $alumno['idAlumno'], $docenteModifica); ?>
            <?php echo generarCeldaActa($alumno['calificacion'], 'calificacion', $alumno['idAlumno'], $docenteModifica); ?>
            <?php echo generarCeldaActa($alumno['libro'], 'libro', $alumno['idAlumno'], $docenteModifica); ?>
            <?php echo generarCeldaActa($alumno['folio'], 'folio', $alumno['idAlumno'], $docenteModifica); ?>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
  
</div>
</div>
  
<?php include '../funciones/footer.html'; ?>
<script src="../funciones/sessionControl.js"></script>

<script>
$(document).ready(function() {
    var idFechaExamen = <?php echo $idFechaExamen; ?>;

    // EVENTO BLUR: Cuando el usuario termina de escribir y sale de la celda
    $('#tablaActa').on('blur', 'td[contenteditable="true"]', function() {
        let $this = $(this);
        let idAlumno = $this.data('id-alumno');
        let columna = $this.data('columna');
        let esNueva = $this.attr('data-new') || '0';
        let valor = $this.text().trim().toUpperCase();

        // Limpieza específica para 'APT' -> vacío como tenías en tu código original
        if (columna === 'calificacion' && valor === 'APT') {
            valor = '';
            $this.text('');
        }

        // Validaciones Front-End
        if (['escrito', 'oral', 'calificacion'].includes(columna)) {
            const valoresValidos = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'A','AP','APTO','NA','']; 
            if (valor !== '' && !valoresValidos.includes(valor)) {
                alert("Dato inválido. Ingrese notas numéricas del 1 al 10 o valores válidos (A, AP, APTO, NA).");
                $this.text('');
                valor = "";
            } else {
                $this.text(valor); // Forzamos la mayúscula en pantalla
            }
        } else if (['libro', 'folio'].includes(columna)) {
            if (valor.length > 10) {
                alert("Máximo 10 caracteres permitidos para Libro/Folio.");
                $this.text('');
                valor = "";
            }
        }

        // Preparar AJAX
        var datos = {
            accion: "actualizarActa",
            idAlumno: idAlumno,
            columna: columna,
            valor: valor,
            esNueva: esNueva,
            idFechaExamen: idFechaExamen
        };

        // Petición al Servidor
        $.ajax({
            type: "POST",
            url: "cargaActa.php", 
            data: datos,
            success: function(respuesta) {
                if (respuesta.trim() === 'error_permiso') {
                    alert('Error de Seguridad: La edición de actas históricas está bloqueada.');
                    $this.css('background-color', 'lightcoral');
                    $this.text(''); 
                } else if (respuesta.trim() === "actualizado") {
                    $this.css('background-color', 'lightgreen');
                    // Actualizamos el flag a 0 para que en esta misma sesión no se considere "vacía" nuevamente
                    $this.attr('data-new', '0'); 
                } else {
                    alert("Aviso del Servidor: " + respuesta);
                    $this.css('background-color', 'lightcoral');
                }
            },
            error: function(xhr, status, error) {
                console.log("Error AJAX:", error);
                $this.css('background-color', 'lightcoral');
            }
        });
    });

    // NAVEGACIÓN POR TECLADO (Mejora la velocidad de carga del profesor)
    $('#tablaActa').on('keydown', 'td[contenteditable="true"]', function(e) {
        let $this = $(this);
        let $currentRow = $this.closest('tr');
        let cellIndex = $this.index(); // En qué columna estamos
        
        if (e.key === 'Enter' || e.key === 'ArrowDown') {
            e.preventDefault(); 
            $this.blur(); // Dispara el guardado
            let $nextRow = $currentRow.next('tr');
            if ($nextRow.length > 0) {
                $nextRow.find('td').eq(cellIndex).focus(); 
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault(); 
            $this.blur();
            let $prevRow = $currentRow.prev('tr');
            if ($prevRow.length > 0) {
                $prevRow.find('td').eq(cellIndex).focus(); 
            }
        }
    });
});
</script>
</body>
</html>