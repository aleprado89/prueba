<?php
declare(strict_types=1);

namespace Sesystem\Tests\Http;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

/**
 * Requiere servidor web local. Ej.: php -S 127.0.0.1:8080 -t .
 * Variable TEST_BASE_URL con barra final, ej. http://127.0.0.1:8080/
 *
 * @group http
 */
final class SecretariaAjaxUnauthorizedTest extends TestCase
{
    public function testAjaxPostToSecretariaWithoutSessionReturns401Json(): void
    {
        $raw = getenv('TEST_BASE_URL');
        if ($raw === false || $raw === '') {
            self::markTestSkipped('Defina TEST_BASE_URL para pruebas HTTP (ver docs/testing.md).');
        }

        $base = rtrim($raw, '/') . '/';
        $client = new Client([
            'base_uri' => $base,
            'http_errors' => false,
            'timeout' => 10,
        ]);

        $res = $client->post('secretaria/menusecretaria.php', [
            'headers' => [
                'X-Requested-With' => 'XMLHttpRequest',
            ],
        ]);

        self::assertSame(401, $res->getStatusCode());
        $json = json_decode((string) $res->getBody(), true);
        self::assertIsArray($json);
        self::assertArrayHasKey('session_expired', $json);
        self::assertTrue($json['session_expired']);
    }
}
