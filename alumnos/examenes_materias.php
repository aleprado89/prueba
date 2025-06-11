<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';
include '../funciones/parametrosWeb.php';
include '../funciones/verificarSesion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // BOTÓN: Ver solicitudes
    if (isset($_POST['submitVer'])) {
        header("Location: ../alumnos/examenes_solicitudes_listado.php");
        exit;
    }

    // BOTÓN: Solicitar examen
    if (isset($_POST['submitSolicitar'])) {
        $idM = $_POST['idM'];
        $nombreM = $_POST['nombreM'];
        $nombreC = $_POST['nombreC'];
        $idDivision = $_POST['idDivision'];

        $html = '<form id="formulario" method="post" action="../alumnos/examenes_solicitar.php">';
        $html .= '<input type="hidden" name="idM" value="' . $idM . '">';
        $html .= '<input type="hidden" name="nombreMateria" value="' . $nombreM . '">';
        $html .= '<input type="hidden" name="nombreCurso" value="' . $nombreC . '">';
        $html .= '<input type="hidden" name="idDivision" value="' . $idDivision . '">';
        $html .= '</form>';
        $html .= '<script>document.getElementById("formulario").submit();</script>';

        echo $html;
        exit;
    }
}

// VARIABLES DE SESIÓN
$idAlumno = $_SESSION['alu_idAlumno'];
$nombreAlumno = $_SESSION['alu_apellido'] . ", " . $_SESSION['alu_nombre'];
$idPlan = $_SESSION['idP'];
$nombrePlan = $_SESSION['nombreP'];
$idCicloLectivo = $_SESSION['idCiclo'];

// CURSOS PREDETERMINADOS
$listadoCursosPredeterminados = buscarCursoPredeterminado($conn, $idPlan);

// Si ya se seleccionó un curso en el POST, lo usamos
if (isset($_POST['idCursoPredeterminado'])) {
    $idCursoPredeterminado = $_POST['idCursoPredeterminado'];
} else {
    // Si no, buscamos el último curso con materias
    $idCursoPredeterminado = null;
    for ($i = count($listadoCursosPredeterminados) - 1; $i >= 0; $i--) {
        $idCursoTest = $listadoCursosPredeterminados[$i]['idcursopredeterminado'];
        $materiasTest = materiasAlumnoCurso($conn, $idAlumno, $idPlan, $idCursoTest);
        if (count($materiasTest) > 0) {
            $idCursoPredeterminado = $idCursoTest;
            break;
        }
    }
}
// LISTADO DE MATERIAS
$listadoMaterias = materiasAlumnoCurso($conn, $idAlumno, $idPlan, $idCursoPredeterminado);
$cantidad = count($listadoMaterias);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exámenes</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/material/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="../js/bootstrap.min.js"></script>
</head>

<body>
<?php include '../funciones/menu.php'; ?>

