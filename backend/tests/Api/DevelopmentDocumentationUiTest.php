<?php

declare(strict_types=1);

namespace Zandu\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

final class DevelopmentDocumentationUiTest extends KernelTestCase
{
    public function testSwaggerUiIsAvailableInDevelopment(): void
    {
        self::bootKernel(['environment' => 'dev', 'debug' => true]);
        $response = self::$kernel->handle(Request::create('/api/docs', server: ['HTTP_ACCEPT' => 'text/html']));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('id="swagger-ui"', (string) $response->getContent());
        self::assertStringContainsString('init-swagger-ui.js', (string) $response->getContent());
    }

    public function testReDocIsAvailableInDevelopment(): void
    {
        self::bootKernel(['environment' => 'dev', 'debug' => true]);
        $response = self::$kernel->handle(Request::create('/api/docs?ui=re_doc', server: ['HTTP_ACCEPT' => 'text/html']));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('init-redoc-ui.js', (string) $response->getContent());
    }

    public function testDocumentationUiRemainsDisabledOutsideDevelopment(): void
    {
        self::bootKernel(['environment' => 'test', 'debug' => true]);
        $response = self::$kernel->handle(Request::create('/api/docs', server: ['HTTP_ACCEPT' => 'text/html']));

        self::assertSame(404, $response->getStatusCode());
    }
}
