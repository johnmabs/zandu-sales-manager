<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\StartStockCount\{StartStockCount, StartStockCountHandler};
use Zandu\Modules\Inventory\Domain\Stock\StockRepository;
use Zandu\Modules\Inventory\Domain\StockCount\{OpenStockCountScopeRepository, StockCount, StockCountLine, StockCountLineRepository, StockCountRepository, StockCountScopeType, StockCountStatus};
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockCountId, StoreId};
use Zandu\SharedKernel\Messaging\{CorrelationId, OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class StartStockCountTest extends TestCase
{
    public function testPartialCountSnapshotsZeroWithoutCreatingAStockPositionAndOpensAtomically(): void
    {
        $count = $this->stockCount();
        $counts = $this->createMock(StockCountRepository::class);
        $counts->expects(self::once())->method('getForUpdate')->willReturn($count);
        $counts->expects(self::once())->method('save')->with($count);
        $lines = $this->createMock(StockCountLineRepository::class);
        $lines->expects(self::once())->method('save')->with(self::callback(fn(StockCountLine $line): bool => '0' === $line->expectedQuantity()->toString() && $this->productId()->equals($line->productId())));
        $scopes = $this->createMock(OpenStockCountScopeRepository::class);
        $scopes->expects(self::once())->method('acquire')->with($this->organizationId(), $this->storeId(), $count->id(), [$this->productId()]);
        $stocks = $this->createMock(StockRepository::class);
        $stocks->expects(self::once())->method('find')->willReturn(null);
        $stocks->expects(self::never())->method('save');
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())->method('authorize')->with($this->actor(), PermissionCode::StockCountStart, self::callback(fn(ResourceScope $scope): bool => $this->storeId()->equals($scope->storeId)));
        $guard = $this->createMock(OperationalGuard::class);
        $guard->expects(self::once())->method('assertStore')->with($this->actor(), $this->storeId());
        $outbox = $this->createMock(OutboxRepository::class);
        $outbox->expects(self::once())->method('append')->with(self::callback(static fn(OutboxMessage $message): bool => 'inventory.stock_count_started.v1' === $message->type && 1 === $message->payload['lineCount']));
        $handler = new StartStockCountHandler($counts, $lines, $scopes, $stocks, new StartStockCountTransaction(), $authorization, $guard, $outbox, new SymfonyUuidV7Generator(), new BrickDecimalFactory(), new FrozenClock(new DateTimeImmutable('2026-08-31T20:00:00Z')));

        $started = $handler(new StartStockCount($count->id(), $this->actor()));

        self::assertSame(StockCountStatus::Open, $started->status());
        self::assertSame(1, $started->totalLineCount());
        self::assertSame('2026-08-31T20:00:00+00:00', $started->startedAt()?->format(DATE_ATOM));
    }

    private function stockCount(): StockCount
    {
        return StockCount::create($this->countId(), $this->organizationId(), $this->storeId(), StockCountScopeType::Partial, $this->actorId(), new DateTimeImmutable('2026-08-31T19:00:00Z'), requestedProductIds: [$this->productId()]);
    }
    private function ids(): SymfonyUuidFactory
    {
        return new SymfonyUuidFactory();
    }
    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0199fa00-0000-7000-8000-000000000001', $this->ids());
    }
    private function storeId(): StoreId
    {
        return StoreId::fromString('0199fa00-0000-7000-8000-000000000002', $this->ids());
    }
    private function productId(): ProductId
    {
        return ProductId::fromString('0199fa00-0000-7000-8000-000000000003', $this->ids());
    }
    private function countId(): StockCountId
    {
        return StockCountId::fromString('0199fa00-0000-7000-8000-000000000004', $this->ids());
    }
    private function actorId(): ActorId
    {
        return ActorId::fromString('0199fa00-0000-7000-8000-000000000005', $this->ids());
    }
    private function actor(): ActorContext
    {
        return new ActorContext($this->actorId(), $this->organizationId(), ActorType::User, CorrelationId::fromString('0199fa00-0000-7000-8000-000000000006', $this->ids()), new DateTimeImmutable('2026-08-31T20:00:00Z'));
    }
}

final class StartStockCountTransaction implements TenantTransaction
{
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $operation();
    }
}
