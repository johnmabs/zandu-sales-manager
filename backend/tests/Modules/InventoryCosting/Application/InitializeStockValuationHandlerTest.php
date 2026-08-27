<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\InventoryCosting\Application;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\Contract\{CostingStockPosition, CostingStockPositionProvider};
use Zandu\Modules\InventoryCosting\Application\InitializeStockValuation\{InitializeStockValuation, InitializeStockValuationHandler};
use Zandu\Modules\InventoryCosting\Domain\InventoryCostingRuleViolation;
use Zandu\Modules\InventoryCosting\Domain\Valuation\{MovingWeightedAverageCalculator, StockValuation, StockValuationRepository};
use Zandu\Modules\InventoryCosting\Domain\ValuationMovement\{StockValuationMovement, StockValuationMovementRepository, StockValuationMovementType};
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard, StoreBusinessContext, StoreBusinessContextProvider};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockId, StockValuationId, StoreId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\SecurityAudit\{ResourceReference, SafeAuditMetadata, SecurityAction, SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final class InitializeStockValuationHandlerTest extends TestCase
{
    private const ORGANIZATION = '0198f401-1111-7111-8111-111111111111';
    private const STORE = '0198f402-1111-7111-8111-111111111111';
    private const PRODUCT = '0198f403-1111-7111-8111-111111111111';
    private const STOCK = '0198f404-1111-7111-8111-111111111111';
    private const ACTOR = '0198f405-1111-7111-8111-111111111111';
    private const CORRELATION = '0198f406-1111-7111-8111-111111111111';

    private SymfonyUuidFactory $uuids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->uuids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
    }

    public function testItInitializesTheExactLockedPositionAndOpeningLedger(): void
    {
        $position = $this->position('10.5');
        $stockPositions = $this->createMock(CostingStockPositionProvider::class);
        $stockPositions->expects(self::once())->method('getForUpdate')->willReturn($position);

        $saved = null;
        $valuations = $this->createMock(StockValuationRepository::class);
        $valuations->expects(self::once())->method('findByStock')->willReturn(null);
        $valuations->expects(self::once())->method('save')->willReturnCallback(static function (StockValuation $valuation) use (&$saved): void {
            $saved = $valuation;
        });

        $opening = null;
        $movements = $this->createMock(StockValuationMovementRepository::class);
        $movements->expects(self::once())->method('append')->willReturnCallback(static function (StockValuationMovement $movement) use (&$opening): void {
            $opening = $movement;
        });

        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())->method('authorize')->with(
            self::isInstanceOf(ActorContext::class),
            PermissionCode::InventoryCostingInitialize,
            self::callback(fn(ResourceScope $scope): bool => $scope->organizationId->toString() === self::ORGANIZATION && $scope->storeId?->toString() === self::STORE),
        );

        $audit = $this->createMock(SecurityAuditTrail::class);
        $audit->expects(self::once())->method('recordSuccess')->with(
            self::isInstanceOf(ActorContext::class),
            SecurityAction::StockValuationInitialized,
            self::callback(static fn(ResourceReference $target): bool => 'STOCK_VALUATION' === $target->type),
            self::callback(static fn(SafeAuditMetadata $metadata): bool => 'Controlled opening count' === $metadata->toArray()['reason']),
            self::equalTo(new DateTimeImmutable('2026-08-27T18:00:00Z')),
        );

        $valuation = ($this->handler($stockPositions, $valuations, $movements, $authorization, $audit))(
            new InitializeStockValuation(
                $this->store(),
                $this->product(),
                $this->decimals->fromString('4000.1234567890123'),
                '  Controlled opening count  ',
                $this->actor(),
            ),
        );

        self::assertSame($valuation, $saved);
        self::assertSame('10.5', $valuation->quantityOnHand()->toString());
        self::assertSame('42001.296296', $valuation->totalValue()->amount()->toString());
        self::assertSame('XAF', $valuation->currency()->code());
        self::assertInstanceOf(StockValuationMovement::class, $opening);
        self::assertSame(StockValuationMovementType::Opening, $opening->type());
        self::assertNull($opening->stockMovementId());
        self::assertSame('4000.123456789012', $opening->unitCost()->amount()->toString());
        self::assertSame('Controlled opening count', $opening->source()->referenceId());
        self::assertSame(self::CORRELATION, $opening->correlationId()->toString());
    }

    public function testItRejectsASecondInitializationBeforeWriting(): void
    {
        $stockPositions = $this->createStub(CostingStockPositionProvider::class);
        $stockPositions->method('getForUpdate')->willReturn($this->position('2'));
        $valuations = $this->createMock(StockValuationRepository::class);
        $valuations->method('findByStock')->willReturn($this->existingValuation());
        $valuations->expects(self::never())->method('save');
        $movements = $this->createMock(StockValuationMovementRepository::class);
        $movements->expects(self::never())->method('append');

        $this->expectException(InventoryCostingRuleViolation::class);
        $this->expectExceptionMessage('Stock valuation is already initialized.');
        ($this->handler($stockPositions, $valuations, $movements))($this->command('4000', 'Opening inventory'));
    }

    public function testZeroPositionRequiresZeroOpeningCost(): void
    {
        $stockPositions = $this->createStub(CostingStockPositionProvider::class);
        $stockPositions->method('getForUpdate')->willReturn($this->position('0'));
        $valuations = $this->createStub(StockValuationRepository::class);
        $valuations->method('findByStock')->willReturn(null);

        $this->expectException(InventoryCostingRuleViolation::class);
        $this->expectExceptionMessage('A zero stock position must be initialized with a zero unit cost.');
        ($this->handler($stockPositions, $valuations))($this->command('1', 'Zero opening'));
    }

    public function testReasonIsMandatoryAndBounded(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->command('0', '   ');
    }

    private function handler(
        CostingStockPositionProvider $stockPositions,
        StockValuationRepository $valuations,
        ?StockValuationMovementRepository $movements = null,
        ?AuthorizationService $authorization = null,
        ?SecurityAuditTrail $audit = null,
    ): InitializeStockValuationHandler {
        $stores = $this->createStub(StoreBusinessContextProvider::class);
        $stores->method('provide')->willReturn(new StoreBusinessContext('Africa/Lagos', 'XAF'));
        $transaction = $this->createStub(TenantTransaction::class);
        $transaction->method('transactional')->willReturnCallback(static fn(OrganizationId $organizationId, callable $operation): mixed => $operation());

        return new InitializeStockValuationHandler(
            $stockPositions,
            $valuations,
            $movements ?? $this->createStub(StockValuationMovementRepository::class),
            $stores,
            new MovingWeightedAverageCalculator(),
            new SymfonyUuidV7Generator(),
            new class implements Clock {
                public function now(): DateTimeImmutable
                {
                    return new DateTimeImmutable('2026-08-27T18:00:00Z');
                }
            },
            $transaction,
            $this->createStub(OperationalGuard::class),
            $authorization ?? $this->createStub(AuthorizationService::class),
            $audit ?? $this->createStub(SecurityAuditTrail::class),
        );
    }

    private function command(string $cost, string $reason): InitializeStockValuation
    {
        return new InitializeStockValuation($this->store(), $this->product(), $this->decimals->fromString($cost), $reason, $this->actor());
    }

    private function position(string $quantity): CostingStockPosition
    {
        return new CostingStockPosition(
            StockId::fromString(self::STOCK, $this->uuids),
            $this->store(),
            $this->product(),
            \Zandu\SharedKernel\Quantity\Quantity::fromString($quantity, $this->decimals),
            1,
        );
    }

    private function existingValuation(): StockValuation
    {
        return StockValuation::initialize(
            StockValuationId::generate(new SymfonyUuidV7Generator()),
            $this->organization(),
            $this->store(),
            $this->product(),
            StockId::fromString(self::STOCK, $this->uuids),
            \Zandu\SharedKernel\Quantity\Quantity::fromString('2', $this->decimals),
            new \Zandu\SharedKernel\Money\Money($this->decimals->fromString('4000'), \Zandu\SharedKernel\Money\Currency::fromCode('XAF')),
            new MovingWeightedAverageCalculator(),
        );
    }

    private function actor(): ActorContext
    {
        return new ActorContext(
            ActorId::fromString(self::ACTOR, $this->uuids),
            $this->organization(),
            ActorType::User,
            CorrelationId::fromString(self::CORRELATION, $this->uuids),
            new DateTimeImmutable('2026-08-27T17:00:00Z'),
        );
    }

    private function organization(): OrganizationId
    {
        return OrganizationId::fromString(self::ORGANIZATION, $this->uuids);
    }

    private function store(): StoreId
    {
        return StoreId::fromString(self::STORE, $this->uuids);
    }

    private function product(): ProductId
    {
        return ProductId::fromString(self::PRODUCT, $this->uuids);
    }
}
