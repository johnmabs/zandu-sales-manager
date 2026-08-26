<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Tenancy;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zandu\Modules\CashManagement\Domain\CashMovement\CashMovementRepository;
use Zandu\Modules\CashManagement\Domain\CashRegister\CashRegisterRepository;
use Zandu\Modules\CashManagement\Domain\CashSession\CashSessionRepository;
use Zandu\Modules\Inventory\Domain\Stock\StockRepository;
use Zandu\Modules\Inventory\Domain\StockMovement\StockMovementRepository;
use Zandu\SharedKernel\Identity\OrganizationId;

final class TenantAwareRepositoryContractTest extends TestCase
{
    #[DataProvider('repositoryProvider')]
    public function testReadAndWriteMethodsRequireOrganizationId(string $repository): void
    {
        foreach ((new ReflectionClass($repository))->getMethods() as $method) {
            foreach ($method->getParameters() as $parameter) {
                if ('organizationId' === $parameter->getName()) {
                    self::assertSame(OrganizationId::class, (string) $parameter->getType());
                }
            }
        }
    }
    public static function repositoryProvider(): array
    {
        return [[StockRepository::class],[StockMovementRepository::class],[CashRegisterRepository::class],[CashSessionRepository::class],[CashMovementRepository::class]];
    }
}
