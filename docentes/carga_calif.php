<?php
session_start();
include '../inicio/conexion.php';
include '../funciones/consultas.php';

$doc_legajo = $_SESSION['doc_legajo'];
$nombreDoc = $_SESSION['doc_apellido'].", ".$_SESSION['doc_nombre'];
$idMateria=$_SESSION['idMateria'];
$ciclolectivo=$_SESSION['ciclolectivo'];
$plan=$_SESSION['plan'];  
$materia=$_SESSION['materia'];
echo $idMateria;
echo $ciclolectivo;
echo $plan;
echo $materia;


?>
<!DOCTYPE html>
<html lang="es"></html>
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
    <h5><?php echo  "Docente: ".$nombreDoc; ?> </h5>
    <select name="ciclolectivo" class="form-select" id="ciclolectivo" onchange="cargarValor(this.value)">
    <?php
        $ciclolectivos = levantarCiclosLectivos(conexion: $conn); // Llamar a la función levantarCiclosLectivos
        foreach ($ciclolectivos as $ciclolectivo) {
          echo '<option value="' . $ciclolectivo['idCicloLectivo'] . '">' . $ciclolectivo['anio'] . '</option>';
        }
        ?>
      </select>
  <br>
  <select name="plan" class="form-select" id="plan" onchange="cargarValor(this.value)">
    <?php
        $planes = buscarPlanesProfesorMateria($conn,$doc_legajo); // Llamar a la función buscarPlanesProfesorMateria
        foreach ($planes as $plan) {
          echo '<option value="' . $plan['idPlan'] . '">' . $plan['nombrePlan'] . '</option>';
        }
        ?>
      </select>
  </div>
  
  
    <br>
    <div>
      <table id="tablaMaterias" class="table table-hover col-12">
        <thead>
          <tr class="table-primary">
            <th scope="col">Materias asignadas al docente</th>
          </tr>
        </thead>
  
        <tbody>     
      
        <?php if (isset($materiasAsignadas)) { ?>
          <?php foreach ($materiasAsignadas as $materia) { ?>
            <tr>
              <td>
                <a href="carga_calif.php" onclick="setMateria(<?php echo $materia['idMateria']; ?>)">
                  <?php echo $materia['Materia']; ?>
                </a>
              </td>
            </tr>
          <?php } ?>
        <?php } ?>
        </tbody>
      </table>
    </div>
  
        </div>
  </div>
  
  
    <!-- Bootstrap JS y jQuery (necesario para el modal) -->
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
      
    <?php include '../funciones/footer.html'; ?>
    </body>
    </html>