<div class="container-fluid fondo">
  <br>
  <div class="container">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="menualumnos.php">Inicio</a></li>
      <li class="breadcrumb-item"><a href="examenes_planes.php">Inscripción a exámenes</a></li>
      <li class="breadcrumb-item active">Materias</li>
    </ol>

    <div class="card padding col-12">
      <h5><?php echo "Alumno: " . $nombreAlumno; ?></h5>
      <h5><?php echo "Carrera: " . $nombrePlan; ?></h5>

      <div class="row">
        <div class="col-12 col-md-4">



          <!-- FORM PARA CAMBIO DE CURSO -->
          <form id="cursoForm" method="post">
            <select name="idCursoPredeterminado" id="curso" class="form-control" onchange="document.getElementById('cursoForm').submit();">
              <?php
              foreach ($listadoCursosPredeterminados as $curso) {
                  $selected = ($curso['idcursopredeterminado'] == $idCursoPredeterminado) ? 'selected' : '';
                  echo '<option value="' . $curso['idcursopredeterminado'] . '" ' . $selected . '>' . $curso['nombreCurso'] . '</option>';
              }
              ?>
            </select>
          </form>
        </div>
      </div>
    </div>

    <br>
    <div class="text-center">
      <form id="ver" method="post">
        <button type="submit" name="submitVer" class="btn btn-primary">Ver Solicitudes</button>
      </form>
    </div>

    <div class="container">
      <br>
      <form id="envio" method="post">
        <input type="hidden" name="idM" id="idM">
        <input type="hidden" name="nombreM" id="nombreM">
        <input type="hidden" name="nombreC" id="nombreC">
        <input type="hidden" name="idDivision" id="idDivision">

        <?php if ($_SESSION['soloLecturaExam'] == 0) { ?>
          <table id="materias" class="table table-hover col-12">
            <thead>
              <tr class="table-primary">
                <th style="display:none;">idMateria</th>
                <th style="display:none;">Materia Completo</th>
                <th>Materia</th>
                <th>Curso</th>
                <th>Estado</th>
                <th>Solicitar Exámen</th>
              </tr>
            </thead>
            <tbody>
              <?php $hayRegistros = false; ?>
              <?php foreach ($listadoMaterias as $materia): ?>
                <?php
                $idMateria = $materia['idMateria'];
                $nombreMateriaCompleto = $materia['Materia'];
                $curso = $materia['Curso'];
                $estado = $materia['Estado'];
                $calif = $materia['CalificacionFinal'];
                $idDivision = $materia['idDivision'];

                if (empty(trim($calif))) {
                    $hayRegistros = true;
                    $nombreMateriaReducido = implode(' ', array_slice(explode(' ', $nombreMateriaCompleto), 0, 8));
                    if (str_word_count($nombreMateriaCompleto) > 8) {
                        $nombreMateriaReducido .= "...";
                    }

                    // Verificar solicitudes existentes
                    $listadoSolicitudes = existeSolicitudExamen($conn, $idAlumno, $idMateria, $idCicloLectivo, $datosColegio[0]['idTurno']);
                    $existePendienteOAprobada = false;
                    foreach ($listadoSolicitudes as $sol) {
                        if ($sol['Estado'] == "Pendiente" || $sol['Estado'] == "Aprobada") {
                            $existePendienteOAprobada = true;
                            break;
                        }
                    }
                ?>
                <tr>
                  <td style="display:none;" name="idM"><?php echo $idMateria; ?></td>
                  <td style="display:none;" name="nombreM"><?php echo $nombreMateriaCompleto; ?></td>
                  <td style="display:none;" name="idDivision"><?php echo $idDivision; ?></td>
                  <td><?php echo $nombreMateriaReducido; ?></td>
                  <td name="nombreC"><?php echo $curso; ?></td>
                  <td><?php echo $estado; ?></td>
                  <td>
                    <button type="submit" name="submitSolicitar" class="btn btn-primary ver-btn">
                      <?php echo $existePendienteOAprobada ? 'Ver Solicitudes' : 'Solicitar'; ?>
                    </button>
                  </td>
                </tr>
              <?php } endforeach; ?>
              <?php if (!$hayRegistros): ?>
                <tr>
                  <td colspan="6">No hay registros</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        <?php } else {
            echo "El período para generar solicitudes de inscripción a examen ha finalizado.";
        } ?>
      </form>
    </div>
  </div>
</div>

<script src="../funciones/sessionControl.js"></script>
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script>
  // Manejo de click en botones de solicitud
  document.addEventListener("DOMContentLoaded", function () {
    const botones = document.querySelectorAll('.ver-btn');
    botones.forEach(function (boton) {
      boton.addEventListener('click', function () {
        const fila = this.closest('tr');
        document.getElementById("idM").value = fila.querySelector("td[name='idM']").innerText;
        document.getElementById("nombreM").value = fila.querySelector("td[name='nombreM']").innerText;
        document.getElementById("nombreC").value = fila.querySelector("td[name='nombreC']").innerText;
        document.getElementById("idDivision").value = fila.querySelector("td[name='idDivision']").innerText;
        document.getElementById("envio").submit();
      });
    });
  });
</script>

<?php include '../funciones/footer.html'; ?>
</body>
</html>
