<?php

if (!isset($_SESSION["doc_legajo"]) && !isset($_SESSION["alu_idAlumno"])) {
    header("Location: ../inicio/login.php?sesion=expirada");
    exit();
}
?>
