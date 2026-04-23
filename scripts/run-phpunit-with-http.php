<?php
/**
 * Levanta el servidor HTTP embebido de PHP, ejecuta PHPUnit (incl. pruebas @group http) y detiene el servidor.
 *
 * Uso: php scripts/run-phpunit-with-http.php [argumentos extra para phpunit...]
 *
 * Variables opcionales: TEST_HTTP_PORT (default 8765), SESYSTEM_* (ver docs/testing.md).
 */
declare(strict_types=1);

$root = realpath(dirname(__DIR__));
if ($root === false) {
    fwrite(STDERR, "No se pudo resolver la raíz del proyecto.\n");
    exit(1);
}

chdir($root);

$port = (int) (getenv('TEST_HTTP_PORT') ?: 8765);
if ($port < 1 || $port > 65535) {
    fwrite(STDERR, "TEST_HTTP_PORT inválido.\n");
    exit(1);
}

$host = '127.0.0.1';
$baseUrl = "http://{$host}:{$port}/";

if (!getenv('SESYSTEM_TEST_MODE')) {
    putenv('SESYSTEM_TEST_MODE=1');
}
putenv('TEST_BASE_URL=' . $baseUrl);
$_ENV['TEST_BASE_URL'] = $baseUrl;

$nullDevice = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'NUL' : '/dev/null';

$phpBin = PHP_BINARY;
$cmd = escapeshellarg($phpBin)
    . ' -S ' . escapeshellarg($host . ':' . (string) $port)
    . ' -t ' . escapeshellarg($root);

$descriptorspec = [
    0 => ['file', $nullDevice, 'r'],
    1 => ['file', $nullDevice, 'w'],
    2 => ['file', $nullDevice, 'w'],
];

$proc = proc_open($cmd, $descriptorspec, $pipes, $root);
if (!is_resource($proc)) {
    fwrite(STDERR, "No se pudo iniciar php -S (servidor embebido).\n");
    exit(1);
}

$exitCode = 1;
try {
    $maxAttempts = 50;
    $ready = false;
    for ($i = 0; $i < $maxAttempts; $i++) {
        $fp = @fsockopen($host, $port, $errno, $errstr, 0.15);
        if (is_resource($fp)) {
            fclose($fp);
            $ready = true;
            break;
        }
        usleep(100000);
    }
    if (!$ready) {
        fwrite(STDERR, "Timeout: el servidor en {$baseUrl} no respondió.\n");
        exit(1);
    }

    $phpunit = $root . '/vendor/phpunit/phpunit/phpunit';
    if (!is_readable($phpunit)) {
        fwrite(STDERR, "No se encontró PHPUnit en vendor/. Ejecute: composer install\n");
        exit(1);
    }

    $extra = array_slice($argv, 1);
    $args = array_merge([$phpBin, $phpunit], $extra);
    $line = '';
    foreach ($args as $a) {
        $line .= ($line === '' ? '' : ' ') . escapeshellarg($a);
    }

    passthru($line, $exitCode);
} finally {
    proc_terminate($proc);
    proc_close($proc);
}

exit($exitCode);
