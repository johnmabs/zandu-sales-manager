<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Organization\Application;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\Organization\Application\CompositeStoreClosureBlockerProvider;
use Zandu\Modules\Organization\Application\Contract\StoreClosureBlockerProvider;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{OrganizationId, StoreId};

final class CompositeStoreClosureBlockerProviderTest extends TestCase
{
    public function testItCombinesProvidersAndRemovesDuplicateBlockersInRegistrationOrder(): void
    {
        $ids = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString('019a0100-0000-7000-8000-000000000001', $ids);
        $storeId = StoreId::fromString('019a0100-0000-7000-8000-000000000002', $ids);
        $first = $this->provider(['OPEN_CASH_SESSION', 'OPEN_STOCK_COUNT']);
        $second = $this->provider(['OPEN_STOCK_COUNT', 'STOCK_REMAINING']);

        self::assertSame(
            ['OPEN_CASH_SESSION', 'OPEN_STOCK_COUNT', 'STOCK_REMAINING'],
            (new CompositeStoreClosureBlockerProvider([$first, $second]))->blockers($organizationId, $storeId),
        );
    }

    /** @param list<string> $blockers */
    private function provider(array $blockers): StoreClosureBlockerProvider
    {
        return new readonly class ($blockers) implements StoreClosureBlockerProvider {
            /** @param list<string> $blockers */
            public function __construct(private array $blockers) {}

            public function blockers(OrganizationId $organizationId, StoreId $storeId): array
            {
                return $this->blockers;
            }
        };
    }
}
