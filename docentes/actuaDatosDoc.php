<?php 
session_start();
include '../inicio/conexion.php';
//include '../funciones/consultas.php';
//include '../funciones/pruebaSession.php';
$doc_legajo = $_SESSION['doc_legajo'];
$nombreDoc = $_SESSION['doc_apellido'] . ", " . $_SESSION['doc_nombre'];

$message="";

$sql = "select p.dni,p.FotoCarnet,p.sexo,p.fechaNac,p.nacionalidad,p.lugarNac,p.provincia,p.ciudad,p.direccion,p.barrio,p.codigoPostal,
p.mail,p.telefono,p.celular,p.telefonoEmergencia,l.estadoCivil,l.titulo,l.mailInst from persona p INNER JOIN personal l ON p.idPersona=l.idPersona
WHERE l.legajo=?"; 
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doc_legajo);
$stmt->execute();
// Obtener el resultado
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $select_dni = $row['dni'];
    $select_FotoCarnet = $row['FotoCarnet'];
    $select_sexo = $row['sexo'];
    $select_fechaNac= $row['fechaNac'];
    $select_nacionalidad = $row['nacionalidad'];
    $select_lugarNac = $row['lugarNac'];
    $select_provincia= $row['provincia'];
    $select_ciudad = $row['ciudad'];
    $select_direccion=$row['direccion'];
    $select_barrio=$row['barrio'];
    $select_codigoPostal=$row['codigoPostal'];
    $select_mail=$row['mail'];
    $select_mailInstitucional=$row['mailInst'];
    $select_telefono=$row['telefono'];
    $select_celular=$row['celular'];
    $select_telefonoEmergencia=$row['telefonoEmergencia'];
    $select_estadoCivil=$row['estadoCivil'];
    $select_titulo=$row['titulo'];
} else {
    $message=$message. " No se encontró el registro del docente. ";
}

// Cerrar la conexión
$stmt->close();
$conn->close();
?>

