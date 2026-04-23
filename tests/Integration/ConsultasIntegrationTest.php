<?php
declare(strict_types=1);

namespace Sesystem\Tests\Integration;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/funciones/consultas.php';

/**
 * @group integration
 */
final class ConsultasIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        global $conn;
        if ($conn === null || !($conn instanceof \mysqli) || $conn->query('SELECT 1') === false) {
            self::markTestSkipped(
                'MySQL de prueba no disponible. Importar esquema, aplicar tests/fixtures/seed_minimal.sql y variables SESYSTEM_DB_* (ver docs/testing.md).'
            );
        }
    }

    public function testBuscarIdCicloReturnsIdForSeededYear(): void
    {
        global $conn;
        $id = buscarIdCiclo($conn, '2026');
        self::assertNotNull($id);
        self::assertSame(1, (int) $id);
    }

    public function testBuscarIdCicloReturnsNullForUnknownYear(): void
    {
        global $conn;
        self::assertNull(buscarIdCiclo($conn, '1900'));
    }

    public function testBuscarnombreCicloReturnsYear(): void
    {
        global $conn;
        $anio = buscarnombreCiclo($conn, 1);
        self::assertSame(2026, (int) $anio);
    }

    public function testObtenerParametrosColegioReturnsArrayForCodNivel6(): void
    {
        global $conn;
        $p = obtenerParametrosColegio($conn, 6);
        self::assertIsArray($p);
        self::assertArrayHasKey('nombreColegio', $p);
        self::assertStringContainsString('Prueba', (string) $p['nombreColegio']);
    }
}
