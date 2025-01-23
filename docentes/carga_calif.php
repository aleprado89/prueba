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

$alumnosCalif=obtenerCalificacionesMateria($conn,$idMateria);
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
    <h5><?php echo  "Ciclo lectivo: ".$ciclolectivo; ?> </h5>
    <h5><?php echo  "Carrera: ".$plan; ?> </h5>
    <h5><?php echo  "Materia: ".$materia; ?> </h5>
    
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
              <td>
                  <?php echo $listado['apellido']."".$listado['nombre']; ?>
              </td>
              <td>
                  <?php echo $listado['n1']; ?>
              </td>
              <td>
                  <?php echo $listado['n2']; ?>
              </td>
              <td>
                  <?php echo $listado['n3']; ?>
              </td>
              <td>
                  <?php echo $listado['n4']; ?>
              </td>
              <td>
                  <?php echo $listado['n5']; ?>
              </td>
              <td>
                  <?php echo $listado['n6']; ?>
              </td>
              <td>
                  <?php echo $listado['n7']; ?>
              </td>
              <td>
                  <?php echo $listado['n8']; ?>
              </td>
              <td>
                  <?php echo $listado['r1']; ?>
              </td>
              <td>
                  <?php echo $listado['r2']; ?>
              </td>
              <td>
                  <?php echo $listado['r3']; ?>
              </td>
              <td>
                  <?php echo $listado['r4']; ?>
              </td>
              <td>
                  <?php echo $listado['r5']; ?>
              </td>
              <td>
                  <?php echo $listado['r6']; ?>
              </td>
              <td>
                  <?php echo $listado['r7']; ?>
              </td>
              <td>
                  <?php echo $listado['r8']; ?>
              </td>
              <td>
                  <?php echo $listado['estadoCursado']; ?>
              </td>
              <td>
                  <?php echo $listado['asistencia']; ?>
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