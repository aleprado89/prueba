<?php
include '../inicio/conexion.php';
include '../funciones/consultas.php';

$mes = 2; // Reemplaza con el mes seleccionado por el usuario
$anio = 2024; // Reemplaza con el año seleccionado por el usuario

$num_dias = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
$columnasAsistencia="";
if ($num_dias == 31) {
    $columnasAsistencia="asis.d1,asis.d2,asis.d3,asis.d4,asis.d5,asis.d6,asis.d7,asis.d8,asis.d9,asis.d10,
    asis.d11,asis.d12,asis.d13,asis.d14,asis.d15,asis.d16,asis.d17,asis.d18,asis.d19,asis.d20,
    asis.d21,asis.d22,asis.d23,asis.d24,asis.d25,asis.d26,asis.d27,asis.d28,asis.d29,asis.d30,asis.d31";
} else if ($num_dias == 30) {
    $columnasAsistencia="asis.d1,asis.d2,asis.d3,asis.d4,asis.d5,asis.d6,asis.d7,asis.d8,asis.d9,asis.d10,
    asis.d11,asis.d12,asis.d13,asis.d14,asis.d15,asis.d16,asis.d17,asis.d18,asis.d19,asis.d20,
    asis.d21,asis.d22,asis.d23,asis.d24,asis.d25,asis.d26,asis.d27,asis.d28,asis.d29,asis.d30";
}
else if ($num_dias == 29) {
    $columnasAsistencia="asis.d1,asis.d2,asis.d3,asis.d4,asis.d5,asis.d6,asis.d7,asis.d8,asis.d9,asis.d10,
    asis.d11,asis.d12,asis.d13,asis.d14,asis.d15,asis.d16,asis.d17,asis.d18,asis.d19,asis.d20,
    asis.d21,asis.d22,asis.d23,asis.d24,asis.d25,asis.d26,asis.d27,asis.d28,asis.d29";
}
else if ($num_dias == 28) {
    $columnasAsistencia="asis.d1,asis.d2,asis.d3,asis.d4,asis.d5,asis.d6,asis.d7,asis.d8,asis.d9,asis.d10,
    asis.d11,asis.d12,asis.d13,asis.d14,asis.d15,asis.d16,asis.d17,asis.d18,asis.d19,asis.d20,
    asis.d21,asis.d22,asis.d23,asis.d24,asis.d25,asis.d26,asis.d27,asis.d28";
}

$idciclo=buscarIdCiclo($conn, $anio);



echo "Ejecutando obtenerAsistenciaMateria...<br>";
try {
    $return = obtenerAsistenciaMateriaPDF($conn, $columnasAsistencia, 5711, $mes, $idciclo);

    echo "obtenerAsistenciaMateria ha terminado. Valor de retorno: $return<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}echo "obtenerAsistenciaMateria ha terminado. Valor de retorno: $return<br>";

var_dump($return);
die();
?>