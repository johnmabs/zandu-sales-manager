<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\InventoryCosting\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\InventoryCosting\Application\{InventoryValuationQueryService, StockValuationMovementViewFactory, StockValuationViewFactory};
use Zandu\Modules\InventoryCosting\Domain\Valuation\{StockValuation, StockValuationRepository};
use Zandu\Modules\InventoryCosting\Domain\ValuationMovement\{StockValuationMovement, StockValuationMovementRepository, StockValuationMovementSource, StockValuationMovementType};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockId, StockValuationId, StockValuationMovementId, StoreId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final class InventoryValuationQueryServiceTest extends TestCase
{
    public function testItListsReadsAndExplainsAStoreValuation(): void
    {
        $uuids = new SymfonyUuidFactory();
        $decimals = new BrickDecimalFactory();
        $organizationId = OrganizationId::fromString('0198f401-1111-7111-8111-111111111111', $uuids);
        $storeId = StoreId::fromString('0198f402-1111-7111-8111-111111111111', $uuids);
        $productId = ProductId::fromString('0198f403-1111-7111-8111-111111111111', $uuids);
        $stockId = StockId::fromString('0198f404-1111-7111-8111-111111111111', $uuids);
        $valuationId = StockValuationId::fromString('0198f407-1111-7111-8111-111111111111', $uuids);
        $currency = Currency::fromCode('XAF');
        $valuation = StockValuation::reconstitute(
            $valuationId,
            $organizationId,
            $storeId,
            $productId,
            $stockId,
            Quantity::fromString('2', $decimals),
            Money::fromString('8', $currency, $decimals),
            1,
        );
        $movement = StockValuationMovement::record(
            StockValuationMovementId::fromString('0198f408-1111-7111-8111-111111111111', $uuids),
            $valuationId,
            $organizationId,
            $storeId,
            $productId,
            $stockId,
            null,
            StockValuationMovementType::Opening,
            Quantity::fromString('2', $decimals),
            Money::fromString('4', $currency, $decimals),
            Money::fromString('8', $currency, $decimals),
            Money::fromString('0', $currency, $decimals),
            Money::fromString('8', $currency, $decimals),
            Money::fromString('0', $currency, $decimals),
            Money::fromString('4', $currency, $decimals),
            StockValuationMovementSource::from('BOOTSTRAP', 'Opening stock'),
            new DateTimeImmutable('2026-09-16T12:00:00Z'),
            CorrelationId::fromString('0198f406-1111-7111-8111-111111111111', $uuids),
        );
        $valuations = $this->createStub(StockValuationRepository::class);
        $valuations->method('findByStore')->willReturn([$valuation]);
        $movements = $this->createStub(StockValuationMovementRepository::class);
        $movements->method('findByValuation')->willReturn([$movement]);
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::exactly(3))->method('authorize')->with(
            self::isInstanceOf(ActorContext::class),
            PermissionCode::InventoryRead,
            self::callback(static fn(ResourceScope $scope): bool => $scope->organizationId->equals($organizationId) && $scope->storeId?->equals($storeId)),
        );
        $queries = new InventoryValuationQueryService(
            $valuations,
            $movements,
            $authorization,
            new StockValuationViewFactory(),
            new StockValuationMovementViewFactory(),
        );
        $actor = new ActorContext(
            ActorId::fromString('0198f405-1111-7111-8111-111111111111', $uuids),
            $organizationId,
            ActorType::User,
            CorrelationId::fromString('0198f406-1111-7111-8111-111111111111', $uuids),
            new DateTimeImmutable('2026-09-16T11:00:00Z'),
        );

        self::assertSame('4.000000000000', $queries->list($actor, $storeId)[0]->averageUnitCost);
        self::assertSame($productId->toString(), $queries->get($actor, $storeId, $productId)->productId);
        $movementView = $queries->movements($actor, $storeId, $productId)[0];
        self::assertSame('OPENING', $movementView->type);
        self::assertSame('Opening stock', $movementView->sourceReferenceId);
    }
}
