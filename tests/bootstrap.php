<?php
declare(strict_types=1);

/**
 * Bootstrap PHPUnit: modo test y carga de conexión + consultas compartidas.
 */
putenv('SESYSTEM_TEST_MODE=1');
$_ENV['SESYSTEM_TEST_MODE'] = '1';

require dirname(__DIR__) . '/vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require dirname(__DIR__) . '/inicio/conexion.php';

// consultas.php se carga con require_once desde tests que lo necesitan (evita
// redeclaración al incluir reportes PDF que también incluyen consultas.php).
