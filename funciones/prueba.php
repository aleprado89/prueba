<?php
include '../inicio/conexion.php';
include 'analisisestado.php';
echo 'test1';

echo "Ejecutando iniciarAnalisis...<br>";
try {
    $return = iniciarAnalisis($conn, 5711, 2429, 110422);
    echo "iniciarAnalisis ha terminado. Valor de retorno: $return<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}echo "iniciarAnalisis ha terminado. Valor de retorno: $return<br>";

echo $return;
echo 'test2';
die();
?>