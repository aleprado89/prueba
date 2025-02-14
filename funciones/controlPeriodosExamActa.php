<?php
// Incluye el archivo de conexión a la base de datos
include '../inicio/conexion.php';

// Función para verificar si un período de actas está abierto
function verificarPeriodoActas($conn, $fechaActual) {
  // Consulta a la base de datos para obtener los períodos de actas
  $query = "SELECT * FROM periodos_actas WHERE fecha_inicio <= '$fechaActual' AND fecha_fin >= '$fechaActual'";
  $result = mysqli_query($conn, $query);
  $periodos = mysqli_fetch_all($result, MYSQLI_ASSOC);

  // Verifica si hay algún período de actas abierto
  if (count($periodos) > 0) {
    return true;
  } else {
    return false;
  }
}

// Verifica si el período de actas está abierto
$fechaActual = date('Y-m-d');
$periodoActasAbierto = verificarPeriodoActas($conn, $fechaActual);

// Devuelve un valor que indica si el período de actas está abierto o cerrado
if ($periodoActasAbierto) {
  echo "abierto";
} else {
  echo "cerrado";
}

// Cierra la conexión a la base de datos
mysqli_close($conn);
ob_clean(); // Limpia el búfer de salida
?>