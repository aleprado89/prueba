<?php
declare(strict_types=1);

namespace Sesystem\Tests\Pdf;

use PHPUnit\Framework\TestCase;

/**
 * Incluye el script real del reporte (proceso aislado: evita conflicto con consultas.php
 * cargada en otras pruebas del mismo suite).
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @group pdf
 */
final class AsistenciaAluPdfSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        global $conn;
        if ($conn === null || !($conn instanceof \mysqli) || $conn->query('SELECT 1') === false) {
            self::markTestSkipped('MySQL de prueba no disponible (ver docs/testing.md).');
        }
    }

    public function testStreamContainsPdfMagicBytes(): void
    {
        $_SESSION['sec_nombreUsuario'] = 'test';
        $_SESSION['sec_id'] = 1;
        $_SESSION['membrete'] = '../img/membrete_mercedarias.png';

        $_GET['idAlumno'] = '1';
        $_GET['idCiclo'] = '1';
        $_GET['mes'] = '3';
        $_GET['plan_nombre'] = 'Plan Test';
        $_GET['nombre_alumno'] = 'Apellido Nombre';

        $reportDir = realpath(dirname(__DIR__, 2) . '/reportes');
        self::assertNotFalse($reportDir);
        $prev = getcwd();
        chdir($reportDir);
        ob_start();
        try {
            include $reportDir . DIRECTORY_SEPARATOR . 'asistenciaAluPDF.php';
        } finally {
            chdir($prev);
            $out = ob_get_clean();
        }

        self::assertNotSame('', $out, 'Salida PDF vacía');
        self::assertSame('%PDF', substr($out, 0, 4));
    }
}
