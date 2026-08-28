<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\InventoryCosting\Application;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\InventoryCosting\Application\Contract\{InventoryCostingMovementType, ValueInventoryMovement};
use Zandu\Modules\InventoryCosting\Application\RepositoryInventoryMovementValuer;
use Zandu\Modules\InventoryCosting\Domain\InventoryCostingRuleViolation;
use Zandu\Modules\InventoryCosting\Domain\Valuation\{MovingWeightedAverageCalculator, StockValuation, StockValuationRepository};
use Zandu\Modules\InventoryCosting\Domain\ValuationMovement\{StockValuationMovement, StockValuationMovementRepository, StockValuationMovementType};
use Zandu\Modules\Organization\Application\Contract\{StoreBusinessContext, StoreBusinessContextProvider};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockId, StockMovementId, StockValuationId, StoreId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final class RepositoryInventoryMovementValuerTest extends TestCase
{
    private const ORGANIZATION = '0198f701-1111-7111-8111-111111111111';
    private const STORE = '0198f702-1111-7111-8111-111111111111';
    private const PRODUCT = '0198f703-1111-7111-8111-111111111111';
    private const STOCK = '0198f704-1111-7111-8111-111111111111';
    private const STOCK_MOVEMENT = '0198f705-1111-7111-8111-111111111111';
    private const VALUATION = '0198f706-1111-7111-8111-111111111111';
    private const ACTOR = '0198f707-1111-7111-8111-111111111111';
    private const CORRELATION = '0198f708-1111-7111-8111-111111111111';

    private SymfonyUuidFactory $uuids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->uuids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
    }

    public function testItValuesInitialStockAndLinksItsPhysicalMovement(): void
    {
        $saved = null;
        $valuations = $this->createMock(StockValuationRepository::class);
        $valuations->expects(self::once())->method('findByStock')->willReturn(null);
        $valuations->expects(self::once())->method('save')->willReturnCallback(static function (StockValuation $valuation) use (&$saved): void {
            $saved = $valuation;
        });
        $ledger = null;
        $movements = $this->createMock(StockValuationMovementRepository::class);
        $movements->expects(self::once())->method('append')->willReturnCallback(static function (StockValuationMovement $movement) use (&$ledger): void {
            $ledger = $movement;
        });

        $this->valuer($valuations, $movements)->value($this->movement(
            InventoryCostingMovementType::InitialStock,
            '10',
            '0',
            '10',
            '4000',
            'Initial stock',
        ));

        self::assertInstanceOf(StockValuation::class, $saved);
        self::assertSame('10', $saved->quantityOnHand()->toString());
        self::assertSame('40000.000000', $saved->totalValue()->amount()->toString());
        self::assertInstanceOf(StockValuationMovement::class, $ledger);
        self::assertSame(StockValuationMovementType::InitialStock, $ledger->type());
        self::assertSame(self::STOCK_MOVEMENT, $ledger->stockMovementId()?->toString());
        self::assertSame('4000.000000000000', $ledger->unitCost()->amount()->toString());
        self::assertSame('40000.000000', $ledger->value()->amount()->toString());
    }

    public function testItValuesIncomingThenOutgoingAdjustmentAtMovingAverage(): void
    {
        $valuation = $this->valuation('10', '4000');
        $valuations = $this->createStub(StockValuationRepository::class);
        $valuations->method('getByStockForUpdate')->willReturn($valuation);
        $ledgers = [];
        $movements = $this->createMock(StockValuationMovementRepository::class);
        $movements->expects(self::exactly(2))->method('append')->willReturnCallback(static function (StockValuationMovement $movement) use (&$ledgers): void {
            $ledgers[] = $movement;
        });
        $valuer = $this->valuer($valuations, $movements);

        $valuer->value($this->movement(InventoryCostingMovementType::AdjustmentIn, '10', '10', '20', '6000', 'Restock'));
        $valuer->value($this->movement(InventoryCostingMovementType::AdjustmentOut, '5', '20', '15', null, 'Shrinkage'));

        self::assertSame('15', $valuation->quantityOnHand()->toString());
        self::assertSame('75000.000000', $valuation->totalValue()->amount()->toString());
        self::assertSame('5000.000000000000', $valuation->averageUnitCost()->amount()->toString());
        self::assertSame(StockValuationMovementType::AdjustmentIn, $ledgers[0]->type());
        self::assertSame('60000.000000', $ledgers[0]->value()->amount()->toString());
        self::assertSame(StockValuationMovementType::AdjustmentOut, $ledgers[1]->type());
        self::assertSame('5000.000000000000', $ledgers[1]->unitCost()->amount()->toString());
        self::assertSame('25000.000000', $ledgers[1]->value()->amount()->toString());
    }

    public function testItValuesASaleAtCurrentAverageCost(): void
    {
        $valuation = $this->valuation('10', '4000');
        $valuations = $this->createStub(StockValuationRepository::class);
        $valuations->method('getByStockForUpdate')->willReturn($valuation);
        $ledger = null;
        $movements = $this->createMock(StockValuationMovementRepository::class);
        $movements->expects(self::once())->method('append')->willReturnCallback(static function (StockValuationMovement $movement) use (&$ledger): void {
            $ledger = $movement;
        });

        $this->valuer($valuations, $movements)->value($this->movement(
            InventoryCostingMovementType::Sale,
            '2',
            '10',
            '8',
            null,
            '0198f709-1111-7111-8111-111111111111',
        ));

        self::assertSame('8', $valuation->quantityOnHand()->toString());
        self::assertSame('32000.000000', $valuation->totalValue()->amount()->toString());
        self::assertInstanceOf(StockValuationMovement::class, $ledger);
        self::assertSame(StockValuationMovementType::Sale, $ledger->type());
        self::assertSame('4000.000000000000', $ledger->unitCost()->amount()->toString());
        self::assertSame('8000.000000', $ledger->value()->amount()->toString());
        self::assertSame('0198f709-1111-7111-8111-111111111111', $ledger->source()->referenceId());
    }

    public function testItRestoresASaleReturnAtItsOriginalCost(): void
    {
        $valuation = $this->valuation('8', '4700');
        $valuations = $this->createStub(StockValuationRepository::class);
        $valuations->method('getByStockForUpdate')->willReturn($valuation);
        $ledger = null;
        $movements = $this->createMock(StockValuationMovementRepository::class);
        $movements->expects(self::once())->method('append')->willReturnCallback(static function (StockValuationMovement $movement) use (&$ledger): void {
            $ledger = $movement;
        });

        $this->valuer($valuations, $movements)->value($this->movement(
            InventoryCostingMovementType::SaleReturn,
            '2',
            '8',
            '10',
            '4000',
            '0198f70a-1111-7111-8111-111111111111',
        ));

        self::assertSame('10', $valuation->quantityOnHand()->toString());
        self::assertSame('45600.000000', $valuation->totalValue()->amount()->toString());
        self::assertSame('4560.000000000000', $valuation->averageUnitCost()->amount()->toString());
        self::assertInstanceOf(StockValuationMovement::class, $ledger);
        self::assertSame(StockValuationMovementType::SaleReturn, $ledger->type());
        self::assertSame('4000.000000000000', $ledger->unitCost()->amount()->toString());
        self::assertSame('8000.000000', $ledger->value()->amount()->toString());
        self::assertSame('0198f70a-1111-7111-8111-111111111111', $ledger->source()->referenceId());
    }

    /** @return iterable<string, array{InventoryCostingMovementType, ?string, string}> */
    public static function invalidCostPolicy(): iterable
    {
        yield 'incoming without cost' => [InventoryCostingMovementType::AdjustmentIn, null, 'VALUATION_UNIT_COST_REQUIRED'];
        yield 'sale return without original cost' => [InventoryCostingMovementType::SaleReturn, null, 'VALUATION_UNIT_COST_REQUIRED'];
        yield 'outgoing with cost' => [InventoryCostingMovementType::AdjustmentOut, '1', 'VALUATION_UNIT_COST_UNEXPECTED'];
    }

    #[DataProvider('invalidCostPolicy')]
    public function testItRejectsInvalidCostPolicyBeforeReadingValuation(InventoryCostingMovementType $type, ?string $cost, string $code): void
    {
        $valuations = $this->createMock(StockValuationRepository::class);
        $valuations->expects(self::never())->method('getByStockForUpdate');

        try {
            $this->valuer($valuations)->value($this->movement($type, '1', '10', '11', $cost, 'Adjustment'));
            self::fail('The invalid cost policy should be rejected.');
        } catch (InventoryCostingRuleViolation $violation) {
            self::assertSame($code, $violation->errorCode());
        }
    }

    public function testItRejectsPhysicalAndValuationQuantityDriftBeforeMutation(): void
    {
        $valuation = $this->valuation('9', '4000');
        $valuations = $this->createStub(StockValuationRepository::class);
        $valuations->method('getByStockForUpdate')->willReturn($valuation);

        try {
            $this->valuer($valuations)->value($this->movement(InventoryCostingMovementType::AdjustmentIn, '1', '10', '11', '5000', 'Restock'));
            self::fail('Quantity drift should be rejected.');
        } catch (InventoryCostingRuleViolation $violation) {
            self::assertSame('VALUATION_STOCK_QUANTITY_MISMATCH', $violation->errorCode());
        }

        self::assertSame('9', $valuation->quantityOnHand()->toString());
        self::assertSame('36000.000000', $valuation->totalValue()->amount()->toString());
    }

    private function valuer(
        StockValuationRepository $valuations,
        ?StockValuationMovementRepository $movements = null,
    ): RepositoryInventoryMovementValuer {
        $stores = $this->createStub(StoreBusinessContextProvider::class);
        $stores->method('provide')->willReturn(new StoreBusinessContext('Africa/Lagos', 'XAF'));

        return new RepositoryInventoryMovementValuer(
            $valuations,
            $movements ?? $this->createStub(StockValuationMovementRepository::class),
            $stores,
            new MovingWeightedAverageCalculator(),
            new SymfonyUuidV7Generator(),
        );
    }

    private function movement(
        InventoryCostingMovementType $type,
        string $quantity,
        string $previousQuantity,
        string $resultingQuantity,
        ?string $incomingUnitCost,
        string $reason,
    ): ValueInventoryMovement {
        return new ValueInventoryMovement(
            StoreId::fromString(self::STORE, $this->uuids),
            ProductId::fromString(self::PRODUCT, $this->uuids),
            StockId::fromString(self::STOCK, $this->uuids),
            StockMovementId::fromString(self::STOCK_MOVEMENT, $this->uuids),
            $type,
            $this->quantity($quantity),
            $this->quantity($previousQuantity),
            $this->quantity($resultingQuantity),
            null !== $incomingUnitCost ? $this->decimals->fromString($incomingUnitCost) : null,
            $reason,
            new DateTimeImmutable('2026-08-27T18:00:00Z'),
            new ActorContext(
                ActorId::fromString(self::ACTOR, $this->uuids),
                OrganizationId::fromString(self::ORGANIZATION, $this->uuids),
                ActorType::User,
                CorrelationId::fromString(self::CORRELATION, $this->uuids),
                new DateTimeImmutable('2026-08-27T17:00:00Z'),
            ),
        );
    }

    private function valuation(string $quantity, string $unitCost): StockValuation
    {
        return StockValuation::initialize(
            StockValuationId::fromString(self::VALUATION, $this->uuids),
            OrganizationId::fromString(self::ORGANIZATION, $this->uuids),
            StoreId::fromString(self::STORE, $this->uuids),
            ProductId::fromString(self::PRODUCT, $this->uuids),
            StockId::fromString(self::STOCK, $this->uuids),
            $this->quantity($quantity),
            Money::fromString($unitCost, Currency::fromCode('XAF'), $this->decimals),
            new MovingWeightedAverageCalculator(),
        );
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
}
