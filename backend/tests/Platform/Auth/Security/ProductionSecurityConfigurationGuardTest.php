<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Auth\Security;

use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Zandu\Platform\Auth\Security\ProductionSecurityConfigurationGuard;

final class ProductionSecurityConfigurationGuardTest extends TestCase
{
    /** @return iterable<string,array{string,string}> */
    public static function invalidProductionSecrets(): iterable
    {
        yield 'empty application secret' => ['', str_repeat('j', 32)];
        yield 'short application secret' => ['short', str_repeat('j', 32)];
        yield 'empty JWT passphrase' => [str_repeat('a', 32), ''];
        yield 'short JWT passphrase' => [str_repeat('a', 32), 'short'];
    }

    #[DataProvider('invalidProductionSecrets')]
    public function testProductionRejectsWeakSecrets(string $appSecret, string $jwtPassphrase): void
    {
        $guard = new ProductionSecurityConfigurationGuard('prod', $appSecret, $jwtPassphrase);

        $this->expectException(LogicException::class);
        $guard($this->mainRequestEvent());
    }

    public function testProductionAcceptsNonEmptySecrets(): void
    {
        $guard = new ProductionSecurityConfigurationGuard('prod', str_repeat('a', 32), str_repeat('j', 32));

        $guard($this->mainRequestEvent());

        self::addToAssertionCount(1);
    }

    public function testDevelopmentDoesNotRequireProductionSecrets(): void
    {
        $guard = new ProductionSecurityConfigurationGuard('dev', '', '');

        $guard($this->mainRequestEvent());

        self::addToAssertionCount(1);
    }

    private function mainRequestEvent(): RequestEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);

        return new RequestEvent($kernel, Request::create('/health/live'), HttpKernelInterface::MAIN_REQUEST);
    }
}