<!-- Bootstrap JS y jQuery (necesario para el modal) -->
<script src="../js/jquery-3.7.1.slim.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

  <?php
  if ($_SERVER["REQUEST_METHOD"] == "POST") {

// Función para limpiar y validar los datos
function validarDato($dato) {
    // Elimina espacios en blanco al inicio y al final
    $dato = trim($dato);
    // Elimina barras invertidas
    $dato = stripslashes($dato);
    // Convierte caracteres especiales en entidades HTML
    $dato = htmlspecialchars($dato);
    return $dato;
}
    // Obtiene y valida los datos del formulario 
    $fechaNac = validarDato($_POST['fechaNac']);
    $FotoCarnet = validarDato($_POST['fotoCarnet']);
    $nacionalidad = validarDato($_POST['nacionalidad']);
    $sexo = validarDato($_POST['sexo']);
    if($sexo=="Masculino")
    $sexo="M";
  else if($sexo=="Femenino")
  $sexo="F";
else if($sexo=="Otro")
$sexo="O";
    $lugarNacimiento = validarDato($_POST['lugarNacimiento']);
    $provincia = validarDato($_POST['provincia']);
    $ciudad = validarDato($_POST['ciudad']);
    $direccion = validarDato($_POST['direccion']);
    $barrio = validarDato($_POST['barrio']);
    $codigoPostal = validarDato($_POST['codigoPostal']);
    $mail = validarDato($_POST['mail']);
    $mailInstitucional = validarDato($_POST['mailInstitucional']);
    $telefono = validarDato($_POST['telefono']);
    $celular = validarDato($_POST['celular']);
    $telefonoEmergencia = validarDato($_POST['telefonoEmergencia']);
    $estadoCivil = validarDato($_POST['estadoCivil']);
    $titulo = validarDato($_POST['titulo']);
    if (isset($_FILES['fotoCarnet']) && $_FILES['fotoCarnet']['error'] === UPLOAD_ERR_OK) {
       // Obtiene la información del archivo
       $nombreArchivo = $_FILES['fotoCarnet']['name'];
       $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
       $rutaTemporal = $_FILES['fotoCarnet']['tmp_name'];
       $formatosPermitidos = ['jpg', 'jpeg', 'png']; // Formatos permitidos

      $tamañoMaximo = 4 * 1024 * 1024; // 4 MB en bytes

        if ($_FILES['fotoCarnet']['size'] > $tamañoMaximo) {
          $_SESSION['message']=$_SESSION['message']. "El archivo es demasiado grande. El tamaño máximo permitido es de 4 MB. ";
        }else if (!in_array($extension, $formatosPermitidos)) {
          $_SESSION['message']=$_SESSION['message']. "Formato de archivo no permitido. Los formatos permitidos son: " . implode(", ", $formatosPermitidos).". ";
      }    else {
           
      // Define la carpeta de destino (asegúrate de que exista y tenga permisos de escritura)
      $carpetaDestino = '../fotosPersonas/'; // Cambia esto a la ruta de tu carpeta
      $rutaDestino = $carpetaDestino . $select_dni." ".date('Y-m-d H-i-s').'.'.$extension;

      // Mueve el archivo a la carpeta de destino
      if (move_uploaded_file($rutaTemporal, $rutaDestino)) {
          //$_SESSION['message']=$_SESSION['message']. "El archivo se ha guardado correctamente en: " . $rutaDestino." ";
      } else {
        $_SESSION['message']=$_SESSION['message']. "Error al mover el archivo. ";
      }
  }} else {
    $_SESSION['message']=$_SESSION['message']. "No se ha subido ningún archivo. ";
    $rutaDestino=$select_FotoCarnet;
  }
  


    include '../inicio/conexion.php';
// Realizar la consulta para actualizar datos
$sql = "UPDATE persona p INNER JOIN personal l ON p.idPersona=l.idPersona
SET p.fechaNac='".$fechaNac."',p.FotoCarnet='".$rutaDestino."', p.nacionalidad='".$nacionalidad."',p.sexo='".$sexo."',
p.lugarNac='".$lugarNacimiento."',p.provincia='".$provincia."',p.ciudad='".$ciudad."',
p.direccion='".$direccion."',p.barrio='".$barrio."',p.codigoPostal='".$codigoPostal."',
p.mail='".$mail."',l.mailInst='".$mailInstitucional."',p.telefono='".$telefono."',
p.celular='".$celular."',p.telefonoEmergencia='".$telefonoEmergencia."',l.estadoCivil='".$estadoCivil."',l.titulo='".$titulo."'
WHERE l.legajo=".$doc_legajo; 
//var_dump($sql);

if ($conn->query($sql) === TRUE) {
  $_SESSION['message']=$_SESSION['message']. "Datos actualizados correctamente. ";
} else {
  $_SESSION['message']=$_SESSION['message']. "Error al actualizar los datos: " . $conn->error;
}
$conn->close();
// Redirigir a la misma página para refrescar el formulario
header("Location: " . $_SERVER['PHP_SELF']);
exit(); // Asegúrate de llamar a exit() después de header()
}
?>
<!DOCTYPE html>
<html lang="es"></html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Datos Personales</title>
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
   <li class="breadcrumb-item active">Actualizar datos</li>
</ol>

  <div class="card padding col-12">
    <h5><?php echo  "Docente: ".$nombreDoc." <br> DNI: ".$select_dni; ?> </h5>
  </div>
  <br>
  <div class="card padding "> 
  <h3>Datos personales</h3>
  <div class="row">
  <div class="col-md-5">
  <form method="post" enctype="multipart/form-data">
  <label for="fechaNac" >Fecha de Nacimiento:</label>
            <input type="date" class="form-control" id="fechaNac" name="fechaNac">
            <br>
            <label for="nacionalidad">Nacionalidad:</label>
            <input type="text" class="form-control" id="nacionalidad" name="nacionalidad">
            <br>
            <label for="estadoCivil">Estado Civil</label>
            <select class="form-control" id="estadoCivil" name="estadoCivil">
                    <option value="Soltero">Soltero</option>
                    <option value="Casado">Casado</option>
                    <option value="Divorciado">Divorciado</option>
                    <option value="Viudo">Viudo</option>
                </select>
                
  </div>
  <div class="col-md-5 offset-md-1">
            <label for="sexo">Sexo:</label>
            <select class="form-control" id="sexo" name="sexo">
                    <option value="M">Masculino</option>
                    <option value="F">Femenino</option>
                    <option value="O">Otro</option>
                </select> 
                    <br>
                       <label for="fotoCarnet">Foto Carnet:</label>
                <?php if (!empty($select_FotoCarnet)): ?><br>
    <img>Archivo actual: <img src='<?php echo $select_FotoCarnet; ?>' width="50px"></img>  </php>
