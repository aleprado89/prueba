<?php

if (!isset($_SESSION["doc_legajo"]) && !isset($_SESSION["alu_idAlumno"])) {
    header("Location: ../inicio/login.php?sesion=expirada");
    exit();
}
?>
<!-- ///  INICIO PASO DE VARIABLE SESSION AL CLIENTE PARA JAVASCRIPT DE ABAJO -->
<?php
$usuarioDocente = isset($_SESSION["doc_legajo"]) ? htmlspecialchars($_SESSION["doc_legajo"], ENT_QUOTES, "UTF-8") : "";
$usuarioAlumno = isset($_SESSION["alu_idAlumno"]) ? htmlspecialchars($_SESSION["alu_idAlumno"], ENT_QUOTES, "UTF-8") : "";
$usuarioActual = $usuarioDocente ?: $usuarioAlumno;
?>

<script>
  window.usuarioActual = "<?php echo $usuarioActual; ?>";
    console.log("usuarioActual cargado:", window.usuarioActual);
</script>