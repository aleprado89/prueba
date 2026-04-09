<?php
declare(strict_types=1);

namespace Sesystem\Tests\Http;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

/**
 * @group http
 */
final class LoginPageHttpTest extends TestCase
{
    public function testLoginPageReturns200AndHtml(): void
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

        $res = $client->get('inicio/login.php');
        self::assertSame(200, $res->getStatusCode());
        $body = (string) $res->getBody();
        self::assertStringContainsStringIgnoringCase('html', $body);
    }
}