<?php endif; ?>
            <input type="file" class="form-control" id="fotoCarnet" name="fotoCarnet">
            <br>
            <label for="titulo">Título:</label>
            <input type="text" class="form-control" id="titulo" name="titulo"><br>
            
  </div>
  </div>
                   
  </div><br>
  <div class="card padding  "> 
  <h3>Datos de domicilio</h3>
  <div class="row">
  <div class="col-md-5">
            <label for="lugarNacimiento">Lugar de Nacimiento:</label>
            <input type="text" class="form-control" id="lugarNacimiento" name="lugarNacimiento"><br>
            <label for="provincia">Provincia:</label>
            <input type="text" class="form-control" id="provincia" name="provincia"><br>
            <label for="ciudad">Ciudad:</label>
            <input type="text" class="form-control" id="ciudad" name="ciudad"><br>
            </div> 
            <div class="col-md-5 offset-md-1">
            <label for="direccion">Direccion:</label>
            <input type="text" class="form-control" id="direccion" name="direccion"><br>
            <label for="barrio">Barrio:</label>
            <input type="text" class="form-control" id="barrio" name="barrio"><br>
            <label for="codigoPostal">Código Postal:</label>
            <input type="text" class="form-control" id="codigoPostal" name="codigoPostal"><br>
            </div></div>
  </div><br>
  <div class="card padding  "> 
  <h3>Datos de contacto</h3>
  <div class="row">
  <div class="col-md-5">
            <label for="mail">Mail:</label>
            <input type="email" class="form-control" id="mail" name="mail"><br>
            <label for="mailInstitucional">Mail Institucional:</label>
            <input type="email" class="form-control" id="mailInstitucional" name="mailInstitucional"><br>
            </div> 
            <div class="col-md-5 offset-md-1">
            <label for="telefono">Teléfono:</label>
            <input type="text" class="form-control" id="telefono" name="telefono"><br>
            <label for="celular">Celular:</label>
            <input type="text" class="form-control" id="celular" name="celular"><br>
            <label for="telefonoEmergencia">Teléfono de Emergencia:</label>
            <input type="text" class="form-control" id="telefonoEmergencia" name="telefonoEmergencia">          
  </div></div></div><br>
  <div class="text-center">
  <button type="submit" class="btn btn-primary" name="submit">Guardar</button>
  </form>
  <!-- <a href="#" onclick="guardarDatos(); return false;">
    <button class="btn btn-primary">Guardar</button>
</a> -->
</div><br>
 </div>
</div>
<?php include '../funciones/footer.html'; ?>



<!-- Modal -->
<div class="modal" id="messageModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Atención</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true"></span>
        </button>
      </div>
      <div class="modal-body">
      <p id="message"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>



<!-- JavaScript para mostrar el modal -->
<script>
$(document).ready(function() {
    <?php
    if (isset($_SESSION['message'])) {
        echo '$("#message").text("' . addslashes($_SESSION['message']) . '");';
        echo '$("#messageModal").modal("show");';
        unset($_SESSION['message']); // Limpiar el mensaje después de mostrarlo
    }
    ?>
});
</script>
 
<script>
    // Cargar  valores en los input
    document.getElementById('sexo').value = '<?php echo addslashes($select_sexo); ?>';
    document.getElementById('fechaNac').value = '<?php echo addslashes($select_fechaNac); ?>';
    document.getElementById('nacionalidad').value = '<?php echo addslashes($select_nacionalidad); ?>';
    document.getElementById('lugarNacimiento').value = '<?php echo addslashes($select_lugarNac); ?>';
    document.getElementById('provincia').value = '<?php echo addslashes($select_provincia); ?>';
    document.getElementById('ciudad').value = '<?php echo addslashes($select_ciudad); ?>';
    document.getElementById('direccion').value = '<?php echo addslashes($select_direccion); ?>';
    document.getElementById('barrio').value = '<?php echo addslashes($select_barrio); ?>';
    document.getElementById('codigoPostal').value = '<?php echo addslashes($select_codigoPostal); ?>';
    document.getElementById('mail').value = '<?php echo addslashes($select_mail); ?>';
    document.getElementById('mailInstitucional').value = '<?php echo addslashes($select_mailInstitucional); ?>';
    document.getElementById('telefono').value = '<?php echo addslashes($select_telefono); ?>';
    document.getElementById('celular').value = '<?php echo addslashes($select_celular); ?>';
    document.getElementById('telefonoEmergencia').value = '<?php echo addslashes($select_telefonoEmergencia); ?>';
    document.getElementById('estadoCivil').value = '<?php echo addslashes($select_estadoCivil); ?>';
    document.getElementById('titulo').value = '<?php echo addslashes($select_titulo); ?>';

</script>
</body>

</html>