<?php
/**
 * Conexión MySQL para entornos de prueba (PHPUnit, CI).
 * Se carga desde conexion.php cuando SESYSTEM_TEST_MODE=1.
 *
 * Credenciales vía variables de entorno; no commitear secretos reales.
 * Si la conexión falla, $conn queda en null (las pruebas de integración deben omitirse).
 */
$servername = getenv('SESYSTEM_DB_HOST') ?: '127.0.0.1';
$username = getenv('SESYSTEM_DB_USER') ?: 'se_user';
$password = getenv('SESYSTEM_DB_PASSWORD');
if ($password === false) {
    $password = '88668866';
}
$dbname = getenv('SESYSTEM_DB_NAME') ?: 'sesystem_test';

$conn = null;
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        error_log('MySQL connect_error (test): ' . $conn->connect_error);
        $conn = null;
    } else {
        $conn->set_charset('utf8mb4');
    }
} catch (mysqli_sql_exception $e) {
    error_log('MySQL exception (test): ' . $e->getMessage());
    $conn = null;
}
