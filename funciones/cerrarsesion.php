<?php
session_start();
session_unset(); // Eliminar todas las variables de sesión
session_destroy(); // Destruir la sesión
header("Location:../inicio/login.php");
?>
<script>
  localStorage.removeItem('usuario_sesion_activa');
  window.location.href = "login.php";
</script>