<?php

if (!isset($_SESSION["doc_legajo"]) && !isset($_SESSION["alu_idAlumno"])) {
    header("Location: ../inicio/login.php?sesion=expirada");
    exit();
}
?>
<?php
// Verifica si la solicitud PROVIENE DE AJAX
// (jQuery y la mayoría de las librerías AJAX envían esta cabecera)
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Si NO es una petición AJAX, imprime el JavaScript.
// Esto asegura que el JS se imprima solo cuando la página se carga normalmente
// y no contamine las respuestas JSON de AJAX.
if (!$is_ajax_request) {
?>
<!-- /// INICIO PASO DE VARIABLE SESSION AL CLIENTE PARA JAVASCRIPT DE ABAJO -->
<script>
window.usuarioActual = "<?php echo isset($_SESSION['doc_legajo']) ? $_SESSION['doc_legajo'] : (isset($_SESSION['alumno_id']) ? $_SESSION['alumno_id'] : 'null'); ?>";
console.log("usuarioActual cargado:", window.usuarioActual);
</script>
<?php
}
