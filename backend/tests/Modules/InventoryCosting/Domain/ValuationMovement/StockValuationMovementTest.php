<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\InventoryCosting\Domain\ValuationMovement;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zandu\Modules\InventoryCosting\Domain\InventoryCostingRuleViolation;
use Zandu\Modules\InventoryCosting\Domain\ValuationMovement\{StockValuationMovement, StockValuationMovementSource, StockValuationMovementType};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, StockId, StockMovementId, StockValuationId, StockValuationMovementId, StoreId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final class StockValuationMovementTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
    }

    public function testItRecordsAnImmutableSaleValuationMovement(): void
    {
        $movement = $this->record(
            StockValuationMovementType::Sale,
            $this->stockMovementId(),
            '2',
            '4000',
            '8000',
            '40000',
            '32000',
            '4000',
            '4000',
        );

        self::assertSame('SALE', $movement->type()->value);
        self::assertSame('2', $movement->quantity()->toString());
        self::assertSame('8000', $movement->value()->amount()->toString());
        self::assertSame('0198f207-1111-7111-8111-111111111111', $movement->stockMovementId()?->toString());
        self::assertSame('SALE', $movement->source()->type());
        self::assertSame('sale-42', $movement->source()->referenceId());
        self::assertSame('UTC', $movement->occurredAt()->getTimezone()->getName());
        self::assertTrue((new ReflectionClass($movement))->isReadOnly());
    }

    public function testOpeningIsTheOnlyMovementWithoutAPhysicalStockMovement(): void
    {
        $opening = $this->record(
            StockValuationMovementType::Opening,
            null,
            '0',
            '0',
            '0',
            '0',
            '0',
            '0',
            '0',
        );

        self::assertNull($opening->stockMovementId());

        $this->expectException(InventoryCostingRuleViolation::class);
        $this->expectExceptionMessage('A physical stock movement is required');
        $this->record(StockValuationMovementType::Sale, null, '1', '1', '1', '1', '0', '1', '0');
    }

    public function testOpeningRejectsANewPhysicalStockMovementReference(): void
    {
        $this->expectException(InventoryCostingRuleViolation::class);
        $this->expectExceptionMessage('An opening valuation cannot reference');

        $this->record(StockValuationMovementType::Opening, $this->stockMovementId(), '1', '1', '1', '0', '1', '0', '1');
    }

    public function testItRejectsInconsistentMovementTotals(): void
    {
        $this->expectException(InventoryCostingRuleViolation::class);
        $this->expectExceptionMessage('Valuation movement totals are inconsistent.');

        $this->record(StockValuationMovementType::Sale, $this->stockMovementId(), '2', '4', '8', '40', '33', '4', '4');
    }

    public function testItRejectsMixedCurrencies(): void
    {
        $this->expectException(InventoryCostingRuleViolation::class);
        $this->expectExceptionMessage('Valuation movement values must use the same currency.');

        $this->record(
            StockValuationMovementType::AdjustmentIn,
            $this->stockMovementId(),
            '1',
            '4',
            '4',
            '40',
            '44',
            '4',
            '4',
            'EUR',
        );
    }

    public function testItRejectsASourceThatDoesNotMatchTheMovementType(): void
    {
        $this->expectException(InventoryCostingRuleViolation::class);
        $this->expectExceptionMessage('Valuation movement source does not match its type.');

        $this->record(
            StockValuationMovementType::AdjustmentIn,
            $this->stockMovementId(),
            '1',
            '4',
            '4',
            '40',
            '44',
            '4',
            '4',
            'XAF',
            'SALE',
        );
    }

    private function record(
        StockValuationMovementType $type,
        ?StockMovementId $stockMovementId,
        string $quantity,
        string $unitCost,
        string $value,
        string $previousTotal,
        string $resultingTotal,
        string $previousAverage,
        string $resultingAverage,
        string $unitCostCurrency = 'XAF',
        ?string $sourceType = null,
    ): StockValuationMovement {
        return StockValuationMovement::record(
            StockValuationMovementId::fromString('0198f201-1111-7111-8111-111111111111', $this->ids),
            StockValuationId::fromString('0198f202-1111-7111-8111-111111111111', $this->ids),
            OrganizationId::fromString('0198f203-1111-7111-8111-111111111111', $this->ids),
            StoreId::fromString('0198f204-1111-7111-8111-111111111111', $this->ids),
            ProductId::fromString('0198f205-1111-7111-8111-111111111111', $this->ids),
            StockId::fromString('0198f206-1111-7111-8111-111111111111', $this->ids),
            $stockMovementId,
            $type,
            Quantity::fromString($quantity, $this->decimals),
            $this->money($unitCost, $unitCostCurrency),
            $this->money($value),
            $this->money($previousTotal),
            $this->money($resultingTotal),
            $this->money($previousAverage),
            $this->money($resultingAverage),
            StockValuationMovementSource::from($sourceType ?? $type->sourceType(), 'sale-42'),
            new DateTimeImmutable('2026-08-27T12:00:00+01:00'),
            CorrelationId::fromString('0198f208-1111-7111-8111-111111111111', $this->ids),
        );
    }

    private function stockMovementId(): StockMovementId
    {
        return StockMovementId::fromString('0198f207-1111-7111-8111-111111111111', $this->ids);
    }

    private function money(string $amount, string $currency = 'XAF'): Money
    {
        return Money::fromString($amount, Currency::fromCode($currency), $this->decimals);
    }
}
