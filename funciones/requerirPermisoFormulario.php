<?php
/**
 * Tras verificarSesion, requerirSecretaria, conexion y consultas:
 * define('ID_FORMULARIO_SECRETARIA', N); // constante de formulariosSecretaria.php
 * require_once '../funciones/requerirPermisoFormulario.php';
 */
declare(strict_types=1);

if (!defined('ID_FORMULARIO_SECRETARIA')) {
    http_response_code(500);
    die('Configuracion de permiso: falta ID_FORMULARIO_SECRETARIA');
}

$idFormReq = (int) ID_FORMULARIO_SECRETARIA;
if ($idFormReq <= 0) {
    http_response_code(500);
    die('Configuracion de permiso invalida');
}

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    die('Sin conexion a base de datos');
}

if (!function_exists('usuarioTieneAccesoFormularioSecretaria')) {
    require_once __DIR__ . '/consultas.php';
}

if (!usuarioTieneAccesoFormularioSecretaria($conn, $idFormReq)) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'permiso_denegado']);
        exit;
    }
    header('Location: ../secretaria/menusecretaria.php?permiso=denegado');
    exit;
}
