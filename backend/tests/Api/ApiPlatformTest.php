<?php

declare(strict_types=1);

namespace Zandu\Tests\Api;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ApiPlatformTest extends KernelTestCase
{
    public function testOpenApiDocumentationCanBeGenerated(): void
    {
        self::bootKernel();

        $factory = self::getContainer()->get(OpenApiFactoryInterface::class);
        self::assertInstanceOf(OpenApiFactoryInterface::class, $factory);

        $openApi = $factory([]);

        self::assertSame('Zandu Sales Manager API', $openApi->getInfo()->getTitle());
        self::assertSame('0.1.0', $openApi->getInfo()->getVersion());
    }
}
