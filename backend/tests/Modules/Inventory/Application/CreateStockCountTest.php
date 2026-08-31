<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Catalog\Application\Contract\{InventoryProductDescriptor, InventoryProductProvider};
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\CreateStockCount\{CreateStockCount, CreateStockCountHandler};
use Zandu\Modules\Inventory\Domain\StockCount\{StockCount, StockCountMode, StockCountRepository, StockCountScopeType, StockCountStatus};
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockCountId, StoreId, UnitOfMeasureId};
use Zandu\SharedKernel\Messaging\{CorrelationId, OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class CreateStockCountTest extends TestCase
{
    public function testItCreatesAndPublishesAPartialGuidedDraftWithinStoreScope(): void
    {
        $repository = $this->createMock(StockCountRepository::class);
        $repository->expects(self::once())->method('save')->with(self::callback(fn(StockCount $count): bool => StockCountStatus::Draft === $count->status() && StockCountMode::Guided === $count->mode() && 1 === count($count->requestedProductIds())));
        $products = $this->createMock(InventoryProductProvider::class);
        $products->expects(self::once())->method('provide')->with($this->organizationId(), $this->productId())->willReturn(new InventoryProductDescriptor($this->productId(), true, 'PHYSICAL', $this->unitId(), 0));
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())->method('authorize')->with($this->actor(), PermissionCode::StockCountCreate, self::callback(fn(ResourceScope $scope): bool => $this->storeId()->equals($scope->storeId)));
        $guard = $this->createMock(OperationalGuard::class);
        $guard->expects(self::once())->method('assertStore')->with($this->actor(), $this->storeId());
        $outbox = $this->createMock(OutboxRepository::class);
        $outbox->expects(self::once())->method('append')->with(self::callback(static fn(OutboxMessage $message): bool => 'inventory.stock_count_created.v1' === $message->type && 1 === $message->payload['requestedProductCount']));
        $handler = new CreateStockCountHandler($repository, $products, new CreateStockCountTransaction(), $authorization, $guard, $outbox, new SymfonyUuidV7Generator(), new FrozenClock(new DateTimeImmutable('2026-08-31T19:00:00Z')));

        $created = $handler(new CreateStockCount($this->storeId(), StockCountScopeType::Partial, [$this->productId()], $this->actor(), StockCountMode::Guided));

        self::assertSame(StockCountScopeType::Partial, $created->scopeType());
        self::assertSame(StockCountMode::Guided, $created->mode());
    }

    private function ids(): SymfonyUuidFactory
    {
        return new SymfonyUuidFactory();
    }
    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0199f800-0000-7000-8000-000000000001', $this->ids());
    }
    private function storeId(): StoreId
    {
        return StoreId::fromString('0199f800-0000-7000-8000-000000000002', $this->ids());
    }
    private function productId(): ProductId
    {
        return ProductId::fromString('0199f800-0000-7000-8000-000000000003', $this->ids());
    }
    private function unitId(): UnitOfMeasureId
    {
        return UnitOfMeasureId::fromString('0199f800-0000-7000-8000-000000000004', $this->ids());
    }
    private function actorId(): ActorId
    {
        return ActorId::fromString('0199f800-0000-7000-8000-000000000005', $this->ids());
    }
    private function actor(): ActorContext
    {
        return new ActorContext($this->actorId(), $this->organizationId(), ActorType::User, CorrelationId::fromString('0199f800-0000-7000-8000-000000000006', $this->ids()), new DateTimeImmutable('2026-08-31T19:00:00Z'));
    }
}

final class CreateStockCountTransaction implements TenantTransaction
{
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $operation();
    }
}
